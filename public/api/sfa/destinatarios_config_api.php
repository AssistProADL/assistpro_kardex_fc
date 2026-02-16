<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../app/db.php';

function jexit(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'options';

try {

    /* =========================================================
       OPTIONS → USA TU API FUNCIONAL
    ========================================================= */
    if ($action === 'options') {

        ob_start();
        require __DIR__ . '/../api_empresas_almacenes_rutas.php';
        $baseJson = ob_get_clean();
        $base = json_decode($baseJson, true);

        if (!$base || empty($base['ok'])) {
            jexit(['ok'=>false,'msg'=>'Error leyendo api_empresas_almacenes_rutas'],500);
        }

        $empresa = $_GET['empresa'] ?? $base['defaults']['empresa'] ?? null;
        $almacen = $_GET['almacen'] ?? $base['defaults']['almacen'] ?? null;

        $listasP = [];
        $listasD = [];
        $listasPromo = [];

        if ($almacen) {

            $listasP = db_all("
                SELECT id, Lista AS nombre
                FROM listap
                WHERE Cve_Almac = ?
                ORDER BY id DESC
            ", [$almacen]);

            $listasD = db_all("
                SELECT id, Lista AS nombre
                FROM listad
                WHERE Cve_Almac = ?
                ORDER BY id DESC
            ", [$almacen]);

            $listasPromo = db_all("
                SELECT id, Lista AS nombre
                FROM listapromo
                WHERE Cve_Almac = ?
                ORDER BY id DESC
            ", [$almacen]);
        }

        jexit([
            'ok' => true,
            'defaults' => [
                'empresa' => $empresa,
                'almacen' => $almacen,
                'ruta'    => null
            ],
            'empresas' => $base['empresas'],
            'almacenes'=> $base['almacenes'],
            'rutas'    => $base['rutas'],
            'listas'   => [
                'P'     => $listasP,
                'D'     => $listasD,
                'PROMO' => $listasPromo
            ]
        ]);
    }


    /* =========================================================
       LIST (GRID)
    ========================================================= */
    if ($action === 'list') {

        $empresa = $_GET['empresa'] ?? null;
        $almacen = $_GET['almacen'] ?? null;
        $ruta    = $_GET['ruta'] ?? null;
        $q       = trim($_GET['q'] ?? '');

        $params = [];

        $sql = "
            SELECT
                d.id_destinatario,
                d.clave_destinatario,
                d.Cve_Clte,
                d.razonsocial,
                cfg.ListaP,
                cfg.ListaD,
                cfg.ListaPromo,
                cfg.DiaVisita
            FROM c_destinatarios d
            LEFT JOIN relclilis cfg 
                ON cfg.Id_Destinatario = d.id_destinatario
            WHERE (d.Activo = 1 OR d.Activo IS NULL)
        ";

        if ($q !== '') {
            $sql .= " AND (
                d.razonsocial LIKE ?
                OR d.clave_destinatario LIKE ?
                OR d.Cve_Clte LIKE ?
            )";
            $like = "%$q%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY d.razonsocial LIMIT 2000";

        $rows = db_all($sql, $params);

        /* ================= CARDS ================= */
        $cards = [
            'destinatarios' => count($rows),
            'con_listaP' => 0,
            'con_listaD' => 0,
            'con_promo' => 0,
            'con_dia' => 0
        ];

        foreach ($rows as $r) {
            if (!empty($r['ListaP']))     $cards['con_listaP']++;
            if (!empty($r['ListaD']))     $cards['con_listaD']++;
            if (!empty($r['ListaPromo'])) $cards['con_promo']++;
            if (!empty($r['DiaVisita']))  $cards['con_dia']++;
        }

        /* ================= LISTAS (NO VACÍAS) ================= */

        $listasP = [];
        $listasD = [];
        $listasPromo = [];

        if ($almacen) {

            $listasP = db_all("
                SELECT id, Lista AS nombre
                FROM listap
                WHERE Cve_Almac = ?
                ORDER BY id DESC
            ", [$almacen]);

            $listasD = db_all("
                SELECT id, Lista AS nombre
                FROM listad
                WHERE Cve_Almac = ?
                ORDER BY id DESC
            ", [$almacen]);

            $listasPromo = db_all("
                SELECT id, Lista AS nombre
                FROM listapromo
                WHERE Cve_Almac = ?
                ORDER BY id DESC
            ", [$almacen]);
        }

        jexit([
            'ok' => true,
            'rows' => $rows,
            'cards' => $cards,
            'listas' => [
                'P' => $listasP,
                'D' => $listasD,
                'PROMO' => $listasPromo
            ]
        ]);
    }


    /* =========================================================
       SAVE
    ========================================================= */
    if ($action === 'save') {

        $id = (int)($_POST['id_destinatario'] ?? 0);

        $listap     = $_POST['listap']     !== '' ? (int)$_POST['listap']     : null;
        $listad     = $_POST['listad']     !== '' ? (int)$_POST['listad']     : null;
        $listapromo = $_POST['listapromo'] !== '' ? (int)$_POST['listapromo'] : null;
        $diavisita  = $_POST['diavisita']  !== '' ? (int)$_POST['diavisita']  : null;

        $exists = db_all("
            SELECT Id 
            FROM relclilis 
            WHERE Id_Destinatario = ? 
            LIMIT 1
        ", [$id]);

        if ($exists) {
            db_execute("
                UPDATE relclilis
                SET ListaP=?, ListaD=?, ListaPromo=?, DiaVisita=?
                WHERE Id_Destinatario=?
            ", [$listap,$listad,$listapromo,$diavisita,$id]);
        } else {
            db_execute("
                INSERT INTO relclilis
                (Id_Destinatario, ListaP, ListaD, ListaPromo, DiaVisita)
                VALUES (?,?,?,?,?)
            ", [$id,$listap,$listad,$listapromo,$diavisita]);
        }

        jexit(['ok'=>true,'msg'=>'Guardado']);
    }

    jexit(['ok'=>false,'msg'=>'Acción no soportada'],400);

} catch (Throwable $e) {
    jexit(['ok'=>false,'msg'=>$e->getMessage()],500);
}
