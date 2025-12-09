<?php
// public/control_patios/patios_admin.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

/*
 * EMPRESAS:       c_compania
 * ALMACENES:      c_almacenp (id, clave, nombre)
 * TRANSPORTISTAS: c_proveedores (es_transportista = 1)
 * TRANSPORTES:    t_transporte
 */

// EMPRESAS (filtro superior)
$empresas = db_all("
    SELECT cve_cia, des_cia
    FROM c_compania
    WHERE COALESCE(Activo,1) = 1
    ORDER BY des_cia
");

// ALMACENES (filtro y modal)
$almacenesp = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    ORDER BY clave, nombre
");

// TRANSPORTISTAS (Empresa Transportista)
$transportistas = db_all("
    SELECT ID_Proveedor, cve_proveedor, Nombre
    FROM c_proveedores
    WHERE COALESCE(Activo,1) = 1
      AND COALESCE(es_transportista,0) = 1
    ORDER BY Nombre
");

// TRANSPORTES (unidad física)
$transportes = db_all("
    SELECT id, ID_Transporte, Nombre, Placas
    FROM t_transporte
    ORDER BY ID_Transporte
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Administración de Patios</title>
<link rel="stylesheet" href="/assets/bootstrap.min.css">
<link rel="stylesheet" href="/assets/fontawesome.min.css">
<script src="/assets/jquery.min.js"></script>
<script src="/assets/bootstrap.bundle.min.js"></script>

<style>
body { font-size:12px; }
.etapa-card {
    border:1px solid #ddd; border-radius:8px;
    padding:6px; margin-bottom:8px; font-size:11px;
    background-color:#ffffff;
    box-shadow:0 1px 3px rgba(0,0,0,0.08);
}
.etapa-header {
    font-weight:bold; font-size:12px;
    display:flex; align-items:center;
}
.etapa-header img.etapa-truck {
    width:32px; height:32px;
    object-fit:contain;
    margin-right:6px;
}
.estado-pill {
    display:inline-block; padding:2px 6px; border-radius:10px;
    font-size:10px; color:#fff;
}
.estado-OK         { background:#28a745; }
.estado-PENDIENTE  { background:#6c757d; }
.estado-EN_PROCESO { background:#ffc107; color:#000; }
.estado-REVISION   { background:#17a2b8; }
.estado-ERROR      { background:#dc3545; }
.small-label { font-size:10px; }
.col-etapa-title {
    font-weight:bold; font-size:12px; text-align:center; margin-bottom:4px;
    text-transform:uppercase;
}
#msg-global { font-size:11px; }
</style>
</head>

<body>
<div class="container-fluid mt-2">
    <h5>Administración de Patios</h5>

    <div id="msg-global" class="text-muted mb-1">
        Selecciona empresa y almacén. Luego presiona <b>Nueva visita</b>.
    </div>

    <!-- FILTROS -->
    <div class="row mb-2">
        <div class="col-md-3">
            <label class="small-label">Empresa (c_compania)</label>
            <select id="f_empresa" class="form-control form-control-sm">
                <option value="">(Seleccione)</option>
                <?php foreach ($empresas as $e): ?>
                    <option value="<?= htmlspecialchars((string)$e['cve_cia']) ?>">
                        <?= htmlspecialchars((string)$e['cve_cia'].' - '.(string)$e['des_cia']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="small-label">Almacén / Patio (c_almacenp.id)</label>
            <select id="f_almacenp" class="form-control form-control-sm">
                <option value="">(Seleccione)</option>
                <?php foreach ($almacenesp as $a): ?>
                    <?php
                        $id    = (string)$a['id'];
                        $clave = (string)$a['clave'];
                        $nom   = (string)$a['nombre'];
                    ?>
                    <option value="<?= htmlspecialchars($id) ?>">
                        <?= htmlspecialchars($clave.' - '.$nom) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button id="btn_refrescar"
                    class="btn btn-outline-secondary btn-sm me-2"
                    type="button">
                Refrescar
            </button>

            <button id="btn_nueva_visita"
                    class="btn btn-primary btn-sm"
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#modalNuevaVisita">
                Nueva visita
            </button>
        </div>
    </div>

    <!-- TABLERO 4 ETAPAS -->
    <div class="row">
        <div class="col-md-3">
            <div class="col-etapa-title">1. Cita</div>
            <div id="col-etapa-1"></div>
        </div>
        <div class="col-md-3">
            <div class="col-etapa-title">2. Arribo / En Patio</div>
            <div id="col-etapa-2"></div>
        </div>
        <div class="col-md-3">
            <div class="col-etapa-title">3. Inspección / QA</div>
            <div id="col-etapa-3"></div>
        </div>
        <div class="col-md-3">
            <div class="col-etapa-title">4. Carga / Descarga</div>
            <div id="col-etapa-4"></div>
        </div>
    </div>
</div>

<!-- MODAL NUEVA VISITA -->
<div class="modal fade" id="modalNuevaVisita">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formNuevaVisita">
        <div class="modal-header">
            <h6 class="modal-title">Registrar nueva visita</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

            <!-- empresa_id (c_compania) oculto, tomado del filtro -->
            <input type="hidden" id="nv_empresa" name="empresa_id">

            <div class="row mb-2">
                <div class="col-md-5">
                    <label class="small-label">Empresa Transportista</label>
                    <select id="nv_proveedor" name="transportista_id" class="form-control form-control-sm">
                        <option value="">Seleccione...</option>
                        <?php foreach ($transportistas as $p): ?>
                            <option value="<?= htmlspecialchars((string)$p['ID_Proveedor']) ?>">
                                <?= htmlspecialchars((string)$p['cve_proveedor'].' - '.(string)$p['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small-label">Almacén / Patio</label>
                    <select id="nv_almacenp" name="almacenp_id" class="form-control form-control-sm">
                        <option value="">Seleccione...</option>
                        <?php foreach ($almacenesp as $a): ?>
                            <?php
                                $id    = (string)$a['id'];
                                $clave = (string)$a['clave'];
                                $nom   = (string)$a['nombre'];
                            ?>
                            <option value="<?= htmlspecialchars($id) ?>">
                                <?= htmlspecialchars($clave.' - '.$nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-2">
                <label class="small-label">Transporte (unidad)</label>
                <select id="nv_transporte" name="id_transporte" class="form-control form-control-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($transportes as $t): ?>
                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['ID_Transporte'].' - '.$t['Nombre'].' ['.$t['Placas'].']') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-2">
                <label class="small-label">Observaciones</label>
                <textarea id="nv_observaciones" name="observaciones" rows="2"
                          class="form-control form-control-sm"></textarea>
            </div>

            <div id="nv-msg" class="text-muted small"></div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">Cerrar</button>
            <button class="btn btn-primary btn-sm" type="submit">Guardar visita</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL VINCULAR OCs -->
<div class="modal fade" id="modalOC">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formVincularOC">
        <div class="modal-header">
            <h6 class="modal-title">Vincular OCs</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" id="id_visita_oc" name="id_visita">
            <div id="lista-ocs"></div>
            <div id="oc-msg" class="text-muted small mt-2"></div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">Cerrar</button>
            <button class="btn btn-primary btn-sm" type="submit">Vincular</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function mostrarMensaje(msg, tipo="info") {
    const el = $("#msg-global");
    el.removeClass("text-muted text-danger text-success");
    if (tipo === "error") el.addClass("text-danger");
    else if (tipo === "ok") el.addClass("text-success");
    else el.addClass("text-muted");
    el.text(msg);
}

function pill(estado) {
    if (!estado) estado = "PENDIENTE";
    return `<span class="estado-pill estado-${estado}">${estado}</span>`;
}

function renderTablero(rows) {
    $("#col-etapa-1, #col-etapa-2, #col-etapa-3, #col-etapa-4").empty();

    if (!rows || rows.length === 0) {
        mostrarMensaje("No hay visitas registradas. Usa 'Nueva visita'.");
        return;
    }

    mostrarMensaje("Tablero actualizado.","ok");

    rows.forEach(function(r){
        const header =
        `<div class="etapa-header">
            <img src="/assets/img/truck_card.png" class="etapa-truck" alt="Transporte">
            <div>
              <div><strong>${r.id_transporte_cod} - ${r.nombre_transporte}</strong>
                  <span class="text-muted">[${r.placas||""}]</span>
              </div>
              <div class="small-label">Visita #${r.id_visita}</div>
            </div>
        </div>`;

        const body =
        `<div class="mt-1">
            <div><span class="small-label">Andén:</span> ${r.id_anden_actual||"-"}</div>
            <div><span class="small-label">Llegó:</span> ${r.fecha_llegada||"-"}</div>
        </div>`;

        const btn =
        `<button class="btn btn-outline-primary btn-sm btn-vincular-oc mt-1"
                 data-id_visita="${r.id_visita}">
            Vincular OCs
         </button>`;

        $("#col-etapa-1").append(`<div class="etapa-card">${header}${pill(r.etapa1_estado)}${body}${btn}</div>`);
        $("#col-etapa-2").append(`<div class="etapa-card">${header}${pill(r.etapa2_estado)}${body}${btn}</div>`);
        $("#col-etapa-3").append(`<div class="etapa-card">${header}${pill(r.etapa3_estado)}${body}${btn}</div>`);
        $("#col-etapa-4").append(`<div class="etapa-card">${header}${pill(r.etapa4_estado)}${body}${btn}</div>`);
    });
}

function cargarTablero() {
    const empresa_id  = $("#f_empresa").val();
    const almacenp_id = $("#f_almacenp").val();

    $.getJSON("patios_tablero_api.php", {
        empresa_id  : empresa_id,
        almacenp_id : almacenp_id
    }).done(function(resp){
        if (!resp.ok) {
            mostrarMensaje("Error al cargar tablero: "+resp.error,"error");
            return;
        }
        renderTablero(resp.data);
    }).fail(function(xhr){
        mostrarMensaje("Error de comunicación con el servidor.","error");
        console.log('Error AJAX tablero:', xhr.status, xhr.statusText, xhr.responseText);
    });
}

function cargarOCsPendientes(id_visita) {
    $("#lista-ocs").html("Cargando...");
    $("#oc-msg").text("");

    const almacenp_id = $("#f_almacenp").val();

    $.getJSON("patios_oc_pendientes.php", {almacenp_id:almacenp_id})
    .done(function(resp){
        if (!resp.ok) {
            $("#lista-ocs").html("");
            $("#oc-msg").text(resp.error);
            return;
        }

        const rows = resp.data||[];
        if (!rows.length) {
            $("#lista-ocs").html("");
            $("#oc-msg").text("No hay OCs pendientes para este almacén.");
            return;
        }

        let html = `<table class="table table-sm table-bordered">
        <thead><tr>
            <th></th><th>OC</th><th>Proveedor</th><th>Fecha</th><th>Pendiente</th>
        </tr></thead><tbody>`;

        rows.forEach(function(oc){
            html += `
            <tr>
                <td><input type="checkbox" class="chk-oc" value="${oc.oc_id}"></td>
                <td>${oc.folio_oc}</td>
                <td>${oc.proveedor_id||""}</td>
                <td>${oc.fecha_oc||""}</td>
                <td>${oc.cant_pendiente||0}</td>
            </tr>`;
        });

        html += `</tbody></table>`;
        $("#lista-ocs").html(html);
    });
}

$(function(){

    $("#btn_refrescar").on("click", cargarTablero);
    cargarTablero();
    setInterval(cargarTablero, 15000);

    $("#btn_nueva_visita").on("click", function(){
        const emp = $("#f_empresa").val();
        const alm = $("#f_almacenp").val();

        $("#nv_empresa").val(emp || '');
        $("#nv_almacenp").val(alm || '');
        $("#nv_proveedor").val('');
        $("#nv_transporte").val('');
        $("#nv_observaciones").val('');
        $("#nv-msg").text('');

        if (!emp || !alm) {
            mostrarMensaje("Sugerencia: selecciona empresa y almacén antes de registrar.","error");
        }
    });

    $("#formNuevaVisita").on("submit", function(e){
        e.preventDefault();
        $("#nv-msg").text("Guardando...");

        $.post("patios_nueva_visita.php", $(this).serialize()
        ).done(function(resp){
            if (typeof resp === "string") {
                try { resp = JSON.parse(resp); } catch(e) {}
            }

            if (!resp || !resp.ok) {
                $("#nv-msg").text((resp && resp.error) ? resp.error : "Error al guardar visita");
                return;
            }

            $("#nv-msg").text("Visita registrada (#"+resp.id_visita+")");
            cargarTablero();
            setTimeout(function(){
                $("#modalNuevaVisita").modal("hide");
            }, 800);

        }).fail(function(xhr){
            $("#nv-msg").text("Error de servidor.");
            console.error('Error AJAX nueva_visita:', xhr.status, xhr.statusText, xhr.responseText);
        });
    });

    $(document).on("click",".btn-vincular-oc",function(){
        const id_visita = $(this).data("id_visita");
        $("#id_visita_oc").val(id_visita);
        cargarOCsPendientes(id_visita);
        $("#modalOC").modal("show");
    });

    $("#formVincularOC").on("submit", function(e){
        e.preventDefault();
        const id_visita = $("#id_visita_oc").val();
        const oc_ids = $(".chk-oc:checked").map(function(){ return this.value }).get();

        if (!oc_ids.length) {
            $("#oc-msg").text("Seleccione al menos una OC.");
            return;
        }

        $("#oc-msg").text("Vinculando...");

        $.post("patios_vincular_oc.php", {
            id_visita:id_visita,
            oc_ids: oc_ids.join(",")
        }).done(function(resp){
            if (typeof resp === "string") {
                try { resp = JSON.parse(resp); } catch(e) {}
            }
            if (!resp || !resp.ok) {
                $("#oc-msg").text((resp && resp.error) ? resp.error : "Error al vincular.");
                return;
            }
            $("#oc-msg").text("OC vinculada.");
            cargarTablero();
            setTimeout(function(){
                $("#modalOC").modal("hide");
            }, 800);
        });
    });

});
</script>

</body>
</html>
<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
