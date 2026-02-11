<?php
// public/sfa/promociones/promocion_reglas.php

// Lectura robusta de parámetros
$promo_id   = $_GET['promo_id'] ?? ($_GET['id'] ?? ($_GET['promoId'] ?? null));
$almacen_id = $_GET['almacen_id'] ?? ($_GET['almacen'] ?? null);

if ($promo_id === null || $promo_id === '') {
    include __DIR__ . '/../../bi/_menu_global.php';
    echo '<div class="alert alert-danger mt-3">Error: Promoción no especificada. <a href="promociones.php">Volver</a></div>';
    include __DIR__ . '/../../bi/_menu_global_end.php';
    exit;
}

// Menú global (vive en public/bi)
include __DIR__ . '/../../bi/_menu_global.php';

// API unificado
$API = '../../api/promociones/promociones_api.php';

// Back URL
$back = 'promociones.php';
if ($almacen_id !== null && $almacen_id !== '') {
    $back .= '?almacen_id=' . urlencode($almacen_id);
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reglas de Promoción</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .page-wrap {
            padding: 16px 18px;
        }

        .titlebar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .muted {
            opacity: .75;
            font-size: 12px;
        }

        .badge-soft {
            background: #f2f4f8;
            border: 1px solid #e4e7ec;
            color: #344054;
        }

        .btn-xxs {
            padding: .15rem .4rem;
            font-size: .75rem;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .trigger-monto,
        .trigger-qty {
            display: none;
        }
    </style>
</head>

<body>
    <div class="page-wrap">
        <div class="titlebar mb-3">
            <div>
                <h4 class="mb-0">Reglas de la Promoción</h4>
                <div class="muted">Define cuándo se activa la promo (tabla <code>promo_rule</code>).</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($back) ?>">Volver</a>
                <button class="btn btn-primary" id="btnNuevaRegla">+ Nueva Regla</button>
            </div>
        </div>

        <div class="alert alert-light border d-flex justify-content-between align-items-center">
            <div>
                <span class="badge badge-soft me-2">Promo ID</span>
                <strong><?= htmlspecialchars($promo_id) ?></strong>
                <?php if ($almacen_id !== null && $almacen_id !== ''): ?>
                    <span class="badge badge-soft ms-3 me-2">Almacén</span>
                    <strong><?= htmlspecialchars($almacen_id) ?></strong>
                <?php endif; ?>
            </div>
            <div class="muted">
                API: <code><?= htmlspecialchars($API) ?></code>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="tblReglas" class="table table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th style="width:240px;">Acciones</th>
                            <th style="width:70px;">Nivel</th>
                            <th style="width:140px;">Trigger</th>
                            <th style="width:130px;">Monto</th>
                            <th style="width:130px;">Cantidad</th>
                            <th style="width:90px;">Acumula</th>
                            <th style="width:120px;">Acumula por</th>
                            <th>Observaciones</th>

                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Regla -->
    <div class="modal fade" id="mdlRegla" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> Regla de activación · Promoción #<?= htmlspecialchars($promo_id) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="id_rule" value="">
                    <div class="small text-muted mb-2">
                        Define cuándo se activa esta promoción.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Nivel</label>
                            <input type="number" class="form-control" id="nivel" value="1" min="1">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Trigger</label>
                            <select class="form-select" id="trigger_tipo">
                                <option value="UNIDADES">UNIDADES</option>
                                <option value="MONTO">MONTO</option>
                                <option value="MIXTO">MIXTO</option>
                            </select>
                        </div>

                        <div class="col-md-3 trigger-monto">
                            <label class="form-label">Monto de compra</label>
                            <input type="number" step="0.01" class="form-control" id="threshold_monto" placeholder="0.00">
                        </div>

                        <div class="col-md-3 trigger-qty">
                            <label class="form-label">Cantidad de unidades</label>
                            <input type="number" step="0.01" class="form-control" id="threshold_qty" placeholder="0.00">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Acumula</label>
                            <select class="form-select" id="acumula">
                                <option value="N">N</option>
                                <option value="S">S</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Acumula por</label>
                            <select class="form-select" id="acumula_por">
                                <option value="TICKET">TICKET</option>
                                <option value="DIA">DIA</option>
                                <option value="PERIODO">PERIODO</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Min. ítems distintos</label>
                            <input type="number" class="form-control" id="min_items_distintos" placeholder="0">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Activo</label>
                            <select class="form-select" id="activo">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <input type="text" class="form-control" id="observaciones" placeholder="Notas operativas de la regla">
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Tip operativo:</strong>
                        <ul class="mb-0">
                            <li><b>MONTO</b> → usa solo Threshold Monto</li>
                            <li><b>UNIDADES</b> → usa solo Threshold Qty</li>
                            <li><b>MIXTO</b> → usa ambos</li>
                        </ul>

                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" id="btnGuardarRegla">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        const API = <?= json_encode($API) ?>;
        const promoId = <?= json_encode((string)$promo_id) ?>;
        const almacenId = <?= json_encode($almacen_id) ?>;

        function apiGet(params) {
            const qs = new URLSearchParams(params).toString();
            return fetch(`${API}?${qs}`, {
                credentials: 'same-origin'
            }).then(r => r.json());
        }

        function apiPost(action, payload) {
            const body = new URLSearchParams(payload);
            return fetch(`${API}?action=${encodeURIComponent(action)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body,
                credentials: 'same-origin'
            }).then(r => r.json());
        }

        let dt;

        function loadTabla() {
            if (dt) {
                dt.ajax.reload(null, false);
                return;
            }

            dt = $('#tblReglas').DataTable({
                pageLength: 25,

                ajax: function(data, cb) {
                    apiGet({
                        action: 'get',
                        id: promoId
                    }).then(res => {
                        const rows = (res && res.ok && Array.isArray(res.rules)) ?
                            res.rules : [];
                        cb({
                            data: rows
                        });
                    }).catch(() => {
                        cb({
                            data: []
                        });
                    });
                },

                columns: [
                    // 🔹 ACCIONES (PRIMERA COLUMNA)
                    {
                        data: null,
                        orderable: false,
                        render: function(r) {

                            const q = new URLSearchParams();
                            q.set('promo_id', promoId);
                            if (almacenId) q.set('almacen_id', almacenId);
                            q.set('id_rule', r.id_rule);

                            return `
        <div class="d-flex flex-wrap gap-1">

            <!-- Regla -->
            <button class="btn btn-outline-primary btn-xxs"
                title="Editar condición de activación"
                onclick='editar(${JSON.stringify(r)})'>
                <i class="fa fa-gear me-1"></i> Regla
            </button>

            <!-- Beneficios -->
            <a class="btn btn-outline-success btn-xxs"
                title="Administrar beneficios de esta regla"
                href="promocion_beneficios.php?${q.toString()}">
                <i class="fa fa-gift me-1"></i> Beneficios
            </a>

            <!-- Scope -->
            <a class="btn btn-outline-secondary btn-xxs"
                title="Definir alcance de la promoción"
                href="promocion_scope.php?promo_id=${encodeURIComponent(promoId)}${almacenId ? `&almacen_id=${encodeURIComponent(almacenId)}` : ''}">
                <i class="fa fa-layer-group me-1"></i> Scope
            </a>

            <!-- Eliminar -->
            <button class="btn btn-outline-danger btn-xxs"
                title="Desactivar regla"
                onclick="eliminar(${r.id_rule})">
                <i class="fa fa-trash"></i>
            </button>

        </div>
        `;
                        }
                    },


                    // 🔹 RESTO DE COLUMNAS
                    {
                        data: 'nivel',
                        defaultContent: ''
                    },
                    {
                        data: 'trigger_tipo',
                        defaultContent: ''
                    },
                    {
                        data: 'threshold_monto',
                        defaultContent: ''
                    },
                    {
                        data: 'threshold_qty',
                        defaultContent: ''
                    },
                    {
                        data: 'acumula',
                        defaultContent: ''
                    },
                    {
                        data: 'acumula_por',
                        defaultContent: ''
                    },
                    {
                        data: 'observaciones',
                        defaultContent: ''
                    }
                ],

                // 🔹 Ordenar por NIVEL (columna 1, porque 0 ahora es Acciones)
                order: [
                    [1, 'asc']
                ]
            });
        }


        function limpiarModal() {
            $('#id_rule').val('');
            $('#nivel').val(1);
            $('#trigger_tipo').val('UNIDADES');
            $('#threshold_monto').val('');
            $('#threshold_qty').val('');
            $('#acumula').val('N');
            $('#acumula_por').val('TICKET');
            $('#min_items_distintos').val('');
            $('#observaciones').val('');
            $('#activo').val('1');
        }

        function aplicarTriggerUI() {
            const trigger = document.getElementById('trigger_tipo').value;

            const montoBox = document.querySelector('.trigger-monto');
            const qtyBox = document.querySelector('.trigger-qty');

            const montoInp = document.getElementById('threshold_monto');
            const qtyInp = document.getElementById('threshold_qty');

            montoBox.style.display = 'none';
            qtyBox.style.display = 'none';

            montoInp.disabled = true;
            qtyInp.disabled = true;

            if (trigger === 'MONTO') {
                montoBox.style.display = 'block';
                montoInp.disabled = false;
            } else if (trigger === 'UNIDADES') {
                qtyBox.style.display = 'block';
                qtyInp.disabled = false;
            } else if (trigger === 'MIXTO') {
                montoBox.style.display = 'block';
                qtyBox.style.display = 'block';
                montoInp.disabled = false;
                qtyInp.disabled = false;
            }
        }



        window.editar = function(r) {
            limpiarModal();
            $('#id_rule').val(r.id_rule || '');
            $('#nivel').val(r.nivel ?? 1);
            $('#trigger_tipo').val(r.trigger_tipo ?? 'UNIDADES');
            $('#threshold_monto').val(r.threshold_monto ?? '');
            $('#threshold_qty').val(r.threshold_qty ?? '');
            $('#acumula').val(r.acumula ?? 'N');
            $('#acumula_por').val(r.acumula_por ?? 'TICKET');
            $('#min_items_distintos').val(r.min_items_distintos ?? '');
            $('#observaciones').val(r.observaciones ?? '');
            $('#activo').val((r.activo ?? 1).toString());
            aplicarTriggerUI();
            new bootstrap.Modal(document.getElementById('mdlRegla')).show();
        }

        window.eliminar = function(id_rule) {
            if (!confirm('Esta acción desactivará la regla y sus beneficios asociados.\n\n¿Deseas continuar?')) return;

            apiPost('rule_del', {
                id_rule: id_rule
            }).then(res => {
                if (res && res.ok) {
                    loadTabla();
                } else {
                    alert((res && (res.error || res.detalle)) ? (res.error || res.detalle) : 'No se pudo eliminar.');
                }
            });
        }

        $('#btnNuevaRegla').on('click', function() {
            limpiarModal();
            aplicarTriggerUI();
            new bootstrap.Modal(document.getElementById('mdlRegla')).show();
        });

        $('#btnGuardarRegla').on('click', function() {
            const payload = {
                id_rule: $('#id_rule').val(),
                promo_id: promoId,
                nivel: $('#nivel').val(),
                trigger_tipo: $('#trigger_tipo').val(),
                threshold_monto: $('#threshold_monto').val(),
                threshold_qty: $('#threshold_qty').val(),
                acumula: $('#acumula').val(),
                acumula_por: $('#acumula_por').val(),
                min_items_distintos: $('#min_items_distintos').val(),
                observaciones: $('#observaciones').val(),
                activo: $('#activo').val()
            };

            // Normalización rápida (evita nulls raros)
            if (payload.threshold_monto === '') payload.threshold_monto = '0';
            if (payload.threshold_qty === '') payload.threshold_qty = '0';
            if (payload.min_items_distintos === '') payload.min_items_distintos = '0';

            apiPost('rule_save', payload).then(res => {
                if (res && res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('mdlRegla')).hide();
                    loadTabla();
                } else {
                    alert((res && (res.error || res.detalle)) ? (res.error || res.detalle) : 'No se pudo guardar.');
                }
            });
        });

        loadTabla();
        document.getElementById('trigger_tipo')
            .addEventListener('change', aplicarTriggerUI);
    </script>

</body>

</html>

<?php include __DIR__ . '/../../bi/_menu_global_end.php'; ?>