<?php
// public/reportes/liquidacion.php

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

// ================== Parámetros ==================
$almacen_id    = isset($_GET['almacen']) ? (int)$_GET['almacen'] : 0;
$ruta_id       = isset($_GET['ruta']) ? (int)$_GET['ruta'] : 0;
$dia_operativo = isset($_GET['dia']) ? (int)$_GET['dia'] : 0;
$accion        = isset($_GET['accion']) ? $_GET['accion'] : null;

// ================== Combo Almacén ==================
$sqlAlm = "SELECT id, clave, nombre FROM c_almacenp WHERE Activo = 1 ORDER BY nombre";
$almacenes = $pdo->query($sqlAlm)->fetchAll(PDO::FETCH_ASSOC);

// ================== Combo Rutas ==================
$rutas = [];
if ($almacen_id > 0) {
    $sqlRut = "
        SELECT ID_Ruta, cve_ruta, descripcion
        FROM t_ruta
        WHERE Activo = 1
          AND cve_almacenp = :almacen_id
        ORDER BY descripcion";
    $stmtRut = $pdo->prepare($sqlRut);
    $stmtRut->execute([':almacen_id' => $almacen_id]);
    $rutas = $stmtRut->fetchAll(PDO::FETCH_ASSOC);
}

// ================== Combo Día Operativo ==================
$dias = [];
if ($ruta_id > 0) {
    $sqlDias = "
        SELECT Id, DiaO, Fecha
        FROM diaso
        WHERE RutaId = :ruta_id
        ORDER BY Fecha DESC
        LIMIT 60";
    $stmtDias = $pdo->prepare($sqlDias);
    $stmtDias->execute([':ruta_id' => $ruta_id]);
    $dias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);
}

// ================== Resultados ==================
$resumen       = [];
$credito_rows  = [];
$devol_rows    = [];
$analisis_rows = [];
$idEmpresa     = null;

if ($almacen_id && $ruta_id && $dia_operativo) {

    // IdEmpresa = c_almacenp.clave
    $sqlEmp = "SELECT clave, nombre FROM c_almacenp WHERE id = :id";
    $stmtEmp = $pdo->prepare($sqlEmp);
    $stmtEmp->execute([':id' => $almacen_id]);
    $empRow    = $stmtEmp->fetch(PDO::FETCH_ASSOC);
    $idEmpresa = $empRow ? $empRow['clave'] : null;
    $almacen_nombre = $empRow ? $empRow['nombre'] : '';

    if ($idEmpresa !== null) {

        $params = [
            ':empresa' => $idEmpresa,
            ':ruta'    => $ruta_id,
            ':dia'     => $dia_operativo,
        ];

        // ---------- 1) VENTAS (para Resumen Financiero) ----------
        $sqlVentas = "
            SELECT
                COALESCE(SUM(v.TOTAL), 0) AS venta_total,
                COALESCE(SUM(CASE WHEN v.TipoVta='Contado' THEN v.TOTAL ELSE 0 END), 0) AS ventas_contado,
                COALESCE(SUM(CASE WHEN v.TipoVta='Credito' THEN v.TOTAL ELSE 0 END), 0) AS ventas_credito,
                COALESCE(SUM(CASE WHEN v.TOTAL < 0 THEN v.TOTAL ELSE 0 END), 0) AS devoluciones
            FROM venta v
            WHERE v.IdEmpresa = :empresa
              AND v.RutaId    = :ruta
              AND v.DiaO      = :dia
              AND v.Cancelada = 0
        ";
        $stmtV = $pdo->prepare($sqlVentas);
        $stmtV->execute($params);
        $rowVentas = $stmtV->fetch(PDO::FETCH_ASSOC) ?: [
            'venta_total'    => 0,
            'ventas_contado' => 0,
            'ventas_credito' => 0,
            'devoluciones'   => 0,
        ];

        // ---------- 2) COBRANZA (para Resumen Financiero) ----------
        $sqlCobRes = "
            SELECT COALESCE(SUM(dc.Abono),0) AS cobranza
            FROM detallecob dc
            WHERE dc.IdEmpresa = :empresa
              AND dc.RutaId    = :ruta
              AND dc.DiaO      = :dia
              AND dc.Cancelada = 0
        ";
        $stmtCobRes = $pdo->prepare($sqlCobRes);
        $stmtCobRes->execute($params);
        $rowCobRes = $stmtCobRes->fetch(PDO::FETCH_ASSOC) ?: ['cobranza' => 0];

        // ---------- 3) DESCUENTOS (Resumen Financiero) ----------
        $sqlDesc = "
            SELECT COALESCE(SUM(dv.DescMon),0) AS descuentos
            FROM detallevet dv
            INNER JOIN venta v
                   ON v.Documento = dv.Docto
                  AND v.IdEmpresa = dv.IdEmpresa
                  AND v.RutaId    = dv.RutaId
            WHERE dv.IdEmpresa = :empresa
              AND dv.RutaId    = :ruta
              AND v.DiaO       = :dia
              AND v.Cancelada  = 0
        ";
        $stmtD = $pdo->prepare($sqlDesc);
        $stmtD->execute($params);
        $rowDesc = $stmtD->fetch(PDO::FETCH_ASSOC) ?: ['descuentos' => 0];

        // ---------- 4) PREVENTA (pendiente de definir) ----------
        $preventa = 0;

        $venta_total    = (float)$rowVentas['venta_total'];
        $ventas_contado = (float)$rowVentas['ventas_contado'];
        $ventas_credito = (float)$rowVentas['ventas_credito'];
        $devoluciones_v = (float)$rowVentas['devoluciones'];  // suele ser negativo
        $cobranza_res   = (float)$rowCobRes['cobranza'];
        $descuentos     = (float)$rowDesc['descuentos'];

        $total_liquidar = $venta_total + $devoluciones_v - $cobranza_res - $descuentos;

        $resumen[] = [
            'venta_total'     => $venta_total,
            'preventa'        => $preventa,
            'ventas_contado'  => $ventas_contado,
            'ventas_credito'  => $ventas_credito,
            'devoluciones'    => $devoluciones_v,
            'cobranza'        => $cobranza_res,
            'descuentos'      => $descuentos,
            'total_liquidar'  => $total_liquidar
        ];

        // ================== 5) CRÉDITO Y COBRANZA ==================
        $sqlCred = "
            SELECT
                c.Documento,
                COALESCE(d.razonsocial,
                         cli.RazonSocial,
                         cli.RazonComercial) AS Cliente,
                c.Saldo AS Credito,
                COALESCE(SUM(dc.Abono),0) AS Cobranza
            FROM cobranza c
            LEFT JOIN detallecob dc
                   ON dc.Documento = c.Documento
                  AND dc.RutaId    = c.RutaId
                  AND dc.DiaO      = :dia_det
                  AND dc.Cancelada = 0
            LEFT JOIN c_destinatarios d
                   ON d.id_destinatario = c.Cliente
            LEFT JOIN c_cliente cli
                   ON cli.Cve_Clte = d.Cve_Clte
            WHERE c.RutaId = :ruta
              AND c.DiaO   = :dia_cab
            GROUP BY c.Documento, Cliente, c.Saldo
            ORDER BY c.Documento
        ";
        $stmtCred = $pdo->prepare($sqlCred);
        $stmtCred->execute([
            ':ruta'    => $ruta_id,
            ':dia_det' => $dia_operativo,
            ':dia_cab' => $dia_operativo,
        ]);
        $credito_rows = $stmtCred->fetchAll(PDO::FETCH_ASSOC);

        // ================== 6) DEVOLUCIONES (detalle por artículo) ==================
        $sqlDev = "
            SELECT
                dv.Articulo    AS clave,
                dv.Descripcion AS articulo,
                0              AS cajas,
                SUM(dv.Pza)    AS piezas,
                SUM(dv.Importe) AS importe
            FROM venta v
            INNER JOIN detallevet dv
                    ON v.Documento = dv.Docto
                   AND v.IdEmpresa = dv.IdEmpresa
                   AND v.RutaId    = dv.RutaId
            WHERE v.IdEmpresa = :empresa
              AND v.RutaId    = :ruta
              AND v.DiaO      = :dia
              AND v.Cancelada = 0
              AND v.TOTAL < 0
            GROUP BY dv.Articulo, dv.Descripcion
            ORDER BY dv.Articulo
        ";
        $stmtDev = $pdo->prepare($sqlDev);
        $stmtDev->execute($params);
        $devol_rows = $stmtDev->fetchAll(PDO::FETCH_ASSOC);

        // ================== 7) ANÁLISIS DE VENTAS ==================
        // 7.1 Stock inicial por artículo (StockHistorico)
        $sqlStock = "
            SELECT Articulo, SUM(Stock) AS stock_ini
            FROM StockHistorico
            WHERE RutaID   = :ruta
              AND DiaO     = :dia
              AND IdEmpresa = :empresa
            GROUP BY Articulo
        ";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute($params);
        $stock_ini = [];
        foreach ($stmtStock->fetchAll(PDO::FETCH_ASSOC) as $rowS) {
            $stock_ini[$rowS['Articulo']] = (float)$rowS['stock_ini'];
        }

        // 7.2 Ventas por artículo (piezas + importe)
        $sqlAnal = "
            SELECT
                dv.Articulo    AS clave,
                dv.Descripcion AS articulo,
                SUM(dv.Pza)    AS ventas_pz,
                SUM(dv.Importe) AS total_importe
            FROM venta v
            INNER JOIN detallevet dv
                    ON v.Documento = dv.Docto
                   AND v.IdEmpresa = dv.IdEmpresa
                   AND v.RutaId    = dv.RutaId
            WHERE v.IdEmpresa = :empresa
              AND v.RutaId    = :ruta
              AND v.DiaO      = :dia
              AND v.Cancelada = 0
            GROUP BY dv.Articulo, dv.Descripcion
            ORDER BY dv.Articulo
        ";
        $stmtAnal = $pdo->prepare($sqlAnal);
        $stmtAnal->execute($params);
        $analisis_rows = [];

        foreach ($stmtAnal->fetchAll(PDO::FETCH_ASSOC) as $rowA) {
            $clave       = $rowA['clave'];
            $desc        = $rowA['articulo'];
            $ventas_pz   = (float)$rowA['ventas_pz'];
            $total_imp   = (float)$rowA['total_importe'];
            $stock_ini_pz = isset($stock_ini[$clave]) ? $stock_ini[$clave] : 0.0;
            $stock_fin_pz = $stock_ini_pz - $ventas_pz;

            $analisis_rows[] = [
                'clave'          => $clave,
                'articulo'       => $desc,
                'stock_ini_cj'   => 0.0,
                'stock_ini_pz'   => $stock_ini_pz,
                'ventas_cj'      => 0.0,
                'ventas_pz'      => $ventas_pz,
                'preventa_cj'    => 0.0,
                'preventa_pz'    => 0.0,
                'entrega_cj'     => 0.0,
                'entrega_pz'     => 0.0,
                'rec_cj'         => 0.0,
                'rec_pz'         => 0.0,
                'dev_cj'         => 0.0,
                'dev_pz'         => 0.0,
                'prom_cj'        => 0.0,
                'prom_pz'        => 0.0,
                'promprev_cj'    => 0.0,
                'promprev_pz'    => 0.0,
                'total_importe'  => $total_imp,
                'stock_fin_cj'   => 0.0,
                'stock_fin_pz'   => $stock_fin_pz,
                'total_ped_cj'   => 0.0,
                'total_ped_pz'   => 0.0,
            ];
        }

        // ================== EXPORTACIONES (PDF / XLS) ==================
        if ($accion === 'xls') {
            // ------- Exportar Excel -------
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=liquidacion_{$ruta_id}_{$dia_operativo}.xls");
            header("Pragma: no-cache");
            header("Expires: 0");

            $r = $resumen[0] ?? [
                'venta_total'=>0,'preventa'=>0,'ventas_contado'=>0,
                'ventas_credito'=>0,'devoluciones'=>0,'cobranza'=>0,
                'descuentos'=>0,'total_liquidar'=>0
            ];

            echo "<table border='1'>";
            echo "<tr><th>Clave Articulo</th><th>Articulo</th><th>CantxCaja</th><th>CantxPieza</th><th>Importe</th><th>PromoxCaja</th><th>PromoxPieza</th></tr>";

            $sum_cj = $sum_pz = $sum_imp = 0.0;

            foreach ($analisis_rows as $row) {
                $cj   = $row['ventas_cj'];
                $pz   = $row['ventas_pz'];
                $imp  = $row['total_importe'];
                $sum_cj  += $cj;
                $sum_pz  += $pz;
                $sum_imp += $imp;

                echo "<tr>";
                echo "<td>".htmlspecialchars($row['clave'])."</td>";
                echo "<td>".htmlspecialchars($row['articulo'])."</td>";
                echo "<td>".$cj."</td>";
                echo "<td>".$pz."</td>";
                echo "<td>".$imp."</td>";
                echo "<td>0</td>";
                echo "<td>0</td>";
                echo "</tr>";
            }

            // Totales y resumen (como tu ejemplo de Excel)
            echo "<tr><td colspan='2'><b>Total</b></td><td><b>{$sum_cj}</b></td><td><b>{$sum_pz}</b></td><td><b>{$sum_imp}</b></td><td>0</td><td>0</td></tr>";
            echo "<tr><td colspan='4'><b>Preventa</b></td><td>".number_format($r['preventa'],2)."</td><td colspan='2'></td></tr>";
            echo "<tr><td colspan='4'><b>Venta credito</b></td><td>".number_format($r['ventas_credito'],2)."</td><td colspan='2'></td></tr>";
            echo "<tr><td colspan='4'><b>Descuentos vp</b></td><td>".number_format($r['descuentos'],2)."</td><td colspan='2'></td></tr>";
            echo "<tr><td colspan='4'><b>Venta contado</b></td><td>".number_format($r['ventas_contado'],2)."</td><td colspan='2'></td></tr>";
            echo "<tr><td colspan='4'><b>Devoluciones</b></td><td>".number_format($r['devoluciones'],2)."</td><td colspan='2'></td></tr>";
            echo "<tr><td colspan='4'><b>Cobranza</b></td><td>".number_format($r['cobranza'],2)."</td><td colspan='2'></td></tr>";
            echo "<tr><td colspan='4'><b>Total a liquidar</b></td><td>".number_format($r['total_liquidar'],2)."</td><td colspan='2'></td></tr>";

            echo "</table>";
            exit;
        }

        if ($accion === 'pdf') {
            // ------- Generar PDF -------
            // Ajusta la ruta de FPDF según tu estructura
            require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

            $pdf = new FPDF('L','mm','Letter');
            $pdf->AddPage();

            // Encabezado basado en tu ejemplo Liquidacion.pdf :contentReference[oaicite:1]{index=1}
            $pdf->SetFont('Arial','B',14);
            $pdf->Cell(0,7,utf8_decode('Ticket de Liquidación'),0,1,'C');
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(0,6,utf8_decode('Ruta: '.$ruta_id.'   D.O.: '.$dia_operativo.'   Almacén: '.$almacen_nombre),0,1,'C');
            $pdf->Ln(3);

            // Análisis de ventas
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(0,6,utf8_decode('Análisis de Ventas'),0,1,'L');
            $pdf->Ln(1);

            $pdf->SetFont('Arial','B',7);

            $w = [20,55,10,10,10,10,10,10,10,10,10,10,10,10,10,10,15,10,10,10,10];
            $headers1 = ['Clave','Articulo','Stk Cj','Stk Pz','Vta Cj','Vta Pz','Prev Cj','Prev Pz','Ent Cj','Ent Pz','Rec Cj','Rec Pz','Dev Cj','Dev Pz','Prm Cj','Prm Pz','Total $','StF Cj','StF Pz','Ped Cj','Ped Pz'];

            foreach ($headers1 as $i => $h) {
                $pdf->Cell($w[$i],5,utf8_decode($h),1,0,'C');
            }
            $pdf->Ln();

            $pdf->SetFont('Arial','',7);

            foreach ($analisis_rows as $row) {
                $pdf->Cell($w[0],5,utf8_decode($row['clave']),1);
                $pdf->Cell($w[1],5,utf8_decode(substr($row['articulo'],0,30)),1);
                $pdf->Cell($w[2],5,number_format($row['stock_ini_cj'],2),1,0,'R');
                $pdf->Cell($w[3],5,number_format($row['stock_ini_pz'],2),1,0,'R');
                $pdf->Cell($w[4],5,number_format($row['ventas_cj'],2),1,0,'R');
                $pdf->Cell($w[5],5,number_format($row['ventas_pz'],2),1,0,'R');
                $pdf->Cell($w[6],5,number_format($row['preventa_cj'],2),1,0,'R');
                $pdf->Cell($w[7],5,number_format($row['preventa_pz'],2),1,0,'R');
                $pdf->Cell($w[8],5,number_format($row['entrega_cj'],2),1,0,'R');
                $pdf->Cell($w[9],5,number_format($row['entrega_pz'],2),1,0,'R');
                $pdf->Cell($w[10],5,number_format($row['rec_cj'],2),1,0,'R');
                $pdf->Cell($w[11],5,number_format($row['rec_pz'],2),1,0,'R');
                $pdf->Cell($w[12],5,number_format($row['dev_cj'],2),1,0,'R');
                $pdf->Cell($w[13],5,number_format($row['dev_pz'],2),1,0,'R');
                $pdf->Cell($w[14],5,number_format($row['prom_cj'],2),1,0,'R');
                $pdf->Cell($w[15],5,number_format($row['prom_pz'],2),1,0,'R');
                $pdf->Cell($w[16],5,number_format($row['total_importe'],2),1,0,'R');
                $pdf->Cell($w[17],5,number_format($row['stock_fin_cj'],2),1,0,'R');
                $pdf->Cell($w[18],5,number_format($row['stock_fin_pz'],2),1,0,'R');
                $pdf->Cell($w[19],5,number_format($row['total_ped_cj'],2),1,0,'R');
                $pdf->Cell($w[20],5,number_format($row['total_ped_pz'],2),1,0,'R');
                $pdf->Ln();
            }

            // Resumen financiero (parte baja del ticket)
            $pdf->Ln(4);
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(0,6,utf8_decode('Resumen Financiero'),0,1,'L');
            $pdf->SetFont('Arial','',8);
            $r = $resumen[0] ?? [
                'venta_total'=>0,'preventa'=>0,'ventas_contado'=>0,
                'ventas_credito'=>0,'devoluciones'=>0,'cobranza'=>0,
                'descuentos'=>0,'total_liquidar'=>0
            ];
            $pdf->Cell(40,5,utf8_decode('Venta Total:'),0,0,'L');      $pdf->Cell(30,5,number_format($r['venta_total'],2),0,1,'R');
            $pdf->Cell(40,5,utf8_decode('Preventa:'),0,0,'L');         $pdf->Cell(30,5,number_format($r['preventa'],2),0,1,'R');
            $pdf->Cell(40,5,utf8_decode('Ventas Contado:'),0,0,'L');   $pdf->Cell(30,5,number_format($r['ventas_contado'],2),0,1,'R');
            $pdf->Cell(40,5,utf8_decode('Ventas Crédito:'),0,0,'L');   $pdf->Cell(30,5,number_format($r['ventas_credito'],2),0,1,'R');
            $pdf->Cell(40,5,utf8_decode('Devoluciones:'),0,0,'L');     $pdf->Cell(30,5,number_format($r['devoluciones'],2),0,1,'R');
            $pdf->Cell(40,5,utf8_decode('Cobranza:'),0,0,'L');         $pdf->Cell(30,5,number_format($r['cobranza'],2),0,1,'R');
            $pdf->Cell(40,5,utf8_decode('Descuentos:'),0,0,'L');       $pdf->Cell(30,5,number_format($r['descuentos'],2),0,1,'R');
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(40,5,utf8_decode('Total a Liquidar:'),0,0,'L'); $pdf->Cell(30,5,number_format($r['total_liquidar'],2),0,1,'R');

            $pdf->Output('I',"liquidacion_{$ruta_id}_{$dia_operativo}.pdf");
            exit;
        }
    }
}
?>
<style>
    :root {
        --ap-primary: #0F5AAD;
        --ap-primary-soft: #e9f1fb;
        --ap-accent: #00A3E0;
        --ap-border: #d9e2f2;
        --ap-text-muted: #6c757d;
    }
    .ap-page-title { font-size:20px;font-weight:600;margin-bottom:12px;color:#1f2933;}
    .ap-card{background:#fff;border-radius:10px;border:1px solid var(--ap-border);margin-bottom:18px;box-shadow:0 2px 4px rgba(15,90,173,.05);}
    .ap-card-header{padding:8px 16px;border-bottom:1px solid var(--ap-border);border-radius:10px 10px 0 0;
        background:linear-gradient(90deg,var(--ap-primary) 0%,var(--ap-accent) 100%);color:#fff;font-size:13px;font-weight:600;
        display:flex;align-items:center;justify-content:space-between;}
    .ap-card-body{padding:12px 16px 14px 16px;font-size:11px;}
    .ap-filters-card{background:#fff;border-radius:10px;border:1px solid var(--ap-border);padding:12px 16px;margin-bottom:16px;}
    .ap-filters-card label{font-size:11px;font-weight:600;color:#4a5568;}
    .ap-filters-card .form-select,.ap-filters-card .form-control{font-size:11px;height:30px;padding:4px 8px;}
    .btn-ap-primary{background:#0F5AAD;border-color:#0F5AAD;font-size:11px;padding:4px 10px;}
    .btn-ap-primary:hover{background:#0c4a8c;border-color:#0c4a8c;}
    .btn-ap-excel{background:#004b94;border-color:#004b94;font-size:11px;padding:4px 10px;color:#fff;}
    .btn-ap-excel:hover{background:#00376c;border-color:#00376c;color:#fff;}
    .btn-ap-pdf{background:#d9534f;border-color:#d43f3a;font-size:11px;padding:4px 10px;color:#fff;}
    .btn-ap-pdf:hover{background:#c9302c;border-color:#ac2925;color:#fff;}
    .ap-table{width:100%;font-size:10px;}
    .ap-table th,.ap-table td{padding:3px 4px;vertical-align:middle;}
    .ap-table thead th{background:#e9f1fb;color:#1f2933;border-bottom:1px solid var(--ap-border);font-weight:600;}
    .ap-table tfoot th{background:#f3f6fb;font-weight:600;}
    .ap-metric-total{font-size:11px;font-weight:600;color:#1f2933;}
    .ap-metric-total span{color:#0F5AAD;}
</style>

<div class="container-fluid mt-2 mb-3">
    <div class="ap-page-title">Reporte de Liquidación</div>

    <!-- Filtros -->
    <div class="ap-filters-card">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4 col-sm-6">
                <label>Almacén:</label>
                <select name="almacen" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- Seleccione --</option>
                    <?php foreach ($almacenes as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" <?= $almacen_id == (int)$a['id'] ? 'selected' : '' ?>>
                            (<?= htmlspecialchars($a['clave']) ?>) - <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 col-sm-6">
                <label>Rutas:</label>
                <select name="ruta" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- Seleccione --</option>
                    <?php foreach ($rutas as $r): ?>
                        <option value="<?= (int)$r['ID_Ruta'] ?>" <?= $ruta_id == (int)$r['ID_Ruta'] ? 'selected' : '' ?>>
                            (<?= htmlspecialchars($r['cve_ruta']) ?>) - <?= htmlspecialchars($r['descripcion']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label>Día Operativo:</label>
                <select name="dia" class="form-select form-select-sm">
                    <option value="0">-- Seleccione --</option>
                    <?php foreach ($dias as $d): ?>
                        <option value="<?= (int)$d['DiaO'] ?>" <?= $dia_operativo == (int)$d['DiaO'] ? 'selected' : '' ?>>
                            <?= (int)$d['DiaO'] ?> | <?= htmlspecialchars($d['Fecha']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-8 text-end">
                <button type="submit" class="btn btn-ap-primary mb-1" name="accion" value="">
                    <i class="fa fa-search"></i> Buscar
                </button><br>
                <button type="submit" class="btn btn-ap-pdf mt-1" name="accion" value="pdf">
                    <i class="fa fa-file-pdf-o"></i> Generar Reporte
                </button>
                <button type="submit" class="btn btn-ap-excel mt-1" name="accion" value="xls">
                    <i class="fa fa-file-excel-o"></i> Exportar XLS
                </button>
            </div>
        </form>
    </div>

    <?php $r = $resumen[0] ?? []; ?>

    <!-- 1) Resumen Financiero -->
    <div class="ap-card">
        <div class="ap-card-header"><span>Resumen Financiero</span></div>
        <div class="ap-card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover ap-table">
                    <thead>
                    <tr>
                        <th class="text-end">Venta Total</th>
                        <th class="text-end">Preventa</th>
                        <th class="text-end">Ventas Contado</th>
                        <th class="text-end">Ventas Crédito</th>
                        <th class="text-end">Devoluciones</th>
                        <th class="text-end">Cobranza</th>
                        <th class="text-end">Descuentos</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($resumen): ?>
                        <tr>
                            <td class="text-end">$<?= number_format($r['venta_total'], 2) ?></td>
                            <td class="text-end">$<?= number_format($r['preventa'], 2) ?></td>
                            <td class="text-end">$<?= number_format($r['ventas_contado'], 2) ?></td>
                            <td class="text-end">$<?= number_format($r['ventas_credito'], 2) ?></td>
                            <td class="text-end">$<?= number_format($r['devoluciones'], 2) ?></td>
                            <td class="text-end">$<?= number_format($r['cobranza'], 2) ?></td>
                            <td class="text-end">$<?= number_format($r['descuentos'], 2) ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Sin información</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($resumen): ?>
                <div class="text-end ap-metric-total">
                    Total a liquidar:
                    <span>$<?= number_format($r['total_liquidar'] ?? 0, 2) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2) Crédito y Cobranza -->
    <div class="ap-card">
        <div class="ap-card-header"><span>Crédito y Cobranza</span></div>
        <div class="ap-card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover ap-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th class="text-end">Crédito</th>
                            <th class="text-end">Cobranza</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $total_credito  = 0.0;
                    $total_cobranza = 0.0;
                    ?>
                    <?php if ($credito_rows): ?>
                        <?php foreach ($credito_rows as $row): ?>
                            <?php
                            $total_credito  += (float)$row['Credito'];
                            $total_cobranza += (float)$row['Cobranza'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Documento']) ?></td>
                                <td><?= htmlspecialchars($row['Cliente']) ?></td>
                                <td class="text-end">$<?= number_format($row['Credito'], 2) ?></td>
                                <td class="text-end">$<?= number_format($row['Cobranza'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">Sin información</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total:</th>
                            <th class="text-end">$<?= number_format($total_credito, 2) ?></th>
                            <th class="text-end">$<?= number_format($total_cobranza, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- 3) Devoluciones -->
    <div class="ap-card">
        <div class="ap-card-header"><span>Devoluciones</span></div>
        <div class="ap-card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover ap-table">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Artículo</th>
                            <th class="text-end">Cajas</th>
                            <th class="text-end">Piezas</th>
                            <th class="text-end">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $total_dev_cajas   = 0.0;
                    $total_dev_piezas  = 0.0;
                    $total_dev_importe = 0.0;
                    ?>
                    <?php if ($devol_rows): ?>
                        <?php foreach ($devol_rows as $row): ?>
                            <?php
                            $total_dev_cajas   += (float)$row['cajas'];
                            $total_dev_piezas  += (float)$row['piezas'];
                            $total_dev_importe += (float)$row['importe'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['clave']) ?></td>
                                <td><?= htmlspecialchars($row['articulo']) ?></td>
                                <td class="text-end"><?= number_format($row['cajas'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['piezas'], 2) ?></td>
                                <td class="text-end">$<?= number_format($row['importe'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Ningún dato disponible en esta tabla</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total:</th>
                            <th class="text-end"><?= number_format($total_dev_cajas, 2) ?></th>
                            <th class="text-end"><?= number_format($total_dev_piezas, 2) ?></th>
                            <th class="text-end">$<?= number_format($total_dev_importe, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- 4) Análisis de Ventas -->
    <div class="ap-card">
        <div class="ap-card-header"><span>Análisis de Ventas</span></div>
        <div class="ap-card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover ap-table">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th colspan="2" class="text-center">Stock Inicial</th>
                            <th colspan="2" class="text-center">Ventas</th>
                            <th colspan="2" class="text-center">Preventa</th>
                            <th colspan="2" class="text-center">Entrega</th>
                            <th colspan="2" class="text-center">Rec</th>
                            <th colspan="2" class="text-center">Dev</th>
                            <th colspan="2" class="text-center">Prom</th>
                            <th colspan="2" class="text-center">Prom Prev</th>
                            <th class="text-end">Total $</th>
                            <th colspan="2" class="text-center">Stock Final</th>
                            <th colspan="2" class="text-center">Total Pedido</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th></th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                            <th class="text-end">Cj</th>
                            <th class="text-end">Pz</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $tot_si_cj = $tot_si_pz = 0.0;
                    $tot_ve_cj = $tot_ve_pz = 0.0;
                    $tot_prev_cj = $tot_prev_pz = 0.0;
                    $tot_ent_cj = $tot_ent_pz = 0.0;
                    $tot_rec_cj = $tot_rec_pz = 0.0;
                    $tot_dev_cj = $tot_dev_pz = 0.0;
                    $tot_prom_cj = $tot_prom_pz = 0.0;
                    $tot_promprev_cj = $tot_promprev_pz = 0.0;
                    $tot_importe = 0.0;
                    $tot_sf_cj = $tot_sf_pz = 0.0;
                    $tot_ped_cj = $tot_ped_pz = 0.0;
                    ?>
                    <?php if ($analisis_rows): ?>
                        <?php foreach ($analisis_rows as $row): ?>
                            <?php
                            $tot_si_cj += $row['stock_ini_cj'];
                            $tot_si_pz += $row['stock_ini_pz'];
                            $tot_ve_cj += $row['ventas_cj'];
                            $tot_ve_pz += $row['ventas_pz'];
                            $tot_prev_cj += $row['preventa_cj'];
                            $tot_prev_pz += $row['preventa_pz'];
                            $tot_ent_cj += $row['entrega_cj'];
                            $tot_ent_pz += $row['entrega_pz'];
                            $tot_rec_cj += $row['rec_cj'];
                            $tot_rec_pz += $row['rec_pz'];
                            $tot_dev_cj += $row['dev_cj'];
                            $tot_dev_pz += $row['dev_pz'];
                            $tot_prom_cj += $row['prom_cj'];
                            $tot_prom_pz += $row['prom_pz'];
                            $tot_promprev_cj += $row['promprev_cj'];
                            $tot_promprev_pz += $row['promprev_pz'];
                            $tot_importe += $row['total_importe'];
                            $tot_sf_cj += $row['stock_fin_cj'];
                            $tot_sf_pz += $row['stock_fin_pz'];
                            $tot_ped_cj += $row['total_ped_cj'];
                            $tot_ped_pz += $row['total_ped_pz'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['clave']) ?> - <?= htmlspecialchars($row['articulo']) ?></td>
                                <td class="text-end"><?= number_format($row['stock_ini_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['stock_ini_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['ventas_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['ventas_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['preventa_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['preventa_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['entrega_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['entrega_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['rec_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['rec_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['dev_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['dev_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['prom_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['prom_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['promprev_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['promprev_pz'], 2) ?></td>
                                <td class="text-end">$<?= number_format($row['total_importe'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['stock_fin_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['stock_fin_pz'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['total_ped_cj'], 2) ?></td>
                                <td class="text-end"><?= number_format($row['total_ped_pz'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="21" class="text-center">Sin información</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="text-end"><?= number_format($tot_si_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_si_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_ve_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_ve_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_prev_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_prev_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_ent_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_ent_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_rec_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_rec_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_dev_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_dev_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_prom_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_prom_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_promprev_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_promprev_pz, 2) ?></th>
                            <th class="text-end">$<?= number_format($tot_importe, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_sf_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_sf_pz, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_ped_cj, 2) ?></th>
                            <th class="text-end"><?= number_format($tot_ped_pz, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
