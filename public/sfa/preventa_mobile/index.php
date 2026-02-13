<?php
session_start();
require_once dirname(__DIR__, 3) . '/app/db.php';

/* ================= LOGIN ================= */
if (isset($_POST['action']) && $_POST['action']=='login') {

    $u = $_POST['usuario'];
    $p = $_POST['password'];

    $user = db_one("SELECT id,nombre 
                    FROM usuarios 
                    WHERE usuario=? 
                    AND password=SHA2(?,256)
                    AND Activo=1", [$u,$p]);

    if($user){
        $_SESSION['sfa_user']=$user;
    } else {
        $error="Credenciales incorrectas";
    }
}

/* ================= LOGOUT ================= */
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php");
    exit;
}

/* ================= GUARDAR VENTA ================= */
if(isset($_POST['action']) && $_POST['action']=='guardar'){

    $cliente=$_POST['cliente'];
    $productos=$_POST['producto'];
    $cantidades=$_POST['cantidad'];
    $vendedor=$_SESSION['sfa_user']['id'];

    db_tx(function() use($cliente,$productos,$cantidades,$vendedor){

        $total=0;

        dbq("INSERT INTO sfa_pedidos(cliente_id,vendedor_id,total,fecha)
             VALUES(?,?,0,NOW())",[$cliente,$vendedor]);

        $pedido=db_val("SELECT LAST_INSERT_ID()");

        foreach($productos as $i=>$prod){

            $precio=db_val("SELECT precio FROM productos WHERE id=?",[$prod]);
            $subtotal=$precio*$cantidades[$i];
            $total+=$subtotal;

            dbq("INSERT INTO sfa_pedido_detalle(pedido_id,producto_id,cantidad,precio,subtotal)
                 VALUES(?,?,?,?,?)",
                 [$pedido,$prod,$cantidades[$i],$precio,$subtotal]);

            dbq("UPDATE inventario SET existencia=existencia-? WHERE producto_id=?",
                 [$cantidades[$i],$prod]);
        }

        dbq("UPDATE sfa_pedidos SET total=? WHERE id=?",[$total,$pedido]);
    });

    header("Location: index.php?view=dashboard");
    exit;
}

$view=$_GET['view'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
--blue:#1326b3;
--dark:#0a0f1f;
--bg:#f3f4f7;
}
body{
margin:0;
background:var(--bg);
font-family:Segoe UI,Arial;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}
.card{
width:100%;
max-width:420px;
background:white;
border-radius:28px;
padding:24px;
box-shadow:0 20px 40px rgba(0,0,0,0.08);
}
h1{font-size:18px;margin:0;}
.sub{font-size:13px;color:#6b7280;margin-bottom:15px;}
.input{
width:100%;
padding:14px;
border-radius:16px;
border:1px solid #e5e7eb;
margin-bottom:10px;
}
.btn{
width:100%;
padding:15px;
border:none;
border-radius:18px;
font-weight:600;
margin-bottom:10px;
}
.primary{background:var(--blue);color:white;}
.dark{background:var(--dark);color:white;}
.menu{background:var(--blue);color:white;}
.small{font-size:12px;color:#999;text-align:center;margin-top:10px;}
.row{display:flex;gap:6px;}
</style>
<script>
function addRow(){
    const container=document.getElementById("detalle");
    const row=document.createElement("div");
    row.className="row";
    row.innerHTML=`
    <select name="producto[]" class="input" required>
        ${document.getElementById('productos_list').innerHTML}
    </select>
    <input type="number" name="cantidad[]" class="input" placeholder="Cant." required>
    `;
    container.appendChild(row);
}
</script>
</head>
<body>

<div class="card">

<?php if(!isset($_SESSION['sfa_user'])): ?>

<h1>SFA AssistPro</h1>
<div class="sub">Acceso operativo</div>

<?php if(isset($error)) echo "<div style='color:red'>$error</div>"; ?>

<form method="POST">
<input type="hidden" name="action" value="login">
<input class="input" name="usuario" placeholder="Usuario">
<input class="input" type="password" name="password" placeholder="Password">
<button class="btn primary">INGRESAR</button>
</form>

<?php else: ?>

<h1>Bienvenido</h1>
<div class="sub"><?= $_SESSION['sfa_user']['nombre'] ?></div>

<?php if($view=='dashboard'): ?>

<button class="btn menu" onclick="location='?view=clientes'">CLIENTES</button>
<button class="btn menu" onclick="location='?view=venta'">NUEVA VENTA</button>
<button class="btn dark" onclick="location='?logout=1'">CERRAR SESIÓN</button>

<?php endif; ?>

<?php if($view=='clientes'):
$clientes=db_all("SELECT id,nombre FROM clientes WHERE Activo=1");
foreach($clientes as $c){
echo "<button class='btn menu' onclick=\"location='?view=venta&cliente={$c['id']}'\">{$c['nombre']}</button>";
}
endif; ?>

<?php if($view=='venta'):
$productos=db_all("SELECT id,nombre FROM productos WHERE Activo=1");
?>

<form method="POST">
<input type="hidden" name="action" value="guardar">
<input type="hidden" name="cliente" value="<?= $_GET['cliente'] ?? '' ?>">

<div id="detalle"></div>

<div style="display:none" id="productos_list">
<option value="">Producto</option>
<?php foreach($productos as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
<?php endforeach; ?>
</div>

<button type="button" class="btn menu" onclick="addRow()">AGREGAR PRODUCTO</button>
<button class="btn primary">CONFIRMAR VENTA</button>

</form>

<button class="btn dark" onclick="location='?view=dashboard'">VOLVER</button>

<?php endif; ?>

<?php endif; ?>

<div class="small">Powered by Adventech Logística</div>

</div>
</body>
</html>
