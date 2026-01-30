<?php
require_once __DIR__ . '/_qa_bootstrap.php';

/*
Body JSON:
{
  "tipo_mov":"ING"|"LIB",
  "id_motivo": 1,
  "tipo_cat":"T"|"C"|"P"|"U"|"A",  // opcional (se usa como default)
  "usuario":"USR",
  "folio":"QA-...." (opcional),
  "items":[
     {"nivel":"TR|CJ|PZ", "cve_almac":100, "idy_ubica":1, "cve_articulo":"X", "cve_lote":"L1", "id_contenedor":123, "cantidad":10, "pzsxcaja":10}
  ]
}
*/

$in = qa_input();
qa_require_params($in, ['tipo_mov','id_motivo','usuario','items']);

$tipo = strtoupper(trim($in['tipo_mov']));
if (!in_array($tipo, ['ING','LIB'], true)) qa_json(false, null, "tipo_mov inválido", 400);

$id_motivo = (int)$in['id_motivo'];
$usuario = substr(trim((string)$in['usuario']), 0, 20);
$tipo_cat_default = strtoupper(trim((string)($in['tipo_cat'] ?? '')));
$folio = trim((string)($in['folio'] ?? ''));
if ($folio === '') $folio = qa_now_folio();

$items = $in['items'];
if (!is_array($items) || count($items) === 0) qa_json(false, null, "items vacío", 400);

try {
    qa_tx_begin();

    foreach ($items as $it) {
        $nivel = strtoupper(trim((string)($it['nivel'] ?? '')));
        $idy_ubica = (int)($it['idy_ubica'] ?? 0);
        $cve_almac = (int)($it['cve_almac'] ?? 0);
        $cve_articulo = trim((string)($it['cve_articulo'] ?? ''));
        $cve_lote = trim((string)($it['cve_lote'] ?? ''));
        $id_cont = isset($it['id_contenedor']) ? (int)$it['id_contenedor'] : null;
        $cantidad = isset($it['cantidad']) ? (float)$it['cantidad'] : null;
        $pzsxcaja = isset($it['pzsxcaja']) ? (int)$it['pzsxcaja'] : null;

        if ($idy_ubica <= 0 || $cve_articulo === '') qa_json(false, $it, "Item inválido (idy_ubica/cve_articulo)", 400);

        // Si viene sin nivel, usar tipo_cat_default para inferir (típicamente en bloque por ubicación/producto)
        if ($nivel === '' && $tipo_cat_default !== '') {
            if ($tipo_cat_default === 'T') $nivel = 'TR';
            elseif ($tipo_cat_default === 'C') $nivel = 'CJ';
            elseif ($tipo_cat_default === 'P') $nivel = 'PZ';
        }

        // --- 1) Actualizar flags (hard-block) ---
        $flag = ($tipo === 'ING') ? 1 : 0;

        if ($nivel === 'TR') {
            // Tarima (LP pallet)
            if ($id_cont === null) qa_json(false, $it, "Tarima requiere id_contenedor (ntarima)", 400);
            qa_exec("UPDATE ts_existenciatarima SET Cuarentena = ? WHERE idy_ubica = ? AND cve_articulo = ? AND lote = ? AND ntarima = ?",
                [$flag, $idy_ubica, $cve_articulo, $cve_lote, $id_cont]);
        } elseif ($nivel === 'CJ') {
            // Caja / Contenedor
            if ($id_cont === null) qa_json(false, $it, "Caja requiere id_contenedor (Id_Caja)", 400);
            // Nota: asume que ya existe columna Cuarentena (tú ya la agregaste).
            qa_exec("UPDATE ts_existenciacajas SET Cuarentena = ? WHERE idy_ubica = ? AND cve_articulo = ? AND cve_lote = ? AND Id_Caja = ?",
                [$flag, $idy_ubica, $cve_articulo, $cve_lote, $id_cont]);
        } elseif ($nivel === 'PZ') {
            // Pieza / lote (si quieres por id, extiende el item con 'id')
            qa_exec("UPDATE ts_existenciapiezas SET Cuarentena = ? WHERE idy_ubica = ? AND cve_articulo = ? AND cve_lote = ?",
                [$flag, $idy_ubica, $cve_articulo, $cve_lote]);
        } else {
            // Bloques por Ubicación/Artículo: si no llega nivel, aplicamos a las 3 tablas en bloque
            // 1) Ubicación completa (U) - bloquea todo en esa ubicación
            if ($tipo_cat_default === 'U') {
                qa_exec("UPDATE ts_existenciatarima SET Cuarentena = ? WHERE idy_ubica = ?", [$flag, $idy_ubica]);
                qa_exec("UPDATE ts_existenciacajas SET Cuarentena = ? WHERE idy_ubica = ?", [$flag, $idy_ubica]);
                qa_exec("UPDATE ts_existenciapiezas SET Cuarentena = ? WHERE idy_ubica = ?", [$flag, $idy_ubica]);
            } else {
                // Artículo + lote (A): bloquea todo el lote en esa ubicación
                qa_exec("UPDATE ts_existenciatarima SET Cuarentena = ? WHERE idy_ubica = ? AND cve_articulo = ? AND lote = ?",
                    [$flag, $idy_ubica, $cve_articulo, $cve_lote]);
                qa_exec("UPDATE ts_existenciacajas SET Cuarentena = ? WHERE idy_ubica = ? AND cve_articulo = ? AND cve_lote = ?",
                    [$flag, $idy_ubica, $cve_articulo, $cve_lote]);
                qa_exec("UPDATE ts_existenciapiezas SET Cuarentena = ? WHERE idy_ubica = ? AND cve_articulo = ? AND cve_lote = ?",
                    [$flag, $idy_ubica, $cve_articulo, $cve_lote]);
            }
        }

        // --- 2) Bitácora t_movcuarentena ---
        if ($tipo === 'ING') {
            // Insert por item (auditable y con folio)
            $tipo_cat_ing = $tipo_cat_default !== '' ? $tipo_cat_default : ($nivel === 'TR' ? 'T' : ($nivel === 'CJ' ? 'C' : ($nivel === 'PZ' ? 'P' : 'A')));
            // cantidad/pzsxcaja: si vienen null, el reporte lo puede inferir; pero intentamos llenarlo.
            qa_exec("INSERT INTO t_movcuarentena
                    (Fol_Folio, Idy_Ubica, IdContenedor, Cve_Articulo, Cve_Lote, Cantidad, PzsXCaja, Fec_Ingreso, Id_MotivoIng, Tipo_Cat_Ing, Usuario_Ing)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [$folio, $idy_ubica, $id_cont, $cve_articulo, $cve_lote, $cantidad, $pzsxcaja, date('Y-m-d H:i:s'), $id_motivo, $tipo_cat_ing, $usuario]);
        } else {
            // Liberación: cerrar registros abiertos. Priorizamos por Folio si viene en item.
            $tipo_cat_lib = $tipo_cat_default !== '' ? $tipo_cat_default : ($nivel === 'TR' ? 'T' : ($nivel === 'CJ' ? 'C' : ($nivel === 'PZ' ? 'P' : 'A')));
            // Si folio viene y quieres liberar todo el folio, manda un solo item con tipo_cat='F' (extensión futura).
            qa_exec("UPDATE t_movcuarentena
                    SET Fec_Libera = ?, Id_MotivoLib = ?, Tipo_Cat_Lib = ?, Usuario_Lib = ?
                    WHERE Idy_Ubica = ? AND Cve_Articulo <=> ? AND NULLIF(Cve_Lote,'') <=> NULLIF(?, '')
                      AND (IdContenedor <=> ?) AND Fec_Libera IS NULL",
                [date('Y-m-d H:i:s'), $id_motivo, $tipo_cat_lib, $usuario, $idy_ubica, $cve_articulo, $cve_lote, $id_cont]);
        }
    }

    qa_tx_commit();
    qa_json(true, ['folio' => $folio, 'procesados' => count($items)], "Movimiento QA ejecutado");
} catch (Throwable $e) {
    qa_tx_rollback();
    qa_json(false, null, "Error ejecutando movimiento QA: " . $e->getMessage(), 500);
}
