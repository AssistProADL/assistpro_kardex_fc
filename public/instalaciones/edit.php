<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");

$id = $_GET['id'] ?? 0;
?>

<style>
    /* ===== AssistPro v2.1 ===== */

    .ap-title {
        font-size: 22px;
        font-weight: 600;
        color: #0B3C8C;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 25px;
    }

    .ap-card {
        background: #f8fafc;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: 0.2s ease-in-out;
    }

    .ap-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .ap-section-title {
        font-size: 13px;
        font-weight: 600;
        color: #0B3C8C;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
    }

    .ap-actions {
        margin-top: 25px;
        display: flex;
        justify-content: space-between;
    }

    .btn-ap-primary {
        background-color: #0B3C8C;
        border: none;
        padding: 8px 18px;
        color: white;
    }

    .btn-ap-primary:hover {
        background-color: #082c63;
    }

    /* Modal custom */

    .ap-modal-success .modal-content {
        border-radius: 10px;
        border: none;
    }

    .ap-modal-success .modal-header {
        background-color: #0B3C8C;
        color: white;
        border-bottom: none;
    }

    .ap-modal-success .modal-footer {
        border-top: none;
    }
</style>


<div class="container-fluid">

    <div class="ap-title">
        <i class="fa fa-pen-to-square"></i>
        <span>Editar Instalación</span>
    </div>

    <div class="ap-card">

        <form id="formEditar">

            <input type="hidden" id="id_instalacion" value="<?= $id ?>">

            <div class="ap-section-title">Información General</div>

            <div class="row mb-4">

                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date"
                        name="fecha_instalacion"
                        id="fecha_instalacion"
                        class="form-control"
                        required>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Lugar</label>
                    <input type="text"
                        name="lugar_instalacion"
                        id="lugar_instalacion"
                        class="form-control"
                        placeholder="Ej. Almacén Norte">
                </div>

            </div>

            <div class="ap-section-title">Condiciones</div>

            <div class="mb-3">
                <textarea name="condiciones"
                    id="condiciones"
                    class="form-control"
                    rows="4"
                    placeholder="Describe condiciones iniciales..."></textarea>
            </div>

            <div class="ap-actions">

                <a href="index.php" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button id="btnActualizar" class="btn btn-ap-primary">
                    <i class="fa fa-save me-1"></i>
                    Actualizar Instalación
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ===== Modal Éxito ===== -->
<div class="modal fade ap-modal-success" id="modalSuccess" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-circle-check me-2"></i>
                    Instalación Actualizada
                </h5>
            </div>

            <div class="modal-body text-center">
                La instalación se actualizó correctamente.
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-ap-primary" id="btnCerrarModal">
                    Ir al listado
                </button>
            </div>

        </div>
    </div>
</div>


<script>
    // ==============================
    // CARGAR DATOS
    // ==============================

    fetch(`../api/api_instalaciones.php?action=get&id=<?= $id ?>`)
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                console.error(data.error);
                return;
            }

            const inst = data.data;

            document.getElementById('fecha_instalacion').value =
                inst.fecha_instalacion.substring(0, 10);

            document.getElementById('lugar_instalacion').value =
                inst.lugar_instalacion ?? '';

            document.getElementById('condiciones').value =
                inst.condiciones_iniciales ?? '';
        });


    // ==============================
    // ACTUALIZAR
    // ==============================

    document.getElementById('formEditar').addEventListener('submit', function(e) {

        e.preventDefault();

        const btn = document.getElementById('btnActualizar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        const payload = {
            id: document.getElementById('id_instalacion').value,
            fecha_instalacion: document.getElementById('fecha_instalacion').value,
            lugar_instalacion: document.getElementById('lugar_instalacion').value,
            condiciones: document.getElementById('condiciones').value
        };

        fetch('../api/api_instalaciones.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {

                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save me-1"></i>Actualizar Instalación';

                if (data.success) {

                    const modal = new bootstrap.Modal(document.getElementById('modalSuccess'));
                    modal.show();

                } else {
                    alert(data.error);
                }

            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save me-1"></i>Actualizar Instalación';
            });

    });


    // ==============================
    // CERRAR MODAL
    // ==============================

    document.getElementById('btnCerrarModal').addEventListener('click', function() {
        window.location.href = "index.php";
    });
</script>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>