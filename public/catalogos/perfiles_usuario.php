<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* mismo CSS base */
  .ap-container {
    padding: 12px;
    font-size: 12px
  }

  .ap-title {
    font-size: 18px;
    font-weight: 600;
    color: #0b5ed7;
    margin-bottom: 10px
  }

  .ap-toolbar {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 8px
  }

  .ap-btn {
    border: 1px solid #d0d7e2;
    background: #fff;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    cursor: pointer
  }

  .ap-btn.primary {
    background: #0b5ed7;
    color: #fff;
    border-color: #0b5ed7
  }

  .ap-btn.ok {
    background: #198754;
    color: #fff;
    border-color: #198754
  }

  .ap-search {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d0d7e2;
    border-radius: 8px;
    padding: 6px 10px;
    background: #fff
  }

  .ap-search i {
    color: #0b5ed7
  }

  .ap-search input {
    border: 0;
    outline: 0;
    font-size: 12px;
    width: 280px
  }

  .ap-grid {
    border: 1px solid #dcdcdc;
    height: 500px;
    overflow: auto
  }

  .ap-grid table {
    width: 100%;
    border-collapse: collapse
  }

  .ap-grid th {
    position: sticky;
    top: 0;
    background: #f4f6fb;
    padding: 6px;
    border-bottom: 1px solid #ccc;
    white-space: nowrap
  }

  .ap-grid td {
    padding: 5px;
    border-bottom: 1px solid #eee;
    white-space: nowrap;
    vertical-align: middle
  }

  .ap-actions i {
    cursor: pointer;
    margin-right: 8px;
    color: #0b5ed7
  }

  .ap-actions i.fa-trash {
    color: #dc3545
  }

  .ap-actions i.fa-undo {
    color: #198754
  }

  .ap-chip {
    font-size: 11px;
    background: #eef2ff;
    color: #1e3a8a;
    border-radius: 10px;
    padding: 2px 8px
  }

  .ap-chip.ok {
    background: #d1e7dd;
    color: #0f5132
  }

  .ap-chip.warn {
    background: #fff3cd;
    color: #664d03
  }

  .ap-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    z-index: 9999
  }

  .ap-modal-content {
    background: #fff;
    width: 980px;
    margin: 2.5% auto;
    padding: 15px;
    border-radius: 10px
  }

  .ap-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px
  }

  .ap-modal-title {
    font-size: 16px;
    font-weight: 700;
    color: #0b5ed7
  }

  .ap-x {
    cursor: pointer;
    font-size: 18px
  }

  .ap-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px
  }

  .ap-field {
    display: flex;
    flex-direction: column;
    gap: 4px
  }

  .ap-label {
    font-weight: 600
  }

  .ap-input {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d0d7e2;
    border-radius: 6px;
    padding: 6px 8px;
    background: #fff
  }

  .ap-input i {
    color: #0b5ed7;
    min-width: 14px
  }

  .ap-input input,
  .ap-input select {
    border: 0;
    outline: 0;
    width: 100%;
    font-size: 12px;
    background: transparent
  }

  .ap-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px
  }

  .ap-err {
    display: none;
    background: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 8px
  }

  .ap-drop {
    border: 1px dashed #9aa7b7;
    border-radius: 10px;
    padding: 10px;
    background: #f8fafc
  }

  .ap-pre {
    max-height: 260px;
    overflow: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px;
    background: #fff
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-id-badge"></i> Catálogo de Perfiles de Usuario</div>

  <div class="ap-toolbar">
    <button class="ap-btn primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-btn" onclick="toggleInactivos()" id="btnInact"><i class="fa fa-eye"></i> Ver inactivos</button>
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por empresa, clave o nombre..." onkeyup="buscar(event)">
    </div>
    <button class="ap-btn" onclick="cargar()"><i class="fa fa-rotate"></i> Refrescar</button>
    <button class="ap-btn" onclick="exportarDatos()"><i class="fa fa-file-export"></i> Exportar CSV</button>
    <button class="ap-btn ok" onclick="abrirImport()"><i class="fa fa-file-import"></i> Importar CSV</button>
    <button class="ap-btn" onclick="descargarLayout()"><i class="fa fa-download"></i> Layout</button>
  </div>

  <div class="ap-err" id="errBox"></div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th style="width:90px">Acciones</th>
          <th>Empresa</th>
          <th>Clave Perfil</th>
          <th>Nombre Perfil</th>
          <th>Vigencia</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
</div>

<!-- Modal CRUD -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div class="ap-modal-head">
      <div class="ap-modal-title" id="mdlTitle">
        <i class="fa fa-id-badge"></i> Perfil
      </div>
      <div class="ap-x" onclick="cerrarModal('mdl')">
        <i class="fa fa-times"></i>
      </div>
    </div>

    <div class="ap-err" id="mdlErr"></div>

    <!-- 🔥 PK AUTOINCREMENT -->
    <input type="hidden" id="id_perfil">

    <div class="ap-form">

      <!-- Empresa -->
      <div class="ap-field">
        <div class="ap-label">Empresa *</div>
        <div class="ap-input">
          <i class="fa fa-building"></i>
          <select id="id_compania"></select>
        </div>
      </div>

      <!-- Clave Perfil -->
      <div class="ap-field">
        <div class="ap-label">Clave Perfil *</div>
        <div class="ap-input">
          <i class="fa fa-key"></i>
          <input id="clave_perfil" placeholder="Ej. ADM, OPR, VEN">
        </div>
      </div>

      <!-- Nombre Perfil -->
      <div class="ap-field">
        <div class="ap-label">Nombre Perfil *</div>
        <div class="ap-input">
          <i class="fa fa-font"></i>
          <input id="nombre_perfil" placeholder="ADMINISTRADOR">
        </div>
      </div>

      <!-- Inicio Vigencia -->
      <div class="ap-field">
        <div class="ap-label">Inicio Vigencia</div>
        <div class="ap-input">
          <i class="fa fa-calendar"></i>
          <input type="date" id="inicio_perfil">
        </div>
      </div>

      <!-- Fin Vigencia -->
      <div class="ap-field">
        <div class="ap-label">Fin Vigencia</div>
        <div class="ap-input">
          <i class="fa fa-calendar"></i>
          <input type="date" id="fin_perfil">
        </div>
      </div>

      <!-- Activo -->
      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input">
          <i class="fa fa-toggle-on"></i>
          <select id="activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>

    </div>

    <div class="ap-foot">
      <button class="ap-btn" onclick="cerrarModal('mdl')">
        Cerrar
      </button>
      <button class="ap-btn primary" onclick="guardar()">
        <i class="fa fa-save"></i> Guardar
      </button>
    </div>
  </div>
</div>

<!-- Modal Import -->
<div class="ap-modal" id="mdlImp">
  <div class="ap-modal-content">
    <div class="ap-modal-head">
      <div class="ap-modal-title"><i class="fa fa-file-import"></i> Importar Perfiles (CSV)</div>
      <div class="ap-x" onclick="cerrarModal('mdlImp')"><i class="fa fa-times"></i></div>
    </div>

    <div class="ap-err" id="impErr"></div>

    <div class="ap-toolbar" style="margin-bottom:10px">
      <button class="ap-btn" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
      <button class="ap-btn" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
      <button class="ap-btn primary" onclick="importarCsv()"><i class="fa fa-cloud-upload"></i> Importar</button>
    </div>

    <div class="ap-drop">
      <div><b>Archivo CSV</b> (UTF-8). Debe respetar el layout.</div>
      <div style="margin-top:8px"><input type="file" id="csvFile" accept=".csv,text/csv"></div>
    </div>

    <div style="margin-top:10px">
      <div class="ap-label">Vista previa</div>
      <div class="ap-pre" id="impPreview">(sin datos)</div>
    </div>

    <div class="ap-foot">
      <button class="ap-btn" onclick="cerrarModal('mdlImp')">Cerrar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/perfiles_usuario.php';
  const KPI = '../api/perfiles_usuario_kpi.php';

  let verInactivos = false;
  let qLast = '';

  function showError(id, msg) {
    const el = document.getElementById(id);
    if (!msg) {
      el.style.display = 'none';
      el.innerHTML = '';
      return;
    }
    el.style.display = 'block';
    el.innerHTML = msg;
  }

  function hideErrors() {
    showError('errBox', '');
    showError('mdlErr', '');
    showError('impErr', '');
  }

  function toggleInactivos() {
    verInactivos = !verInactivos;
    btnInact.innerHTML = verInactivos ? '<i class="fa fa-eye-slash"></i> Ocultar inactivos' : '<i class="fa fa-eye"></i> Ver inactivos';
    cargar();
  }

  function buscar(ev) {
    if (ev && ev.key && ev.key !== 'Enter') return;
    qLast = (q.value || '').trim();
    cargar();
  }

  function cargar() {
    hideErrors();

    fetch(API + '?action=list&inactivos=' + (verInactivos ? 1 : 0) + '&q=' + encodeURIComponent(qLast || ''))
      .then(r => r.json())
      .then(resp => {

        if (resp.error) {
          showError('errBox', resp.error);
          return;
        }

        const rows = resp.rows || [];
        let h = '';

        rows.forEach(c => {

          const activo = (String(c.activo || '1') === '1') ?
            '<span class="ap-chip ok">Activo</span>' :
            '<span class="ap-chip warn">Inactivo</span>';

          const vigencia =
            (c.inicio_perfil || '-') +
            ' / ' +
            (c.fin_perfil || '-');

          h += `<tr>
          <td class="ap-actions">
            ${(String(c.activo || '1') === '1' && !verInactivos)
              ? `<i class="fa fa-edit" title="Editar" onclick="editar(${c.id_perfil})"></i>
                 <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${c.id_perfil})"></i>`
              : `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${c.id_perfil})"></i>`}
          </td>
          <td>${c.clave_empresa || ''} - ${c.des_cia || ''}</td>
          <td><b>${c.clave_perfil || ''}</b></td>
          <td>${c.nombre_perfil || ''}</td>
          <td>${vigencia}</td>
          <td>${activo}</td>
        </tr>`;
        });

        tb.innerHTML = h || '<tr><td colspan="6" style="padding:10px;color:#6c757d">Sin registros</td></tr>';
      });
  }

  function cargarCompanias() {
    fetch('../api/empresas.php?action=list')
      .then(r => r.json())
      .then(resp => {

        if (resp.error) {
          console.error(resp.error);
          return;
        }

        const rows = resp.rows || [];
        let h = '<option value="">Seleccione...</option>';

        rows.forEach(c => {
          h += `<option value="${c.cve_cia}">
                ${c.clave_empresa} - ${c.des_cia}
              </option>`;
        });

        id_compania.innerHTML = h;
      });
  }


  function validar() {
    hideErrors();

    const d = [];
    const comp = id_compania.value;
    const clave = clave_perfil.value.trim();
    const nombre = nombre_perfil.value.trim();

    if (!comp) d.push('Empresa obligatoria.');
    if (!clave) d.push('Clave perfil obligatoria.');
    if (!nombre) d.push('Nombre perfil obligatorio.');

    if (d.length) {
      showError('mdlErr', 'Validación:<br>- ' + d.join('<br>- '));
      return false;
    }

    return true;
  }

  function nuevo() {
    hideErrors();

    mdlTitle.innerHTML = '<i class="fa fa-id-badge"></i> Nuevo Perfil';

    id_perfil.value = '';
    id_compania.value = '';
    clave_perfil.value = '';
    nombre_perfil.value = '';
    inicio_perfil.value = '';
    fin_perfil.value = '';
    activo.value = '1';

    abrirModal('mdl');
  }

  function editar(id) {
    hideErrors();

    fetch(API + '?action=get&id_perfil=' + id)
      .then(r => r.json())
      .then(c => {

        if (c.error) {
          showError('errBox', c.error);
          return;
        }

        mdlTitle.innerHTML = '<i class="fa fa-id-badge"></i> Editar Perfil';

        id_perfil.value = c.id_perfil || '';
        clave_perfil.value = c.clave_perfil || '';
        nombre_perfil.value = c.nombre_perfil || '';
        id_compania.value = c.id_compania || '';
        activo.value = String(c.activo || '1');
        inicio_perfil.value = c.inicio_perfil || '';
        fin_perfil.value = c.fin_perfil || '';

        abrirModal('mdl');
      });
  }

  function guardar() {
    if (!validar()) return;

    const fd = new FormData();

    fd.append('action', id_perfil.value ? 'update' : 'create');

    fd.append('id_perfil', id_perfil.value);
    fd.append('id_compania', id_compania.value);
    fd.append('clave_perfil', clave_perfil.value);
    fd.append('nombre_perfil', nombre_perfil.value);
    fd.append('inicio_perfil', inicio_perfil.value);
    fd.append('fin_perfil', fin_perfil.value);
    fd.append('activo', activo.value);

    fetch(API, {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.error) {
          alert(resp.error);
          return;
        }

        cerrarModal('mdl');
        cargar();
      });
  }

  function eliminar(id) {
    if (!confirm('¿Inactivar perfil?')) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id_perfil', id);

    fetch(API, {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.error) alert(resp.error);
        cargar();
      });
  }

  function recuperar(id) {
    if (!confirm('¿Recuperar perfil?')) return;

    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('id_perfil', id);

    fetch(API, {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.error) alert(resp.error);
        cargar();
      });
  }


  function exportarDatos() {
    window.open(API + '?action=export&inactivos=' + (verInactivos ? 1 : 0) + '&q=' + encodeURIComponent(qLast || ''), '_blank');
  }

  function descargarLayout() {
    window.open(API + '?action=layout', '_blank');
  }

  function abrirImport() {
    hideErrors();
    csvFile.value = '';
    impPreview.innerText = '(sin datos)';
    abrirModal('mdlImp');
  }

  function previsualizarCsv() {
    hideErrors();
    const f = csvFile.files[0];
    if (!f) {
      showError('impErr', 'Selecciona un CSV.');
      return;
    }
    const fd = new FormData();
    fd.append('action', 'import_preview');
    fd.append('csv', f);
    fetch(API, {
      method: 'POST',
      body: fd
    }).then(r => r.json()).then(resp => {
      if (resp.error) {
        showError('impErr', resp.error + (resp.detalles ? '<br>- ' + resp.detalles.join('<br>- ') : ''));
        return;
      }
      impPreview.innerText = resp.preview_text || '(sin datos)';
    });
  }

  function importarCsv() {
    hideErrors();
    const f = csvFile.files[0];
    if (!f) {
      showError('impErr', 'Selecciona un CSV.');
      return;
    }
    if (!confirm('¿Importar CSV? UPSERT por id_perfil.')) return;
    const fd = new FormData();
    fd.append('action', 'import');
    fd.append('csv', f);
    fetch(API, {
      method: 'POST',
      body: fd
    }).then(r => r.json()).then(resp => {
      if (resp.error) {
        showError('impErr', resp.error + (resp.detalles ? '<br>- ' + resp.detalles.join('<br>- ') : ''));
        return;
      }
      cerrarModal('mdlImp');
      cargar();
      alert('Importación OK. Insertados: ' + (resp.inserted || 0) + ' / Actualizados: ' + (resp.updated || 0) + ' / Errores: ' + (resp.errors || 0));
    });
  }

  function abrirModal(id) {
    document.getElementById(id).style.display = 'block';
  }

  function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  // 🔥 Inicialización correcta
  document.addEventListener('DOMContentLoaded', function() {
    cargarCompanias(); // primero llena el select
    cargar(); // luego carga la tabla
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>