<?php
// public/inventarios/inventario_fisico.php
// Inventario Físico – Selección de BL con existencia teórica (PZ + CT + LP)

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ==========================================================
   LECTURA DE FILTROS
   ========================================================== */

// Empresa (c_compania.cve_cia)
$empresa_id   = isset($_GET['empresa_id'])   ? trim($_GET['empresa_id'])   : '';
// Almacén padre (c_almacenp.id)
$almacenp_id  = isset($_GET['almacenp_id'])  ? trim($_GET['almacenp_id'])  : '';
// Zona (c_almacen.cve_almac)
$zona_id      = isset($_GET['zona_id'])      ? trim($_GET['zona_id'])      : '';

$empresas  = [];
$almacenes = [];
$zonas     = [];
$bls       = [];
$error_msg = '';

try {

    /* ==========================================================
       EMPRESAS  (c_compania)
       ========================================================== */
    $sqlEmp = "
        SELECT 
            cve_cia       AS empresa_id,
            COALESCE(des_cia, clave_empresa, CONCAT('CIA ', cve_cia)) AS nombre
        FROM c_compania
        WHERE IFNULL(Activo,1) = 1
        ORDER BY des_cia, clave_empresa
    ";
    $empresas = db_all($sqlEmp);

    /* ==========================================================
       ALMACENES (c_almacenp) por empresa
       ========================================================== */
    $paramsAlm = [];
    $sqlAlm = "
        SELECT 
            ap.id AS almacenp_id,
            TRIM(CONCAT(IFNULL(ap.clave,''),' - ',IFNULL(ap.nombre,''))) AS nombre
        FROM c_almacenp ap
        WHERE IFNULL(ap.Activo,1) = 1
    ";
    if ($empresa_id !== '') {
        $sqlAlm .= " AND ap.cve_cia = :empresa_id ";
        $paramsAlm[':empresa_id'] = $empresa_id;
    }
    $sqlAlm .= " ORDER BY ap.nombre ";

    $almacenes = db_all($sqlAlm, $paramsAlm);

    /* ==========================================================
       ZONAS (c_almacen) con existencia para el almacén padre
       ========================================================== */
    if ($almacenp_id !== '') {
        $paramsZona = [':almacenp_id' => $almacenp_id];

        $sqlZona = "
            SELECT 
                a.cve_almac,
                TRIM(CONCAT(IFNULL(a.clave_almacen,''),' - ',IFNULL(a.des_almac,''))) AS nombre,
                SUM(
                    IFNULL(p.Existencia,0) +
                    IFNULL(c.PiezasXCaja,0) +
                    IFNULL(t.existencia,0)
                ) AS existencia_total
            FROM c_almacen a
            JOIN c_almacenp q 
                  ON a.cve_almacenp = q.id
            JOIN c_ubicacion u
                  ON u.cve_almac = a.cve_almac
            LEFT JOIN ts_existenciapiezas p
                  ON p.cve_almac = q.id
                 AND p.idy_ubica = u.idy_ubica
            LEFT JOIN ts_existenciacajas c
                  ON c.Cve_Almac = q.id
                 AND c.idy_ubica = u.idy_ubica
            LEFT JOIN ts_existenciatarima t
                  ON t.cve_almac = q.id
                 AND t.idy_ubica = u.idy_ubica
            WHERE a.cve_almacenp = :almacenp_id
        ";

        if ($empresa_id !== '') {
            $sqlZona .= " AND q.cve_cia = :empresa_id ";
            $paramsZona[':empresa_id'] = $empresa_id;
        }

        $sqlZona .= "
            GROUP BY a.cve_almac, a.des_almac
            HAVING existencia_total > 0
            ORDER BY a.cve_almac
        ";

        $zonas = db_all($sqlZona, $paramsZona);
    }

    /* ==========================================================
       BLs (CodigoCSD) con existencia teórica PZ + CT + LP
       ========================================================== */
    if ($almacenp_id !== '') {

        $paramsBL = [':almacenp_id' => $almacenp_id];

        $sqlBL = "
            SELECT 
                u.cve_almac,
                u.idy_ubica,
                u.CodigoCSD          AS bl,
                u.cve_pasillo        AS pasillo,
                u.cve_rack           AS rack,
                u.cve_nivel          AS nivel,
                u.Seccion            AS zona_logica,
                u.Ubicacion          AS posicion,
                SUM(
                    IFNULL(p.Existencia,0) +
                    IFNULL(c.PiezasXCaja,0) +
                    IFNULL(t.existencia,0)
                )                    AS existencia_total
            FROM c_almacen a
            JOIN c_almacenp q 
                  ON a.cve_almacenp = q.id
            JOIN c_ubicacion u
                  ON u.cve_almac = a.cve_almac
            LEFT JOIN ts_existenciapiezas p
                  ON p.cve_almac = q.id
                 AND p.idy_ubica = u.idy_ubica
            LEFT JOIN ts_existenciacajas c
                  ON c.Cve_Almac = q.id
                 AND c.idy_ubica = u.idy_ubica
            LEFT JOIN ts_existenciatarima t
                  ON t.cve_almac = q.id
                 AND t.idy_ubica = u.idy_ubica
            WHERE a.cve_almacenp = :almacenp_id
        ";

        if ($empresa_id !== '') {
            $sqlBL .= " AND q.cve_cia = :empresa_id ";
            $paramsBL[':empresa_id'] = $empresa_id;
        }

        if ($zona_id !== '') {
            $sqlBL .= " AND a.cve_almac = :zona_id ";
            $paramsBL[':zona_id'] = $zona_id;
        }

        $sqlBL .= "
            GROUP BY
                u.cve_almac,
                u.idy_ubica,
                u.CodigoCSD,
                u.cve_pasillo,
                u.cve_rack,
                u.cve_nivel,
                u.Seccion,
                u.Ubicacion
            HAVING existencia_total > 0
            ORDER BY
                u.cve_pasillo,
                u.cve_rack,
                u.cve_nivel,
                u.Seccion,
                u.Ubicacion,
                u.CodigoCSD
        ";

        $bls = db_all($sqlBL, $paramsBL);
    }

} catch (Exception $ex) {
    $error_msg = $ex->getMessage();
}
?>
<div class="container-fluid" style="font-size:10px;">

    <!-- TÍTULO -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-semibold">Inventario Físico – Selección de BL</h5>
            <small class="text-muted">
                Filtra por empresa, almacén y zona para listar solo los BL (ubicaciones) con existencia teórica.
            </small>
        </div>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger py-2 small">
            <strong>Error:</strong> <?= h($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- FILTROS -->
    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">

                <!-- Empresa -->
                <div class="col-md-3 col-sm-6">
                    <label for="empresa_id" class="form-label mb-1">Empresa</label>
                    <select name="empresa_id" id="empresa_id"
                            class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        <option value="">(Todas)</option>
                        <?php foreach ($empresas as $e): ?>
                            <option value="<?= h($e['empresa_id']) ?>"
                                <?= ($empresa_id !== '' && $empresa_id == $e['empresa_id']) ? 'selected' : '' ?>>
                                <?= h($e['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Almacén (c_almacenp) -->
                <div class="col-md-3 col-sm-6">
                    <label for="almacenp_id" class="form-label mb-1">Almacén</label>
                    <select name="almacenp_id" id="almacenp_id"
                            class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        <option value="">Seleccione...</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= h($a['almacenp_id']) ?>"
                                <?= ($almacenp_id !== '' && $almacenp_id == $a['almacenp_id']) ? 'selected' : '' ?>>
                                <?= h($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Zona de almacenaje (c_almacen) -->
                <div class="col-md-3 col-sm-6">
                    <label for="zona_id" class="form-label mb-1">Zona de almacenaje</label>
                    <select name="zona_id" id="zona_id"
                            class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        <option value="">(Todas)</option>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?= h($z['cve_almac']) ?>"
                                <?= ($zona_id !== '' && $zona_id == $z['cve_almac']) ? 'selected' : '' ?>>
                                <?= h($z['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botón consultar -->
                <div class="col-md-3 col-sm-6 text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 mt-3">
                        Consultar BLs
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- LISTA DE BLs CON EXISTENCIA -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">BLs con existencia teórica</h6>
                <small class="text-muted">
                    Solo se muestran las ubicaciones (BL) donde la suma de piezas, cajas y tarimas es &gt; 0.
                </small>
            </div>

            <?php if ($almacenp_id === ''): ?>
                <div class="alert alert-info py-2 small mb-0">
                    Selecciona un almacén para listar BLs con existencia teórica.
                </div>
            <?php else: ?>

                <?php if (!$bls): ?>
                    <div class="alert alert-warning py-2 small mb-0">
                        No se encontraron BLs con existencia teórica para los filtros seleccionados.
                    </div>
                <?php else: ?>
                    <form method="post" action="#">
                        <!-- Luego conectaremos esto al snapshot -->
                        <div class="table-responsive" style="max-height:460px; overflow-y:auto;">
                            <table class="table table-sm table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:30px;">
                                            <input type="checkbox"
                                                   onclick="const c=this.checked;document.querySelectorAll('.chk-bl').forEach(x=>x.checked=c);">
                                        </th>
                                        <th>BL</th>
                                        <th>Pasillo</th>
                                        <th>Rack</th>
                                        <th>Nivel</th>
                                        <th>Zona</th>
                                        <th>Posición</th>
                                        <th class="text-end">Existencia teórica</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalExist = 0;
                                    foreach ($bls as $row):
                                        $totalExist += (float)$row['existencia_total'];
                                    ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                       class="chk-bl"
                                                       name="bls[]"
                                                       value="<?= h($row['idy_ubica']) ?>">
                                            </td>
                                            <td><?= h($row['bl']) ?></td>
                                            <td><?= h($row['pasillo']) ?></td>
                                            <td><?= h($row['rack']) ?></td>
                                            <td><?= h($row['nivel']) ?></td>
                                            <td><?= h($row['zona_logica']) ?></td>
                                            <td><?= h($row['posicion']) ?></td>
                                            <td class="text-end">
                                                <?= number_format((float)$row['existencia_total'], 4) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="7" class="text-end">Total existencia teórica (BL listados):</th>
                                        <th class="text-end">
                                            <?= number_format($totalExist, 4) ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                Snapshot teórico (pendiente)
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</div> <!-- /.container-fluid -->

</div> <!-- /.content-wrapper -->
</body>
</html>
