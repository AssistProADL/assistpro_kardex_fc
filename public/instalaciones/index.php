<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");
?>

<style>
    /* ===== AssistPro v2.0 ===== */

    .ap-title {
        font-size: 20px;
        font-weight: 600;
        color: #0B3C8C;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .ap-card {
        background: #f7f9fc;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .ap-grid-wrapper {
        max-height: 260px;
        /* 5 filas aprox */
        overflow-y: auto;
        overflow-x: auto;
        padding: 10px;
    }

    .ap-grid table {
        width: 100%;
        font-size: 14px;
    }

    .ap-grid thead {
        background-color: #0B3C8C;
        color: white;
    }

    .badge-ap {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
    }

    .badge-borrador {
        background: #f6c23e33;
        color: #a67c00;
    }

    .badge-completado {
        background: #1cc88a33;
        color: #0f6848;
    }

    .badge-cancelado {
        background: #e74a3b33;
        color: #842029;
    }
</style>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div class="ap-title">
            <i class="fa fa-screwdriver-wrench"></i>
            <span>Instalaciones</span>
        </div>

        <a href="create.php" class="btn btn-primary">
            <i class="fa fa-plus"></i> Nueva Instalación
        </a>
    </div>

    <div class="ap-card">

        <div class="ap-grid-wrapper ap-grid">

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="200">Acciones</th>
                        <th>Folio</th>
                        <th>Activo</th>
                        <th>Técnico</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody id="tablaInstalaciones">
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>

</div>

<script>
    function badgeEstado(estado) {

        switch (estado) {
            case 'COMPLETADO':
                return '<span class="badge-ap badge-completado">COMPLETADO</span>';

            case 'BORRADOR':
                return '<span class="badge-ap badge-borrador">BORRADOR</span>';

            case 'CANCELADO':
                return '<span class="badge-ap badge-cancelado">CANCELADO</span>';

            default:
                return estado;
        }
    }

    function cargarInstalaciones() {

        fetch('../api/api_instalaciones.php?action=list')
            .then(response => response.json())
            .then(data => {

                const tbody = document.getElementById('tablaInstalaciones');
                tbody.innerHTML = '';

                if (!data.data || data.data.length === 0) {

                    tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            No hay instalaciones registradas
                        </td>
                    </tr>
                `;
                    return;
                }

                data.data.forEach(row => {

                    tbody.innerHTML += `
                    <tr>
                        <td>
                            <a href="edit.php?id=${row.id_instalacion}" 
                               class="btn btn-sm btn-outline-primary me-1">Editar</a>

                            <a href="checklist.php?id=${row.id_instalacion}" 
                               class="btn btn-sm btn-outline-success me-1">Checklist</a>

                            <a href="print.php?id=${row.id_instalacion}" 
                               class="btn btn-sm btn-outline-dark" 
                               target="_blank">PDF</a>
                        </td>
                        <td>${row.folio}</td>
                        <td>${row.activo}</td>
                        <td>${row.tecnico}</td>
                        <td>${row.fecha}</td>
                        <td>${badgeEstado(row.estado)}</td>
                    </tr>
                `;
                });

            })
            .catch(error => {
                console.error(error);
            });
    }

    document.addEventListener('DOMContentLoaded', cargarInstalaciones);
</script>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>