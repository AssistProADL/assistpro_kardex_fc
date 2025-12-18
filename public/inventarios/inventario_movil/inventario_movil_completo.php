<?php
/* 
 INVENTARIO FÍSICO MÓVIL – 5"
 Zebra / Unitech
 Resolución real: 360x640
*/
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=360, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Inventario Físico</title>

<style>
*{box-sizing:border-box}
html,body{
    margin:0;
    padding:0;
    width:100%;
    height:100%;
    font-family:Arial,Helvetica,sans-serif;
    background:#e9edf3;
}

/* SIMULADOR DE DISPOSITIVO */
.device{
    width:360px;
    height:640px;
    margin:0 auto;
    background:#f4f6f9;
    overflow-y:auto;
    border:1px solid #cfd8e3; /* solo para desktop */
}

/* HEADER */
header{
    background:#0F5AAD;
    color:#fff;
    padding:10px;
    font-size:15px;
    font-weight:bold;
}

/* CONTENIDO */
.section{padding:10px}

.card{
    background:#fff;
    border-radius:6px;
    padding:10px;
    margin-bottom:8px;
    box-shadow:0 1px 3px rgba(0,0,0,.15);
}

.label{font-size:11px;color:#666}
.big{font-size:16px;font-weight:bold}

/* BOTONES */
.btn{
    width:100%;
    padding:16px 8px;
    margin-top:8px;
    border:none;
    border-radius:6px;
    font-size:16px;
    font-weight:bold;
}

.btn-primary{background:#0F5AAD;color:#fff}
.btn-success{background:#28a745;color:#fff}
.btn-warning{background:#f0ad4e;color:#fff}

.status-online{color:#28a745;font-size:13px;font-weight:bold}
.status-offline{color:#dc3545;font-size:13px;font-weight:bold}

hr{
    border:none;
    border-top:6px solid #dbe3f0;
    margin:0;
}
</style>
</head>

<body>

<div class="device">

<!-- VISTA 1: INICIO -->
<header>Inventario Físico</header>
<div class="section">
    <div class="card">
        <div class="label">Almacén</div>
        <div class="big">CDMX</div>
        <div class="label">Usuario</div>
        <div class="big">jperez</div>
    </div>

    <div class="card">
        <div class="big">Estado: EN PROCESO</div>
        <div>Avance: 45%</div>
        <div class="status-online">● Online</div>
    </div>

    <button class="btn btn-primary">INICIAR CONTEO</button>
</div>

<hr>

<!-- VISTA 2: ESCANEAR UBICACIÓN -->
<header>Escanear Ubicación</header>
<div class="section">
    <div class="card">
        <div class="label">CódigoCSD</div>
        <div class="big">A-03-R02-N01</div>
    </div>
    <button class="btn btn-primary">ESCANEAR UBICACIÓN</button>
</div>

<hr>

<!-- VISTA 3: TIPO DE CONTEO -->
<header>Tipo de Conteo</header>
<div class="section">
    <button class="btn btn-primary">PIEZAS</button>
    <button class="btn btn-primary">CONTENEDORES</button>
    <button class="btn btn-primary">PALLETS</button>
</div>

<hr>

<!-- VISTA 4: PALLET CON CHAROLAS -->
<header>Pallet</header>
<div class="section">
    <div class="card">
        <div class="big">PLT-000456</div>
        <div class="label">Contiene charolas</div>
        <div>1 / 3 contadas</div>
    </div>
    <button class="btn btn-warning">CONTAR CHAROLAS</button>
</div>

<hr>

<!-- VISTA 5: CONTEO CHAROLA -->
<header>Charola</header>
<div class="section">
    <div class="card">
        <div class="label">Charola</div>
        <div class="big">CH-00091</div>
        <div class="label">Producto</div>
        <div class="big">XYZ-456</div>
    </div>

    <div class="card">
        <div class="label">Cantidad</div>
        <div class="big">120</div>
    </div>

    <button class="btn btn-success">CONFIRMAR</button>
</div>

<hr>

<!-- VISTA 6: RESUMEN UBICACIÓN -->
<header>Resumen Ubicación</header>
<div class="section">
    <div class="card">
        <div>Piezas ✔</div>
        <div>Contenedores ✔</div>
        <div>Pallets ✔</div>
    </div>
    <button class="btn btn-primary">SIGUIENTE UBICACIÓN</button>
</div>

<hr>

<!-- VISTA 7: OFFLINE -->
<header>Offline</header>
<div class="section">
    <div class="card">
        <div class="big">Registros pendientes</div>
        <div>8</div>
        <div class="status-offline">● Offline</div>
    </div>
    <button class="btn btn-success">SINCRONIZAR</button>
</div>

</div>

</body>
</html>
