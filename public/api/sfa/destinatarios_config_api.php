<?php
// public/api/sfa/destinatarios_config_api.php
// API JSON para Asignación de Listas por Destinatario (legacy compatible)

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../app/db.php'; // ajusta si tu ruta cambia

function jexit(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'options';

// Helpers defensivos: si una tabla/columna no existe no queremos HTML ni fatal.
// db_all/db_execute ya deberían lanzar Exception; la capturamos.
try {

    // =========================
    // OPTIONS (combos + defaults)
    // =========================
    if ($action === 'options') {

        // Empresas (si no hay tabla, devolvemos vacío sin romper)
        $empresas = [];
        try {
            $empresas = db_all("
                SELECT id, nombre
                FROM c_compania
                ORDER BY nombre
            ");
        } catch (Throwable $e) {
            $empresas = [];
        }

        // Default empresa = primera
        $empresa = $_GET['empresa'] ?? ($_POST['empresa'] ?? null);
        if (!$empresa && !empty($empresas)) {
            $empresa = (string)$empresas[0]['id'];
        }

        // Almacenes por empresa
        $almacenes = [];
        if ($empresa) {
            try {
                $almacenes = db_all("
                    SELECT id_almac AS id, des_almac AS nombre
                    FROM c_almacenp
                    WHERE cve_cia = ?
                    ORDER BY des_almac
                ", [$empresa]);
            } catch (Throwable $e) {
                $almacenes = [];
            }
        }

        $almacen = $_GET['almacen'] ?? ($_POST['almacen'] ?? null);
        if (!$almacen && !empty($almacenes)) {
            $almacen = (string)$almacenes[0]['id'];
        }

        // Rutas (si hay t_ruta la usamos; si no, devolvemos [])
        $rutas = [];
        try {
            // Intento con campos típicos: ID_Ruta/cve_ruta/descripcion
            $rutas = db_all("
                SELECT ID_Ruta AS id, CONCAT(cve_ruta,' - ',descripcion) AS nombre
                FROM t_ruta
                WHERE (Activo = 1 OR Activo = '1' OR Activo IS NULL)
                ORDER BY cve_ruta, descripcion
            ");
        } catch (Throwable $e) {
            // fallback si la tabla existe pero campos distintos:
            try {
                $rutas = db_all("SELECT id AS id, nombre AS nombre FROM rutas ORDER BY nombre");
            } catch (Throwable $e2) {
                $rutas = [];
            }
        }

        // Listas por almacén
        $listasP = [];
        $listasD = [];
        $listasPromo = [];

        if ($almacen) {
            // Lista Precios (listap) – campos reales: Fechaini / FechaFin / Cve_Almac
            try {
                $listasP = db_all("
                    SELECT id, Lista AS nombre, Fechaini AS fecha_ini, FechaFin AS fecha_fin
                    FROM listap
                    WHERE Cve_Almac = ?
                    ORDER BY id DESC
                ", [$almacen]);
            } catch (Throwable $e) {
                $listasP = [];
            }

            // Lista Descuentos (listad) – campos: Fechaini / FechaFin / Cve_Almac
            try {
                $listasD = db_all("
                    SELECT id, Lista AS nombre, Fechaini AS fecha_ini, FechaFin AS fecha_fin
                    FROM listad
                    WHERE Cve_Almac = ?
                    ORDER BY id DESC
                ", [$almacen]);
            } catch (Throwable $e) {
                $listasD = [];
            }

            // Lista Promociones (listapromo) – campos: Fechal / FechaF / Cve_Almac
            try {
                $listasPromo = db_all("
                    SELECT id, Lista AS nombre, Fechal AS fecha_ini, FechaF AS fecha_fin
                    FROM listapromo
                    WHERE Cve_Almac = ?
                    ORDER BY id DESC
                ", [$almacen]);
            } catch (Throwable $e) {
                $listasPromo = [];
            }
        }

        jexit([
            'ok' => true,
            'defaults' => [
                'empresa' => $empresa,
                'almacen' => $almacen,
                'ruta'    => null,
            ],
            'empresas' => $empresas,
            'almacenes' => $almacenes,
            'rutas' => $rutas,
            'listas' => [
                'P' => $listasP,
                'D' => $listasD,
                'PROMO' => $listasPromo,
            ]
        ]);
    }

    // =========================
    // LIST (grid + cards)
    // =========================
    if ($action === 'list') {

        $empresa = $_GET['empresa'] ?? null;
        $almacen = $_GET['almacen'] ?? null;
        $ruta    = $_GET['ruta'] ?? null; // opcional
        $q       = trim((string)($_GET['q'] ?? ''));

        // Rutas por cliente (si relclirutas existe) – usamos IdCliente = Cve_Clte
        // y enlazamos t_ruta si existe.
        $joinRutas = "
            LEFT JOIN (
                SELECT
                    rc.IdCliente,
                    GROUP_CONCAT(DISTINCT
                        COALESCE(CONCAT(tr.cve_ruta,' - ',tr.descripcion), CONCAT('RUTA#',rc.IdRuta))
                        ORDER BY rc.IdRuta SEPARATOR ', '
                    ) AS rutas_txt
                FROM relclirutas rc
                LEFT JOIN t_ruta tr ON tr.ID_Ruta = rc.IdRuta
                " . ($empresa ? "WHERE rc.IdEmpresa = ?" : "") . "
                GROUP BY rc.IdCliente
            ) rrt ON rrt.IdCliente = d.Cve_Clte
        ";

        $params = [];
        if ($empresa) $params[] = $empresa;

        // Filtro por ruta (si se selecciona) – no rompe aunque no exista t_ruta
        $whereRuta = "";
        if ($ruta) {
            $whereRuta = " AND EXISTS (
                SELECT 1 FROM relclirutas rc2
                WHERE rc2.IdCliente = d.Cve_Clte
                  AND rc2.IdRuta = ?
                  " . ($empresa ? "AND rc2.IdEmpresa = ?" : "") . "
            ) ";
        }

        if ($ruta) {
            $params[] = $ruta;
            if ($empresa) $params[] = $empresa;
        }

        // Search server-side
        $whereQ = "";
        if ($q !== '') {
            $whereQ = " AND (
                d.razonsocial LIKE ?
                OR d.clave_destinatario LIKE ?
                OR d.Cve_Clte LIKE ?
                OR CAST(d.id_destinatario AS CHAR) LIKE ?
            ) ";
            $like = "%{$q}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Grid base
        $rows = db_all("
            SELECT
                d.id_destinatario,
                d.clave_destinatario,
                d.Cve_Clte,
                d.razonsocial,
                COALESCE(rrt.rutas_txt, '—') AS rutas,
                cfg.ListaP,
                cfg.ListaD,
                cfg.ListaPromo,
                cfg.DiaVisita
            FROM c_destinatarios d
            LEFT JOIN relclilis cfg ON cfg.Id_Destinatario = d.id_destinatario
            $joinRutas
            WHERE (d.Activo = '1' OR d.Activo = 1 OR d.Activo IS NULL)
            $whereRuta
            $whereQ
            ORDER BY d.razonsocial
            LIMIT 2000
        ", $params);

        // Cards (sobre base filtrada)
        $cards = [
            'destinatarios' => count($rows),
            'con_listaP' => 0,
            'con_listaD' => 0,
            'con_promo' => 0,
            'con_dia' => 0
        ];
        foreach ($rows as $r) {
            if (!empty($r['ListaP'])) $cards['con_listaP']++;
            if (!empty($r['ListaD'])) $cards['con_listaD']++;
            if (!empty($r['ListaPromo'])) $cards['con_promo']++;
            if (!empty($r['DiaVisita'])) $cards['con_dia']++;
        }

        // Listas para dropdowns (según almacén)
        $listasP = [];
        $listasD = [];
        $listasPromo = [];
        if ($almacen) {
            try {
                $listasP = db_all("SELECT id, Lista AS nombre, Fechaini AS fecha_ini, FechaFin AS fecha_fin FROM listap WHERE Cve_Almac=? ORDER BY id DESC", [$almacen]);
            } catch (Throwable $e) {}
            try {
                $listasD = db_all("SELECT id, Lista AS nombre, Fechaini AS fecha_ini, FechaFin AS fecha_fin FROM listad WHERE Cve_Almac=? ORDER BY id DESC", [$almacen]);
            } catch (Throwable $e) {}
            try {
                $listasPromo = db_all("SELECT id, Lista AS nombre, Fechal AS fecha_ini, FechaF AS fecha_fin FROM listapromo WHERE Cve_Almac=? ORDER BY id DESC", [$almacen]);
            } catch (Throwable $e) {}
        }

        jexit([
            'ok' => true,
            'cards' => $cards,
            'rows' => $rows,
            'listas' => [
                'P' => $listasP,
                'D' => $listasD,
                'PROMO' => $listasPromo
            ],
        ]);
    }

    // =========================
    // SAVE (single)
    // =========================
    if ($action === 'save') {
        $id = (int)($_POST['id_destinatario'] ?? 0);
        if ($id <= 0) jexit(['ok' => false, 'msg' => 'id_destinatario inválido'], 400);

        $listap     = $_POST['listap'] !== '' ? ($_POST['listap'] ?? null) : null;
        $listad     = $_POST['listad'] !== '' ? ($_POST['listad'] ?? null) : null;
        $listapromo = $_POST['listapromo'] !== '' ? ($_POST['listapromo'] ?? null) : null;
        $diavisita  = $_POST['diavisita'] !== '' ? ($_POST['diavisita'] ?? null) : null;

        // Normaliza a int donde aplica
        $listap     = $listap     !== null ? (int)$listap : null;
        $listad     = $listad     !== null ? (int)$listad : null;
        $listapromo = $listapromo !== null ? (int)$listapromo : null;
        $diavisita  = $diavisita  !== null ? (int)$diavisita : null;

        $exists = db_all("SELECT Id FROM relclilis WHERE Id_Destinatario = ? LIMIT 1", [$id]);
        $allNull = ($listap === null && $listad === null && $listapromo === null && $diavisita === null);

        if ($allNull) {
            // Limpieza elegante: si queda todo null, borramos la relación
            db_execute("DELETE FROM relclilis WHERE Id_Destinatario = ?", [$id]);
            jexit(['ok' => true, 'msg' => 'Asignación eliminada (sin valores).']);
        }

        if ($exists) {
            db_execute("
                UPDATE relclilis
                SET ListaP = ?, ListaD = ?, ListaPromo = ?, DiaVisita = ?
                WHERE Id_Destinatario = ?
            ", [$listap, $listad, $listapromo, $diavisita, $id]);
        } else {
            db_execute("
                INSERT INTO relclilis (Id_Destinatario, ListaP, ListaD, ListaPromo, DiaVisita)
                VALUES (?, ?, ?, ?, ?)
            ", [$id, $listap, $listad, $listapromo, $diavisita]);
        }

        jexit(['ok' => true, 'msg' => 'Guardado correcto.']);
    }

    // =========================
    // BULK SAVE (multiple)
    // =========================
    if ($action === 'bulk_save') {
        $raw = $_POST['items'] ?? '[]';
        if (is_string($raw)) {
            $items = json_decode($raw, true);
        } else {
            $items = $raw;
        }
        if (!is_array($items)) jexit(['ok' => false, 'msg' => 'items inválido'], 400);

        $ok = 0; $del = 0; $fail = 0;

        foreach ($items as $it) {
            try {
                $id = (int)($it['id_destinatario'] ?? 0);
                if ($id <= 0) { $fail++; continue; }

                $listap     = ($it['listap'] ?? null);     $listap     = ($listap === '' ? null : $listap);
                $listad     = ($it['listad'] ?? null);     $listad     = ($listad === '' ? null : $listad);
                $listapromo = ($it['listapromo'] ?? null); $listapromo = ($listapromo === '' ? null : $listapromo);
                $diavisita  = ($it['diavisita'] ?? null);  $diavisita  = ($diavisita === '' ? null : $diavisita);

                $listap     = $listap     !== null ? (int)$listap : null;
                $listad     = $listad     !== null ? (int)$listad : null;
                $listapromo = $listapromo !== null ? (int)$listapromo : null;
                $diavisita  = $diavisita  !== null ? (int)$diavisita : null;

                $exists = db_all("SELECT Id FROM relclilis WHERE Id_Destinatario = ? LIMIT 1", [$id]);
                $allNull = ($listap === null && $listad === null && $listapromo === null && $diavisita === null);

                if ($allNull) {
                    db_execute("DELETE FROM relclilis WHERE Id_Destinatario = ?", [$id]);
                    $del++;
                    continue;
                }

                if ($exists) {
                    db_execute("UPDATE relclilis SET ListaP=?, ListaD=?, ListaPromo=?, DiaVisita=? WHERE Id_Destinatario=?",
                        [$listap, $listad, $listapromo, $diavisita, $id]
                    );
                } else {
                    db_execute("INSERT INTO relclilis (Id_Destinatario, ListaP, ListaD, ListaPromo, DiaVisita) VALUES (?, ?, ?, ?, ?)",
                        [$id, $listap, $listad, $listapromo, $diavisita]
                    );
                }
                $ok++;
            } catch (Throwable $e) {
                $fail++;
            }
        }

        jexit([
            'ok' => true,
            'msg' => "Guardado masivo: {$ok} ok, {$del} limpiados, {$fail} fallidos.",
            'stats' => ['ok' => $ok, 'deleted' => $del, 'failed' => $fail]
        ]);
    }

    jexit(['ok' => false, 'msg' => 'Acción no soportada'], 400);

} catch (Throwable $e) {
    jexit(['ok' => false, 'msg' => 'Error API: '.$e->getMessage()], 500);
}
