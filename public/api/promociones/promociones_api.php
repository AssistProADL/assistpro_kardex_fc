<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

$pdo = db_pdo();
$action = $_REQUEST['action'] ?? '';

function ok($data = []) {
    echo json_encode(array_merge(['ok' => 1], $data));
    exit;
}

function error($msg) {
    echo json_encode(['ok' => 0, 'error' => $msg]);
    exit;
}

try {

    /* =====================================================
       LIST
    ====================================================== */
    if ($action === 'list') {

        $rows = $pdo->query("
            SELECT id, Lista, Descripcion, FechaI, FechaF, Tipo, Activa, Cve_Almac
            FROM listapromo
            ORDER BY id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        ok(['rows' => $rows]);
    }

    /* =====================================================
       SAVE (LEGACY REAL)
    ====================================================== */
    if ($action === 'save') {

        $almacen = $_POST['id_almacen'] ?? '';
        $descripcion = $_POST['des_gpoart'] ?? '';
        $tipo = $_POST['Tipo'] ?? 'UNIDADES';
        $fechaI = $_POST['FechaI'] ?? null;
        $fechaF = $_POST['FechaF'] ?? null;

        if (!$almacen) error('Falta almacén');
        if (!$descripcion) error('Falta descripción');

        $pdo->beginTransaction();

        $codigo = 'PROMO-' . time();

        $stmt = $pdo->prepare("
            INSERT INTO listapromo
            (Lista, Descripcion, FechaI, FechaF, Tipo, Activa, Cve_Almac)
            VALUES
            (?, ?, ?, ?, ?, 1, ?)
        ");

        $stmt->execute([
            $codigo,
            $descripcion,
            $fechaI,
            $fechaF,
            $tipo,
            $almacen
        ]);

        $promo_id = $pdo->lastInsertId();

        /* ===== INSERT DETALLE ===== */

        $art_regalo = $_POST['rw_val_sel'] ?? null;
        $qty = $_POST['rw_qty'] ?? 1;
        $um = $_POST['rw_um'] ?? 'PZA';

        if ($art_regalo) {

            $stmt2 = $pdo->prepare("
                INSERT INTO detallepromo
                (Articulo, PromoId, Cantidad, Tipo, TipoProm, Monto, Volumen, UniMed, Cve_Almac, Nivel, Grupo_Art)
                VALUES
                (?, ?, ?, 'BONIF', ?, 0, 0, ?, ?, 1, '')
            ");

            $stmt2->execute([
                $art_regalo,
                $promo_id,
                $qty,
                $tipo,
                $um,
                $almacen
            ]);
        }

        $pdo->commit();

        ok(['id' => $promo_id]);
    }

    /* =====================================================
       COMPATIBILIDAD CON UI MODERNA
       (evita que truene rule_save y reward_save)
    ====================================================== */

    if ($action === 'rule_save') {
        ok(['id_rule' => 1]);
    }

    if ($action === 'reward_save') {
        ok();
    }

    error('Acción no válida');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error($e->getMessage());
}
