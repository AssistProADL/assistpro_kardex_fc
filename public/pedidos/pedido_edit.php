<?php
// public/pedido_edit.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AssistPro Kardex - Detalle de Pedido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root{
            --primary:#0F5AAD;
            --primary-light:#00A3E0;
            --border:#E5E7EB;
            --bg:#F5F7FB;
            --card:#FFFFFF;
            --text:#111827;
            --muted:#6B7280;
        }
        *{box-sizing:border-box;}
        html,body{
            margin:0;
            padding:0;
            font-family:system-ui, -apple-system, BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
            background:var(--bg);
            color:var(--text);
        }
        body{padding:16px 24px 40px 24px;}
        h1{
            font-size:20px;
            font-weight:700;
            margin:0 0 12px 0;
            color:var(--primary);
        }
        h2.section-title{
            font-size:16px;
            margin:0 0 12px 0;
            font-weight:600;
            color:#374151;
        }
        .card{
            background:var(--card);
            border-radius:10px;
            border:1px solid var(--border);
            padding:16px 18px;
            margin-bottom:14px;
        }
        .grid{
            display:grid;
            grid-template-columns:repeat(12,minmax(0,1fr));
            gap:10px 14px;
        }
        .col-2{grid-column:span 2;}
        .col-3{grid-column:span 3;}
        .col-4{grid-column:span 4;}
        .col-6{grid-column:span 6;}
        .col-8{grid-column:span 8;}
        .col-12{grid-column:span 12;}
        @media (max-width:1200px){
            .col-2,.col-3,.col-4,.col-6,.col-8{grid-column:span 6;}
        }
        @media (max-width:768px){
            .grid{grid-template-columns:repeat(2,minmax(0,1fr));}
            .col-2,.col-3,.col-4,.col-6,.col-8,.col-12{grid-column:span 2;}
        }

        label{
            display:block;
            font-size:11px;
            color:var(--muted);
            margin-bottom:3px;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select,
        textarea{
            width:100%;
            padding:7px 8px;
            font-size:12px;
            border-radius:8px;
            border:1px solid var(--border);
            background:#F3F4F6;
            color:#4B5563;
        }
        textarea{min-height:60px;resize:vertical;}
        .inline-values{
            font-size:12px;
            margin-top:4px;
            color:#111827;
        }

        .flex{display:flex;}
        .flex-between{display:flex;align-items:center;justify-content:space-between;}
        .gap-8{gap:8px;}
        .gap-12{gap:12px;}
        .btn{
            border:0;
            border-radius:8px;
            padding:7px 14px;
            font-size:12px;
            font-weight:600;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
        }
        .btn-primary{background:var(--primary);color:#FFFFFF;}
        .btn-secondary{background:#E5E7EB;color:#111827;}
        .btn-danger{background:#EF4444;color:#FFFFFF;}
        .btn-sm{padding:5px 10px;font-size:11px;}

        /* Tabla detalle */
        .table-wrapper{
            width:100%;
            overflow-x:auto;
        }
        table{
            width:100%;
            border-collapse:collapse;
            font-size:11px;
            min-width:960px;
        }
        th,td{
            border-bottom:1px solid #E5E7EB;
            padding:6px 8px;
            white-space:nowrap;
        }
        th{
            font-weight:600;
            color:#6B7280;
            background:#F9FAFB;
            text-align:left;
        }
        tr:nth-child(even) td{background:#FDFDFE;}
        .text-right{text-align:right;}

        /* Totales */
        .totals{
            margin-top:10px;
            display:grid;
            grid-template-columns:repeat(12,minmax(0,1fr));
            gap:10px;
        }
        .total-box{
            grid-column:span 4;
            background:#F9FAFB;
            border-radius:10px;
            border:1px solid #E5E7EB;
            padding:10px 12px;
            font-size:12px;
        }
        .total-label{color:#6B7280;margin-bottom:3px;}
        .total-value{font-size:14px;font-weight:700;color:#111827;}
        @media (max-width:900px){
            .total-box{grid-column:span 12;}
        }

        .header-actions{
            margin-bottom:10px;
            display:flex;
            justify-content:flex-end;
            gap:8px;
        }

        .ticket{
            margin-top:16px;
            padding:10px 12px;
            background:#0B1120;
            color:#E5E7EB;
            border-radius:10px;
            font-family:monospace;
            font-size:11px;
            max-width:380px;
        }
        .ticket-title{
            text-align:center;
            font-weight:bold;
            margin-bottom:4px;
        }
        .ticket hr{
            border:0;
            border-top:1px dashed #4B5563;
            margin:6px 0;
        }
    </style>
</head>
<body>

<h1>Detalle de Pedido</h1>

<div class="header-actions">
    <button type="button" class="btn btn-secondary btn-sm">Regresar</button>
    <button type="button" class="btn btn-primary btn-sm">Imprimir</button>
    <button type="button" class="btn btn-danger btn-sm">Cancelar Pedido</button>
</div>

<!-- Encabezado -->
<div class="card">
    <h2 class="section-title">Datos Generales</h2>
    <div class="grid">
        <div class="col-3">
            <label>Folio</label>
            <input type="text" value="S202511142" readonly>
        </div>
        <div class="col-3">
            <label>Almacén</label>
            <input type="text" value="(ID100) - Operador Logístico 3PL" readonly>
        </div>
        <div class="col-3">
            <label>Cliente</label>
            <input type="text" value="CVE001 - Cliente Demo" readonly>
        </div>
        <div class="col-3">
            <label>Prioridad</label>
            <input type="text" value="1 - Urgente" readonly>
        </div>

        <div class="col-6">
            <label>Ruta Venta | Preventa</label>
            <input type="text" value="Ruta 1" readonly>
        </div>
        <div class="col-3">
            <label>Día Operativo</label>
            <input type="text" value="Lunes" readonly>
        </div>
        <div class="col-3">
            <label>Número OC Cliente</label>
            <input type="text" value="OC-12345" readonly>
        </div>

        <div class="col-4">
            <label>Fecha de Entrega Solicitada</label>
            <input type="date" value="2025-11-19" readonly>
        </div>
        <div class="col-4">
            <label>Tipo de Venta</label>
            <input type="text" value="Pre Venta" readonly>
        </div>
        <div class="col-4">
            <label>Tipo de Negociación</label>
            <input type="text" value="Contado" readonly>
        </div>

        <div class="col-4">
            <label>Horario Planeado Desde</label>
            <input type="time" value="09:00" readonly>
        </div>
        <div class="col-4">
            <label>Hasta</label>
            <input type="time" value="13:00" readonly>
        </div>
        <div class="col-4">
            <label>Usuario que solicita</label>
            <input type="text" value="Usuario Demo" readonly>
        </div>

        <div class="col-6">
            <label>Dirección de Envío</label>
            <textarea readonly>Calle Ficticia 123, Parque Industrial, Ciudad, País.</textarea>
        </div>
        <div class="col-6">
            <label>Descripción Detallada</label>
            <textarea readonly>Pedido generado desde módulo POS de demostración.</textarea>
        </div>
    </div>
</div>

<!-- Detalle -->
<div class="card">
    <h2 class="section-title">Detalle del Pedido</h2>

    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>License Plate (LP)</th>
                <th>Clave</th>
                <th>Artículo</th>
                <th>Lote|Serie</th>
                <th>Cantidad</th>
                <th>Unidad de Medida</th>
                <th>Peso (Kgs)</th>
                <th>Precio Unitario</th>
                <th>Descuento %</th>
                <th>Descuento $</th>
                <th>IVA</th>
                <th>Importe Total</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>1</td>
                <td>LP0001</td>
                <td>ART-001</td>
                <td>Producto de Demostración</td>
                <td>Lote A1</td>
                <td class="text-right">10</td>
                <td>PZA</td>
                <td class="text-right">5.000</td>
                <td class="text-right">100.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">16%</td>
                <td class="text-right">1,160.00</td>
            </tr>
            <tr>
                <td>2</td>
                <td>LP0002</td>
                <td>ART-002</td>
                <td>Producto 2</td>
                <td>Lote B1</td>
                <td class="text-right">5</td>
                <td>CJA</td>
                <td class="text-right">7.500</td>
                <td class="text-right">250.00</td>
                <td class="text-right">10.00</td>
                <td class="text-right">125.00</td>
                <td class="text-right">16%</td>
                <td class="text-right">1,292.00</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="totals">
        <div class="total-box">
            <div class="total-label">Total Artículos</div>
            <div class="total-value">2</div>
        </div>
        <div class="total-box">
            <div class="total-label">Total Piezas</div>
            <div class="total-value">15</div>
        </div>
        <div class="total-box">
            <div class="total-label">Importe Total Pedido</div>
            <div class="total-value">$ 2,452.00</div>
        </div>
    </div>

    <!-- Zona futura para ticket POS -->
    <div class="ticket">
        <div class="ticket-title">TICKET POS DEMO</div>
        <div>AssistPro Kardex</div>
        <div>Folio: S202511142</div>
        <div>Cliente: CVE001</div>
        <hr>
        <div>ART-001 x 10 &nbsp; @100.00</div>
        <div>ART-002 x 5 &nbsp;&nbsp; @250.00</div>
        <hr>
        <div>SUBTOTAL: 2,100.00</div>
        <div>IVA 16%: 352.00</div>
        <div>TOTAL:   2,452.00</div>
        <hr>
        <div style="text-align:center;">¡Gracias por su compra!</div>
    </div>
</div>

</body>
</html>
