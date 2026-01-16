// /public/mobile/js/login.js
document.addEventListener('DOMContentLoaded', () => {
  cargarAlmacenes();

  const form = document.getElementById('loginForm');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      login();
    });
  }
});

async function cargarAlmacenes() {
  const msg = document.getElementById('msg');
  const sel = document.getElementById('almacen');

  try {
    const res = await fetch(API_FILTROS, { cache: 'no-store' });
    const json = await res.json();

    if (!json.ok) {
      throw new Error((json.error || 'Error backend') + (json.raw ? `: ${json.raw}` : ''));
    }

    // El core normalmente trae algo tipo {almacenes:[...]} dentro de data
    const data = json.data || {};
    const almacenes = data.almacenes || data.Almacenes || [];

    sel.innerHTML = '';
    if (!almacenes.length) {
      sel.innerHTML = `<option value="">(sin almacenes)</option>`;
      return;
    }

    almacenes.forEach(a => {
      // soporta distintos nombres de campo (id/cve_almac/almacenp_id)
      const id = a.id ?? a.cve_almac ?? a.almacenp_id ?? a.id_almacen ?? '';
      const nombre = a.nombre ?? a.descrip ?? a.descripcion ?? a.cve_almac ?? String(id);
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = nombre;
      sel.appendChild(opt);
    });

    if (msg) { msg.style.display = 'none'; msg.textContent = ''; }
  } catch (err) {
    console.error(err);
    if (msg) {
      msg.style.display = 'block';
      msg.textContent = 'Error cargando almacenes: respuesta no JSON.';
    }
    sel.innerHTML = `<option value="">(sin almacenes)</option>`;
  }
}

function login() {
  const user = document.getElementById('usuario').value.trim();
  const pwd  = document.getElementById('pwd').value.trim();
  const alm  = document.getElementById('almacen').value;

  const msg = document.getElementById('msg');
  if (!user || !pwd || !alm) {
    if (msg) { msg.style.display = 'block'; msg.textContent = 'Complete usuario, password y almacén.'; }
    return;
  }

  // Sin sesiones: persistimos en localStorage
  localStorage.setItem('mobile_user', user);
  localStorage.setItem('mobile_almacen', alm);

  // Redirige al menú móvil (no al web)
  window.location.href = 'menu.php';
}
