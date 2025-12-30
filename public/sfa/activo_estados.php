<?php
// /public/sfa/activo_estados.php
require_once __DIR__ . '/../../app/db.php';
?>
<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<style>
  .ap-title{ font-weight:700; letter-spacing:.2px; }
  .ap-sub{ color:#6b7280; font-size:12px; }
  .ap-toolbar .btn{ margin-left:6px; }
  .ap-kpi{ display:flex; gap:8px; align-items:center; font-size:12px; color:#374151; }
  .ap-kpi .pill{ background:#eef2ff; border:1px solid #e5e7eb; padding:4px 10px; border-radius:999px; }
  .ap-table th, .ap-table td{ font-size:12px; vertical-align:middle; }
  .ap-actions .btn{ padding:.2rem .45rem; font-size:12px; }
  .dot{ display:inline-flex; align-items:center; gap:8px; }
  .dot i{ font-size:12px; }
  .muted{ color:#6b7280; }
</style>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <div class="ap-title h5 mb-1">Catálogo | Estados del Activo</div>
      <div class="ap-sub">Semáforo operativo (VERDE / AMARILLO / ROJO) | Softdelete | CSV</div>
    </div>

    <div class="ap-toolbar">
      <button class="btn btn-outline-secondary btn-sm" id="btnExport">
        <i class="fa-solid fa-file-export me-1"></i> Exportar CSV
      </button>
      <label class="btn btn-outline-primary btn-sm mb-0">
        <i class="fa-solid fa-file-import me-1"></i> Importar CSV
        <input type="file" id="fileCsv" accept=".csv" hidden>
      </label>
      <button class="btn btn-primary btn-sm" id="btnNuevo">
        <i class="fa-solid fa-plus me-1"></i> Nuevo
      </button>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="ap-kpi">
          <span class="pill">Total: <b id="kpiTotal">0</b></span>
          <span class="pill">Activos: <b id="kpiActivos">0</b></span>
        </div>
        <div class="d-flex gap-2">
          <input type="text" id="txtBuscar" class="form-control form-control-sm" style="min-width:260px"
                 placeholder="Buscar nombre...">
          <button class="btn btn-outline-secondary btn-sm" id="btnRefresh">
            <i class="fa-solid fa-rotate me-1"></i> Recargar
          </button>
        </div>
      </div>

      <div class="mt-2 muted" style="font-size:12px;">
        Recomendación: define estados como “Disponible”, “Asignado a Cliente”, “En Mantenimiento”, “Baja”, etc. y asigna semáforo.
      </div>

      <div class="table-responsive mt-3">
        <table class="table table-sm table-hover ap-table" id="tbl">
          <thead class="table-light">
            <tr>
              <th style="width:110px;">Acciones</th>
              <th style="width:90px;">ID</th>
              <th>Nombre</th>
              <th style="width:160px;">Semáforo</th>
              <th style="width:90px;">Activo</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="5" class="text-center muted py-4">Cargando...</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- Modal Nuevo/Editar -->
<div class="modal fade" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="mdlTitle">Estado</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="f_id_estado">

        <div class="mb-2">
          <label class="form-label mb-1">Nombre <span class="text-danger">*</span></label>
          <input type="text" class="form-control form-control-sm" id="f_nombre" maxlength="80">
        </div>

        <div class="mb-2">
          <label class="form-label mb-1">Semáforo</label>
          <select class="form-select form-select-sm" id="f_semaforo">
            <option value="VERDE">VERDE</option>
            <option value="AMARILLO">AMARILLO</option>
            <option value="ROJO">ROJO</option>
          </select>
        </div>

        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" id="f_activo" checked>
          <label class="form-check-label" for="f_activo">Activo</label>
        </div>

        <div class="alert alert-danger d-none mt-3" id="mdlErr"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary btn-sm" id="btnGuardar">
          <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  // ✅ Endpoint real que ya te responde (según tu captura):
  const API_URL = "../api/activo_estado_api.php";

  const $ = (q) => document.querySelector(q);
  const tbody = $("#tbody");
  const txtBuscar = $("#txtBuscar");

  let ROWS = [];

  function esc(s){
    return (s ?? "").toString()
      .replaceAll("&","&amp;").replaceAll("<","&lt;")
      .replaceAll(">","&gt;").replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }

  function semaforoIcon(semaforo){
    const v = (semaforo || "VERDE").toUpperCase();
    if(v === "ROJO")     return `<span class="dot"><i class="fa-solid fa-circle text-danger"></i> <span>ROJO</span></span>`;
    if(v === "AMARILLO") return `<span class="dot"><i class="fa-solid fa-circle text-warning"></i> <span>AMARILLO</span></span>`;
    return `<span class="dot"><i class="fa-solid fa-circle text-success"></i> <span>VERDE</span></span>`;
  }

  function activoBadge(activo){
    const on = (parseInt(activo||0) === 1);
    return on ? `<span class="badge text-bg-success">Sí</span>` : `<span class="badge text-bg-secondary">No</span>`;
  }

  function render(){
    const q = (txtBuscar.value || "").trim().toLowerCase();
    const data = !q ? ROWS : ROWS.filter(r => (r.nombre||"").toLowerCase().includes(q));

    $("#kpiTotal").textContent = data.length;
    $("#kpiActivos").textContent = data.filter(r => parseInt(r.activo||0)===1).length;

    if(!data.length){
      tbody.innerHTML = `<tr><td colspan="5" class="text-center muted py-4">Sin registros</td></tr>`;
      return;
    }

    tbody.innerHTML = data.map(r => {
      const id = r.id_estado ?? r.id ?? "";
      return `
        <tr>
          <td class="ap-actions">
            <button class="btn btn-outline-primary btn-sm" data-edit="${esc(id)}" title="Editar">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" data-del="${esc(id)}" title="Eliminar (softdelete)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
          <td>${esc(id)}</td>
          <td>${esc(r.nombre)}</td>
          <td>${semaforoIcon(r.semaforo)}</td>
          <td>${activoBadge(r.activo)}</td>
        </tr>
      `;
    }).join("");
  }

  async function api(action, payload = {}){
    const body = new URLSearchParams({ action, ...payload });
    const res = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body
    });
    const txt = await res.text();
    let json;
    try { json = JSON.parse(txt); }
    catch(e){ throw new Error("Respuesta no-JSON del API: " + txt.slice(0,200)); }
    if(!json.success && json.ok !== 1){
      throw new Error(json.message || json.error || "Operación no exitosa");
    }
    return json;
  }

  async function load(){
    try{
      tbody.innerHTML = `<tr><td colspan="5" class="text-center muted py-4">Cargando...</td></tr>`;
      const j = await api("list");
      // soporta ambas formas: rows o data
      ROWS = (j.rows && Array.isArray(j.rows)) ? j.rows : (j.data && Array.isArray(j.data) ? j.data : []);
      render();
    }catch(err){
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">No se pudo cargar</td></tr>`;
      alert("No se pudo cargar");
    }
  }

  function openModal(row = null){
    $("#mdlErr").classList.add("d-none");
    $("#mdlErr").textContent = "";

    if(row){
      $("#mdlTitle").textContent = "Editar Estado";
      $("#f_id_estado").value = row.id_estado ?? row.id ?? "";
      $("#f_nombre").value = row.nombre ?? "";
      $("#f_semaforo").value = (row.semaforo ?? "VERDE").toUpperCase();
      $("#f_activo").checked = (parseInt(row.activo||0) === 1);
    }else{
      $("#mdlTitle").textContent = "Nuevo Estado";
      $("#f_id_estado").value = "";
      $("#f_nombre").value = "";
      $("#f_semaforo").value = "VERDE";
      $("#f_activo").checked = true;
    }

    const modal = new bootstrap.Modal(document.getElementById("mdl"));
    modal.show();
  }

  async function guardar(){
    const id_estado = ($("#f_id_estado").value || "").trim();
    const nombre = ($("#f_nombre").value || "").trim();
    const semaforo = ($("#f_semaforo").value || "VERDE").toUpperCase();
    const activo = $("#f_activo").checked ? 1 : 0;

    if(!nombre){
      $("#mdlErr").textContent = "El nombre es obligatorio.";
      $("#mdlErr").classList.remove("d-none");
      return;
    }

    try{
      $("#btnGuardar").disabled = true;

      if(id_estado){
        await api("update", { id_estado, nombre, semaforo, activo });
      }else{
        await api("create", { nombre, semaforo, activo });
      }

      bootstrap.Modal.getInstance(document.getElementById("mdl")).hide();
      await load();
    }catch(err){
      console.error(err);
      $("#mdlErr").textContent = err.message || "Error guardando";
      $("#mdlErr").classList.remove("d-none");
    }finally{
      $("#btnGuardar").disabled = false;
    }
  }

  async function eliminar(id_estado){
    if(!confirm("¿Eliminar (softdelete) este estado?")) return;
    try{
      await api("delete", { id_estado });
      await load();
    }catch(err){
      console.error(err);
      alert(err.message || "No se pudo eliminar");
    }
  }

  function exportCsv(){
    const header = ["id_estado","nombre","semaforo","activo"];
    const lines = [header.join(",")].concat(
      ROWS.map(r => [
        (r.id_estado ?? r.id ?? ""),
        `"${(r.nombre ?? "").toString().replaceAll('"','""')}"`,
        (r.semaforo ?? "VERDE"),
        (parseInt(r.activo||0)===1 ? 1 : 0)
      ].join(","))
    );
    const blob = new Blob([lines.join("\n")], { type:"text/csv;charset=utf-8" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "c_activo_estado.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  async function importCsv(file){
    const text = await file.text();
    const rows = text.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
    if(rows.length < 2) return;

    // espera encabezado: id_estado,nombre,semaforo,activo (id puede venir vacío)
    const data = rows.slice(1).map(line => {
      // parse simple csv (soporta comillas en nombre)
      const parts = [];
      let cur = "", inQ = false;
      for(let i=0;i<line.length;i++){
        const ch = line[i];
        if(ch === '"'){
          if(inQ && line[i+1] === '"'){ cur += '"'; i++; }
          else inQ = !inQ;
        } else if(ch === "," && !inQ){
          parts.push(cur); cur = "";
        } else cur += ch;
      }
      parts.push(cur);

      return {
        id_estado: (parts[0]||"").trim(),
        nombre: (parts[1]||"").trim(),
        semaforo: ((parts[2]||"VERDE").trim() || "VERDE").toUpperCase(),
        activo: (parts[3]||"1").trim() === "0" ? 0 : 1
      };
    }).filter(r => r.nombre);

    // estrategia: upsert por nombre (si tu API lo maneja), o por id si viene
    for(const r of data){
      try{
        if(r.id_estado){
          await api("update", r);
        }else{
          await api("create", { nombre:r.nombre, semaforo:r.semaforo, activo:r.activo });
        }
      }catch(e){
        console.warn("Import error", r, e);
      }
    }
    await load();
  }

  // Eventos
  $("#btnRefresh").addEventListener("click", load);
  $("#btnNuevo").addEventListener("click", () => openModal(null));
  $("#btnGuardar").addEventListener("click", guardar);
  $("#btnExport").addEventListener("click", exportCsv);
  txtBuscar.addEventListener("input", render);

  $("#fileCsv").addEventListener("change", async (ev) => {
    const f = ev.target.files && ev.target.files[0];
    if(!f) return;
    await importCsv(f);
    ev.target.value = "";
  });

  tbody.addEventListener("click", (ev) => {
    const btnEdit = ev.target.closest("[data-edit]");
    const btnDel  = ev.target.closest("[data-del]");
    if(btnEdit){
      const id = btnEdit.getAttribute("data-edit");
      const row = ROWS.find(r => String(r.id_estado ?? r.id ?? "") === String(id));
      openModal(row || null);
    }
    if(btnDel){
      const id = btnDel.getAttribute("data-del");
      eliminar(id);
    }
  });

  // Init
  load();
})();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
