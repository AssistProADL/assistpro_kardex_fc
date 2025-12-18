<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

$id = $_GET['id'] ?? 0;
if (!$id) {
    die('OT no especificada');
}

/* =============================
   DATOS DE LA ORDEN
   ============================= */
$sql = "
SELECT 
    id,
    Folio_Pro,
    Referencia,
    Cve_Articulo,
    Cantidad,
    Status
FROM t_ordenprod
WHERE id = :id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$ot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ot) {
    die('Orden de producción no encontrada');
}

/* =============================
   VALIDAR BOM
   ============================= */
$tieneBom = false;

if (!empty($ot['Referencia'])) {
    $sqlBom = "
    SELECT COUNT(*) 
    FROM td_ordenprod
    WHERE Folio_Pro = :folio
      AND Activo = 1
    ";
    $stmtBom = $pdo->prepare($sqlBom);
    $stmtBom->execute([':folio' => $ot['Referencia']]);
    $tieneBom = $stmtBom->fetchColumn() > 0;
}

/* =============================
   SI YA ESTÁ INICIADA
   ============================= */
if ($ot['Status'] === 'I') {
    header("Location: registrar_produccion.php?id={$id}");
    exit;
}
?>

<style>
.ap-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #dbe3f0;
    padding: 18px;
    box-shadow: 0 2px 4px rgba(15,90,173,.08);
}
.ap-title {
    font-size: 18px;
    font-weight: 700;
    color: #0F5AAD;
}
.ap-label {
    font-size: 11px;
    color: #6c757d;
}
.ap-value {
    font-size: 14px;
    font-weight: 600;
}
.ap-btn-start {
    font-size: 14px;
    padding: 10px 22px;
}
</style>

<div class="container-fluid mt-4">

    <div class="ap-card mx-auto" style="max-width:720px">

        <div class="mb-3">
            <div class="ap-title">
                <i class="fa fa-play-circle me-1"></i> Iniciar Producción
            </div>
            <div class="ap-label">
                Arranque controlado de Orden de Producción
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="ap-label">Orden de Producción</div>
                <div class="ap-value"><?= htmlspecialchars($ot['Folio_Pro']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="ap-label">Producto</div>
                <div class="ap-value"><?= htmlspecialchars($ot['Cve_Articulo']) ?></div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="ap-label">Cantidad Planeada</div>
                <div class="ap-value"><?= number_format($ot['Cantidad'], 2) ?></div>
            </div>
            <div class="col-md-6">
                <div class="ap-label">Usuario</div>
                <div class="ap-value"><?= $_SESSION['usuario'] ?? 'Operador' ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="ap-label">Fecha / Hora</div>
                <div class="ap-value"><?= date('d/m/Y H:i:s') ?></div>
            </div>
            <div class="col-md-6">
                <div class="ap-label">Estado Actual</div>
                <div class="ap-value">
                    <?= $ot['Status'] === 'P' ? 'Pendiente' : $ot['Status'] ?>
                </div>
            </div>
        </div>

        <?php if (!$tieneBom): ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                No es posible iniciar la producción porque la OT no tiene componentes (BOM) definidos.
            </div>
        <?php endif; ?>

        <div class="text-center">
            <button id="btnIniciar"
                class="btn btn-success ap-btn-start"
                <?= (!$tieneBom || $ot['Status'] !== 'P') ? 'disabled' : '' ?>
            >
                <i class="fa fa-play"></i> Iniciar Producción
            </button>
        </div>

    </div>
</div>

<script>
const btn = document.getElementById('btnIniciar');
if (btn) {
    btn.addEventListener('click', function () {

        this.disabled = true;
        this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Iniciando...';

        fetch('../api/iniciar_produccion.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=<?= $ot['id'] ?>'
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.ok) {
                window.location.href = 'registrar_produccion.php?id=<?= $ot['id'] ?>';
            } else {
                alert(resp.error || 'Error al iniciar producción');
                location.reload();
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
