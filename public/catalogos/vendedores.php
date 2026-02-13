<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* ===== MISMO ESTILO ASSISTPRO (compacto) ===== */
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #e9ecef;
    margin: 0;
    color: #212529;
  }

  .ap-container {
    padding: 15px;
    font-size: 12px;
  }

  .ap-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .ap-grid i {
    cursor: pointer;
    color: #0d6efd;
    transition: transform .15s ease;
  }

  .ap-grid i:hover {
    transform: scale(1.1);
  }


  /* Toolbar */
  .ap-toolbar {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
    background: #fff;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
  }

  .ap-search {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    border: 1px solid #ced4da;
    padding: 6px 10px;
    border-radius: 4px;
  }

  .ap-search input {
    border: none;
    outline: none;
    width: 100%;
    font-size: 12px;
  }

  /* Grid */
  .ap-grid {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    overflow: hidden;
  }

  .ap-grid-wrapper {
    max-height: calc(100vh - 250px);
    overflow: auto;
  }

  .ap-grid table {
    width: 100%;
    border-collapse: collapse;
  }

  .ap-grid table {
    min-width: 1400px;
  }


  .ap-grid th {
    background: #f8f9fa;
    padding: 8px;
    font-size: 11px;
    position: sticky;
    top: 0;
  }

  .ap-grid td {
    padding: 8px;
    font-size: 11px;
    border-bottom: 1px solid #f1f3f5;
  }

  .ap-chip {
    font-size: 11px;
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    cursor: pointer;
  }

  .ap-chip.primary {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
  }

  .ap-chip.ok {
    background: #d1e7dd;
    color: #0f5132;
  }

  .ap-chip.warn {
    background: #fff3cd;
    color: #664d03;
  }

  /* Modal */
  .ap-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
  }

  .ap-modal-content {
    background: #fff;
    width: 800px;
    max-width: 95%;
    padding: 20px;
    border-radius: 8px;
  }

  .ap-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
  }

  .ap-input {
    border: 1px solid #ced4da;
    padding: 6px 10px;
    border-radius: 4px;
  }

  .ap-input input,
  .ap-input select {
    border: none;
    outline: none;
    width: 100%;
    font-size: 12px;
  }
</style>

<div class="ap-container">

  <div class="ap-title">
    <i class="fa fa-user-tie"></i> Catálogo de Vendedores
  </div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar nombre..."
        onkeydown="if(event.key==='Enter')buscar()">
    </div>

    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip primary" onclick="nuevo()">
      <i class="fa fa-plus"></i> Nuevo
    </button>
  </div>

  <div class="ap-grid">
    <div class="ap-grid-wrapper">
      <table>
        <thead>
          <tr>
            <th>Acciones</th>
            <th>Activo</th>
            <th>Clave</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Supervisor</th>
            <th>Calle</th>
            <th>Colonia</th>
            <th>Ciudad</th>
            <th>Estado</th>
            <th>País</th>
            <th>CP</th>
            <th>Password EDA</th>
          </tr>
        </thead>

        <tbody id="tb"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <h3 id="mdlTitle">Agente de ventas</h3>

    <input type="hidden" id="Id_Vendedor" value="0">

    <div class="ap-form">

      <div>
        <label>Nombre</label>
        <div class="ap-input">
          <input id="Nombre" style="text-transform:uppercase">
        </div>
      </div>

      <div>
        <label>Supervisor (ID)</label>
        <div class="ap-input">
          <select id="Id_Supervisor">
            <option value="">Seleccione...</option>
          </select>

        </div>
      </div>

      <div>
        <label>Clave Vendedor</label>
        <div class="ap-input">
          <input id="Cve_Vendedor" maxlength="20">
        </div>
      </div>

      <div>
        <label>¿Es Ayudante?</label>
        <div class="ap-input">
          <select id="Ban_Ayudante">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
        </div>
      </div>


      <div>
        <label>Calle y Número</label>
        <div class="ap-input">
          <input id="CalleNumero" style="text-transform:uppercase">
        </div>
      </div>

      <div>
        <label>Colonia</label>
        <div class="ap-input">
          <input id="Colonia" style="text-transform:uppercase">
        </div>
      </div>

      <div>
        <label>Ciudad</label>
        <div class="ap-input">
          <input id="Ciudad" style="text-transform:uppercase">
        </div>
      </div>

      <div>
        <label>Estado</label>
        <div class="ap-input">
          <input id="Estado" style="text-transform:uppercase">
        </div>
      </div>

      <div>
        <label>País</label>
        <div class="ap-input">
          <input id="Pais" style="text-transform:uppercase">
        </div>
      </div>

      <div>
        <label>Código Postal</label>
        <div class="ap-input">
          <input id="CodigoPostal">
        </div>
      </div>

      <div>
        <label>Activo</label>
        <div class="ap-input">
          <select id="Activo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <div>
        <label>Password EDA</label>
        <div class="ap-input">
          <input id="Psswd_EDA" maxlength="20">
        </div>
      </div>


    </div>

    <div style="text-align:right; margin-top:20px;">
      <button class="ap-chip" onclick="cerrar()">Cancelar</button>
      <button class="ap-chip primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/vendedores_api.php';

  function limpiar() {
    document.getElementById('q').value = '';
    buscar();
  }


  function cargarSupervisores() {
    fetch(API + '?action=supervisores')
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          const sel = document.getElementById('Id_Supervisor');
          sel.innerHTML = '<option value="">Seleccione...</option>' +
            j.data.map(s =>
              `<option value="${s.Id_Supervisor}">${s.Nombre}</option>`
            ).join('');
        }
      });
  }


  function abrir() {
    document.getElementById('mdl').style.display = 'flex';
  }

  function cerrar() {
    document.getElementById('mdl').style.display = 'none';
  }

  function nuevo() {
    document.getElementById('Id_Vendedor').value = 0;
    document.querySelectorAll('#mdl input').forEach(i => i.value = '');
    document.getElementById('Activo').value = 1;
    document.getElementById('Ban_Ayudante').value = 0;
    abrir();
  }


  function buscar() {
    const q = document.getElementById('q').value;

    fetch(API + '?action=list&q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          const tb = document.getElementById('tb');
          tb.innerHTML = j.data.map(r => `
<tr>
  <td>
    <i class="fa fa-pen"
       title="Editar vendedor"
       onclick="editar(${r.Id_Vendedor})"></i>
  </td>

  <td>
    <span class="ap-chip ${r.Activo==1?'ok':'warn'}"
          onclick="toggleActivo(${r.Id_Vendedor}, ${r.Activo})"
          style="cursor:pointer">
      ${r.Activo==1?'Activo':'Inactivo'}
    </span>
  </td>

  <td>${r.Cve_Vendedor || ''}</td>
  <td><b>${r.Nombre || ''}</b></td>
  <td>${r.Ban_Ayudante==1 ? 'AYUDANTE' : 'VENDEDOR'}</td>
  <td>${r.Id_Supervisor || ''}</td>
  <td>${r.CalleNumero || ''}</td>
  <td>${r.Colonia || ''}</td>
  <td>${r.Ciudad || ''}</td>
  <td>${r.Estado || ''}</td>
  <td>${r.Pais || ''}</td>
  <td>${r.CodigoPostal || ''}</td>
  <td>${r.Psswd_EDA || ''}</td>
</tr>
`).join('');


        }
      });
  }

  function editar(id) {
    fetch(API + '?action=get&id=' + id)
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          const d = j.data;
          for (let k in d) {
            const el = document.getElementById(k);
            if (el) el.value = d[k];
          }
          abrir();
        }
      });
  }

  function guardar() {
    const data = {
      Id_Vendedor: document.getElementById('Id_Vendedor').value,
      Cve_Vendedor: document.getElementById('Cve_Vendedor').value,
      Ban_Ayudante: document.getElementById('Ban_Ayudante').value,
      Nombre: document.getElementById('Nombre').value,
      Psswd_EDA: document.getElementById('Psswd_EDA').value,
      Id_Supervisor: document.getElementById('Id_Supervisor').value,
      CalleNumero: document.getElementById('CalleNumero').value,
      Colonia: document.getElementById('Colonia').value,
      Ciudad: document.getElementById('Ciudad').value,
      Estado: document.getElementById('Estado').value,
      Pais: document.getElementById('Pais').value,
      CodigoPostal: document.getElementById('CodigoPostal').value,
      Activo: document.getElementById('Activo').value
    };



    fetch(API + '?action=save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      })
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          cerrar();
          buscar();
        } else {
          console.log("Error backend:", j);
        }
      })
      .catch(e => console.error("Error fetch:", e));

  }

  function toggleActivo(id, estadoActual) {

    const nuevoEstado = estadoActual == 1 ? 0 : 1;

    fetch(API + '?action=toggle_activo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          Id_Vendedor: id,
          Activo: nuevoEstado
        })
      })
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          buscar(); // refresca grid
        } else {
          alert("Error al cambiar estado");
        }
      });
  }


  document.addEventListener('DOMContentLoaded', () => {
    cargarSupervisores();
    buscar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>