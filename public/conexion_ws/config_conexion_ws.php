<?php
// public/config_conexion_ws.php
require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$usuario = $_SESSION['username'] ?? 'ADMIN';

// ==========================
// LECTURA DE ACCIONES
// ==========================
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$mensaje_ok  = '';
$mensaje_err = '';

try {
    if ($accion === 'guardar') {

        $data = [
            'sistema'         => trim($_POST['sistema'] ?? ''),
            'nombre_conexion' => trim($_POST['nombre_conexion'] ?? ''),
            'url_base'        => trim($_POST['url_base'] ?? ''),
            'tipo_auth'       => trim($_POST['tipo_auth'] ?? ''),
            'company_db'      => trim($_POST['company_db'] ?? ''),
            'usuario_ws'      => trim($_POST['usuario_ws'] ?? ''),
            'password_ws'     => trim($_POST['password_ws'] ?? ''),
            'token_ws'        => trim($_POST['token_ws'] ?? ''),
            'observaciones'   => trim($_POST['observaciones'] ?? ''),
            'activo'          => isset($_POST['activo']) ? 1 : 0,
        ];

        if ($data['sistema'] === '' || $data['nombre_conexion'] === '' || $data['url_base'] === '') {
            throw new Exception('Sistema, nombre de conexión y URL base son obligatorios.');
        }

        if ($id > 0) {
            // UPDATE
            dbq("
                UPDATE assistpro_etl_fc.c_ws_conexion
                   SET sistema         = :sistema,
                       nombre_conexion = :nombre_conexion,
                       url_base        = :url_base,
                       tipo_auth       = :tipo_auth,
                       company_db      = :company_db,
                       usuario_ws      = :usuario_ws,
                       password_ws     = :password_ws,
                       token_ws        = :token_ws,
                       observaciones   = :observaciones,
                       activo          = :activo,
                       fecha_mod       = NOW(),
                       usuario_mod     = :usuario_mod
                 WHERE id = :id
            ", [
                ':sistema'         => $data['sistema'],
                ':nombre_conexion' => $data['nombre_conexion'],
                ':url_base'        => $data['url_base'],
                ':tipo_auth'       => $data['tipo_auth'],
                ':company_db'      => $data['company_db'] ?: null,
                ':usuario_ws'      => $data['usuario_ws'] ?: null,
                ':password_ws'     => $data['password_ws'] ?: null,
                ':token_ws'        => $data['token_ws'] ?: null,
                ':observaciones'   => $data['observaciones'] ?: null,
                ':activo'          => $data['activo'],
                ':usuario_mod'     => $usuario,
                ':id'              => $id,
            ]);

            $mensaje_ok = 'Conexión actualizada correctamente.';

        } else {
            // INSERT
            dbq("
                INSERT INTO assistpro_etl_fc.c_ws_conexion
                    (sistema, nombre_conexion, url_base, tipo_auth,
                     company_db, usuario_ws, password_ws, token_ws,
                     observaciones, activo, fecha_crea, usuario_crea)
                VALUES
                    (:sistema, :nombre_conexion, :url_base, :tipo_auth,
                     :company_db, :usuario_ws, :password_ws, :token_ws,
                     :observaciones, :activo, NOW(), :usuario_crea)
            ", [
                ':sistema'         => $data['sistema'],
                ':nombre_conexion' => $data['nombre_conexion'],
                ':url_base'        => $data['url_base'],
                ':tipo_auth'       => $data['tipo_auth'],
                ':company_db'      => $data['company_db'] ?: null,
                ':usuario_ws'      => $data['usuario_ws'] ?: null,
                ':password_ws'     => $data['password_ws'] ?: null,
                ':token_ws'        => $data['token_ws'] ?: null,
                ':observaciones'   => $data['observaciones'] ?: null,
                ':activo'          => $data['activo'],
                ':usuario_crea'    => $usuario,
            ]);

            $mensaje_ok = 'Conexión registrada correctamente.';
        }

    } elseif ($accion === 'borrar' && $id > 0) {
        // Soft delete
        dbq("
            UPDATE assistpro_etl_fc.c_ws_conexion
               SET activo = 0,
                   fecha_mod = NOW(),
                   usuario_mod = :usuario
             WHERE id = :id
        ", [
            ':usuario' => $usuario,
            ':id'      => $id,
        ]);
        $mensaje_ok = 'Conexión desactivada.';
    }
} catch (Exception $e) {
    $mensaje_err = $e->getMessage();
}

// ==========================
// LECTURA PARA EDICIÓN
// ==========================
$editar = null;
if ($id > 0) {
    $editar = db_one("
        SELECT *
          FROM assistpro_etl_fc.c_ws_conexion
         WHERE id = :id
    ", [':id' => $id]);
}

// Listado
$lista = db_all("
    SELECT *
      FROM assistpro_etl_fc.c_ws_conexion
     ORDER BY activo DESC, nombre_conexion
", []);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Configuración de conexiones WS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial,sans-serif;font-size:10px;background:#f2f4f8;margin:0}
.wrap{padding:14px 18px;margin-left:260px;max-width:1200px}
h1{font-size:16px;margin:0 0 10px;color:#0F5AAD}
.card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.12);padding:12px 16px;margin-bottom:12px}
label{font-weight:bold;color:#555;font-size:10px}
input[type=text],input[type=password],select,textarea{
    width:100%;font-size:10px;padding:5px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box
}
textarea{min-height:60px}
.row{display:flex;gap:10px;flex-wrap:wrap}
.col{flex:1 1 200px}
.btn{font-size:10px;padding:5px 10px;border-radius:6px;border:1px solid #0F5AAD;background:#0F5AAD;color:#fff;cursor:pointer}
.btn.btn-light{background:#fff;color:#0F5AAD}
.btn.btn-danger{border-color:#c0392b;color:#fff;background:#e74c3c}
.msg-ok{margin-bottom:8px;padding:6px 8px;border-radius:6px;background:#e9f8ef;border:1px solid #2ecc71;color:#1e7c3c}
.msg-err{margin-bottom:8px;padding:6px 8px;border-radius:6px;background:#fdecea;border:1px solid #e74c3c;color:#b1271b}
table{width:100%;border-collapse:collapse;font-size:10px}
th,td{padding:5px 6px;border-bottom:1px solid #eee;text-align:left}
th{background:#f5f7fb}
.badge{display:inline-block;padding:2px 6px;border-radius:6px;font-size:9px}
.badge.activo{background:#e9f8ef;color:#1e7c3c}
.badge.inactivo{background:#fdecea;color:#b1271b}
</style>
</head>
<body>
<div class="wrap">
    <h1>Configuración de conexiones WS (SAP B1 / ERPs)</h1>

    <?php if ($mensaje_ok): ?>
        <div class="msg-ok"><?php echo htmlspecialchars($mensaje_ok); ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="msg-err"><?php echo htmlspecialchars($mensaje_err); ?></div>
    <?php endif; ?>

    <!-- FORMULARIO DE ALTA / EDICIÓN -->
    <div class="card">
        <form method="post">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?php echo $editar ? (int)$editar['id'] : 0; ?>">

            <div class="row">
                <div class="col">
                    <label>Sistema</label>
                    <select name="sistema" required>
                        <?php
                        $sistema = $editar['sistema'] ?? 'SAP_B1_SL';
                        ?>
                        <option value="SAP_B1_SL" <?php echo $sistema==='SAP_B1_SL'?'selected':''; ?>>SAP B1 - Service Layer</option>
                        <option value="SAP_B1_DI" <?php echo $sistema==='SAP_B1_DI'?'selected':''; ?>>SAP B1 - DI Server</option>
                        <option value="OTRO" <?php echo $sistema==='OTRO'?'selected':''; ?>>Otro ERP / REST</option>
                    </select>
                </div>
                <div class="col">
                    <label>Nombre de conexión</label>
                    <input type="text" name="nombre_conexion" required
                           value="<?php echo htmlspecialchars($editar['nombre_conexion'] ?? ''); ?>">
                </div>
                <div class="col">
                    <label>Tipo autenticación</label>
                    <?php $ta = $editar['tipo_auth'] ?? 'SERVICELAYER'; ?>
                    <select name="tipo_auth">
                        <option value="SERVICELAYER" <?php echo $ta==='SERVICELAYER'?'selected':''; ?>>Service Layer (session)</option>
                        <option value="BASIC" <?php echo $ta==='BASIC'?'selected':''; ?>>Basic Auth (usuario/contraseña)</option>
                        <option value="TOKEN" <?php echo $ta==='TOKEN'?'selected':''; ?>>Token / API Key</option>
                    </select>
                </div>
            </div>

            <div class="row" style="margin-top:8px">
                <div class="col">
                    <label>URL base WS</label>
                    <input type="text" name="url_base" required
                           placeholder="https://servidor:50000/b1s/v1/"
                           value="<?php echo htmlspecialchars($editar['url_base'] ?? ''); ?>">
                </div>
                <div class="col">
                    <label>Base de datos / Company (SAP B1)</label>
                    <input type="text" name="company_db"
                           value="<?php echo htmlspecialchars($editar['company_db'] ?? ''); ?>">
                </div>
                <div class="col" style="align-self:flex-end">
                    <label><input type="checkbox" name="activo" value="1"
                           <?php echo (!isset($editar['activo']) || $editar['activo']) ? 'checked' : ''; ?>>
                        Activo
                    </label>
                </div>
            </div>

            <div class="row" style="margin-top:8px">
                <div class="col">
                    <label>Usuario WS</label>
                    <input type="text" name="usuario_ws"
                           value="<?php echo htmlspecialchars($editar['usuario_ws'] ?? ''); ?>">
                </div>
                <div class="col">
                    <label>Contraseña WS</label>
                    <input type="password" name="password_ws"
                           value="<?php echo htmlspecialchars($editar['password_ws'] ?? ''); ?>">
                </div>
                <div class="col">
                    <label>Token / API Key</label>
                    <input type="text" name="token_ws"
                           value="<?php echo htmlspecialchars($editar['token_ws'] ?? ''); ?>">
                </div>
            </div>

            <div style="margin-top:8px">
                <label>Observaciones</label>
                <textarea name="observaciones"><?php echo htmlspecialchars($editar['observaciones'] ?? ''); ?></textarea>
            </div>

            <div style="margin-top:10px;text-align:right">
                <button type="submit" class="btn">
                    <?php echo $editar ? 'Actualizar conexión' : 'Guardar conexión'; ?>
                </button>
                <?php if ($editar): ?>
                    <a href="config_conexion_ws.php" class="btn btn-light">Nuevo</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- LISTADO -->
    <div class="card">
        <h2 style="font-size:12px;margin:0 0 8px">Conexiones registradas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sistema</th>
                    <th>Nombre</th>
                    <th>URL base</th>
                    <th>Tipo Auth</th>
                    <th>Company</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$lista): ?>
                <tr><td colspan="8" style="color:#888">Sin conexiones registradas.</td></tr>
            <?php else: foreach ($lista as $row): ?>
                <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['sistema'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_conexion'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['url_base'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['tipo_auth'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['company_db'] ?? ''); ?></td>
                    <td>
                        <?php if ($row['activo']): ?>
                            <span class="badge activo">ACTIVO</span>
                        <?php else: ?>
                            <span class="badge inactivo">INACTIVO</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-light" href="config_conexion_ws.php?id=<?php echo (int)$row['id']; ?>">Editar</a>
                        <?php if ($row['activo']): ?>
                        <form method="post" style="display:inline"
                              onsubmit="return confirm('¿Desactivar esta conexión?');">
                            <input type="hidden" name="accion" value="borrar">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="btn btn-danger">Desactivar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
