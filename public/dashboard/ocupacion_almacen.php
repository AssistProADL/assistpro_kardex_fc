<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
/* === AssistPro Style === */
.ap-container {
    padding: 10px;
}

.ap-title {
    font-size: 20px;
    font-weight: 600;
    color: #0d6efd;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.ap-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 10px;
}

.ap-card {
    border-radius: 8px;
    padding: 10px;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
}

.ap-card h6 {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}

.ap-card span {
    font-size: 22px;
    font-weight: bold;
}

.ap-table-wrapper {
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
    overflow: auto;
    max-height: calc(25 * 34px);
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

thead {
    background: #f1f4f9;
    position: sticky;
    top: 0;
    z-index: 2;
}

th, td {
    padding: 6px 8px;
    text-align: center;
    border-bottom: 1px solid #e5e5e5;
    white-space: nowrap;
}

.badge-green { background: #198754; color: #fff; }
.badge-yellow { background: #ffc107; color: #000; }
.badge-red { background: #dc3545; color: #fff; }

.spinner {
    text-align: center;
    padding: 30px;
    font-size: 14px;
}
</style>

<div class="ap-container">

    <div class="ap-title">
        <i class="bi bi-box-seam"></i>
        Análisis de Ocupación de Almacenes
    </div>

    <!-- Cards -->
    <div class="ap-cards">
        <div class="ap-card">
            <h6>Ubicaciones</h6>
            <span id="card_ubicaciones">0</span>
        </div>
        <div class="ap-card">
            <h6>Ocupación Volumen</h6>
            <span id="card_volumen">0%</span>
        </div>
        <div class="ap-card">
            <h6>Ocupación Peso</h6>
            <span id="card_peso">0%</span>
        </div>
        <div class="ap-card">
            <h6>Almacenes</h6>
            <span id="card_almacenes">0</span>
        </div>
    </div>

    <!-- Tabla -->
    <div class="ap-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Almacén</th>
                    <th>Ubicaciones</th>
                    <th>Libres</th>
                    <th>Ocupadas</th>
                    <th>Vol. Total</th>
                    <th>Vol. Ocupado</th>
                    <th>% Vol</th>
                    <th>Peso Máx</th>
                    <th>Peso Ocupado</th>
                    <th>% Peso</th>
                </tr>
            </thead>
            <tbody id="tabla_ocupacion">
                <tr>
                    <td colspan="10" class="spinner">
                        <i class="bi bi-arrow-repeat"></i> Cargando información...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<script>
const API_URL = "../api/ocupacion_almacen.php";

fetch(API_URL)
    .then(r => r.json())
    .then(res => {

        const tbody = document.getElementById("tabla_ocupacion");
        tbody.innerHTML = "";

        if (!res.success || res.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10">Sin información</td></tr>`;
            return;
        }

        let totalUbic = 0;
        let totalVolPct = 0;
        let totalPesoPct = 0;

        res.data.forEach(row => {

            totalUbic += parseInt(row.total_ubicaciones);
            totalVolPct += parseFloat(row.ocupacion_volumen_pct);
            totalPesoPct += parseFloat(row.ocupacion_peso_pct);

            const badgeVol = row.ocupacion_volumen_pct < 70 ? 'badge-green' :
                             row.ocupacion_volumen_pct < 85 ? 'badge-yellow' : 'badge-red';

            const badgePeso = row.ocupacion_peso_pct < 70 ? 'badge-green' :
                              row.ocupacion_peso_pct < 85 ? 'badge-yellow' : 'badge-red';

            tbody.innerHTML += `
                <tr>
                    <td>${row.almacen}</td>
                    <td>${row.total_ubicaciones}</td>
                    <td>${row.ubicaciones_libres}</td>
                    <td>${row.ubicaciones_ocupadas}</td>
                    <td>${row.volumen_total}</td>
                    <td>${row.volumen_ocupado}</td>
                    <td><span class="${badgeVol}">${row.ocupacion_volumen_pct}%</span></td>
                    <td>${row.peso_maximo}</td>
                    <td>${row.peso_ocupado}</td>
                    <td><span class="${badgePeso}">${row.ocupacion_peso_pct}%</span></td>
                </tr>
            `;
        });

        document.getElementById("card_ubicaciones").innerText = totalUbic;
        document.getElementById("card_almacenes").innerText = res.data.length;
        document.getElementById("card_volumen").innerText = (totalVolPct / res.data.length).toFixed(2) + "%";
        document.getElementById("card_peso").innerText = (totalPesoPct / res.data.length).toFixed(2) + "%";

    })
    .catch(err => {
        document.getElementById("tabla_ocupacion").innerHTML =
            `<tr><td colspan="10">Error al cargar información</td></tr>`;
        console.error(err);
    });
</script>
