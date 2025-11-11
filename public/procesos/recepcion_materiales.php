<?php
// =========================================================
// MÓDULO: RECEPCIÓN DE MATERIALES
// Proyecto: AssistPro Kardex FC
// =========================================================

require_once __DIR__ . '/../../db.php

// 🔹 La clase CortinaEntrada ya no se usa.
// 🔹 Las zonas de recepción se obtienen vía API (action: 'traer_zonas')
//    desde el catálogo tubicacionesretencion.
# $listaZR = new \CortinaEntrada\CortinaEntrada();

$listaProvee = new \Proveedores\Proveedores();
$listaProyectos = new \Proyectos\Proyectos();
$listaAlma = new \Almacen\Almacen();
$listaUser = new \Usuarios\Usuarios();
$listaOC = new \OrdenCompra\OrdenCompra();
$listaAP = new \AlmacenP\AlmacenP();
$listaLotes = new \Lotes\Lotes();
$listaProto = new \Protocolos\Protocolos();
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Recepción de Materiales</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f4f6f9;
            font-size: 14px;
        }
        .titulo {
            color: #0F5AAD;
            font-weight: 600;
        }
        .subtitulo {
            color: #6c757d;
            font-size: 13px;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 5px 20px rgba(15,90,173,0.1);
            border: none;
        }
        .form-select, .form-control {
            font-size: 13px;
        }
        .btn {
            font-size: 13px;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div id="app" class="container-fluid py-3">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h5 class="titulo mb-1">Recepción de Materiales</h5>
                    <div class="subtitulo">
                        Selecciona el tipo de recepción y la zona (Cortina) correspondiente.
                    </div>
                </div>
                <div>
                    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Volver</a>
                </div>
            </div>

            <!-- Filtros principales -->
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tipo de Recepción</label>
                    <select v-model="tipo" class="form-select">
                        <option value="">Seleccione tipo</option>
                        <option value="OC">Orden de Compra (OC)</option>
                        <option value="RL">Recepción Logística (RL)</option>
                        <option value="XD">CrossDocking (XD)</option>
                        <option value="DV">Logística Inversa (Devoluciones)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Almacén</label>
                    <select v-model="almacen" @change="traerZonas" class="form-select">
                        <option value="">Seleccione</option>
                        <?php foreach ($listaAP->listarTodo() as $a): ?>
                            <option value="<?= htmlspecialchars($a['id']); ?>">
                                <?= htmlspecialchars($a['cve_almacenp'] . ' - ' . $a['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Zona de Recepción (Cortina)</label>
                    <select v-model="zona" class="form-select">
                        <option value="">Seleccione zona</option>
                        <option v-for="z in zonas" :key="z.id" :value="z.cve_ubicacion">
                            {{ z.cve_ubicacion }} - {{ z.desc_ubicacion }}
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button @click="continuar" class="btn btn-primary w-100">Continuar Recepción</button>
                </div>
            </div>

            <hr class="my-3">

            <!-- Tabla temporal de documentos -->
            <div v-if="documentos.length" class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Documento</th>
                            <th>Proveedor / Cliente</th>
                            <th>Almacén</th>
                            <th>Zona</th>
                            <th>Estatus</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(doc, i) in documentos" :key="i">
                            <td>{{ i + 1 }}</td>
                            <td>{{ doc.folio }}</td>
                            <td>{{ doc.proveedor }}</td>
                            <td>{{ doc.almacen }}</td>
                            <td>{{ doc.zona }}</td>
                            <td>
                                <span class="badge bg-success" v-if="doc.estatus === 'OK'">Recibido</span>
                                <span class="badge bg-warning text-dark" v-else>En proceso</span>
                            </td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" @click="abrir(doc)">Abrir</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="text-muted fst-italic mt-2">
                No hay documentos cargados aún.
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: "#app",
    data: {
        tipo: "",
        almacen: "",
        zona: "",
        zonas: [],
        documentos: []
    },
    methods: {
        traerZonas() {
            if (!this.almacen) {
                this.zonas = [];
                return;
            }

            $.ajax({
                type: "POST",
                dataType: "json",
                url: "/api/ordendecompra/update/index.php",
                data: {
                    action: "traer_zonas",
                    almacen: this.almacen
                },
                success: (data) => {
                    if (data.success) {
                        this.zonas = data.zonas;
                    } else {
                        Swal.fire("Aviso", "No se encontraron zonas para este almacén", "info");
                    }
                },
                error: () => {
                    Swal.fire("Error", "No se pudo obtener la lista de zonas", "error");
                }
            });
        },

        continuar() {
            if (!this.tipo || !this.almacen || !this.zona) {
                Swal.fire("Faltan datos", "Debes seleccionar tipo, almacén y zona", "warning");
                return;
            }

            // Guardar la zona seleccionada y continuar flujo
            $.ajax({
                type: "POST",
                url: "/api/recepcion/iniciar.php",
                data: {
                    tipo: this.tipo,
                    almacen: this.almacen,
                    zona: this.zona
                },
                success: (resp) => {
                    Swal.fire("Listo", "Recepción iniciada correctamente", "success");
                },
                error: () => {
                    Swal.fire("Error", "No se pudo iniciar la recepción", "error");
                }
            });
        },

        abrir(doc) {
            Swal.fire("Detalle", `Abrir documento ${doc.folio}`, "info");
        }
    }
});
</script>
</body>
</html>
