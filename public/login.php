<?php
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(0, '/'); // Cookie de sesión global
  session_start();
}
require_once __DIR__ . '/../app/db.php';

// Si ya hay sesión, redirigir al dashboard
if (!empty($_SESSION['username'])) {
  header('Location: /assistpro_kardex_fc/public/bi/index.php');
  exit;
}

$err = '';
// ---- POST: Autenticación ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim($_POST['user'] ?? '');
  $pass = (string) ($_POST['pass'] ?? '');

  // Usuario activo
  $row = db_one("
    SELECT 
      cve_usuario, nombre_completo, perfil, COALESCE(pwd_usuario,'') AS pwd_usuario,
      Activo
    FROM c_usuario
    WHERE TRIM(cve_usuario) = TRIM(:u)
      AND COALESCE(Activo,'1') IN ('1','S','SI','TRUE')
    LIMIT 1
  ", [':u' => $user]);

  if (!$row) {
    $err = 'Usuario no encontrado o inactivo';
  } else {
    // Validar contraseña
    $dbpwd = (string) ($row['pwd_usuario'] ?? '');
    if ($dbpwd !== '' && $dbpwd !== $pass) {
      $err = 'Contraseña incorrecta';
    } else {
      // Sesión básica (sin almacén/empresa aún)
      $_SESSION['username'] = $row['cve_usuario'];
      $_SESSION['nombre_completo'] = $row['nombre_completo'] ?? ($row['cve_usuario'] ?? '');
      $_SESSION['perfil'] = $row['perfil'] ?? '';

      // Inicializar variables de contexto vacías (se llenarán en filtros)
      $_SESSION['cve_almac'] = '';
      $_SESSION['empresas'] = [];

      // Redirigir al BI
      header('Location: /assistpro_kardex_fc/public/bi/index.php');
      exit;
    }
  }
}

// Mensaje GET opcional
if (!$err && isset($_GET['err'])) {
  $err = trim((string) $_GET['err']);
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Login · AssistPro ER®</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --ap-blue: #0a2a6b;
      --ap-blue-dark: #061b46;
    }

    body {
      min-height: 100vh;
      background: url('/assistpro_kardex_fc/public/assets/br/warehouse-br.jpg') center/cover no-repeat fixed;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    .overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(10, 42, 107, 0.65);
      backdrop-filter: blur(4px);
      z-index: 0;
    }

    .card-login {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 400px;
      border: 0;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card-header {
      background: #fff;
      border-bottom: 1px solid #f0f0f0;
      padding: 2rem 1.5rem 1rem;
      text-align: center;
    }

    .logo-img {
      height: 45px;
      margin-bottom: 0.5rem;
    }

    .app-title {
      font-weight: 700;
      color: var(--ap-blue);
      font-size: 1.1rem;
      letter-spacing: -0.5px;
    }

    .card-body {
      padding: 2rem;
      background: #fff;
    }

    .form-floating>.form-control:focus~label,
    .form-floating>.form-control:not(:placeholder-shown)~label {
      color: var(--ap-blue);
      transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
    }

    .form-control:focus {
      border-color: var(--ap-blue);
      box-shadow: 0 0 0 0.25rem rgba(10, 42, 107, 0.15);
    }

    .btn-primary {
      background-color: var(--ap-blue);
      border-color: var(--ap-blue);
      padding: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: all 0.3s;
    }

    .btn-primary:hover {
      background-color: var(--ap-blue-dark);
      border-color: var(--ap-blue-dark);
      transform: translateY(-1px);
    }

    .footer-text {
      font-size: 0.8rem;
      color: #8898aa;
      text-align: center;
      margin-top: 1.5rem;
    }
  </style>
</head>

<body>

  <div class="overlay"></div>

  <div class="card card-login">
    <div class="card-header">
      <img src="/assistpro_kardex_fc/public/assets/logo/assistpro-er.svg" alt="AssistPro" class="logo-img">
      <div class="app-title">Business Intelligence Suite</div>
    </div>

    <div class="card-body">
      <?php if ($err): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <div><?= htmlspecialchars($err) ?></div>
        </div>
      <?php endif; ?>

      <form method="post" id="frmLogin" autocomplete="off">

        <!-- Usuario -->
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="user" name="user" placeholder="Usuario" required
            value="<?= htmlspecialchars($_GET['u'] ?? '') ?>">
          <label for="user"><i class="bi bi-person me-1"></i> Usuario</label>
        </div>

        <!-- Contraseña -->
        <div class="form-floating mb-4">
          <input type="password" class="form-control" id="pass" name="pass" placeholder="Contraseña" required>
          <label for="pass"><i class="bi bi-lock me-1"></i> Contraseña</label>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg shadow-sm">
            INGRESAR <i class="bi bi-arrow-right-short"></i>
          </button>
        </div>

        <div class="footer-text">
          &copy; <?= date('Y') ?> Adventech Logística.<br>Todos los derechos reservados.
        </div>
      </form>
    </div>
  </div>

</body>

</html>