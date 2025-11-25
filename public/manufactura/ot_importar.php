<?php
@session_start();

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

/* ============================================================
   UTILIDADES
============================================================ */

function jexit($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Convierte un número con diferentes formatos a float:
 * 12
 * 12.0
 * 12,0
 * 1,234.50
 * 1.234,50
 */
function parse_decimal($raw) {
    $s = trim((string)$raw);
    if ($s === '') return null;

    $s = str_replace(' ', '', $s);
    $hasDot   = strpos($s, '.') !== false;
    $hasComma = strpos($s, ',') !== false;

    if ($hasDot && $hasComma) {
        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        $decPos    = max($lastDot, $lastComma);
        $intPart   = substr($s, 0, $decPos);
        $decPart   = substr($s, $decPos + 1);
        $intPart   = str_replace(['.', ','], '', $intPart);
        $norm      = $intPart . '.' . $decPart;
    }
    elseif ($hasComma) {
        $norm = str_replace('.', '', $s);
        $norm = str_replace(',', '.', $norm);
    }
    else {
        $norm = str_replace(',', '', $s);
    }

    return is_numeric($norm) ? (float)$norm : null;
}


$op = $_POST['op'] ?? $_GET['op'] ?? null;

if ($op) {
    try {

        /* ============================================================
           1) GET_ZONAS
        ============================================================ */
        if ($op === 'get_zonas') {
            $almacenClave = trim($_POST['almacen'] ?? '');
            if ($almacenClave === '') {
                jexit(['ok'=>false,'msg'=>'Debe seleccionar un almacén']);
            }

            $row = db_row("
                SELECT id
                FROM c_almacenp
                WHERE clave = ?
                LIMIT 1
            ", [$almacenClave]);

            if (!$row) {
                jexit(['ok'=>false,'msg'=>"Almacén no existe en c_almacenp: $almacenClave"]);
            }

            $idAlm = (int)$row['id'];

            $zonas = db_all("
                SELECT DISTINCT
                    CAST(a.cve_almac AS UNSIGNED) AS cve_almac,
                    a.des_almac
                FROM c_almacen a
                JOIN c_ubicacion u ON u.cve_almac = CAST(a.cve_almac AS UNSIGNED)
                WHERE a.cve_almacenp = ?
                  AND IFNULL(a.Activo,1)=1
                  AND IFNULL(u.Activo,1)=1
                ORDER BY a.des_almac
            ", [$idAlm]);

            jexit(['ok'=>true,'data'=>$zonas]);
        }

        /* ============================================================
           2) GET_BLS
        ============================================================ */
        if ($op === 'get_bls') {
            $zona = intval($_POST['zona'] ?? 0);
            if ($zona <= 0) {
                jexit(['ok'=>false,'msg'=>'Zona inválida']);
            }

            $bls = db_all("
                SELECT idy_ubica, CodigoCSD AS bl
                FROM c_ubicacion
                WHERE cve_almac = ?
                  AND IFNULL(Activo,1)=1
                  AND IFNULL(CodigoCSD,'') <> ''
                ORDER BY CodigoCSD
            ", [$zona]);

            jexit(['ok'=>true,'data'=>$bls]);
        }

        /* ============================================================
           3) PREVIEW CSV
        ============================================================ */
        if ($op === 'preview') {

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                jexit(['ok'=>false,'msg'=>'Archivo CSV no recibido']);
            }

            $tmp = $_FILES['file']['tmp_name'];
            $fh  = fopen($tmp, 'r');

            if (!$fh) jexit(['ok'=>false,'msg'=>'No se pudo abrir archivo CSV']);

            $rows = [];
            $line = 0;
            $okCount = 0;
            $badCount= 0;

            // LAYOUT REAL (7 columnas):
            // 0: OT_Cliente
            // 1: Artículo compuesto
            // 2: Lote
            // 3: Caducidad
            // 4: Cantidad a producir
            // 5: Fecha compromiso
            // 6: LP
            //
            // OJO: aquí se asume separador coma. Si tu CSV usa ';', cambia ',' por ';'
            while (($r = fgetcsv($fh, 5000, ',')) !== false) {
                $line++;
                if ($line == 1) continue; // Cabecera

                $otCli   = trim($r[0] ?? '');
                $art     = trim($r[1] ?? '');
                $lote    = trim($r[2] ?? '');
                $cad     = trim($r[3] ?? '');
                $cantRaw = trim($r[4] ?? '');
                $fecComp = trim($r[5] ?? '');
                $lp      = trim($r[6] ?? '');

                $status = 'OK';
                $msg    = '';

                if ($art === '') {
                    $status = 'ERROR';
                    $msg    = 'Artículo compuesto vacío';
                }

                $cant = parse_decimal($cantRaw);
                if ($status==='OK' && ($cant===null || $cant<=0)) {
                    $status = 'ERROR';
                    $msg    = 'Cantidad a producir inválida';
                }

                if ($status==='OK') $okCount++; else $badCount++;

                $rows[] = [
                    'linea'    => $line-1,
                    'ot_cli'   => $otCli,
                    'art'      => $art,
                    'lote'     => $lote,
                    'cad'      => $cad,
                    'cant'     => $cantRaw,
                    'fec_comp' => $fecComp,
                    'lp'       => $lp,
                    'status'   => $status,
                    'msg'      => $msg
                ];
            }

            fclose($fh);

            jexit([
                'ok'   => true,
                'msg'  => "Preview: $okCount OK, $badCount con error",
                'rows' => $rows
            ]);
        }

        /* ============================================================
           4) IMPORTAR
        ============================================================ */
        if ($op === 'import') {

            $almacen = trim($_POST['almacen'] ?? '');
            $zona    = intval($_POST['zona'] ?? 0);
            $bl      = intval($_POST['bl'] ?? 0);
            $json    = $_POST['rows'] ?? '[]';

            if ($almacen==='' || $zona<=0 || $bl<=0) {
                jexit(['ok'=>false,'msg'=>'Debe seleccionar Almacén, Zona y BL']);
            }

            $rows = json_decode($json, true);
            if (!is_array($rows) || empty($rows)) {
                jexit(['ok'=>false,'msg'=>'No hay datos para importar']);
            }

            $total   = 0;
            $errores = [];
            $detalle = [];

            try {
                $pdo->beginTransaction();

                foreach ($rows as $i=>$r) {
                    if (($r['status'] ?? '') !== 'OK') continue;

                    $otCli   = trim($r['ot_cli'] ?? '');
                    $art     = trim($r['art'] ?? '');
                    $cantRaw = trim($r['cant'] ?? '');
                    $fecComp = trim($r['fec_comp'] ?? '');
                    $lp      = trim($r['lp'] ?? '');

                    $cantidad = parse_decimal($cantRaw);
                    if ($cantidad===null || $cantidad<=0) {
                        $errores[]="Fila {$r['linea']}: cantidad inválida ($cantRaw)";
                        continue;
                    }

                    $artRow = db_row("
                        SELECT cve_articulo, des_articulo
                        FROM c_articulo
                        WHERE cve_articulo = ?
                    ", [$art]);

                    if (!$artRow) {
                        $errores[]="Fila {$r['linea']}: artículo no existe ($art)";
                        continue;
                    }

                    // Folio = OT cliente, si viene; si no, generamos uno.
                    $folioPro = ($otCli!='' ? $otCli : 'OP'.date('YmdHis').$i);

                    // Insert según estructura real de t_ordenprod
                    dbq("
                        INSERT INTO t_ordenprod (
                            Folio_Pro,
                            Cve_Articulo,
                            Cant_Prod,
                            cve_almac,
                            id_zona_almac,
                            idy_ubica,
                            Fecha,
                            FechaReg,
                            Status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), 'P')
                    ", [
                        $folioPro,
                        $art,
                        $cantidad,
                        $almacen,
                        $zona,
                        $bl
                    ]);

                    $total++;
                    $detalle[] = [
                        'folio'    => $folioPro,
                        'art'      => $art,
                        'cantidad' => $cantidad,
                        'linea'    => $r['linea'],
                        'lp'       => $lp
                    ];
                }

                $pdo->commit();

                jexit([
                    'ok'      => true,
                    'msg'     => "$total OT(s) importadas correctamente",
                    'data'    => $detalle,
                    'errores' => $errores
                ]);

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                jexit(['ok'=>false,'msg'=>$e->getMessage()]);
            }
        }

        jexit(['ok'=>false,'msg'=>'Operación no válida']);

    } catch (Throwable $e) {
        jexit(['ok'=>false,'msg'=>$e->getMessage()]);
    }
}


/* ============================================================
   VISTA HTML
============================================================ */

$TITLE = 'Importador Masivo de Órdenes de Producción';
include __DIR__.'/../bi/_menu_global.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Importador OT</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet">
<style>
body{font-size:10px;}
.table-sm td,.table-sm th{padding:.3rem;}
</style>
</head>

<body>
<div class="container-fluid mt-2">

<div class="card">
<div class="card-header"><b>Importador de Órdenes de Producción</b></div>
<div class="card-body">

<!-- filtros -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <label>Almacén</label>
        <select id="cmbAlmacenP" class="form-select form-select-sm">
            <option value="">Seleccione</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Zona</label>
        <select id="cmbZona" class="form-select form-select-sm" disabled>
            <option value="">Seleccione</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>BL Manufactura</label>
        <select id="cmbBL" class="form-select form-select-sm" disabled>
            <option value="">Seleccione</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Archivo CSV</label>
        <input id="fileCsv" type="file" class="form-control form-control-sm" accept=".csv">
    </div>
</div>

<div class="row mb-3">
    <div class="col">
        <button id="btnPreview" class="btn btn-outline-primary btn-sm">Previsualizar</button>
        <button id="btnImport" class="btn btn-primary btn-sm" disabled>Importar</button>
    </div>
</div>

<!-- tabla preview -->
<div class="table-responsive">
<table id="tblPreview" class="table table-sm table-striped table-bordered">
<thead>
<tr>
    <th>#</th>
    <th>OT Cliente</th>
    <th>Artículo</th>
    <th>Lote</th>
    <th>Caducidad</th>
    <th>Cantidad</th>
    <th>Fecha Comp</th>
    <th>LP</th>
    <th>Status</th>
    <th>Mensaje</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>

</div>
</div>

<!-- Modal Resultado -->
<div class="modal fade" id="resModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title">Resultado Importación</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div id="resResumen"></div>
<table class="table table-sm table-bordered mt-2">
<thead><tr>
<th>Folio</th><th>Artículo</th><th>Cantidad</th><th>Línea CSV</th><th>LP</th>
</tr></thead>
<tbody id="resDetalleBody"></tbody>
</table>
</div>
</div>
</div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

let tbl = null;
let lastPreview = [];

/* ============================================================
   CARGAR ALMACENES DESDE API (action=init)
============================================================ */

function cargarAlmacenes() {
    $.ajax({
        url: '../api/filtros_assistpro.php',
        method: 'GET',
        dataType: 'json',
        data: { action: 'init' },
        success: function(r){
            const $c = $('#cmbAlmacenP');
            $c.empty().append('<option value="">Seleccione</option>');

            if (!r || !r.ok) {
                console.error('Error al cargar almacenes desde API', r);
                return;
            }

            (r.almacenes || []).forEach(function(a){
                const val  = a.cve_almac; // clave tipo WHCR
                const text = '(' + (a.clave_almacen || a.cve_almac) + ') ' +
                             (a.des_almac || a.cve_almac);
                $c.append(
                    $('<option>').val(val).text(text)
                );
            });
        },
        error: function(xhr, status, err){
            console.error('Error AJAX almacenes', status, err);
        }
    });
}
cargarAlmacenes();

/* ============================================================
   CARGAR ZONAS
============================================================ */

$('#cmbAlmacenP').on('change', function(){
    const alm = $(this).val();
    const $z = $('#cmbZona');
    const $b = $('#cmbBL');

    $z.prop('disabled', true).empty().append('<option value="">Seleccione</option>');
    $b.prop('disabled', true).empty().append('<option value="">Seleccione</option>');

    if (!alm) return;

    $.post('ot_importar.php', {op:'get_zonas',almacen:alm}, function(r){
        if (r.ok) {
            r.data.forEach(z=>{
                $z.append(`<option value="${z.cve_almac}">${z.des_almac}</option>`);
            });
            if (r.data.length) $z.prop('disabled',false);
        } else {
            console.error('Error get_zonas', r.msg);
        }
    },'json');
});

/* ============================================================
   CARGAR BLS
============================================================ */

$('#cmbZona').on('change', function(){
    const zona = $(this).val();
    const $b = $('#cmbBL');

    $b.prop('disabled',true).empty().append('<option value="">Seleccione</option>');
    if (!zona) return;

    $.post('ot_importar.php', {op:'get_bls',zona:zona}, function(r){
        if (r.ok) {
            r.data.forEach(b=>{
                $b.append(`<option value="${b.idy_ubica}">${b.bl}</option>`);
            });
            if (r.data.length) $b.prop('disabled',false);
        } else {
            console.error('Error get_bls', r.msg);
        }
    },'json');
});

/* ============================================================
   PREVIEW
============================================================ */

$('#btnPreview').on('click', function(){

    const alm  = $('#cmbAlmacenP').val();
    const zona = $('#cmbZona').val();
    const bl   = $('#cmbBL').val();
    const file = $('#fileCsv')[0].files[0];

    if (!alm || !zona || !bl) {
        alert('Seleccione almacén, zona y BL');
        return;
    }
    if (!file) {
        alert('Seleccione el archivo CSV');
        return;
    }

    let fd = new FormData();
    fd.append('op','preview');
    fd.append('file',file);

    $.ajax({
        url:'ot_importar.php',
        type:'POST',
        data:fd,
        processData:false,
        contentType:false,
        dataType:'json',
        success:function(r){
            if (!tbl) {
                tbl = $('#tblPreview').DataTable({
                    pageLength:25,
                    info:true,
                    searching:false,
                    autoWidth:false,
                    columns: [
                        { data: 'linea'    },
                        { data: 'ot_cli'   },
                        { data: 'art'      },
                        { data: 'lote'     },
                        { data: 'cad'      },
                        { data: 'cant'     },
                        { data: 'fec_comp' },
                        { data: 'lp'       },
                        { data: 'status'   },
                        { data: 'msg'      }
                    ],
                    createdRow: function(row, data){
                        if (data.status === 'ERROR') {
                            $(row).addClass('table-danger');
                        }
                    }
                });
            }
            if (r.ok) {
                lastPreview = r.rows || [];
                tbl.clear().rows.add(lastPreview).draw();
                $('#btnImport').prop('disabled', lastPreview.length === 0);
            } else {
                alert(r.msg || 'Error en preview');
            }
        },
        error:function(xhr, status, err){
            console.error('Error AJAX preview', status, err);
        }
    });
});

/* ============================================================
   IMPORTAR
============================================================ */

$('#btnImport').on('click', function(){

    const alm  = $('#cmbAlmacenP').val();
    const zona = $('#cmbZona').val();
    const bl   = $('#cmbBL').val();

    if (!alm || !zona || !bl) {
        alert('Seleccione almacén, zona y BL');
        return;
    }

    $.post('ot_importar.php',{
        op:'import',
        almacen:alm,
        zona:zona,
        bl:bl,
        rows:JSON.stringify(lastPreview)
    },function(r){
        if (r.ok) {
            $('#resResumen').text(r.msg);
            const $tb = $('#resDetalleBody');
            $tb.empty();
            (r.data||[]).forEach(x=>{
                $tb.append(`
                    <tr>
                      <td>${x.folio}</td>
                      <td>${x.art}</td>
                      <td>${x.cantidad}</td>
                      <td>${x.linea}</td>
                      <td>${x.lp}</td>
                    </tr>
                `);
            });
            new bootstrap.Modal(document.getElementById('resModal')).show();
        } else {
            alert(r.msg || 'Error al importar');
        }
    },'json');
});

</script>
</body>
</html>

<?php include __DIR__.'/../bi/_menu_global_end.php'; ?>
