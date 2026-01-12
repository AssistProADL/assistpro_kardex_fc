<?php
require_once __DIR__ . '/../../app/db.php';
include __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();
$ID_EMPRESA = '1';

$query_rutas = "SELECT ID_Ruta, descripcion FROM t_ruta ORDER BY ID_Ruta";
$stmt_rutas = $pdo->prepare($query_rutas);
$stmt_rutas->execute();
$rutas = $stmt_rutas->fetchAll(PDO::FETCH_ASSOC);

$ruta_sel_raw = $_GET['ruta_id'] ?? '';
$ruta_is_all  = ($ruta_sel_raw === 'all');
$ruta_sel     = $ruta_is_all ? 0 : (int)$ruta_sel_raw;

$params = [':idEmpresa' => $ID_EMPRESA];
$whereRuta = "";
if(!$ruta_is_all && $ruta_sel > 0){
  $whereRuta = " AND rc.IdRuta = :idRuta ";
  $params[':idRuta'] = $ruta_sel;
}

$query_clientes = "
  SELECT
    rc.IdRuta,
    rc.IdEmpresa,
    d.id_destinatario,
    d.Cve_Clte,
    d.razonsocial AS nombre_tienda,
    d.direccion,
    d.colonia,
    d.postal,
    d.ciudad,
    d.estado,
    d.contacto,
    d.telefono,
    d.email_destinatario,
    d.latitud,
    d.longitud,
    c.RazonSocial AS razon_social_cliente,
    c.dias_credito,
    c.saldo_deudor_inicial,
    c.limite_credito
  FROM relclirutas rc
  JOIN c_destinatarios d
    ON d.id_destinatario = CAST(rc.IdCliente AS UNSIGNED)
  LEFT JOIN c_cliente c
    ON c.Cve_Clte = d.Cve_Clte
  WHERE rc.IdEmpresa = :idEmpresa
    $whereRuta
    AND IFNULL(d.Activo,'1')='1'
";

$stmt_clientes = $pdo->prepare($query_clientes);
$stmt_clientes->execute($params);
$clientes_data = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

$clientes_json = json_encode($clientes_data, JSON_UNESCAPED_UNICODE);
$rutas_json    = json_encode($rutas, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Distribución de Clientes por Ruta</title>

  <!-- Google Maps -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC5xF7JtKzw9cTRRXcDAqTThbYnMCiYOVM&callback=initMap" async defer></script>

  <!-- MarkerClusterer (oficial Google - CDN) -->
  <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>

  <style>
    .wrap{padding:10px 12px}
    .h1{font-size:34px;font-weight:800;margin:0 0 8px 0}
    .bar{display:flex;gap:10px;align-items:center;margin:8px 0 10px 0;flex-wrap:wrap}
    label{font-size:14px;font-weight:600;color:#0b5ed7}
    select{font-size:13px;padding:6px 8px;border-radius:8px;border:1px solid #d0d7e2;min-width:320px}
    .badge{border:1px solid #d0d7e2;border-radius:999px;padding:4px 10px;font-size:12px;color:#6c757d;background:#fff}

    .grid{display:grid;grid-template-columns: 1fr 360px; gap:10px; align-items:stretch}
    #map{height:650px;width:100%;border-radius:10px;border:1px solid #d0d7e2}

    .panel{
      background:#fff;border:1px solid #d0d7e2;border-radius:10px;
      padding:10px; height:650px; overflow:auto;
      box-shadow:0 1px 3px rgba(0,0,0,.05)
    }
    .p-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .p-title b{font-size:14px;color:#0b5ed7}
    .p-muted{font-size:12px;color:#6c757d}
    .p-row{margin:8px 0;font-size:12px}
    .p-row b{display:inline-block;min-width:120px;color:#1f2d3d}
    .pill{display:inline-block;border:1px solid #d0d7e2;border-radius:999px;padding:3px 8px;font-size:11px;color:#6c757d;background:#f8fafc}
    .money{font-weight:800}
    .risk-ok{color:#198754;font-weight:700}
    .risk-mid{color:#fd7e14;font-weight:700}
    .risk-hi{color:#dc3545;font-weight:700}

    @media (max-width: 1100px){
      .grid{grid-template-columns: 1fr}
      .panel{height:auto}
      #map{height:520px}
    }
  </style>
</head>
<body>
<div class="wrap">
  <div class="h1">Distribución de Clientes en el Mapa</div>

  <div class="bar">
    <label for="ruta_select">Ruta:</label>
    <select id="ruta_select" onchange="updateMap()">
      <option value="">Selecciona una ruta</option>
      <option value="all" <?php echo $ruta_is_all ? 'selected' : ''; ?>>Todas las rutas</option>
      <?php foreach($rutas as $r): ?>
        <option value="<?php echo (int)$r['ID_Ruta']; ?>" <?php echo (!$ruta_is_all && $ruta_sel==(int)$r['ID_Ruta'])?'selected':''; ?>>
          <?php echo htmlspecialchars($r['descripcion']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <span class="badge">Empresa: <?php echo htmlspecialchars($ID_EMPRESA); ?></span>
    <span class="badge" id="badgeCount">Clientes: 0</span>
    <span class="badge" id="badgeRoutes" style="display:none">Rutas: 0</span>
  </div>

  <div class="grid">
    <div id="map"></div>

    <!-- Panel lateral -->
    <div class="panel">
      <div class="p-title">
        <b>Detalle del Cliente</b>
        <span class="pill" id="pRuta">Ruta: —</span>
      </div>
      <div class="p-muted" id="pHint">Selecciona un marcador para ver la ficha comercial.</div>

      <div id="pBody" style="display:none">
        <div class="p-row"><b>Tienda:</b> <span id="pTienda">—</span></div>
        <div class="p-row"><b>Cliente (Cve):</b> <span id="pCve">—</span></div>
        <div class="p-row"><b>Contacto:</b> <span id="pContacto">—</span></div>
        <div class="p-row"><b>Teléfono:</b> <span id="pTel">—</span></div>
        <div class="p-row"><b>Email:</b> <span id="pMail">—</span></div>

        <hr>

        <div class="p-row"><b>Dirección:</b> <span id="pDir">—</span></div>
        <div class="p-row"><b>Colonia:</b> <span id="pCol">—</span></div>
        <div class="p-row"><b>CP:</b> <span id="pCP">—</span></div>
        <div class="p-row"><b>Ciudad/Estado:</b> <span id="pCiudad">—</span></div>

        <hr>

        <div class="p-row"><b>Días crédito:</b> <span id="pDias">—</span></div>
        <div class="p-row"><b>Saldo deudor:</b> <span class="money" id="pSaldo">—</span></div>
        <div class="p-row"><b>Límite:</b> <span class="money" id="pLimite">—</span></div>
        <div class="p-row"><b>Riesgo:</b> <span id="pRiesgo">—</span></div>

        <hr>

        <div class="p-row"><b>GPS:</b> <span id="pGps">—</span></div>
      </div>
    </div>
  </div>
</div>

<script>
  const clientes = <?php echo $clientes_json ?: '[]'; ?>;
  const rutas = <?php echo $rutas_json ?: '[]'; ?>;

  let map, clusterer;
  let markers = [];
  let infoWindow;

  // Paleta corporativa (rotación por ruta)
  const COLORS = [
    "#0b5ed7","#198754","#6f42c1","#fd7e14","#20c997",
    "#dc3545","#0dcaf0","#6610f2","#6c757d","#212529"
  ];

  function money(n){
    n = Number(n || 0);
    return n.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
  }

  function rutaNombre(idRuta){
    const r = rutas.find(x => String(x.ID_Ruta) === String(idRuta));
    return r ? r.descripcion : String(idRuta || '—');
  }

  function colorByRuta(idRuta){
    const i = Math.abs(parseInt(idRuta || 0, 10)) % COLORS.length;
    return COLORS[i];
  }

  // Crea un SVG marker con color por ruta
  function svgMarker(color){
    const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="${color}">
        <path d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>
      </svg>`;
    return {
      url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg),
      scaledSize: new google.maps.Size(28, 28),
      anchor: new google.maps.Point(14, 28)
    };
  }

  function initMap(){
    map = new google.maps.Map(document.getElementById("map"), {
      zoom: 10,
      center: { lat: 19.432608, lng: -99.133209 },
      mapTypeControl: false,
      streetViewControl: false
    });

    infoWindow = new google.maps.InfoWindow();

    // Badges
    document.getElementById("badgeCount").textContent = "Clientes: " + (clientes ? clientes.length : 0);

    const rutaSel = document.getElementById('ruta_select').value;
    if(rutaSel === 'all'){
      const setR = new Set();
      (clientes||[]).forEach(c => setR.add(String(c.IdRuta||'')));
      document.getElementById('badgeRoutes').style.display = '';
      document.getElementById('badgeRoutes').textContent = 'Rutas: ' + setR.size;
    }

    if(!clientes || clientes.length===0) return;

    const bounds = new google.maps.LatLngBounds();

    markers = (clientes||[])
      .map(c => {
        const lat = parseFloat(c.latitud);
        const lng = parseFloat(c.longitud);
        if(isNaN(lat) || isNaN(lng)) return null;

        const color = colorByRuta(c.IdRuta);
        const marker = new google.maps.Marker({
          position: { lat, lng },
          title: c.nombre_tienda || c.razon_social_cliente || c.Cve_Clte || '',
          icon: svgMarker(color)
        });

        marker.addListener("click", () => {
          // InfoWindow rápido
          const html = `
            <div style="font-size:13px;line-height:1.25">
              <div style="font-weight:800;color:#0b5ed7">${(c.nombre_tienda||c.razon_social_cliente||'Cliente')}</div>
              <div><b>Ruta:</b> ${rutaNombre(c.IdRuta)}</div>
              <div><b>Cliente:</b> ${(c.Cve_Clte||'—')}</div>
              <div><b>Crédito:</b> ${(c.dias_credito ?? '—')} días</div>
              <div><b>Saldo:</b> ${money(c.saldo_deudor_inicial||0)}</div>
            </div>
          `;
          infoWindow.setContent(html);
          infoWindow.open(map, marker);

          // Panel lateral (ficha comercial)
          fillPanel(c);
        });

        bounds.extend(marker.getPosition());
        return marker;
      })
      .filter(Boolean);

    // Cluster (mejor UX para “Todas las rutas”)
    clusterer = new markerClusterer.MarkerClusterer({ map, markers });

    if(!bounds.isEmpty()){
      map.fitBounds(bounds);
    }
  }

  function riskLabel(saldo, limite){
    saldo = Number(saldo || 0);
    limite = Number(limite || 0);

    if(limite <= 0){
      // demo: si no hay límite, califica por saldo
      if(saldo <= 3000) return `<span class="risk-ok">Bajo</span>`;
      if(saldo <= 9000) return `<span class="risk-mid">Medio</span>`;
      return `<span class="risk-hi">Alto</span>`;
    }

    const ratio = saldo / limite;
    if(ratio <= 0.30) return `<span class="risk-ok">Bajo</span>`;
    if(ratio <= 0.70) return `<span class="risk-mid">Medio</span>`;
    return `<span class="risk-hi">Alto</span>`;
  }

  function fillPanel(c){
    document.getElementById('pHint').style.display = 'none';
    document.getElementById('pBody').style.display = '';

    document.getElementById('pRuta').textContent = 'Ruta: ' + rutaNombre(c.IdRuta);

    document.getElementById('pTienda').textContent = (c.nombre_tienda || c.razon_social_cliente || '—');
    document.getElementById('pCve').textContent = (c.Cve_Clte || '—');
    document.getElementById('pContacto').textContent = (c.contacto || '—');
    document.getElementById('pTel').textContent = (c.telefono || '—');
    document.getElementById('pMail').textContent = (c.email_destinatario || '—');

    document.getElementById('pDir').textContent = (c.direccion || '—');
    document.getElementById('pCol').textContent = (c.colonia || '—');
    document.getElementById('pCP').textContent = (c.postal || '—');
    document.getElementById('pCiudad').textContent = ((c.ciudad || '') + (c.estado ? (', ' + c.estado) : '')).trim() || '—';

    const dias = (c.dias_credito ?? '—');
    const saldo = money(c.saldo_deudor_inicial || 0);
    const limite = money(c.limite_credito || 0);

    document.getElementById('pDias').textContent = dias;
    document.getElementById('pSaldo').textContent = saldo;
    document.getElementById('pLimite').textContent = limite;
    document.getElementById('pRiesgo').innerHTML = riskLabel(c.saldo_deudor_inicial, c.limite_credito);

    document.getElementById('pGps').textContent = (c.latitud || '—') + ', ' + (c.longitud || '—');
  }

  function updateMap(){
    const rutaId = document.getElementById('ruta_select').value;
    if(rutaId){
      window.location.href = '?ruta_id=' + encodeURIComponent(rutaId);
    }else{
      window.location.href = window.location.pathname;
    }
  }
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
