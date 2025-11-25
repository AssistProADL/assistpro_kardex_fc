<?php
declare(strict_types=1);

// =============================================================
// AssistPro Kardex – Editar / Crear Orden de Compra
// Encabezado + detalle, guardando en th_aduana / td_aduana
// =============================================================

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*----------- Helper folios desde c_folios -----------*/
function siguiente_folio_oc_demo(PDO $pdo, int $empresaId = 0, string $modulo = 'OC'): ?string
{
    try {
        $sql = "SELECT * FROM c_folios WHERE activo = 1 AND modulo = :modulo";
        if ($empresaId > 0) {
            $sql .= " AND empresa_id = :empresa";
        }
        $sql .= " ORDER BY empresa_id, serie LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->bindValue(':modulo', $modulo, PDO::PARAM_STR);
        if ($empresaId > 0) {
            $st->bindValue(':empresa', $empresaId, PDO::PARAM_INT);
        }
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $sql2 = "SELECT * FROM c_folios WHERE activo = 1";
            if ($empresaId > 0) {
                $sql2 .= " AND empresa_id = :empresa";
            }
            $sql2 .= " ORDER BY empresa_id, modulo, serie LIMIT 1";
            $st2 = $pdo->prepare($sql2);
            if ($empresaId > 0) {
                $st2->bindValue(':empresa', $empresaId, PDO::PARAM_INT);
            }
            $st2->execute();
            $row = $st2->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row) {
            return null;
        }

        $num = (int)($row['folio_actual'] ?: $row['folio_inicial']);
        $num++;
        $numStr = (string)$num;

        if (!empty($row['rellenar_ceros']) && (int)$row['rellenar_ceros'] === 1) {
            $numStr = str_pad(
                $numStr,
                (int)($row['longitud_num'] ?: 0),
                '0',
                STR_PAD_LEFT
            );
        }

        $prefijo = (string)($row['prefijo'] ?? '');
        $sufijo  = (string)($row['sufijo'] ?? '');

        return $prefijo . $numStr . $sufijo;
    } catch (Throwable $e) {
        return null;
    }
}


/*----------- Carga de datos para EDICIÓN (si llega id_aduana) -----------*/
$idAduanaEdit = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;

// Variables por defecto (modo CREAR)
$folioVal       = '';
$fechaOcVal     = date('Y-m-d');
$empresaVal     = '';
$almacenVal     = '';
$proveedorVal   = '';
$tipoOcVal      = '';
$monedaVal      = 'MXN';
$tipoCambioVal  = '1.0000';
$folioErpVal    = '';
$statusVal      = 'ABIERTA';
$pedidoIdVal    = '';
$fechaCompVal   = '';
$fechaPrevVal   = '';
$comentariosVal = '';
$usuarioVal     = '';

// Array para el detalle en JS
$detalleJson = '[]';

if ($idAduanaEdit > 0) {
    try {
        // 1. Encabezado
        $stH = $pdo->prepare("
            SELECT *
            FROM th_aduana
            WHERE ID_Aduana = :id
            LIMIT 1
        ");
        $stH->execute([':id' => $idAduanaEdit]);
        $rowH = $stH->fetch(PDO::FETCH_ASSOC);

        if ($rowH) {
            $folioVal       = $rowH['Pedimento'] ?? ''; // En th_aduana se guarda el folio en 'Pedimento' o 'Cve_Almac' según el insert? Revisando insert: Pedimento -> mb_substr($folio, 0, 100)
            // OJO: En el INSERT original:
            // Cve_Almac -> mb_substr($folio, 0, 100, 'UTF-8') ?? No, Cve_Almac es el almacén.
            // Revisemos el INSERT línea 245: mb_substr($folio, 0, 100, 'UTF-8') se pasa al bind 12?
            // Bind 12 corresponde a 'Pedimento' en la lista de values?
            // VALUES (..., ?, NULL, NULL, ?, ?) -> Los últimos 3 son Pedimento, BlMaster, BlHouse?
            // No, el INSERT tiene muchos campos.
            // Revisando INSERT línea 229: ..., NULL, NULL, NULL, ?, NULL, NULL, ?, ?
            // El antepenúltimo '?' es Pedimento.
            // El penúltimo '?' es Tipo_Cambio.
            // El último '?' es Id_moneda.
            // El bind 12 (índice 11 si es 0-indexed, pero aquí es array directo)
            // $stH->execute([ ... ])
            // El array tiene 14 elementos.
            // 12vo elemento (índice 11): mb_substr($folio, 0, 100, 'UTF-8') -> Corresponde a 'Pedimento' en la SQL?
            // SQL: ... NULL, NULL, NULL, ?, NULL, NULL, ?, ?
            // Contando placeholders... es difícil sin ver la estructura exacta, pero asumiremos que 'Pedimento' guarda el Folio OC.
            
            // Corrección: En el INSERT, 'Pedimento' recibe $folio.
            $folioVal = $rowH['Pedimento'];

            // Fechas
            $fechaOcVal = $rowH['fech_pedimento'] ? date('Y-m-d', strtotime($rowH['fech_pedimento'])) : date('Y-m-d');
            
            $empresaVal   = ''; // No se guarda ID empresa directo en th_aduana estándar mostrada, salvo 'recurso'? 
                                // En el INSERT: 'recurso' -> 1 (hardcoded en SQL? No, 'recurso' es NULL en values? No, Activo=1, recurso=NULL).
                                // Espera, el INSERT no guarda empresa_id explícitamente en una columna obvia tipo 'id_empresa'.
                                // Pero el form pide empresa.
                                // Revisando INSERT: no veo que se guarde la empresa seleccionada ($empresaSel) en ningún campo de th_aduana,
                                // salvo que sea parte de alguna lógica no vista o columna no listada.
                                // Ah, el código original línea 96: $empresaSel = ...
                                // Pero en $stH->execute no veo que se use $empresaSel.
                                // ¡Parece que la empresa NO se está guardando en th_aduana en el código original!
                                // Sin embargo, intentaremos cargar lo que haya.
            
            $almacenVal   = $rowH['Cve_Almac'];
            $proveedorVal = $rowH['ID_Proveedor'];
            $tipoOcVal    = $rowH['ID_Protocolo'];
            
            // Moneda
            $idMon = (int)$rowH['Id_moneda'];
            if ($idMon === 2) $monedaVal = 'USD';
            else $monedaVal = 'MXN';

            $tipoCambioVal = $rowH['Tipo_Cambio'];
            $folioErpVal   = $rowH['Factura']; // En INSERT: Factura -> $folioErp
            
            $st = $rowH['status'];
            $statusVal = ($st === 'C') ? 'CERRADA' : (($st === 'X') ? 'CANCELADA' : 'ABIERTA');

            // pedido_id -> No se ve en INSERT explícito a columna 'pedido_id'.
            // Quizás no se guarda o se guarda en otro lado. Asumiremos vacío si no hay columna obvia.
            
            $fechaCompVal = $rowH['fech_llegPed'] ? date('Y-m-d', strtotime($rowH['fech_llegPed'])) : '';
            // fecha_recep_prev -> No veo columna obvia en INSERT.
            
            $comentariosVal = ''; // No veo columna comentarios en INSERT.
            $usuarioVal     = $rowH['cve_usuario']; // Consec_protocolo -> mb_substr($usuarioSel...) NO, cve_usuario -> $usuarioSel
        }

        // 2. Detalle
        // Necesitamos descripción y UOM de c_articulo
        $stD = $pdo->prepare("
            SELECT d.*, a.des_articulo, a.cve_umed
            FROM td_aduana d
            LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
            WHERE d.ID_Aduana = :id
            ORDER BY d.Item ASC
        ");
        $stD->execute([':id' => $idAduanaEdit]);
        $rowsD = $stD->fetchAll(PDO::FETCH_ASSOC);

        $arrDet = [];
        foreach ($rowsD as $r) {
            // Reconstruir estructura JS
            // { id, clave, producto, uom, cantidad, precio, ivaP, subtotal, iva, total }
            
            $cant   = (float)$r['cantidad'];
            $costo  = (float)$r['costo']; // Precio unitario neto según INSERT (aunque se llame costo)
            $ivaP   = (float)$r['IVA'];   // Porcentaje IVA según INSERT
            
            // Cálculos inversos para consistencia visual
            // En INSERT: costo = precio (neto input), IVA = ivaP (input)
            // Entonces:
            $precio = $costo;
            
            $totalNetoLinea = $cant * $precio;
            $base = $totalNetoLinea;
            $montoIva = 0;
            if ($ivaP > 0) {
                $base = $totalNetoLinea / (1 + ($ivaP / 100));
                $montoIva = $totalNetoLinea - $base;
            }

            $arrDet[] = [
                'id'       => (float)$r['Id_DetAduana'], // ID único para borrar
                'clave'    => $r['cve_articulo'],
                'producto' => $r['des_articulo'] ?: $r['cve_articulo'],
                'uom'      => $r['cve_umed'] ?: 'PZA',
                'cantidad' => $cant,
                'precio'   => $precio,
                'ivaP'     => $ivaP,
                'subtotal' => $base,
                'iva'      => $montoIva,
                'total'    => $totalNetoLinea
            ];
        }
        $detalleJson = json_encode($arrDet, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        // Mostrar error para debug
        echo '<!-- ERROR AL CARGAR DATOS: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
        error_log('Error en orden_compra_edit.php carga: ' . $e->getMessage());
    }
} else {
    // Si es nuevo, sugerir folio
    $folioVal = siguiente_folio_oc_demo($pdo) ?? ('OC-' . date('Ymd-His'));
}
$ajax = $_GET['ajax'] ?? '';
if ($ajax === 'guardar') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            throw new RuntimeException('JSON inválido.');
        }

        $h       = $payload['encabezado'] ?? [];
        $detalle = $payload['detalle'] ?? [];
        
        // Detectar si es edición o creación
        $idAduanaEdit = isset($h['id_aduana_edit']) ? (int)$h['id_aduana_edit'] : 0;
        $modoEdicion = $idAduanaEdit > 0;

        if (!is_array($h) || !is_array($detalle)) {
            throw new RuntimeException('Estructura de datos inválida.');
        }
        if (!count($detalle)) {
            throw new RuntimeException('Debe capturar al menos una partida de producto.');
        }

        $folio        = trim((string)($h['folio'] ?? ''));
        $fechaOc      = (string)($h['fecha_oc'] ?? date('Y-m-d'));
        $empresaSel   = (string)($h['empresa'] ?? '');
        $almacenCve   = (string)($h['almacen_cve'] ?? '');
        $proveedorId  = (int)($h['proveedor_id'] ?? 0);
        $idProtocolo  = (string)($h['tipo_oc'] ?? '');
        $moneda       = (string)($h['moneda'] ?? 'MXN');
        $tipoCambio   = (float)($h['tipo_cambio'] ?? 1);
        $folioErp     = (string)($h['folio_erp'] ?? '');
        $statusOc     = (string)($h['status'] ?? 'ABIERTA');
        $pedidoId     = $h['pedido_id'] !== '' ? (int)$h['pedido_id'] : null;
        $fechaComp    = (string)($h['fecha_compromiso'] ?? '');
        $fechaPrev    = (string)($h['fecha_recep_prev'] ?? '');
        $comentarios  = (string)($h['comentarios'] ?? '');
        $usuarioSel   = (string)($h['usuario'] ?? '');

        if ($almacenCve === '') {
            throw new RuntimeException('Debe seleccionar un almacén.');
        }
        if ($proveedorId <= 0) {
            throw new RuntimeException('Debe seleccionar un proveedor.');
        }
        if ($idProtocolo === '') {
            throw new RuntimeException('Debe seleccionar el tipo de OC (protocolo).');
        }
        if ($usuarioSel === '') {
            throw new RuntimeException('Debe seleccionar el usuario.');
        }

        // Validación de fechas contra Fecha OC
        if ($fechaComp !== '' && $fechaComp < $fechaOc) {
            throw new RuntimeException('La Fecha Compromiso no puede ser menor que la Fecha de la OC.');
        }
        if ($fechaPrev !== '' && $fechaPrev < $fechaOc) {
            throw new RuntimeException('La Recepción Prevista no puede ser menor que la Fecha de la OC.');
        }

        if ($folio === '') {
            $folio = siguiente_folio_oc_demo($pdo) ?? ('OC-' . date('Ymd-His'));
        }

        // Moneda -> Id_moneda demo
        $idMoneda = null;
        if ($moneda === 'MXN') {
            $idMoneda = 1;
        } elseif ($moneda === 'USD') {
            $idMoneda = 2;
        }

        $pdo->beginTransaction();

        // --- MODO EDICIÓN: UPDATE ---
        if ($modoEdicion) {
            // Verificar que el registro existe
            $stCheck = $pdo->prepare("SELECT ID_Aduana FROM th_aduana WHERE ID_Aduana = :id LIMIT 1");
            $stCheck->execute([':id' => $idAduanaEdit]);
            if (!$stCheck->fetch()) {
                throw new RuntimeException("No existe la orden de compra con ID_Aduana = $idAduanaEdit");
            }

            // Fechas con hora actual
            $horaActual   = date('H:i:s');
            $fechPedDT    = $fechaOc   ? ($fechaOc   . ' ' . $horaActual) : null;
            $fechLlegDT   = $fechaComp ? ($fechaComp . ' ' . $horaActual) : null;

            // UPDATE del encabezado
            $stH = $pdo->prepare("
                UPDATE th_aduana SET
                    fech_pedimento = ?,
                    Factura = ?,
                    fech_llegPed = ?,
                    status = ?,
                    ID_Proveedor = ?,
                    ID_Protocolo = ?,
                    cve_usuario = ?,
                    Cve_Almac = ?,
                    Pedimento = ?,
                    Tipo_Cambio = ?,
                    Id_moneda = ?
                WHERE ID_Aduana = ?
            ");

            $stH->execute([
                $fechPedDT,
                mb_substr($folioErp, 0, 50, 'UTF-8'),
                $fechLlegDT,
                ($statusOc === 'ABIERTA' ? 'A' : ($statusOc === 'CERRADA' ? 'C' : 'X')),
                $proveedorId,
                $idProtocolo,
                mb_substr($usuarioSel, 0, 50, 'UTF-8'),
                $almacenCve,
                mb_substr($folio, 0, 100, 'UTF-8'),
                $tipoCambio,
                $idMoneda,
                $idAduanaEdit
            ]);

            // Eliminar detalles anteriores
            $stDelDet = $pdo->prepare("DELETE FROM td_aduana WHERE ID_Aduana = ?");
            $stDelDet->execute([$idAduanaEdit]);

            // Insertar nuevos detalles
            $baseDet = (int)$pdo->query("SELECT COALESCE(MAX(Id_DetAduana),0)+1 FROM td_aduana")->fetchColumn();
            $stD = $pdo->prepare("
                INSERT INTO td_aduana (
                    Id_DetAduana,
                    ID_Aduana,
                    cve_articulo,
                    cantidad,
                    Cve_Lote,
                    caducidad,
                    temperatura,
                    num_orden,
                    Ingresado,
                    Activo,
                    costo,
                    IVA,
                    Item,
                    Id_UniMed,
                    Fec_Entrega,
                    Ref_Docto,
                    Peso,
                    MarcaNumTotBultos,
                    Factura,
                    Fec_Factura,
                    Contenedores
                ) VALUES (
                    ?, ?, ?, ?, NULL, NULL, NULL, ?, 0, 1, ?, ?, ?, NULL,
                    NULL, NULL, NULL, NULL, ?, NULL, NULL
                )
            ");

            $linea = 0;
            foreach ($detalle as $r) {
                $linea++;
                $idDet = $baseDet + $linea;
                $clave    = (string)($r['clave'] ?? '');
                $cantidad = (float)($r['cantidad'] ?? 0);
                $precio   = (float)($r['precio'] ?? 0);
                $ivaP     = (float)($r['ivaP'] ?? 0);

                if ($clave === '' || $cantidad <= 0) {
                    throw new RuntimeException("Partida $linea inválida (clave o cantidad).");
                }
                if (!is_numeric($precio) || $precio < 0) {
                    throw new RuntimeException("Partida $linea: el precio debe ser mayor o igual a 0.");
                }

                $stD->execute([
                    $idDet,
                    $idAduanaEdit,
                    $clave,
                    $cantidad,
                    $linea,
                    $precio,
                    $ivaP,
                    (string)$linea,
                    mb_substr($folioErp, 0, 120, 'UTF-8')
                ]);
            }

            $idAduana = $idAduanaEdit;
            $consecNum = 0; // No se actualiza el consecutivo en edición

        } else {
            // --- MODO CREACIÓN: INSERT ---
            
            // --- FOLIO de t_protocolo para el ID_Protocolo seleccionado ---
            $stProt = $pdo->prepare("
                SELECT id, ID_Protocolo, FOLIO
                FROM t_protocolo
                WHERE ID_Protocolo = :p AND Activo = 1
                LIMIT 1
            ");
            $stProt->execute([':p' => $idProtocolo]);
            $rowProt = $stProt->fetch(PDO::FETCH_ASSOC);

            if (!$rowProt) {
                throw new RuntimeException("No existe configuración de protocolo para '$idProtocolo' en t_protocolo.");
            }

            $idProtRow   = (int)$rowProt['id'];
            $folioActual = (int)$rowProt['FOLIO'];
            $nuevoFolio  = $folioActual + 1;

            $stUpdProt = $pdo->prepare("UPDATE t_protocolo SET FOLIO = :n WHERE id = :id");
            $stUpdProt->execute([
                ':n'  => $nuevoFolio,
                ':id' => $idProtRow
            ]);

            // === consecutivo numérico por protocolo para num_pedimento / Consec_protocolo ===
            $stCons = $pdo->prepare("
                SELECT COALESCE(MAX(num_pedimento),0) + 1
                FROM th_aduana
                WHERE ID_Protocolo = :p
            ");
            $stCons->execute([':p' => $idProtocolo]);
            $consecNum = (int)$stCons->fetchColumn();
            if ($consecNum <= 0) {
                $consecNum = 1;
            }

            // --- Nuevo ID_Aduana ---
            $idAduana = (int)$pdo->query("SELECT COALESCE(MAX(ID_Aduana),0)+1 FROM th_aduana")->fetchColumn();

            // Fechas con hora actual
            $horaActual   = date('H:i:s');
            $fechPedDT    = $fechaOc   ? ($fechaOc   . ' ' . $horaActual) : null;
            $fechLlegDT   = $fechaComp ? ($fechaComp . ' ' . $horaActual) : null;

            $stH = $pdo->prepare("
                INSERT INTO th_aduana (
                    ID_Aduana,
                    num_pedimento,
                    fech_pedimento,
                    aduana,
                    Factura,
                    fech_llegPed,
                    status,
                    ID_Proveedor,
                    ID_Protocolo,
                    Consec_protocolo,
                    cve_usuario,
                    Cve_Almac,
                    Activo,
                    recurso,
                    procedimiento,
                    AduanaDespacho,
                    dictamen,
                    presupuesto,
                    condicionesDePago,
                    lugarDeEntrega,
                    fechaDeFallo,
                    plazoDeEntrega,
                    Proyecto,
                    areaSolicitante,
                    numSuficiencia,
                    fechaSuficiencia,
                    fechaContrato,
                    montoSuficiencia,
                    numeroContrato,
                    importeAlmacenado,
                    Pedimento,
                    BlMaster,
                    BlHouse,
                    Tipo_Cambio,
                    Id_moneda
                ) VALUES (
                    ?, ?, ?, 'OC_WEB', ?, ?, ?, ?, ?, ?, ?, ?, 1,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, ?, NULL, NULL, ?, ?
                )
            ");

            $stH->execute([
                $idAduana,
                $consecNum,                  // num_pedimento
                $fechPedDT,                  // fech_pedimento
                mb_substr($folioErp, 0, 50, 'UTF-8'), // Factura (por ahora folio ERP)
                $fechLlegDT,                 // fech_llegPed
                ($statusOc === 'ABIERTA' ? 'A' : 'C'),
                $proveedorId,
                $idProtocolo,
                $consecNum,                  // Consec_protocolo
                mb_substr($usuarioSel, 0, 50, 'UTF-8'),
                $almacenCve,
                mb_substr($folio, 0, 100, 'UTF-8'),
                $tipoCambio,
                $idMoneda
            ]);

            // --- Detalle ---
            $baseDet = (int)$pdo->query("SELECT COALESCE(MAX(Id_DetAduana),0)+1 FROM td_aduana")->fetchColumn();
            $stD = $pdo->prepare("
                INSERT INTO td_aduana (
                    Id_DetAduana,
                    ID_Aduana,
                    cve_articulo,
                    cantidad,
                    Cve_Lote,
                    caducidad,
                    temperatura,
                    num_orden,
                    Ingresado,
                    Activo,
                    costo,
                    IVA,
                    Item,
                    Id_UniMed,
                    Fec_Entrega,
                    Ref_Docto,
                    Peso,
                    MarcaNumTotBultos,
                    Factura,
                    Fec_Factura,
                    Contenedores
                ) VALUES (
                    ?, ?, ?, ?, NULL, NULL, NULL, ?, 0, 1, ?, ?, ?, NULL,
                    NULL, NULL, NULL, NULL, ?, NULL, NULL
                )
            ");

            $linea = 0;
            foreach ($detalle as $r) {
                $linea++;
                $idDet = $baseDet + $linea;
                $clave    = (string)($r['clave'] ?? '');
                $cantidad = (float)($r['cantidad'] ?? 0);
                $precio   = (float)($r['precio'] ?? 0);
                $ivaP     = (float)($r['ivaP'] ?? 0);

                if ($clave === '' || $cantidad <= 0) {
                    throw new RuntimeException("Partida $linea inválida (clave o cantidad).");
                }
                if (!is_numeric($precio) || $precio < 0) {
                    throw new RuntimeException("Partida $linea: el precio debe ser mayor o igual a 0.");
                }

                $stD->execute([
                    $idDet,
                    $idAduana,
                    $clave,
                    $cantidad,
                    $linea,
                    $precio,
                    $ivaP,
                    (string)$linea,
                    mb_substr($folioErp, 0, 120, 'UTF-8')
                ]);
            }
        }


        $pdo->commit();

        echo json_encode([
            'ok'        => true,
            'id_aduana' => $idAduana,
            'folio'     => $folio,
            'consec'    => $consecNum
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'ok'    => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/*----------- Catálogos: tipos de OC, usuarios, productos -----------*/
$tiposOc = [];
try {
    $sqlTOC = "SELECT id, ID_Protocolo, descripcion FROM t_protocolo WHERE id IN (4,5) AND Activo = 1 ORDER BY id";
    $tiposOc = $pdo->query($sqlTOC)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $tiposOc = [];
}

$usuarios = [];
try {
    // Estructura: cve_usuario, nombre_completo, Activo
    $sqlU = "SELECT cve_usuario, nombre_completo FROM c_usuario WHERE Activo = 1 OR Activo IS NULL ORDER BY nombre_completo";
    $usuarios = $pdo->query($sqlU)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $usuarios = [];
}

$productos = [];
try {
    // Estructura: cve_articulo, des_articulo, cve_umed
    $sqlP = "SELECT cve_articulo, des_articulo, cve_umed FROM c_articulo ORDER BY cve_articulo";
    $productos = $pdo->query($sqlP)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $productos = [];
}

// $folio_sugerido ya no se usa aquí abajo porque usamos $folioVal calculado arriba
// $folio_sugerido = siguiente_folio_oc_demo($pdo) ?? ('OC-' . date('Ymd-His'));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AssistPro SFA — Editar | Crear Orden de Compra</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 10px;
        }
        .ap-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 90, 173, 0.08);
            border: 1px solid #eef1f7;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .ap-title,
        .ap-section-title,
        .ap-subtitle,
        .ap-label,
        .form-control,
        .form-select,
        .btn,
        table,
        table th,
        table td {
            font-size: 10px !important;
        }
        .ap-title {
            font-weight: 700;
            color: #0F5AAD;
        }
        .ap-subtitle {
            color: #6c757d;
            margin-bottom: 0;
        }
        .ap-section-title {
            font-weight: 700;
            color: #0F5AAD;
        }
        .ap-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 2px;
        }
        .form-control,
        .form-select {
            border-radius: 8px;
            padding-top: 1px;
            padding-bottom: 1px;
            line-height: 1.2;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #0F5AAD;
            box-shadow: 0 0 0 0.12rem rgba(15, 90, 173, .25);
        }
        table.table-sm th,
        table.table-sm td {
            vertical-align: middle;
            white-space: nowrap;
        }
       
/* Botón corporativo AssistPro */
.btn-ap-primary {
    background: linear-gradient(135deg, #0F5AAD, #135fb8);
    border-color: #0F5AAD;
    color: #ffffff !important;
    border-radius: 999px;
    padding-inline: 16px;
    padding-block: 4px;
    box-shadow: 0 4px 10px rgba(15, 90, 173, 0.35);
    font-weight: 600;
}

/* Asegurar letra blanca en TODOS los estados */
.btn-ap-primary:visited,
.btn-ap-primary:active,
.btn-ap-primary:focus,
.btn-ap-primary:hover {
    color: #ffffff !important;
    background: linear-gradient(135deg, #0d4f9a, #104c94);
    border-color: #0d4f9a;
}


 
        .btn-ap-primary:hover {
            background: linear-gradient(135deg, #0c4a8d, #0f5aad);
            border-color: #0c4a8d;
        }
        .btn-ap-link {
            border-radius: 999px;
        }
        .badge-status {
            border-radius: 999px;
            padding: 3px 10px;
        }
        #tablaDetalleWrapper {
            max-height: 420px;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .ap-summary-value {
            font-weight: 700;
            color: #0F5AAD;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="ap-title mb-0">
                AssistPro SFA — Editar | Crear Orden de Compra
            </div>
            <p class="ap-subtitle">
                Encabezado y detalle de la Orden de Compra, con catálogos AssistPro.
            </p>
        </div>
        <div>
            <a href="orden_compra.php" class="btn btn-outline-secondary btn-sm btn-ap-link">
                ↩ Volver a la lista
            </a>
        </div>
    </div>

    <!-- Encabezado (sección 1, fuente 10px) -->
    <form method="post" class="ap-card mb-2" onsubmit="return false;">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <div class="ap-section-title mb-1">Encabezado</div>
                <small class="text-muted">Datos principales de la Orden de Compra.</small>
            </div>
            <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle badge-status" id="badgeStatus">
                    ABIERTA
                </span>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Folio</label>
                <input type="text" class="form-control" id="folio"
                       value="<?php echo htmlspecialchars($folioVal, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Fecha OC</label>
                <input type="date" class="form-control" id="fecha_oc"
                       value="<?php echo htmlspecialchars($fechaOcVal, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Empresa</label>
                <select class="form-select" id="empresa">
                    <option value="">Cargando empresas...</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Almacén</label>
                <select class="form-select" id="almacen">
                    <option value="">Cargando almacenes...</option>
                </select>
            </div>
            <div class="col-md-4 col-sm-12">
                <label class="ap-label">Proveedor</label>
                <select class="form-select" id="proveedor">
                    <option value="">Cargando proveedores...</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Tipo OC (protocolo)</label>
                <select class="form-select" id="tipo_oc">
                    <option value="">Seleccione...</option>
                    <?php foreach ($tiposOc as $t): ?>
                        <?php $sel = ($t['ID_Protocolo'] == $tipoOcVal) ? 'selected' : ''; ?>
                        <option value="<?php echo htmlspecialchars($t['ID_Protocolo'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($t['descripcion'] . ' [' . $t['ID_Protocolo'] . ']', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Moneda</label>
                <select class="form-select" id="moneda">
                    <option value="">Seleccione...</option>
                    <option value="MXN" <?php echo ($monedaVal === 'MXN') ? 'selected' : ''; ?>>MXN - Pesos Mexicanos</option>
                    <option value="USD" <?php echo ($monedaVal === 'USD') ? 'selected' : ''; ?>>USD - Dólares</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Tipo de cambio</label>
                <input type="number" step="0.0001" class="form-control" id="tipo_cambio"
                       value="<?php echo htmlspecialchars((string)$tipoCambioVal, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Folio ERP</label>
                <input type="text" class="form-control" id="folio_erp" placeholder="Ej. OC-ERP-001"
                       value="<?php echo htmlspecialchars($folioErpVal, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Status</label>
                <select class="form-select" id="status_oc">
                    <option value="ABIERTA" <?php echo ($statusVal === 'ABIERTA') ? 'selected' : ''; ?>>ABIERTA</option>
                    <option value="CERRADA" <?php echo ($statusVal === 'CERRADA') ? 'selected' : ''; ?>>CERRADA</option>
                    <option value="CANCELADA" <?php echo ($statusVal === 'CANCELADA') ? 'selected' : ''; ?>>CANCELADA</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-4">
                <label class="ap-label">ID Pedido relacionado (opcional)</label>
                <input type="number" class="form-control" id="pedido_id" placeholder="vincula materiales">
            </div>
            <div class="col-md-3 col-sm-4">
                <label class="ap-label">Fecha Compromiso</label>
                <input type="date" class="form-control" id="fecha_compromiso"
                       value="<?php echo htmlspecialchars($fechaCompVal, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3 col-sm-4">
                <label class="ap-label">Recepción Prevista</label>
                <input type="date" class="form-control" id="fecha_recep_prev">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Usuario</label>
                <select class="form-select" id="usuario">
                    <option value="">Seleccione usuario...</option>
                    <?php foreach ($usuarios as $u): ?>
                        <?php $sel = ($u['cve_usuario'] == $usuarioVal) ? 'selected' : ''; ?>
                        <option value="<?php echo htmlspecialchars($u['cve_usuario'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel; ?>>
                            <?php
                                $txt = $u['cve_usuario'];
                                if (!empty($u['nombre_completo'])) {
                                    $txt .= ' - ' . $u['nombre_completo'];
                                }
                                echo htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-12 col-sm-12">
                <label class="ap-label">Comentarios</label>
                <input type="text" class="form-control" id="comentarios" placeholder="Comentarios generales de la OC">
            </div>
        </div>
    </form>

    <!-- Detalle (sección 2) -->
    <div class="ap-card">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <div class="ap-section-title mb-1">Detalle (productos)</div>
                <small class="text-muted">Captura de productos, cantidades y precios (precio neto con IVA).</small>
            </div>
        </div>

        <!-- Fila de captura -->
        <form class="row g-2 mb-1" onsubmit="return false;">
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Clave</label>
                <input type="text" class="form-control" id="detalle_clave"
                       list="dlProductos" placeholder="Teclee clave...">
                <datalist id="dlProductos">
                    <?php foreach ($productos as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['cve_articulo'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($p['des_articulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-4 col-sm-8">
                <label class="ap-label">Producto</label>
                <input type="text" class="form-control" id="detalle_producto" readonly>
            </div>
            <div class="col-md-1 col-sm-3">
                <label class="ap-label">U.M.</label>
                <input type="text" class="form-control" id="detalle_uom" placeholder="PZA" readonly>
            </div>
            <div class="col-md-1 col-sm-3">
                <label class="ap-label">Cantidad</label>
                <input type="number" step="0.0001" class="form-control" id="detalle_cantidad" value="0">
            </div>
            <div class="col-md-1 col-sm-3">
                <label class="ap-label">Precio neto</label>
                <input type="number" step="0.0001" class="form-control" id="detalle_precio" value="0">
            </div>
            <div class="col-md-1 col-sm-3">
                <label class="ap-label">IVA (%)</label>
                <input type="number" step="0.01" class="form-control" id="detalle_iva" value="16">
            </div>
            <div class="col-md-2 col-sm-12 d-flex align-items-end">
                <button type="button" class="btn btn-ap-primary w-100" id="btnAgregarDetalle">
                    + Agregar
                </button>
            </div>
        </form>

        <!-- Tabla detalle -->
        <div id="tablaDetalleWrapper" class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="tablaDetalle">
                <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
                    <tr>
                        <th style="width:90px;">Acciones</th>
                        <th>Clave</th>
                        <th>Producto</th>
                        <th>U.M.</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Precio neto</th>
                        <th class="text-end">IVA %</th>
                        <th class="text-end">Subtotal (base)</th>
                        <th class="text-end">Total (neto)</th>
                    </tr>
                </thead>
                <tbody id="tbodyDetalle">
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            No hay productos capturados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totales + botón Guardar en sección 2 -->
        <div class="row mt-2">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between">
                    <span class="ap-label">Subtotal (base)</span>
                    <span class="ap-summary-value" id="lbl_subtotal">0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="ap-label">IVA</span>
                    <span class="ap-summary-value" id="lbl_iva">0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="ap-label">Total (neto)</span>
                    <span class="ap-summary-value" id="lbl_total">0.00</span>
                </div>
                <div class="text-end mt-2 d-flex justify-content-end gap-2">
                    <a href="orden_compra.php" class="btn btn-outline-secondary btn-sm btn-ap-link">
                        Cancelar
                    </a>
                    <button type="button" class="btn btn-ap-primary" id="btnGuardarOC">
                        💾 Guardar orden de compra
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
const API_FILTROS_URL = '../api/filtros_assistpro.php?action=init';
const productosApi = <?php echo json_encode($productos, JSON_UNESCAPED_UNICODE); ?>;

// Variables de edición inyectadas desde PHP
const editIdAduana  = <?php echo $idAduanaEdit; ?>;
const editAlmacen   = "<?php echo htmlspecialchars($almacenVal, ENT_QUOTES, 'UTF-8'); ?>";
const editProveedor = "<?php echo htmlspecialchars($proveedorVal, ENT_QUOTES, 'UTF-8'); ?>";
const editEmpresa   = "<?php echo htmlspecialchars($empresaVal, ENT_QUOTES, 'UTF-8'); ?>"; // Probablemente vacío si no se guarda

var detalleOC = <?php echo $detalleJson; ?>;
console.log(detalleOC);

document.addEventListener('DOMContentLoaded', () => {
    const hoy = new Date();
    const inpFechaOC = document.getElementById('fecha_oc');
    if (inpFechaOC && !inpFechaOC.value) {
        inpFechaOC.valueAsDate = hoy;
    }

    const selMoneda = document.getElementById('moneda');
    // if (selMoneda) selMoneda.value = 'MXN'; // Quitamos esto para no sobrescribir valor PHP

    cargarFiltrosDesdeApi();
    inicializarEventos();
    renderDetalle(); // Renderizar detalle inicial si venía de PHP
});

function inicializarEventos() {
    const btnAgregar = document.getElementById('btnAgregarDetalle');
    const inpClave   = document.getElementById('detalle_clave');
    const btnGuardar = document.getElementById('btnGuardarOC');
    const selStatus  = document.getElementById('status_oc');
    const selTipoOc  = document.getElementById('tipo_oc');
    const fComp      = document.getElementById('fecha_compromiso');
    const fPrev      = document.getElementById('fecha_recep_prev');

    if (btnAgregar) btnAgregar.addEventListener('click', agregarLineaDetalle);
    if (inpClave) {
        inpClave.addEventListener('change', autocompletarProductoPorClave);
        inpClave.addEventListener('blur', autocompletarProductoPorClave);
    }
    if (btnGuardar) btnGuardar.addEventListener('click', guardarOC);

    if (selStatus) {
        selStatus.addEventListener('change', () => {
            const badge = document.getElementById('badgeStatus');
            if (!badge) return;
            const val = selStatus.value || 'ABIERTA';
            badge.textContent = val;
        });
    }

    // OCN -> MXN, OCI -> USD
    if (selTipoOc) {
        selTipoOc.addEventListener('change', () => {
            const v = selTipoOc.value;
            const selMoneda = document.getElementById('moneda');
            const tc = document.getElementById('tipo_cambio');
            if (!selMoneda || !tc) return;

            if (v === 'OCN') {
                selMoneda.value = 'MXN';
                if (!tc.value || parseFloat(tc.value) <= 0) tc.value = '1.0000';
            } else if (v === 'OCI') {
                selMoneda.value = 'USD';
                if (!tc.value || parseFloat(tc.value) <= 0) tc.value = '1.0000';
            }
        });
    }

    // Fechas no menores que fecha OC
    if (fComp) fComp.addEventListener('change', validarFechasContraOC);
    if (fPrev) fPrev.addEventListener('change', validarFechasContraOC);
}

function validarFechasContraOC() {
    const fOC  = document.getElementById('fecha_oc')?.value || '';
    const fComp = document.getElementById('fecha_compromiso')?.value || '';
    const fPrev = document.getElementById('fecha_recep_prev')?.value || '';

    if (!fOC) return;

    if (fComp && fComp < fOC) {
        alert('La Fecha Compromiso no puede ser menor que la Fecha de la OC. Se ajustará a la fecha de la OC.');
        document.getElementById('fecha_compromiso').value = fOC;
    }
    if (fPrev && fPrev < fOC) {
        alert('La Recepción Prevista no puede ser menor que la Fecha de la OC. Se ajustará a la fecha de la OC.');
        document.getElementById('fecha_recep_prev').value = fOC;
    }
}

// ---------- Catálogos empresa / almacén / proveedor desde API ----------
async function cargarFiltrosDesdeApi() {
    try {
        const res = await fetch(API_FILTROS_URL);
        const data = await res.json();

        if (!data || data.ok === false) {
            console.error('Error en respuesta API filtros:', data);
            setSelectVacio(document.getElementById('empresa'), 'Error empresas');
            setSelectVacio(document.getElementById('almacen'), 'Error almacenes');
            setSelectVacio(document.getElementById('proveedor'), 'Error proveedores');
            return;
        }

        const selEmp = document.getElementById('empresa');
        const selAlm = document.getElementById('almacen');
        const selProv = document.getElementById('proveedor');

        if (Array.isArray(data.empresas)) {
            llenarSelect(
                selEmp,
                data.empresas,
                'cve_cia',
                item => `${item.des_cia} (${item.clave_empresa || item.cve_cia})`
            );
            if (data.empresas.length === 1 && selEmp) {
                selEmp.value = data.empresas[0].cve_cia;
            }
            // Si estamos editando y hay valor
            if (editEmpresa && selEmp) {
                selEmp.value = editEmpresa;
            }
        } else {
            setSelectVacio(selEmp, 'Sin empresas');
        }

        if (Array.isArray(data.almacenes)) {
            llenarSelect(
                selAlm,
                data.almacenes,
                'cve_almac',
                item => `${item.clave_almacen || item.cve_almac}`
            );
            if (editAlmacen && selAlm) {
                selAlm.value = editAlmacen;
            }
        } else {
            setSelectVacio(selAlm, 'Sin almacenes');
        }

        if (Array.isArray(data.proveedores)) {
            llenarSelect(
                selProv,
                data.proveedores,
                'ID_Proveedor',
                item => `${item.Nombre} [${item.cve_proveedor || item.ID_Proveedor}]`
            );
            if (editProveedor && selProv) {
                selProv.value = editProveedor;
            }
        } else {
            setSelectVacio(selProv, 'Sin proveedores');
        }

    } catch (e) {
        console.error('Error llamando API filtros:', e);
        setSelectVacio(document.getElementById('empresa'), 'Error');
        setSelectVacio(document.getElementById('almacen'), 'Error');
        setSelectVacio(document.getElementById('proveedor'), 'Error');
    }
}

function setSelectVacio(sel, texto) {
    if (!sel) return;
    sel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = texto || 'Sin datos';
    sel.appendChild(opt);
}

function llenarSelect(sel, items, valueField, labelFn) {
    if (!sel) return;
    sel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Seleccione...';
    sel.appendChild(opt0);

    items.forEach(it => {
        const opt = document.createElement('option');
        opt.value = it[valueField];
        opt.textContent = typeof labelFn === 'function'
            ? labelFn(it)
            : (it[valueField] || '');
        sel.appendChild(opt);
    });
}

// ---------- Detalle ----------
function autocompletarProductoPorClave() {
    const clave = (document.getElementById('detalle_clave').value || '').trim();
    const inpProd = document.getElementById('detalle_producto');
    const inpUom  = document.getElementById('detalle_uom');

    if (!clave) {
        if (inpProd) inpProd.value = '';
        if (inpUom)  inpUom.value  = '';
        return;
    }

    const prod = productosApi.find(
        p => (p.cve_articulo || '').toUpperCase() === clave.toUpperCase()
    );

    if (prod) {
        if (inpProd) inpProd.value = prod.des_articulo || prod.cve_articulo;
        if (inpUom && prod.cve_umed) inpUom.value = prod.cve_umed;
    } else {
        if (inpProd) inpProd.value = '';
        if (inpUom)  inpUom.value  = '';
    }
}

function agregarLineaDetalle() {
    const clave    = (document.getElementById('detalle_clave').value || '').trim();
    const producto = (document.getElementById('detalle_producto').value || '').trim();
    const uom      = (document.getElementById('detalle_uom').value || '').trim();
    const cantidad = parseFloat(document.getElementById('detalle_cantidad').value || '0');
    const precio   = parseFloat(document.getElementById('detalle_precio').value || '0');
    const ivaP     = parseFloat(document.getElementById('detalle_iva').value || '0');

    if (!clave) {
        alert('Capture la clave de producto.');
        return;
    }

    const prodCat = productosApi.find(
        p => (p.cve_articulo || '').toUpperCase() === clave.toUpperCase()
    );
    if (!prodCat) {
        alert('La clave capturada no existe en el catálogo de productos.');
        return;
    }

    if (!(cantidad > 0)) {
        alert('La cantidad debe ser mayor a 0.');
        return;
    }
    if (isNaN(precio) || precio < 0) {
        alert('El precio neto debe ser mayor o igual a 0.');
        return;
    }

    const descFinal = producto || prodCat.des_articulo || prodCat.cve_articulo;

    // Precio NETO (incluye IVA) -> desglosar
    const totalNetoLinea = cantidad * precio;
    let base = totalNetoLinea;
    let iva = 0;
    if (ivaP > 0) {
        base = totalNetoLinea / (1 + (ivaP / 100));
        iva  = totalNetoLinea - base;
    }
    const subtotal = base;
    const total = totalNetoLinea;

    detalleOC.push({
        id: Date.now() + Math.random(),
        clave,
        producto: descFinal,
        uom,
        cantidad,
        precio,
        ivaP,
        subtotal,
        iva,
        total
    });

    renderDetalle();
    limpiarDetalleCaptura();
}

function limpiarDetalleCaptura() {
    document.getElementById('detalle_clave').value = '';
    document.getElementById('detalle_producto').value = '';
    document.getElementById('detalle_uom').value = '';
    document.getElementById('detalle_cantidad').value = '0';
    document.getElementById('detalle_precio').value = '0';
    document.getElementById('detalle_iva').value = '16';
}

function eliminarLineaDetalle(id) {
    detalleOC = detalleOC.filter(r => r.id !== id);
    renderDetalle();
}

function renderDetalle() {
    const tbody = document.getElementById('tbodyDetalle');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!detalleOC.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 9;
        td.className = 'text-center text-muted';
        td.textContent = 'No hay productos capturados.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        actualizarTotales();
        return;
    }

    detalleOC.forEach(r => {
        const tr = document.createElement('tr');

        const tdAcc = document.createElement('td');
        const btnDel = document.createElement('button');
        btnDel.type = 'button';
        btnDel.className = 'btn btn-outline-danger btn-sm';
        btnDel.textContent = 'Eliminar';
        btnDel.addEventListener('click', () => eliminarLineaDetalle(r.id));
        tdAcc.appendChild(btnDel);
        tr.appendChild(tdAcc);

        const tdClave = document.createElement('td');
        tdClave.textContent = r.clave;
        tr.appendChild(tdClave);

        const tdProd = document.createElement('td');
        tdProd.textContent = r.producto;
        tr.appendChild(tdProd);

        const tdUom = document.createElement('td');
        tdUom.textContent = r.uom;
        tr.appendChild(tdUom);

        const tdCant = document.createElement('td');
        tdCant.className = 'text-end';
        tdCant.textContent = r.cantidad.toFixed(4);
        tr.appendChild(tdCant);

        const tdPrecio = document.createElement('td');
        tdPrecio.className = 'text-end';
        tdPrecio.textContent = r.precio.toFixed(4);
        tr.appendChild(tdPrecio);

        const tdIvaP = document.createElement('td');
        tdIvaP.className = 'text-end';
        tdIvaP.textContent = r.ivaP.toFixed(2) + '%';
        tr.appendChild(tdIvaP);

        const tdSub = document.createElement('td');
        tdSub.className = 'text-end';
        tdSub.textContent = r.subtotal.toFixed(2);
        tr.appendChild(tdSub);

        const tdTot = document.createElement('td');
        tdTot.className = 'text-end';
        tdTot.textContent = r.total.toFixed(2);
        tr.appendChild(tdTot);

        tbody.appendChild(tr);
    });

    actualizarTotales();
}

function actualizarTotales() {
    let subtotal = 0, iva = 0, total = 0;
    detalleOC.forEach(r => {
        subtotal += r.subtotal;
        iva += r.iva;
        total += r.total;
    });

    document.getElementById('lbl_subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('lbl_iva').textContent      = iva.toFixed(2);
    document.getElementById('lbl_total').textContent    = total.toFixed(2);
}

// ---------- Guardar OC ----------
async function guardarOC() {
    if (!detalleOC.length) {
        alert('Capture al menos un producto antes de guardar.');
        return;
    }

    const empresaSel = document.getElementById('empresa')?.value || '';
    const almSel     = document.getElementById('almacen')?.value || '';
    const provSel    = document.getElementById('proveedor')?.value || '';
    const tipoOc     = document.getElementById('tipo_oc')?.value || '';
    const usuarioSel = document.getElementById('usuario')?.value || '';

    if (!almSel) {
        alert('Seleccione un almacén.');
        return;
    }
    if (!provSel) {
        alert('Seleccione un proveedor.');
        return;
    }
    if (!tipoOc) {
        alert('Seleccione el tipo de OC (protocolo).');
        return;
    }
    if (!usuarioSel) {
        alert('Seleccione el usuario.');
        return;
    }

    // Validar fechas aquí también
    const fechaOC  = document.getElementById('fecha_oc')?.value || '';
    const fechaComp = document.getElementById('fecha_compromiso')?.value || '';
    const fechaPrev = document.getElementById('fecha_recep_prev')?.value || '';

    if (fechaOC) {
        if (fechaComp && fechaComp < fechaOC) {
            alert('La Fecha Compromiso no puede ser menor que la Fecha de la OC.');
            return;
        }
        if (fechaPrev && fechaPrev < fechaOC) {
            alert('La Recepción Prevista no puede ser menor que la Fecha de la OC.');
            return;
        }
    }

    const payload = {
        encabezado: {
            id_aduana_edit: editIdAduana > 0 ? editIdAduana : 0,
            folio: document.getElementById('folio')?.value || '',
            fecha_oc: fechaOC,
            empresa: empresaSel,
            almacen_cve: almSel,
            proveedor_id: provSel ? parseInt(provSel, 10) : 0,
            tipo_oc: tipoOc,
            moneda: document.getElementById('moneda')?.value || '',
            tipo_cambio: parseFloat(document.getElementById('tipo_cambio')?.value || '1'),
            folio_erp: document.getElementById('folio_erp')?.value || '',
            status: document.getElementById('status_oc')?.value || 'ABIERTA',
            pedido_id: document.getElementById('pedido_id')?.value || '',
            fecha_compromiso: fechaComp,
            fecha_recep_prev: fechaPrev,
            comentarios: document.getElementById('comentarios')?.value || '',
            usuario: usuarioSel
        },
        detalle: detalleOC
    };


    try {
        const res = await fetch('orden_compra_edit.php?ajax=guardar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!data || !data.ok) {
            alert('No se pudo guardar la OC.\n' + (data && data.error ? data.error : 'Error desconocido.'));
            return;
        }

        const modoEdicion = editIdAduana > 0;
        const mensaje = modoEdicion 
            ? 'OC actualizada correctamente.\nID_Aduana: ' + data.id_aduana + '\nFolio: ' + data.folio
            : 'OC registrada correctamente.\nID_Aduana: ' + data.id_aduana + '\nFolio: ' + data.folio + '\nConsecutivo: ' + data.consec;
        
        alert(mensaje);
        
        if (modoEdicion) {
            // Recargar la misma página en modo edición
            window.location.href = 'orden_compra_edit.php?id_aduana=' + data.id_aduana;
        } else {
            // Ir a la lista después de crear
            window.location.href = 'orden_compra.php';
        }

    } catch (e) {
        console.error(e);
        alert('Error de comunicación con el servidor.');
    }
}
</script>
