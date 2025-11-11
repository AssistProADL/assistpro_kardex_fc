<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/db.php';

$err = '';
// ---- POST: Autenticación ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user    = trim($_POST['user'] ?? '');
  $pass    = (string)($_POST['pass'] ?? '');
  $alm     = trim($_POST['alm']  ?? '');
  $empSel  = $_POST['empresas'] ?? []; // array (puede venir vacío)
  $empAll  = isset($_POST['emp_all']) ? (bool)$_POST['emp_all'] : false;

  // Usuario activo
  $row = db_one("
    SELECT 
      cve_usuario, nombre_completo, perfil, COALESCE(pwd_usuario,'') AS pwd_usuario,
      Activo
    FROM c_usuario
    WHERE TRIM(cve_usuario) = TRIM(:u)
      AND COALESCE(Activo,'1') IN ('1','S','SI','TRUE')
    LIMIT 1
  ", [':u'=>$user]);

  if (!$row) {
    $err = 'Usuario no encontrado o inactivo';
  } else {
    // Validar contraseña (texto plano, según estructura recibida)
    $dbpwd = (string)($row['pwd_usuario'] ?? '');
    if ($dbpwd !== '' && $dbpwd !== $pass) {
      $err = 'Contraseña incorrecta';
    } else {
      if ($alm === '') {
        $err = 'Debe seleccionar un almacén';
      } else {
        // Sesión
        $_SESSION['username']        = $row['cve_usuario'];
        $_SESSION['nombre_completo'] = $row['nombre_completo'] ?? ($row['cve_usuario'] ?? '');
        $_SESSION['perfil']          = $row['perfil'] ?? '';
        $_SESSION['cve_almac']       = $alm;

        // Empresas cliente (=1)
        $_SESSION['empresas_all']    = (bool)$empAll;
        $_SESSION['empresas']        = is_array($empSel) ? array_values($empSel) : [];

        // Redirigir al BI
        header('Location: /assistpro_kardex_fc/public/bi/index.php');
        exit;
      }
    }
  }
}

// Mensaje GET opcional
if (!$err && isset($_GET['err'])) {
  $err = trim((string)$_GET['err']);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Business Intelligence Suite · Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --ap-blue:#0a2a6b;
    }
    body{
      min-height:100vh;
      background: url('/assistpro_kardex_fc/public/assets/br/warehouse-br.jpg') center/cover no-repeat fixed;
      display:flex; align-items:center; justify-content:center;
      padding:20px;
    }
    .card-login{
      width:100%; max-width:760px; border:0; border-radius:18px;
      box-shadow:0 18px 40px rgba(0,0,0,.18);
      overflow:hidden; backdrop-filter: blur(2px);
    }
    .card-login .header{
      background:#fff; padding:14px 16px; display:flex; align-items:center; gap:10px; border-bottom:1px solid #eef2f6;
    }
    .card-login .header .logo-plate{
      background:#fff; border-radius:12px; padding:6px 10px; box-shadow:0 6px 16px rgba(0,0,0,.10);
    }
    .card-login .header img{ height:28px; display:block; }
    .card-login .brand-title{ font-weight:800; color:var(--ap-blue); margin-left:8px; }
    .muted{ color:#6c7a89; }
    .select-multi{
      min-height: 140px;
      border-radius: .5rem;
    }
  </style>
</head>
<body>

<div class="card card-login bg-white">
  <div class="header">
    <div class="logo-plate">
      <img src="/assistpro_kardex_fc/assets/logo/assistpro-er.svg" alt="AssistPro ER">
    </div>
    <div class="brand-title">Business Intelligence Suite</div>
  </div>

  <div class="card-body p-4">
    <?php if ($err): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="post" id="frmLogin" autocomplete="off">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label">Usuario</label>
          <input type="text" name="user" id="user" class="form-control" required value="<?= htmlspecialchars($_GET['u'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Contraseña</label>
          <input type="password" name="pass" id="pass" class="form-control" required>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">Almacén</label>
          <select name="alm" id="alm" class="form-select" required>
            <option value="">(Escriba usuario)</option>
          </select>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label d-flex align-items-center justify-content-between">
            <span>Empresas / Proveedores <span class="muted">(Cliente = 1)</span></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAll">Seleccionar todas</button>
          </label>
          <select name="empresas[]" id="empresas" class="form-select select-multi" multiple disabled>
            <option value="">Seleccione almacén para ver empresas…</option>
          </select>
          <input type="hidden" name="emp_all" id="emp_all" value="0">
        </div>
      </div>

      <div class="mt-4 d-grid">
        <button class="btn btn-primary btn-lg" style="background:#0a2a6b;border-color:#0a2a6b;">
          Ingresar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const $user = document.getElementById('user');
const $alm  = document.getElementById('alm');
const $emp  = document.getElementById('empresas');
const $all  = document.getElementById('btnAll');
const $flag = document.getElementById('emp_all');

function clearSelect(sel, placeholder){
  sel.innerHTML = '';
  const opt = document.createElement('option');
  opt.value=''; opt.textContent = placeholder || '—';
  sel.appendChild(opt);
}

async function loadAlmacenes(u){
  clearSelect($alm, u ? 'Cargando almacenes…' : '(Escriba usuario)');
  $alm.disabled = !u;
  if(!u) return;
  try{
    const r = await fetch('/assistpro_kardex_fc/public/login_almacenes.php?user='+encodeURIComponent(u));
    const data = await r.json();
    clearSelect($alm, 'Seleccione…');
    data.forEach(row=>{
      const o = document.createElement('option');
      o.value = (row.cve_almac || '').trim();
      o.textContent = (row.des_almac || row.cve_almac || '').trim() + (row.cve_almac ? ` (${row.cve_almac})` : '');
      $alm.appendChild(o);
    });
  }catch(e){
    clearSelect($alm,'Error cargando almacenes'); 
  }
}

async function loadEmpresas(u, a){
  $emp.disabled = true;
  $flag.value = '0';
  clearSelect($emp, a ? 'Cargando…' : 'Seleccione almacén para ver empresas…');
  if(!u || !a) return;
  try{
    const r = await fetch('/assistpro_kardex_fc/public/login_empresas.php?user='+encodeURIComponent(u)+'&alm='+encodeURIComponent(a));
    const data = await r.json();
    $emp.innerHTML = '';
    data.forEach(row=>{
      const o = document.createElement('option');
      o.value = (row.cve_proveedor || '').trim();
      o.textContent = `${(row.des_proveedor || '').trim()} [${(row.cve_proveedor || '').trim()}]`;
      $emp.appendChild(o);
    });
    $emp.disabled = false;
  }catch(e){
    clearSelect($emp,'Error cargando empresas');
  }
}

$user.addEventListener('change', ()=>{
  loadAlmacenes($user.value.trim());
  clearSelect($emp,'Seleccione almacén para ver empresas…');
  $emp.disabled = true;
  $flag.value='0';
});

$alm.addEventListener('change', ()=>{
  loadEmpresas($user.value.trim(), $alm.value.trim());
  $flag.value='0';
});

$all.addEventListener('click', ()=>{
  if($emp.disabled){ return; }
  for(const o of $emp.options){ o.selected = true; }
  $flag.value = '1'; // todas
});

document.addEventListener('DOMContentLoaded', ()=>{
  const u = $user.value.trim();
  if(u){ loadAlmacenes(u); }
});
</script>
</body>
</html>
