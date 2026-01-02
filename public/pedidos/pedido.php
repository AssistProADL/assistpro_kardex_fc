<?php
// public/pedidos/pedido.php
// NO session_start (por instrucción)

$title = "Registro de Pedidos";
require_once __DIR__ . "/../bi/_menu_global.php";
?>

<style>
  .ap-form label { font-size: 12px; }
  .ap-form .form-control, .ap-form .form-select { font-size: 13px; }
  .ap-card { border: 1px solid #e6e9ef; border-radius: 10px; }
  .ap-card .card-header { background: #f7f9fc; border-bottom: 1px solid #e6e9ef; }
  .ap-help { font-size: 12px; color: #6b7280; }
  .ap-chip { display:inline-block; padding:2px 8px; border-radius: 999px; font-size: 12px; background:#eef2ff; color:#1e40af; }
  .ap-table { font-size: 12px; }
  .ap-table td, .ap-table th { vertical-align: middle; }
  .ap-actions a { font-size: 12px; text-decoration:none; }
  .ap-muted { color:#6b7280; font-size:12px; }
  .ap-kpi { font-size: 12px; color:#334155; }
  .ap-kpi strong { font-size: 16px; }
</style>

<div class="container-fluid py-3 ap-form">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">Registro de Pedidos</h4>
      <div class="ap-muted">Captura operativa rápida vía APIs (sin cargas pesadas).</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnLimpiar">Limpiar</button>
      <button class="btn btn-primary btn-sm" id="btnGuardar">Guardar Pedido</button>
    </div>
  </div>

  <!-- 1 Empresa / Almacén -->
  <div class="card ap-card mb-3">
    <div class="card-header">
      <strong>1. Empresa y Almacén</strong>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Empresa <span class="text-danger">*</span></label>
          <select class="form-select" id="empresa">
            <option value="">Cargando...</option>
          </select>
          <div class="ap-help" id="empresaHelp"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Almacén <span class="text-danger">*</span></label>
          <select class="form-select" id="almacen">
            <option value="">Seleccione empresa primero</option>
          </select>
          <div class="ap-help" id="almacenHelp"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2 Pedido externo -->
  <div class="card ap-card mb-3">
    <div class="card-header">
      <strong>2. Pedido Externo</strong>
    </div>
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Folio</label>
          <input type="text" class="form-control" id="folio" value="(sin guardar)" readonly>
        </div>

        <div class="col-md-5">
          <label class="form-label">Cliente / Destinatario <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="cliente_buscar" placeholder="Buscar cliente (código/razón social/RFC)">
          <input type="hidden" id="id_destinatario" value="">
          <div class="ap-help" id="clienteHelp">Escribe al menos 2 caracteres.</div>
          <div class="list-group mt-1" id="clienteResultados" style="max-height: 240px; overflow:auto; display:none;"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Usuario que solicita</label>
          <select class="form-select" id="usuario_solicita">
            <option value="WEB" selected>WEB</option>
            <option value="WMS">WMS</option>
            <option value="SFA">SFA</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Prioridad</label>
          <select class="form-select" id="prioridad">
            <option value="3" selected>3 - Normal</option>
            <option value="2">2 - Alta</option>
            <option value="1">1 - Urgente</option>
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label">Nombre Cliente</label>
          <input type="text" class="form-control" id="nombre_cliente" value="Sin Cliente" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Fecha de entrega solicitada <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="fecha_entrega" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Tipo de Venta</label>
          <div class="d-flex gap-3 mt-1">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo_venta" id="tv_venta" value="VENTA">
              <label class="form-check-label" for="tv_venta">Venta</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo_venta" id="tv_preventa" value="PREVENTA" checked>
              <label class="form-check-label" for="tv_preventa">Pre Venta</label>
            </div>
          </div>
        </div>

        <div class="col-md-2">
          <label class="form-label">Horario Desde</label>
          <input type="time" class="form-control" id="hora_desde">
        </div>

        <div class="col-md-2">
          <label class="form-label">Horario Hasta</label>
          <input type="time" class="form-control" id="hora_hasta">
        </div>

        <div class="col-md-5">
          <label class="form-label">Dirección de Entrega <span class="text-danger">*</span></label>
          <select class="form-select" id="direccion_entrega">
            <option value="">Seleccione cliente primero</option>
          </select>
          <div class="ap-help">Dirección asociada al cliente para envío.</div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Contacto</label>
          <select class="form-select" id="contacto">
            <option value="">Cargando...</option>
          </select>
          <div class="ap-help" id="contactoHelp"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Observaciones</label>
          <textarea class="form-control" id="observaciones" rows="2" placeholder="Notas operativas / comerciales"></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- 3 Pedido interno (placeholder operativo) -->
  <div class="card ap-card mb-3">
    <div class="card-header">
      <strong>3. Pedido Interno</strong> <span class="ap-chip ms-2">pendiente rutas/vendedores</span>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Ruta Venta | Preventa</label>
          <select class="form-select" id="ruta">
            <option value="">(pendiente API rutas)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Vendedor / Agente</label>
          <select class="form-select" id="vendedor">
            <option value="">(pendiente API vendedores)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Día Operativo</label>
          <input type="text" class="form-control" id="dia_operativo" placeholder="Ej. Lunes">
        </div>
      </div>
      <div class="ap-help mt-2">
        Pedido interno = solicitud de stock para ruta (fase siguiente). Hoy nos enfocamos en Pedido Externo para registrar ya.
      </div>
    </div>
  </div>

  <!-- 4 Detalle -->
  <div class="card ap-card mb-3">
    <div class="card-header">
      <strong>4. Registro del Detalle</strong>
    </div>
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Artículo <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="articulo_buscar" placeholder="Buscar artículo (clave/descr.)">
          <div class="ap-help" id="articuloHelp">Escribe al menos 2 caracteres. Filtra por almacén seleccionado.</div>
          <div class="list-group mt-1" id="articuloResultados" style="max-height: 240px; overflow:auto; display:none;"></div>
          <input type="hidden" id="articulo_id" value="">
          <input type="hidden" id="articulo_cve" value="">
          <input type="hidden" id="articulo_desc" value="">
        </div>

        <div class="col-md-2">
          <label class="form-label">Cantidad <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="cantidad" min="1" step="1" value="1">
        </div>

        <div class="col-md-2">
          <label class="form-label">Precio Unitario</label>
          <input type="number" class="form-control" id="precio" min="0" step="0.01" value="0.00">
        </div>

        <div class="col-md-2 d-grid">
          <button class="btn btn-outline-primary" id="btnAddDetalle">Agregar</button>
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="ap-kpi">
          Líneas: <strong id="kpiLineas">0</strong> &nbsp; | &nbsp;
          Importe: <strong id="kpiImporte">0.00</strong>
        </div>
        <button class="btn btn-outline-danger btn-sm" id="btnVaciar">Vaciar detalle</button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-striped ap-table" id="tablaDetalle">
          <thead>
            <tr>
              <th style="width:80px;">Acciones</th>
              <th>Clave</th>
              <th>Descripción</th>
              <th style="width:120px;" class="text-end">Cantidad</th>
              <th style="width:140px;" class="text-end">Precio</th>
              <th style="width:160px;" class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="6" class="text-center ap-muted">Sin líneas</td></tr>
          </tbody>
        </table>
      </div>

      <div class="ap-help">
        Estándar AssistPro: acciones a la izquierda, vista ligera, sin precarga masiva.
      </div>
    </div>
  </div>

</div>

<script>
(function(){
  // ========= Config endpoints =========
  const API_EMPRESAS  = "../api/empresas_api.php?action=list";
  const API_ALMACENES = "../api/filtros_almacenes.php?action=almacenes"; // + &cve_cia=
  const API_DESTINAT  = "../api/destinatarios.php"; // tu API existente
  const API_CONTACTOS = "../api/api_contactos.php?action=list&start=0&length=5000";
  const API_ARTICULOS = "../api/pedidos/articulos_api.php"; // TU PATH NUEVO

  // Guardado (si ya tienes endpoint, aquí lo conectamos)
  const API_PEDIDOS   = "../api/pedidos/pedidos_api.php"; // action=guardar (o el que uses)

  // ========= Helpers =========
  const $ = (id)=>document.getElementById(id);

  function money(v){
    return (Number(v||0)).toFixed(2);
  }

  async function fetchJSON(url){
    const r = await fetch(url, {cache:"no-store"});
    const t = await r.text();
    try { return JSON.parse(t); }
    catch(e){
      throw new Error("Respuesta no JSON: " + t.substring(0,180));
    }
  }

  function showList(id, show){
    $(id).style.display = show ? "block" : "none";
  }

  function debounce(fn, ms){
    let t=null;
    return (...args)=>{
      clearTimeout(t);
      t=setTimeout(()=>fn(...args), ms);
    };
  }

  // ========= State =========
  let detalle = [];

  // ========= Load empresas =========
  async function loadEmpresas(){
    try{
      const j = await fetchJSON(API_EMPRESAS);
      const data = j.data || j.rows || j.result || [];
      const sel = $("empresa");
      sel.innerHTML = `<option value="">Seleccione</option>`;
      data.forEach(x=>{
        // empresas_api.php trae: cve_cia, des_cia (según tu screenshot)
        const id = x.cve_cia ?? x.id ?? x.clave_empresa ?? "";
        const name = x.des_cia ?? x.nombre ?? x.razon_social ?? ("Empresa " + id);
        sel.insertAdjacentHTML("beforeend", `<option value="${id}">${name}</option>`);
      });
      $("empresaHelp").textContent = data.length ? (data.length + " empresas") : "Sin empresas";
    }catch(e){
      $("empresa").innerHTML = `<option value="">Error</option>`;
      $("empresaHelp").textContent = e.message;
    }
  }

  // ========= Load almacenes (por empresa) =========
  async function loadAlmacenes(){
    const cve_cia = $("empresa").value;
    const sel = $("almacen");
    sel.innerHTML = `<option value="">Cargando...</option>`;
    $("almacenHelp").textContent = "";

    if(!cve_cia){
      sel.innerHTML = `<option value="">Seleccione empresa primero</option>`;
      return;
    }
    try{
      const j = await fetchJSON(API_ALMACENES + "&cve_cia=" + encodeURIComponent(cve_cia));
      const data = j.data || [];
      sel.innerHTML = `<option value="">Seleccione</option>`;
      data.forEach(x=>{
        const id = x.almacenp_id ?? x.id ?? "";
        const clave = x.clave ?? "";
        const nombre = x.nombre ?? "";
        sel.insertAdjacentHTML("beforeend", `<option value="${id}">(${clave}) - ${nombre}</option>`);
      });
      $("almacenHelp").textContent = data.length ? (data.length + " almacenes") : "Sin almacenes para esta empresa";
    }catch(e){
      sel.innerHTML = `<option value="">Error</option>`;
      $("almacenHelp").textContent = e.message;
    }
  }

  // ========= Load contactos =========
  async function loadContactos(){
    try{
      const j = await fetchJSON(API_CONTACTOS);
      const data = j.data || j.rows || [];
      const sel = $("contacto");
      sel.innerHTML = `<option value="">Seleccione</option>`;
      data.forEach(x=>{
        const id = x.id ?? x.Id ?? x.id_contacto ?? "";
        const name = x.nombre ?? x.Nombre ?? x.contacto ?? ("Contacto " + id);
        sel.insertAdjacentHTML("beforeend", `<option value="${id}">${name}</option>`);
      });
      $("contactoHelp").textContent = data.length ? (data.length + " contactos") : "Sin contactos";
    }catch(e){
      $("contacto").innerHTML = `<option value="">Error</option>`;
      $("contactoHelp").textContent = e.message;
    }
  }

  // ========= Buscar destinatarios =========
  const buscarDestinatarios = debounce(async ()=>{
    const q = $("cliente_buscar").value.trim();
    if(q.length < 2){
      showList("clienteResultados", false);
      $("clienteHelp").textContent = "Escribe al menos 2 caracteres.";
      return;
    }

    try{
      // Tu destinatarios.php típicamente soporta search/paging; si no, esto igual funciona si regresa data
      const url = API_DESTINAT + "?draw=1&start=0&length=50&search[value]=" + encodeURIComponent(q);
      const j = await fetchJSON(url);
      const data = j.data || [];
      const box = $("clienteResultados");
      box.innerHTML = "";
      data.slice(0,50).forEach(x=>{
        const id = x.id_destinatario ?? x.id ?? "";
        const cve = x.Cve_Clte ?? x.cve_clte ?? "";
        const rz  = x.razonsocial ?? x.nombre ?? "";
        const dir = (x.direccion ?? "") + " " + (x.colonia ?? "") + " " + (x.ciudad ?? "");
        const item = document.createElement("button");
        item.type="button";
        item.className="list-group-item list-group-item-action";
        item.innerHTML = `<div><strong>${cve}</strong> - ${rz}</div><div class="ap-muted">${dir}</div>`;
        item.addEventListener("click", ()=>{
          $("id_destinatario").value = id;
          $("cliente_buscar").value = `${cve} - ${rz}`;
          $("nombre_cliente").value = rz || "Cliente";
          showList("clienteResultados", false);
          loadDireccionesCliente(x);
        });
        box.appendChild(item);
      });

      $("clienteHelp").textContent = data.length ? (data.length + " resultados") : "Sin resultados";
      showList("clienteResultados", true);
    }catch(e){
      $("clienteHelp").textContent = e.message;
      showList("clienteResultados", false);
    }
  }, 280);

  // Direcciones: por ahora usa el registro seleccionado (y si tu API trae más, se amplía después)
  function loadDireccionesCliente(x){
    const sel = $("direccion_entrega");
    sel.innerHTML = "";

    // Si tu API trae varias direcciones en un array, aquí se enchufa:
    // x.direcciones = [...]
    if(Array.isArray(x.direcciones) && x.direcciones.length){
      sel.insertAdjacentHTML("beforeend", `<option value="">Seleccione</option>`);
      x.direcciones.forEach((d,i)=>{
        const label = d.label ?? d.direccion ?? ("Dirección " + (i+1));
        sel.insertAdjacentHTML("beforeend", `<option value="${d.id ?? i}">${label}</option>`);
      });
      return;
    }

    // Fallback: dirección del registro
    const label = [x.direccion, x.colonia, x.ciudad, x.postal].filter(Boolean).join(", ");
    sel.insertAdjacentHTML("beforeend", `<option value="1">${label || "Dirección cliente"}</option>`);
  }

  // ========= Buscar artículos =========
  const buscarArticulos = debounce(async ()=>{
    const q = $("articulo_buscar").value.trim();
    const almacen_id = $("almacen").value;

    if(!almacen_id){
      $("articuloHelp").textContent = "Selecciona un almacén para filtrar artículos.";
      showList("articuloResultados", false);
      return;
    }
    if(q.length < 2){
      $("articuloHelp").textContent = "Escribe al menos 2 caracteres.";
      showList("articuloResultados", false);
      return;
    }

    try{
      const url = API_ARTICULOS + "?q=" + encodeURIComponent(q) + "&almacen_id=" + encodeURIComponent(almacen_id) + "&limit=50";
      const j = await fetchJSON(url);

      // Tu articulos_api.php nuevo parece devolver array directo (por screenshot), o {ok:true,data:[]}
      const data = Array.isArray(j) ? j : (j.data || j.rows || []);
      const box = $("articuloResultados");
      box.innerHTML = "";

      data.slice(0,50).forEach(x=>{
        const id   = x.id ?? x.Id ?? "";
        const cve  = x.cve ?? x.Cve_Articulo ?? x.cve_articulo ?? "";
        const desc = x.descripcion ?? x.des_ssgpoort ?? x.Descripcion ?? "";
        const precio = x.precio ?? x.Precio ?? 0;

        const item = document.createElement("button");
        item.type="button";
        item.className="list-group-item list-group-item-action";
        item.innerHTML = `<div><strong>${cve}</strong> - ${desc}</div>
                          <div class="ap-muted">Precio: ${money(precio)}</div>`;
        item.addEventListener("click", ()=>{
          $("articulo_id").value = id;
          $("articulo_cve").value = cve;
          $("articulo_desc").value = desc;
          $("articulo_buscar").value = `${cve} - ${desc}`;
          if(Number($("precio").value||0) === 0 && Number(precio||0) > 0){
            $("precio").value = money(precio);
          }
          showList("articuloResultados", false);
        });
        box.appendChild(item);
      });

      $("articuloHelp").textContent = data.length ? (data.length + " resultados") : "Sin resultados";
      showList("articuloResultados", true);
    }catch(e){
      $("articuloHelp").textContent = e.message;
      showList("articuloResultados", false);
    }
  }, 280);

  // ========= Detalle handlers =========
  function renderDetalle(){
    const tb = $("tablaDetalle").querySelector("tbody");
    tb.innerHTML = "";

    if(!detalle.length){
      tb.innerHTML = `<tr><td colspan="6" class="text-center ap-muted">Sin líneas</td></tr>`;
      $("kpiLineas").textContent = "0";
      $("kpiImporte").textContent = "0.00";
      return;
    }

    let total = 0;
    detalle.forEach((d, idx)=>{
      const sub = Number(d.cantidad) * Number(d.precio);
      total += sub;

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="ap-actions">
          <a href="#" data-del="${idx}" class="text-danger">Eliminar</a>
        </td>
        <td>${d.cve}</td>
        <td>${d.desc}</td>
        <td class="text-end">${d.cantidad}</td>
        <td class="text-end">${money(d.precio)}</td>
        <td class="text-end">${money(sub)}</td>
      `;
      tb.appendChild(tr);
    });

    tb.querySelectorAll("[data-del]").forEach(a=>{
      a.addEventListener("click", (ev)=>{
        ev.preventDefault();
        const i = Number(a.getAttribute("data-del"));
        detalle.splice(i,1);
        renderDetalle();
      });
    });

    $("kpiLineas").textContent = String(detalle.length);
    $("kpiImporte").textContent = money(total);
  }

  function addLinea(){
    const id = $("articulo_id").value;
    const cve = $("articulo_cve").value;
    const desc = $("articulo_desc").value;
    const cant = Number($("cantidad").value || 0);
    const prec = Number($("precio").value || 0);

    if(!id || !cve){
      alert("Selecciona un artículo.");
      return;
    }
    if(!cant || cant <= 0){
      alert("Cantidad inválida.");
      return;
    }

    // Si ya existe, sumamos cantidad (operación elegante)
    const ex = detalle.find(x => x.cve === cve);
    if(ex){
      ex.cantidad = Number(ex.cantidad) + cant;
      if(prec > 0) ex.precio = prec;
    }else{
      detalle.push({ id, cve, desc, cantidad: cant, precio: prec });
    }

    $("articulo_id").value = "";
    $("articulo_cve").value = "";
    $("articulo_desc").value = "";
    $("articulo_buscar").value = "";
    $("cantidad").value = 1;

    renderDetalle();
  }

  // ========= Guardar =========
  async function guardarPedido(){
    // Validación mínima operativa
    const empresa = $("empresa").value;
    const almacen = $("almacen").value;
    const id_dest = $("id_destinatario").value;
    const fecha = $("fecha_entrega").value;

    if(!empresa){ alert("Selecciona empresa."); return; }
    if(!almacen){ alert("Selecciona almacén."); return; }
    if(!id_dest){ alert("Selecciona cliente/destinatario."); return; }
    if(!fecha){ alert("Selecciona fecha de entrega."); return; }
    if(!detalle.length){ alert("Agrega al menos una línea al detalle."); return; }

    const payload = {
      empresa,
      almacen,
      id_destinatario: id_dest,
      usuario_solicita: $("usuario_solicita").value,
      prioridad: $("prioridad").value,
      tipo_venta: document.querySelector('input[name="tipo_venta"]:checked')?.value || "PREVENTA",
      fecha_entrega: fecha,
      hora_desde: $("hora_desde").value,
      hora_hasta: $("hora_hasta").value,
      direccion_entrega: $("direccion_entrega").value,
      contacto: $("contacto").value,
      observaciones: $("observaciones").value,
      detalle
    };

    try{
      // Ajusta el action a tu API real cuando confirmes nombre.
      // Este POST no rompe nada y te deja listo para conectar.
      const r = await fetch(API_PEDIDOS + "?action=guardar", {
        method: "POST",
        headers: {"Content-Type":"application/json; charset=utf-8"},
        body: JSON.stringify(payload)
      });
      const t = await r.text();
      let j;
      try{ j = JSON.parse(t); } catch(e){ throw new Error("Respuesta no JSON: " + t.substring(0,180)); }

      if(!j.ok){
        alert("No se pudo guardar: " + (j.error || "Error"));
        return;
      }

      // Si tu API devuelve folio
      if(j.folio) $("folio").value = j.folio;

      alert("Pedido guardado correctamente.");
    }catch(e){
      alert("Error guardando: " + e.message);
    }
  }

  // ========= Limpiar =========
  function limpiar(){
    $("folio").value = "(sin guardar)";
    $("cliente_buscar").value = "";
    $("id_destinatario").value = "";
    $("nombre_cliente").value = "Sin Cliente";
    $("direccion_entrega").innerHTML = `<option value="">Seleccione cliente primero</option>`;
    $("observaciones").value = "";
    detalle = [];
    renderDetalle();
  }

  // ========= Events =========
  $("empresa").addEventListener("change", loadAlmacenes);
  $("cliente_buscar").addEventListener("input", buscarDestinatarios);
  $("articulo_buscar").addEventListener("input", buscarArticulos);
  $("btnAddDetalle").addEventListener("click", (e)=>{ e.preventDefault(); addLinea(); });
  $("btnVaciar").addEventListener("click", (e)=>{ e.preventDefault(); detalle = []; renderDetalle(); });
  $("btnLimpiar").addEventListener("click", (e)=>{ e.preventDefault(); limpiar(); });
  $("btnGuardar").addEventListener("click", (e)=>{ e.preventDefault(); guardarPedido(); });

  // Click fuera para cerrar listas
  document.addEventListener("click", (ev)=>{
    const cr = $("clienteResultados");
    const ar = $("articuloResultados");
    if(!cr.contains(ev.target) && ev.target !== $("cliente_buscar")) showList("clienteResultados", false);
    if(!ar.contains(ev.target) && ev.target !== $("articulo_buscar")) showList("articuloResultados", false);
  });

  // ========= Init =========
  (async function init(){
    renderDetalle();
    await loadEmpresas();
    await loadContactos();
    // almacenes se cargan al seleccionar empresa
  })();

})();
</script>

<?php require_once __DIR__ . "/../bi/_menu_global_end.php"; ?>
