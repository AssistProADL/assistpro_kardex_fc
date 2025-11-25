<?php
// public/pedido.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Frame general con menús globales
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
    :root{
        --ap-primary:#0F5AAD;
        --ap-primary-light:#00A3E0;
        --ap-danger:#F05252;
        --ap-bg:#F5F7FB;
        --ap-card:#FFFFFF;
        --ap-border:#E5E7EB;
        --ap-text:#111827;
        --ap-muted:#6B7280;
    }

    body{
        background-color:var(--ap-bg);
    }

    .ap-page-wrapper{
        padding:16px 24px 40px 24px;
    }

    .ap-title{
        font-size:20px;
        font-weight:700;
        margin:0 0 12px 0;
        color:var(--ap-primary);
    }

    .ap-card{
        background:var(--ap-card);
        border-radius:10px;
        border:1px solid var(--ap-border);
        padding:16px 18px;
        margin-bottom:14px;
    }

    .ap-section-title{
        font-size:15px;
        margin:0 0 12px 0;
        font-weight:600;
        color:#374151;
    }

    .ap-grid{
        display:grid;
        grid-template-columns:repeat(12,minmax(0,1fr));
        gap:10px 14px;
    }
    .ap-col-2{grid-column:span 2;}
    .ap-col-3{grid-column:span 3;}
    .ap-col-4{grid-column:span 4;}
    .ap-col-6{grid-column:span 6;}
    .ap-col-8{grid-column:span 8;}
    .ap-col-12{grid-column:span 12;}

    @media (max-width:1200px){
        .ap-col-2,.ap-col-3,.ap-col-4,.ap-col-6,.ap-col-8{grid-column:span 6;}
    }
    @media (max-width:768px){
        .ap-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
        .ap-col-2,.ap-col-3,.ap-col-4,.ap-col-6,.ap-col-8,.ap-col-12{grid-column:span 2;}
    }

    label{
        display:block;
        font-size:11px;
        color:var(--ap-muted);
        margin-bottom:3px;
    }
    .ap-required::after{
        content:"*";
        color:#DC2626;
        margin-left:3px;
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
        border:1px solid var(--ap-border);
        background:#FFFFFF;
    }
    input[readonly], textarea[readonly], .ap-ro{
        background:#F3F4F6;
        color:#4B5563;
    }
    textarea{
        resize:vertical;
        min-height:60px;
    }

    .ap-inline-options{
        display:flex;
        flex-wrap:wrap;
        gap:10px 24px;
        font-size:12px;
    }
    .ap-inline-options label{
        margin-bottom:0;
        display:flex;
        align-items:center;
        gap:4px;
        cursor:pointer;
        font-size:12px;
        color:#374151;
    }

    .ap-btn{
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
        white-space:nowrap;
    }
    .ap-btn-primary{
        background:var(--ap-primary);
        color:white;
    }
    .ap-btn-secondary{
        background:#E5E7EB;
        color:#111827;
    }
    .ap-btn-danger{
        background:var(--ap-danger);
        color:#FFFFFF;
    }
    .ap-btn-sm{
        padding:5px 10px;
        font-size:11px;
    }
    .ap-btn-lg{
        padding:9px 18px;
        font-size:13px;
    }

    .ap-flex{display:flex;}
    .ap-flex-between{display:flex;align-items:center;justify-content:space-between;}
    .ap-gap-8{gap:8px;}
    .ap-gap-12{gap:12px;}
    .ap-mt-8{margin-top:8px;}
    .ap-mt-12{margin-top:12px;}
    .ap-mt-16{margin-top:16px;}
    .ap-text-right{text-align:right;}

    /* Detalle */
    .ap-detail-card{
        border-radius:10px;
        border:1px solid var(--ap-border);
        background:#F9FAFB;
        padding:14px 16px;
    }

    .ap-table-wrapper{
        width:100%;
        overflow-x:auto;
    }
    table.ap-table{
        width:100%;
        border-collapse:collapse;
        font-size:11px;
        min-width:960px;
    }
    .ap-table th,
    .ap-table td{
        border-bottom:1px solid #E5E7EB;
        padding:6px 8px;
        white-space:nowrap;
    }
    .ap-table th{
        font-weight:600;
        color:#6B7280;
        background:#F9FAFB;
        text-align:left;
    }
    .ap-table tr:nth-child(even) td{
        background:#FDFDFE;
    }

    .ap-totals{
        display:grid;
        grid-template-columns:repeat(12,minmax(0,1fr));
        gap:12px;
        margin-top:14px;
    }
    .ap-total-box{
        grid-column:span 4;
        background:#F9FAFB;
        border-radius:10px;
        border:1px solid #E5E7EB;
        padding:10px 12px;
        font-size:12px;
    }
    .ap-total-label{
        color:#6B7280;
        margin-bottom:3px;
    }
    .ap-total-value{
        font-size:14px;
        font-weight:700;
        color:#111827;
    }
    @media (max-width:900px){
        .ap-total-box{grid-column:span 12;}
    }

    .ap-footer-actions{
        margin-top:18px;
        display:flex;
        justify-content:flex-end;
    }

    .ap-help-text{
        font-size:11px;
        color:#9CA3AF;
        margin-top:4px;
    }
</style>

<div class="ap-page-wrapper">
    <h1 class="ap-title">Registro de Pedidos</h1>

    <!-- ===================== Sección 1: Empresa y Almacén ===================== -->
    <div class="ap-card">
        <h2 class="ap-section-title">1. Empresa y Almacén</h2>
        <div class="ap-grid">
            <div class="ap-col-4">
                <label class="ap-required">Empresa</label>
                <select>
                    <option>Empresa Demo 1</option>
                    <option>Empresa Demo 2</option>
                </select>
            </div>
            <div class="ap-col-4">
                <label class="ap-required">Almacén</label>
                <select>
                    <option>(ID100) - Operador Logístico 3PL</option>
                    <option>(ID200) - Almacén Norte</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===================== Sección 2: Pedido Externo ===================== -->
    <div class="ap-card">
        <h2 class="ap-section-title">2. Pedido Externo</h2>
        <div class="ap-grid">
            <div class="ap-col-3">
                <label class="ap-required">Folio</label>
                <input type="text" value="S202511142" readonly>
            </div>

            <div class="ap-col-3">
                <label class="ap-required">Cliente</label>
                <input type="text" placeholder="Código del Cliente">
            </div>

            <div class="ap-col-3">
                <label class="ap-required">Usuario que solicita</label>
                <select>
                    <option>Seleccione</option>
                    <option>Usuario 1</option>
                    <option>Usuario 2</option>
                </select>
            </div>

            <div class="ap-col-3">
                <label>Prioridad</label>
                <select>
                    <option>1 - Urgente</option>
                    <option>2 - Alta</option>
                    <option>3 - Normal</option>
                </select>
            </div>

            <div class="ap-col-6">
                <label>Nombre Cliente</label>
                <input type="text" class="ap-ro" value="Sin Cliente" readonly>
            </div>

            <div class="ap-col-3">
                <label class="ap-required">Fecha de entrega solicitada</label>
                <input type="date" value="2025-11-19">
            </div>

            <div class="ap-col-3">
                <label>Tipo de Venta</label>
                <div class="ap-inline-options">
                    <label><input type="radio" name="tipo_venta" value="venta"> Venta</label>
                    <label><input type="radio" name="tipo_venta" value="preventa" checked> Pre Venta</label>
                </div>
            </div>

            <div class="ap-col-2">
                <label>Horario Desde</label>
                <input type="time">
            </div>
            <div class="ap-col-2">
                <label>Horario Hasta</label>
                <input type="time">
            </div>

            <div class="ap-col-6">
                <label class="ap-required">Dirección de Entrega</label>
                <div class="ap-flex ap-gap-8">
                    <select style="flex:1;">
                        <option>Seleccione</option>
                    </select>
                    <button type="button" class="ap-btn ap-btn-secondary ap-btn-sm">Agregar Destinatario</button>
                </div>
                <div class="ap-help-text">Dirección asociada al cliente para el envío del pedido.</div>
            </div>

            <div class="ap-col-3">
                <label>Contacto</label>
                <select>
                    <option>Seleccione</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===================== Sección 3: Pedido Interno ===================== -->
    <div class="ap-card">
        <h2 class="ap-section-title">3. Pedido Interno</h2>
        <div class="ap-grid">
            <div class="ap-col-4">
                <label>Ruta Venta | Preventa</label>
                <select>
                    <option>Ruta</option>
                    <option>Ruta 1</option>
                    <option>Ruta 2</option>
                </select>
            </div>
            <div class="ap-col-4">
                <label>Vendedor / Agente</label>
                <select>
                    <option>Seleccione</option>
                    <option>Vendedor 1</option>
                    <option>Vendedor 2</option>
                </select>
            </div>
            <div class="ap-col-4">
                <label>Dia Operativo</label>
                <input type="text" class="ap-ro" value="" placeholder="Ej. Lunes">
            </div>
        </div>
    </div>

    <!-- ===================== Sección 4: Registro del Detalle ===================== -->
    <div class="ap-card">
        <h2 class="ap-section-title">4. Registro del Detalle</h2>

        <div class="ap-detail-card">
            <div class="ap-grid">

                <!-- Fila 1: Proyecto / Contenedor / Pallet / Lote / Lote Alterno -->
                <div class="ap-col-3">
                    <label class="ap-required">Proyecto</label>
                    <select id="proyecto">
                        <option>Seleccione Proyecto</option>
                    </select>
                </div>

                <div class="ap-col-2">
                    <label>Contenedor</label>
                    <select id="contenedor">
                        <option>Seleccione Contenedor</option>
                    </select>
                </div>

                <div class="ap-col-2">
                    <label>Pallet</label>
                    <select id="pallet">
                        <option>Seleccione Pallet</option>
                    </select>
                </div>

                <div class="ap-col-3">
                    <label>Lote | Serie</label>
                    <select id="lote">
                        <option>Seleccione</option>
                    </select>
                </div>

                <div class="ap-col-2">
                    <label>Lote | Serie Alterno</label>
                    <select id="lote_alt">
                        <option>Seleccione Alterno</option>
                    </select>
                </div>

                <!-- Fila 2: Artículo / UOM / Cantidad / Precio / Subtotal -->
                <div class="ap-col-4">
                    <label class="ap-required">Artículo</label>
                    <select id="articulo">
                        <option>Seleccione un Artículo</option>
                    </select>
                </div>

                <div class="ap-col-2">
                    <label class="ap-required">Unidad de Medida</label>
                    <select id="uom">
                        <option>PZA</option>
                        <option>CJA</option>
                    </select>
                </div>

                <div class="ap-col-2">
                    <label class="ap-required">Cantidad</label>
                    <input type="number" id="cantidad" min="0" placeholder="Cantidad">
                </div>

                <div class="ap-col-2">
                    <label>Precio Unitario</label>
                    <input type="number" id="precio" step="0.01" min="0" value="0.00">
                </div>

                <div class="ap-col-2">
                    <label>SubTotal</label>
                    <input type="text" id="subtotal" class="ap-ro" readonly value="0.00">
                </div>

                <!-- Fila 3: Descuentos, IVA, Importe -->
                <div class="ap-col-3">
                    <label>Descuento %</label>
                    <input type="number" id="descuento_pct" step="0.01" min="0" value="0">
                </div>

                <div class="ap-col-3">
                    <label>Descuento $</label>
                    <input type="number" id="descuento_monto" step="0.01" min="0" value="0.00">
                </div>

                <div class="ap-col-3">
                    <label>IVA</label>
                    <input type="number" id="iva" step="0.01" min="0" value="0.16">
                </div>

                <div class="ap-col-3">
                    <label>Importe Total</label>
                    <input type="text" id="importe" class="ap-ro" readonly value="0.00">
                </div>

            </div>

            <div class="ap-flex-between ap-mt-12">
                <div class="ap-help-text">
                    Capture la partida y presione <strong>Agregar</strong> para registrarla en la grilla.
                </div>
                <div class="ap-flex ap-gap-8">
                    <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" onclick="apAgregarPartida()">Agregar</button>
                    <button type="button" class="ap-btn ap-btn-secondary ap-btn-sm">Excel</button>
                    <button type="button" class="ap-btn ap-btn-danger ap-btn-sm">Imprimir Venta</button>
                </div>
            </div>
        </div>

        <!-- Grilla ordenada -->
        <div class="ap-table-wrapper ap-mt-16">
            <table class="ap-table" id="tblDetalle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Proyecto</th>
                    <th>Contenedor</th>
                    <th>Pallet</th>
                    <th>Artículo</th>
                    <th>UOM</th>
                    <th>Lote|Serie</th>
                    <th>Lote|Serie Alterno</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Descto %</th>
                    <th>Descto $</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <!-- se llena vía JS -->
                </tbody>
            </table>
        </div>

        <div class="ap-help-text ap-mt-8">
            Grilla ordenada y limitada a 25 registros por página, con scroll horizontal y vertical según estándar AssistPro.
        </div>

        <div class="ap-totals">
            <div class="ap-total-box">
                <div class="ap-total-label">Total Artículos</div>
                <div class="ap-total-value" id="totalArticulos">0</div>
            </div>
            <div class="ap-total-box">
                <div class="ap-total-label">Total Piezas</div>
                <div class="ap-total-value" id="totalPiezas">0</div>
            </div>
            <div class="ap-total-box">
                <div class="ap-total-label">Importe Total Pedido</div>
                <div class="ap-total-value" id="totalImporte">0.00</div>
            </div>
        </div>

        <div class="ap-footer-actions">
            <button type="button" class="ap-btn ap-btn-primary ap-btn-lg">Guardar</button>
        </div>
    </div>
</div>

<script>
    const apPartidas = [];

    function apCalcImportesPartida() {
        const precio = Number(document.getElementById('precio').value || 0);
        const cantidad = Number(document.getElementById('cantidad').value || 0);
        const descMonto = Number(document.getElementById('descuento_monto').value || 0);
        const descPct = Number(document.getElementById('descuento_pct').value || 0);
        const ivaPct = Number(document.getElementById('iva').value || 0);

        let subtotal = precio * cantidad;
        if (descPct > 0) subtotal = subtotal * (1 - (descPct / 100));
        subtotal = subtotal - descMonto;
        if (subtotal < 0) subtotal = 0;

        const iva = subtotal * ivaPct;
        const total = subtotal + iva;

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('importe').value = total.toFixed(2);
    }

    ['precio','cantidad','descuento_monto','descuento_pct','iva'].forEach(id=>{
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', apCalcImportesPartida);
    });

    function apAgregarPartida() {
        const articuloSel = document.getElementById('articulo');
        if (!articuloSel.value) {
            alert('Seleccione un artículo para agregar.');
            return;
        }

        const data = {
            proyecto: document.getElementById('proyecto').value,
            contenedor: document.getElementById('contenedor').value,
            pallet: document.getElementById('pallet').value,
            articulo: articuloSel.options[articuloSel.selectedIndex].text,
            uom: document.getElementById('uom').value,
            lote: document.getElementById('lote').value,
            lote_alt: document.getElementById('lote_alt').value,
            cantidad: Number(document.getElementById('cantidad').value || 0),
            precio: Number(document.getElementById('precio').value || 0),
            descuento_pct: Number(document.getElementById('descuento_pct').value || 0),
            descuento_monto: Number(document.getElementById('descuento_monto').value || 0),
            iva: Number(document.getElementById('iva').value || 0),
            importe: Number(document.getElementById('importe').value || 0)
        };

        apPartidas.push(data);
        apRenderPartidas();
    }

    function apEliminarPartida(idx){
        apPartidas.splice(idx,1);
        apRenderPartidas();
    }

    function apRenderPartidas() {
        const tbody = document.querySelector('#tblDetalle tbody');
        tbody.innerHTML = '';

        let totalArt = apPartidas.length;
        let totalPzas = 0;
        let totalImporte = 0;

        apPartidas.forEach((p, idx) => {
            totalPzas += p.cantidad;
            totalImporte += p.importe;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td>${p.proyecto || ''}</td>
                <td>${p.contenedor || ''}</td>
                <td>${p.pallet || ''}</td>
                <td>${p.articulo || ''}</td>
                <td>${p.uom || ''}</td>
                <td>${p.lote || ''}</td>
                <td>${p.lote_alt || ''}</td>
                <td class="ap-text-right">${p.cantidad.toLocaleString()}</td>
                <td class="ap-text-right">${p.precio.toFixed(2)}</td>
                <td class="ap-text-right">${p.descuento_pct.toFixed(2)}</td>
                <td class="ap-text-right">${p.descuento_monto.toFixed(2)}</td>
                <td class="ap-text-right">${p.iva.toFixed(2)}</td>
                <td class="ap-text-right">${p.importe.toFixed(2)}</td>
                <td>
                    <button type="button" class="ap-btn ap-btn-secondary ap-btn-sm" onclick="apEliminarPartida(${idx})">Eliminar</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('totalArticulos').textContent = totalArt;
        document.getElementById('totalPiezas').textContent = totalPzas.toLocaleString();
        document.getElementById('totalImporte').textContent = totalImporte.toFixed(2);
    }
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
