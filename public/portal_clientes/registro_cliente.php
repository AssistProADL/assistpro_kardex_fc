<?php
// public/portal_clientes/registro_cliente.php

// Inicializar sesión ANTES de cualquier output
require_once __DIR__ . '/../../app/bootstrap.php';
\AssistPro\Helpers\SessionManager::init();

require_once __DIR__ . '/../../app/db.php';

$errores = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $empresa = trim($_POST['empresa'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $comentarios = trim($_POST['comentarios'] ?? '');

  if ($nombre === '') {
    $errores[] = 'El nombre de contacto es obligatorio.';
  }
  if ($email === '' && $telefono === '') {
    $errores[] = 'Capture al menos un medio de contacto (correo o teléfono).';
  }

  if (empty($errores)) {
    // Guardar como prospecto CRM (lead e-commerce)
    dbq("
            INSERT INTO crm_prospectos (nombre, empresa, email, telefono, comentarios, origen)
            VALUES (?, ?, ?, ?, ?, 'E-COMMERCE')
        ", [$nombre, $empresa, $email, $telefono, $comentarios]);

    $ok = true;
  }
}
?><!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Registro de cliente e-commerce</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/fontawesome.min.css">
  <style>
    body {
      font-size: 12px;
      background-color: #f5f7fb;
    }

    .container-registro {
      padding: 15px 20px;
    }

    h3 {
      font-size: 18px;
      font-weight: 600;
      color: #003366;
      margin-bottom: 10px;
    }

    .card-registro {
      background: #fff;
      border-radius: 10px;
      border: 1px solid #dde3f0;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
      padding: 15px;
      max-width: 700px;
    }

    label {
      font-weight: 600;
      font-size: 11px;
    }
  </style>
</head>

<body>
  <?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

  <div class="container-registro">
    <h3>Registro de cliente para E-Commerce</h3>

    <div class="card-registro">
      <?php if ($ok): ?>
        <div class="alert alert-success alert-sm">
          Gracias por su interés. Hemos recibido sus datos y un ejecutivo de ventas dará seguimiento.
        </div>
      <?php endif; ?>

      <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-sm">
          <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="nombre">Nombre de contacto *</label>
            <input type="text" name="nombre" id="nombre" class="form-control form-control-sm"
              value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
          </div>
          <div class="form-group col-md-6">
            <label for="empresa">Empresa</label>
            <input type="text" name="empresa" id="empresa" class="form-control form-control-sm"
              value="<?= htmlspecialchars($_POST['empresa'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="email">Correo electrónico</label>
            <input type="email" name="email" id="email" class="form-control form-control-sm"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group col-md-6">
            <label for="telefono">Teléfono / Móvil</label>
            <input type="text" name="telefono" id="telefono" class="form-control form-control-sm"
              value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="comentarios">Comentarios (requerimientos, productos de interés, etc.)</label>
          <textarea name="comentarios" id="comentarios" rows="3"
            class="form-control form-control-sm"><?= htmlspecialchars($_POST['comentarios'] ?? '') ?></textarea>
        </div>

        <div class="text-right">
          <a href="catalogo.php" class="btn btn-outline-secondary btn-sm">Regresar al catálogo</a>
          <button type="submit" class="btn btn-primary btn-sm">Enviar registro</button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
  <script src="../assets/jquery.min.js"></script>
  <script src="../assets/bootstrap.bundle.min.js"></script>
</body>

</html>