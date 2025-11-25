<?php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

// Datos empresa (opcional; si no existe c_compania, se usan defaults)
$cia = [
    'nombre'   => 'Adventech Logística',
    'rfc'      => '',
    'direccion'=> '',
    'logo_path'=> '/public/img/logo_adventech.png'
];

try {
    $tmp = db_one("SELECT nombre, rfc, direccion, logo_path 
                   FROM c_compania 
                   WHERE Activo = 1
                   LIMIT 1");
    if ($tmp) {
        $cia['nombre']    = $tmp['nombre']    ?? $cia['nombre'];
        $cia['rfc']       = $tmp['rfc']       ?? $cia['rfc'];
        $cia['direccion'] = $tmp['direccion'] ?? $cia['direccion'];
        $cia['logo_path'] = $tmp['logo_path'] ?? $cia['logo_path'];
    }
} catch (Exception $e) {
    // si falla, usamos los defaults
}

// Plantillas de filtros disponibles para este módulo (existencias)
$plantillas = [];
try {
    $plantillas = db_all("
        SELECT id, nombre
        FROM ap_plantillas_filtros
        WHERE modulo_clave = 'existencias_ubicacion'
        ORDER BY es_default DESC, nombre
    ");
} catch (Exception $e) {
    $plantillas = [];
}

// Tipos de reporte (por ahora solo uno, pero ya queda preparado)
$tiposReporte = [
    'existencias' => 'Existencias por Ubicación',
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes de Inventarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <style>
        body {
            background-color: #f5f7fb;
        }
        .page-header {
            padding: 10px 0 5px 0;
        }
        .page-header h2 {
            font-size: 20px;
            margin: 0;
            padding: 0;
        }
        .page-header small {
            font-size: 12px;
            color: #666;
        }
        .kpi-row {
            margin-top: 15px;
        }
        .card-kpi {
            background: #ffffff;
            border-radius: 8px;
            padding: 10px 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            font-size: 11px;
        }
        .card-kpi b {
            font-size: 16px;
        }
        .card-kpi span {
            color: #555;
        }
        .card-kpi .kpi-caption {
            font-size: 10px;
            color: #999;
        }
        .filters-panel {
            background: #0F5AAD;
            color: #ffffff;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        .filters-panel h6 {
            margin: 0 0 5px 0;
            font-size: 13px;
        }
        .filters-panel .form-label {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .filters-panel .form-select,
        .filters-panel .form-control {
            font-size: 11px;
            padding: 2px 5px;
        }
        .filters-panel .btn-sm {
            font-size: 11px;
            padding: 3px 8px;
        }

        .table-responsive {
            margin-top: 10px;
            font-size: 10px;
        }

        .dt-search,
        .dataTables_length {
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-2 mb-3">

    <!-- ENCABEZADO -->
    <div class="row page-header align-items-center">
        <div class="col-md-8">
            <h2>
                📊 Reportes de Inventarios
            </h2>
            <small>
                Vista base:
                <span id="vista_sql_base">v_existencias_por_ubicacion_ao</span>
                &nbsp;|&nbsp;
                Módulo: <span id="modulo_clave">existencias_ubicacion</span>
            </small>
        </div>
        <div class="col-md-4 text-end">
            <button id="btnCorreo" class="btn btn-outline-secondary btn-sm">
                Enviar por correo
            </button>
        </div>
    </div>

    <!-- FILTROS (solo visuales) -->
    <div class="filters-panel">
        <div class="row g-2 align-items-end">

            <!-- Plantilla de filtros -->
            <div class="col-md-4">
                <label for="plantilla_id" class="form-label">Plantilla de filtros</label>
                <select id="plantilla_id" class="form-select form-select-sm">
                    <option value="">(Sin plantilla, filtros libres)</option>
                    <?php foreach ($plantillas as $p): ?>
                        <option value="<?= htmlspecialchars($p['id']) ?>">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tipo de reporte -->
            <div class="col-md-3">
                <label for="tipo_reporte" class="form-label">Tipo de reporte</label>
                <select id="tipo_reporte" class="form-select form-select-sm">
                    <?php foreach ($tiposReporte as $clave => $nombre): ?>
                        <option value="<?= htmlspecialchars($clave) ?>">
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botones -->
            <div class="col-md-3 text-end">
                <button id="btnBuscar" class="btn btn-warning btn-sm">
                    Consultar
                </button>
                <button id="btnReset" class="btn btn-light btn-sm">
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Filtros de contexto (solo estilo) -->
        <div class="row g-2 mt-2">
            <div class="col-md-3">
                <label for="f_empresa" class="form-label">Empresa</label>
                <select id="f_empresa" class="form-select form-select-sm">
                    <option value="">FOAM CREATIONS MEXICO SA DE CV</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="f_almacen" class="form-label">Almacén</label>
                <select id="f_almacen" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="f_bl" class="form-label">BL (Bin Location)</label>
                <select id="f_bl" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="f_fecha_corte" class="form-label">Fecha corte</label>
                <input type="date" id="f_fecha_corte" class="form-control form-control-sm">
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row kpi-row">
        <div class="col-md-3">
            <div class="card-kpi">
                <span>Registros consultados</span><br>
                <b id="kpi_total_reg">0</b>
                <div class="kpi-caption">Máximo 100 filas por consulta</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-kpi">
                <span>License Plates distintos</span><br>
                <b id="kpi_total_lps">0</b>
                <div class="kpi-caption">Detectados en el resultado</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-kpi">
                <span>Ubicaciones (BL) distintas</span><br>
                <b id="kpi_total_ubics">0</b>
                <div class="kpi-caption">BL de la vista</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-kpi">
                <span>Productos distintos</span><br>
                <b id="kpi_total_prod">0</b>
                <div class="kpi-caption">Por clave de artículo</div>
            </div>
        </div>
    </div>

    <!-- GRILLA -->
    <div class="table-responsive">
        <table id="tblDatos" class="display nowrap" width="100%">
            <thead id="thead_dynamic"></thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- PIE -->
    <div class="text-end mt-3" style="font-size:9px;">
        Reporte / plantilla: <span id="lbl_plantilla">Existencias por ubicación</span>
    </div>

</div>

<!-- Modal de correo (sólo visual) -->
<div class="modal fade" id="modalCorreo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enviar reporte por correo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Destinatario(s)</label>
            <input type="text" id="mail_to" class="form-control form-control-sm" placeholder="correo@dominio.com">
        </div>
        <div class="mb-2">
            <label class="form-label">Asunto</label>
            <input type="text" id="mail_subject" class="form-control form-control-sm" value="Reporte de inventarios">
        </div>
        <div class="mb-2">
            <label class="form-label">Mensaje</label>
            <textarea id="mail_body" rows="3" class="form-control form-control-sm">Adjunto reporte de inventarios.</textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button id="btnEnviarCorreo" class="btn btn-primary btn-sm">Enviar</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
const configs = {
    existencias: {
        thead: `
            <tr>
                <th>BL</th>
                <th>Pasillo</th>
                <th>Rack</th>
                <th>Nivel</th>
                <th>Almacén</th>
                <th>Artículo</th>
                <th>Descripción</th>
                <th>Tipo control</th>
                <th>Lote</th>
                <th>Caducidad</th>
                <th>Existencia</th>
                <th>Es QA</th>
                <th>Disponible</th>
                <th>LP</th>
            </tr>
        `
    }
};

let dt = null;

// Construye el encabezado de la tabla según el tipo de reporte
function buildThead() {
    const tipo = $('#tipo_reporte').val() || 'existencias';
    const cfg  = configs[tipo];

    if (cfg && cfg.thead) {
        $('#thead_dynamic').html(cfg.thead);
    } else {
        $('#thead_dynamic').empty();
    }

    // Inicializa DataTable (solo estilo, sin datos)
    if (!dt) {
        dt = $('#tblDatos').DataTable({
            paging: true,
            pageLength: 25,
            lengthChange: false,
            searching: false,
            info: true,
            ordering: false,
            scrollX: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    } else {
        dt.clear().draw();
    }

    // KPIs en cero (sin datos reales)
    $('#kpi_total_reg').text('0');
    $('#kpi_total_lps').text('0');
    $('#kpi_total_ubics').text('0');
    $('#kpi_total_prod').text('0');
}

// Limpia los filtros visualmente (no hay backend)
function limpiarFiltros() {
    $('#f_empresa').val('');
    $('#f_almacen').val('');
    $('#f_bl').val('');
    $('#f_fecha_corte').val('');
}

// Simula la consulta: solo limpia la grilla y deja el estilo
function ejecutarConsultaDummy() {
    if (!dt) {
        buildThead();
    }
    dt.clear().draw();
    $('#kpi_total_reg').text('0');
    $('#kpi_total_lps').text('0');
    $('#kpi_total_ubics').text('0');
    $('#kpi_total_prod').text('0');
}

// Eventos
$(document).ready(function () {
    buildThead();

    $('#tipo_reporte').on('change', function () {
        buildThead();
    });

    $('#btnBuscar').on('click', function (e) {
        e.preventDefault();
        ejecutarConsultaDummy();
    });

    $('#btnReset').on('click', function (e) {
        e.preventDefault();
        limpiarFiltros();
        ejecutarConsultaDummy();
    });

    // Modal de correo (solo visual)
    $('#btnCorreo').on('click', function (e) {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('modalCorreo'));
        modal.show();
    });

    $('#btnEnviarCorreo').on('click', function (e) {
        e.preventDefault();
        alert('Función de envío de correo pendiente de implementación.');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCorreo'));
        if (modal) modal.hide();
    });
});
</script>

</body>
</html>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
