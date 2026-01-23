<?php
// public/vas/servicios_vas.php
// UI Upgrade (look & feel + modal alta/edición):
// - Cards más finas (shadow suave, borde superior azul corporativo, títulos en azul)
// - Modal Bootstrap para Alta y Edición (POST/PUT a api/vas/servicios.php)
// - Acciones a la izquierda (Editar abre modal, Borrar desactiva)
// - Espaciado compacto (filas y cabecera)
// - Incluye _menu_global.php y _menu_global_end.php

$MENU_INI = __DIR__ . "/../bi/_menu_global.php";
$MENU_FIN = __DIR__ . "/../bi/_menu_global_end.php";

if (file_exists($MENU_INI)) { include $MENU_INI; }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>VAS · Administración de Servicios</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables Bootstrap 5 -->
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <style>
    :root{
      /* Azul corporativo (ajústalo si tu marca usa otro hex) */
      --corp-blue: #0b4aa2;
      --corp-blue-2: #0a3f8a;
      --ink: #0f172a;
      --muted: #6b7280;
      --line: #e8eaef;
      --bg: #f6f8fb;
    }

    body { background: var(--bg); color: var(--ink); }

    .page-title{
      font-weight: 800;
      letter-spacing: .2px;
      color: var(--corp-blue);
      margin: 0;
    }
    .page-sub{
      color: var(--muted);
      font-size: 13px;
      margin-top: 2px;
    }

    .toolbar-card{
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      padding: 14px;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }

    .kpi-card{
      position: relative;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      padding: 14px;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
      overflow: hidden;
    }
    .kpi-card::before{
      content:"";
      position:absolute;
      top:0; left:0; right:0;
      height: 3px;
      background: linear-gradient(90deg, var(--corp-blue), #2c7be5);
    }
    .kpi-title{
      font-size: 12px;
      color: var(--corp-blue);
      font-weight: 700;
      margin: 0 0 2px 0;
      text-transform: uppercase;
      letter-spacing: .5px;
    }
    .kpi-value{
      font-size: 26px;
      font-weight: 800;
      margin: 0;
      line-height: 1.1;
    }

    .table-wrap{
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      padding: 10px;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }

    /* DataTable compacto y limpio */
    table.dataTable thead th { white-space: nowrap; }
    .table > :not(caption) > * > * { padding: .32rem .55rem; }
    table.dataTable tbody td { padding-top: .32rem !important; padding-bottom: .32rem !important; }
    .dataTables_wrapper .dataTables_length select { min-width: 72px; }

    /* Columnas */
    .col-actions { width: 150px; }
    .col-id { width: 70px; }
    .col-clave { width: 120px; }
    .col-tipo { width: 150px; }
    .col-precio { width: 120px; }
    .col-moneda { width: 90px; }
    .col-activo { width: 90px; }

    .badge { font-weight: 700; }
    .btn-sm { padding: .2rem .55rem; border-radius: 10px; }

    .muted-note{ color: var(--muted); font-size: 12px; }
    .hint{
      display:flex; gap:.4rem; align-items:center;
      color: var(--muted); font-size: 12px;
      margin-top: 6px;
    }
    .hint-dot{
      width: 6px; height: 6px; border-radius: 999px; background: #cbd5e1;
    }

    /* Modal form */
    .form-label{ font-weight: 700; color:#334155; }
    .req::after{ content:" *"; color:#dc2626; font-weight:800; }
  </style>
</head>

<body>
  <div class="container-fluid py-3">

    <div class="d-flex align-items-start justify-content-between mb-3">
      <div>
        <h4 class="page-title">VAS · Administración de Servicios</h4>
        <div class="page-sub">Catálogo maestro + control operativo de precios por compañía (y contexto de almacén).</div>
      </div>

      <button class="btn btn-primary" id="btnNuevo">
        + Nuevo
      </button>
    </div>

    <div class="toolbar-card mb-3">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-4">
          <label class="form-label">Compañía</label>
          <select id="cve_cia" class="form-select">
            <!-- Si tu catálogo real llena esto desde DB, reemplaza estas opciones -->
            <option value="1">FOAM CREATIONS MEXICO SA DE CV</option>
            <option value="2">ADVL PRUEBAS</option>
            <option value="3">ADVL PRB</option>
          </select>
          <div class="hint"><span class="hint-dot"></span><span>El almacén es contextual. El catálogo VAS se gestiona por empresa (IdEmpresa = cve_cia).</span></div>
        </div>

        <div class="col-12 col-lg-4">
          <label class="form-label">Almacén (contexto)</label>
          <select id="almacenp_id" class="form-select" disabled>
            <option value="">--</option>
          </select>
          <div class="muted-note mt-1">
            Fuente: api/filtros_almacenes.php?action=almacenes&cve_cia=...
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <label class="form-label">Buscar</label>
          <div class="input-group">
            <input id="q" class="form-control" placeholder="clave / nombre" />
            <button class="btn btn-outline-primary" id="btnAplicar">Aplicar</button>
            <button class="btn btn-outline-secondary" id="btnLimpiar">Limpiar</button>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-12 col-md-3">
        <div class="kpi-card">
          <p class="kpi-title">Servicios activos</p>
          <p class="kpi-value" id="kpiActivos">0</p>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="kpi-card">
          <p class="kpi-title">Servicios total</p>
          <p class="kpi-value" id="kpiTotal">0</p>
        </div>
      </div>
    </div>

    <div class="table-wrap">
      <table id="tblServicios" class="table table-striped table-hover align-middle w-100">
        <thead>
          <tr>
            <th class="col-actions">Acciones</th>
            <th class="col-id">ID</th>
            <th class="col-clave">Clave</th>
            <th>Servicio</th>
            <th class="col-tipo">Tipo Cobro</th>
            <th class="col-precio text-end">Precio Base</th>
            <th class="col-moneda">Moneda</th>
            <th class="col-activo">Activo</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

  </div>

  <!-- Modal Alta/Edición -->
  <div class="modal fade" id="mdlServicio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content" style="border-radius:16px;">
        <div class="modal-header">
          <div>
            <h5 class="modal-title m-0" id="mdlTitle" style="color:var(--corp-blue); font-weight:800;">Servicio VAS</h5>
            <div class="muted-note" id="mdlSub">Alta / edición del catálogo</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="f_id_servicio" value="">
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label req">Clave</label>
              <input class="form-control" id="f_clave" maxlength="20" placeholder="Ej. EMPQ01">
            </div>

            <div class="col-12 col-md-8">
              <label class="form-label req">Servicio</label>
              <input class="form-control" id="f_nombre" maxlength="150" placeholder="Ej. Empaque especial">
            </div>

            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" id="f_desc" rows="2" maxlength="255" placeholder="Detalle operativo / estándar de ejecución"></textarea>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label req">Tipo cobro</label>
              <select class="form-select" id="f_tipo">
                <option value="fijo">Fijo</option>
                <option value="por_pieza">Por pieza</option>
                <option value="por_pedido">Por pedido</option>
                <option value="por_hora">Por hora</option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label req">Precio base</label>
              <input class="form-control" id="f_precio" type="number" step="0.01" min="0" placeholder="0.00">
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label req">Moneda</label>
              <select class="form-select" id="f_moneda">
                <option value="MXN">MXN</option>
                <option value="USD">USD</option>
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label req">Activo</label>
              <select class="form-select" id="f_activo">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>

          <div class="alert alert-info mt-3 mb-0" style="border-radius:14px;">
            <div class="d-flex gap-2">
              <div style="font-weight:800;">Tip:</div>
              <div>Este catálogo es por <b>empresa</b>. En la siguiente iteración podemos habilitar <b>precio por almacén</b> (contextual) usando tu selector.</div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" id="btnGuardar">
            Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts en orden correcto -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // APIs reales (desde /public/vas/servicios_vas.php)
    const API_ALMACENES = "../api/filtros_almacenes.php";   // action=almacenes&cve_cia=1
    const API_SERVICIOS = "../api/vas/servicios.php";       // GET/POST/PUT/DELETE

    let dt = null;
    let modal = null;

    function toast(msg, type="info"){
      const cls = (type==="error") ? "text-bg-danger" : (type==="ok" ? "text-bg-success" : "text-bg-secondary");
      const el = document.createElement("div");
      el.className = `toast align-items-center ${cls} border-0 position-fixed bottom-0 end-0 m-3`;
      el.role = "alert";
      el.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${msg}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
      document.body.appendChild(el);
      const t = new bootstrap.Toast(el, { delay: 2500 });
      t.show();
      el.addEventListener("hidden.bs.toast", ()=> el.remove());
    }

    async function apiGet(url){
      const r = await fetch(url, { headers: { "Accept":"application/json" }});
      const j = await r.json().catch(()=> ({}));
      if(!r.ok) throw new Error(j.msg || j.error || ("HTTP " + r.status));
      return j;
    }

    async function apiJson(url, method, body){
      const r = await fetch(url, {
        method,
        headers: { "Content-Type":"application/json", "Accept":"application/json" },
        body: JSON.stringify(body || {})
      });
      const j = await r.json().catch(()=> ({}));
      if(!r.ok || !j.ok) throw new Error(j.msg || j.error || "Operación no completada");
      return j;
    }

    function fmtMoney(v){
      if(v===null || v===undefined || v==="") return "";
      const n = Number(v);
      if(Number.isNaN(n)) return String(v);
      return n.toFixed(2);
    }

    function normalizeTipoCobro(v){
      const m = { "fijo":"Fijo", "por_pieza":"Por pieza", "por_pedido":"Por pedido", "por_hora":"Por hora" };
      return m[v] || (v || "");
    }

    function ensureDataTable(){
      if(dt) return dt;

      dt = $("#tblServicios").DataTable({
        pageLength: 25,
        lengthMenu: [10,25,50,100],
        order: [[3,"asc"]],
        language: {
          search: "Buscar en tabla:",
          lengthMenu: "Mostrar _MENU_",
          info: "Mostrando _START_ a _END_ de _TOTAL_",
          paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" },
          zeroRecords: "Sin resultados",
          infoEmpty: "Sin registros"
        },
        columns: [
          { data:null, orderable:false, render:(row)=>{
              const id = row.id_servicio;
              return `
                <div class="d-flex gap-1">
                  <button class="btn btn-outline-primary btn-sm" data-act="edit" data-id="${id}">Editar</button>
                  <button class="btn btn-outline-danger btn-sm" data-act="del" data-id="${id}">Borrar</button>
                </div>`;
            }
          },
          { data:"id_servicio" },
          { data:"clave_servicio" },
          { data:"nombre" },
          { data:"tipo_cobro", render:(d)=> normalizeTipoCobro(d) },
          { data:"precio_base", className:"text-end", render:(d)=> fmtMoney(d) },
          { data:"moneda" },
          { data:"Activo", render:(d)=> (String(d)==="1" ? "<span class='badge bg-success'>Sí</span>" : "<span class='badge bg-secondary'>No</span>") }
        ]
      });

      // Acciones tabla
      $("#tblServicios tbody").on("click", "button[data-act='edit']", function(){
        const id = Number($(this).data("id") || 0);
        const row = dt.rows().data().toArray().find(r => Number(r.id_servicio) === id);
        if(!row) return;
        openModalEdit(row);
      });

      $("#tblServicios tbody").on("click", "button[data-act='del']", async function(){
        const id = Number($(this).data("id") || 0);
        if(!id) return;
        if(!confirm("¿Desactivar servicio id=" + id + "?")) return;

        try{
          const IdEmpresa = $("#cve_cia").val();
          await apiJson(`${API_SERVICIOS}?id=${id}`, "DELETE", { IdEmpresa });
          toast("Servicio desactivado", "ok");
          await loadServicios();
        }catch(e){
          console.error(e);
          toast(e.message, "error");
        }
      });

      return dt;
    }

    async function loadAlmacenes(){
      const cia = $("#cve_cia").val();
      const $sel = $("#almacenp_id");

      $sel.prop("disabled", true).empty().append(`<option value="">--</option>`);

      if(!cia){
        $sel.prop("disabled", false);
        return;
      }

      try{
        const url = `${API_ALMACENES}?action=almacenes&cve_cia=${encodeURIComponent(cia)}`;
        const js = await apiGet(url);
        const rows = js.data || [];
        rows.forEach(r=>{
          const id = r.almacenp_id;
          const clave = r.clave ? ` · ${r.clave}` : "";
          const nombre = r.nombre || ("Almacén " + id);
          $sel.append(`<option value="${String(id)}">${nombre}${clave}</option>`);
        });
        $sel.prop("disabled", false);
      }catch(e){
        console.error(e);
        toast("No se pudieron cargar almacenes: " + e.message, "error");
        $sel.prop("disabled", false);
      }
    }

    async function loadServicios(){
      const IdEmpresa = $("#cve_cia").val();
      const q = ($("#q").val() || "").trim();

      if(!IdEmpresa){
        $("#kpiTotal").text("0");
        $("#kpiActivos").text("0");
        ensureDataTable().clear().draw();
        return;
      }

      try{
        const url =
          `${API_SERVICIOS}?IdEmpresa=${encodeURIComponent(IdEmpresa)}&Activo=1&search=${encodeURIComponent(q)}`;

        const js = await apiGet(url);
        if(!js.ok) throw new Error(js.msg || "Respuesta inválida");

        const rows = js.data || [];
        $("#kpiTotal").text(String(rows.length));
        $("#kpiActivos").text(String(rows.filter(r=> String(r.Activo)==="1").length));

        const table = ensureDataTable();
        table.clear().rows.add(rows).draw();
      }catch(e){
        console.error(e);
        toast("Servicios: " + e.message, "error");
        $("#kpiTotal").text("0");
        $("#kpiActivos").text("0");
        ensureDataTable().clear().draw();
      }
    }

    function resetModal(){
      $("#f_id_servicio").val("");
      $("#f_clave").val("");
      $("#f_nombre").val("");
      $("#f_desc").val("");
      $("#f_tipo").val("fijo");
      $("#f_precio").val("0.00");
      $("#f_moneda").val("MXN");
      $("#f_activo").val("1");
    }

    function openModalNew(){
      resetModal();
      $("#mdlTitle").text("Nuevo servicio VAS");
      $("#mdlSub").text("Alta del catálogo (IdEmpresa actual)");
      modal.show();
      setTimeout(()=> $("#f_clave").trigger("focus"), 150);
    }

    function openModalEdit(row){
      resetModal();
      $("#mdlTitle").text("Editar servicio VAS");
      $("#mdlSub").text("Actualización del catálogo (IdEmpresa actual)");

      $("#f_id_servicio").val(row.id_servicio);
      $("#f_clave").val(row.clave_servicio || "");
      $("#f_nombre").val(row.nombre || "");
      $("#f_desc").val(row.descripcion || "");
      $("#f_tipo").val(row.tipo_cobro || "fijo");
      $("#f_precio").val(row.precio_base ?? "0.00");
      $("#f_moneda").val((row.moneda || "MXN").toUpperCase());
      $("#f_activo").val(String(row.Activo ?? 1));

      modal.show();
      setTimeout(()=> $("#f_nombre").trigger("focus"), 150);
    }

    async function saveModal(){
      const IdEmpresa = $("#cve_cia").val();
      const id = Number($("#f_id_servicio").val() || 0);

      const payload = {
        IdEmpresa,
        clave_servicio: ($("#f_clave").val() || "").trim().toUpperCase(),
        nombre: ($("#f_nombre").val() || "").trim(),
        descripcion: ($("#f_desc").val() || "").trim(),
        tipo_cobro: $("#f_tipo").val(),
        precio_base: Number($("#f_precio").val() || 0),
        moneda: ($("#f_moneda").val() || "MXN").trim().toUpperCase(),
        Activo: Number($("#f_activo").val() || 1)
      };

      if(!payload.IdEmpresa) throw new Error("Selecciona una compañía (IdEmpresa).");
      if(!payload.clave_servicio || !payload.nombre) throw new Error("Clave y Servicio son requeridos.");

      const btn = document.getElementById("btnGuardar");
      btn.disabled = true;
      btn.textContent = "Guardando...";

      try{
        if(id > 0){
          await apiJson(`${API_SERVICIOS}?id=${id}`, "PUT", payload);
          toast("Servicio actualizado", "ok");
        }else{
          await apiJson(`${API_SERVICIOS}`, "POST", payload);
          toast("Servicio creado", "ok");
        }
        modal.hide();
        await loadServicios();
      }finally{
        btn.disabled = false;
        btn.textContent = "Guardar";
      }
    }

    function wireUI(){
      $("#btnAplicar").on("click", async ()=> { await loadServicios(); });
      $("#btnLimpiar").on("click", async ()=>{
        $("#q").val("");
        await loadServicios();
      });

      $("#cve_cia").on("change", async ()=>{
        await loadAlmacenes();
        await loadServicios();
      });

      $("#almacenp_id").on("change", async ()=>{
        // Contexto almacén listo para fase precio por almacén
        await loadServicios();
      });

      $("#q").on("keydown", async (e)=>{
        if(e.key==="Enter"){
          e.preventDefault();
          await loadServicios();
        }
      });

      $("#btnNuevo").on("click", ()=> openModalNew());
      $("#btnGuardar").on("click", async ()=>{
        try{
          await saveModal();
        }catch(e){
          console.error(e);
          toast(e.message, "error");
        }
      });
    }

    $(async function(){
      if(typeof window.jQuery !== "function" || typeof $.fn.DataTable !== "function"){
        console.error("Faltan librerías: jQuery/DataTables");
        toast("Faltan librerías (jQuery/DataTables).", "error");
        return;
      }

      modal = new bootstrap.Modal(document.getElementById("mdlServicio"), { backdrop:"static" });

      wireUI();
      ensureDataTable();

      await loadAlmacenes();
      await loadServicios();
    });
  </script>
</body>
</html>
<?php
if (file_exists($MENU_FIN)) { include $MENU_FIN; }
?>
