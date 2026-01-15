<?php
// /public/api/stock/existencias_ubicacion_total.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  echo json_encode(["ok"=>1, "status"=>"preflight"]);
  exit;
}

function getv(string $k, $default=null) {
  return isset($_GET[$k]) ? $_GET[$k] : $default;
}
function geti(string $k, int $default=0): int {
  return isset($_GET[$k]) ? (int)$_GET[$k] : $default;
}
function getb(string $k, bool $default=false): bool {
  if (!isset($_GET[$k])) return $default;
  $v = strtolower((string)$_GET[$k]);
  return in_array($v, ['1','true','yes','y','si'], true);
}

try {
  // db.php vive en /app/db.php (tu estructura)
  require_once __DIR__ . '/../../../app/db.php';

  // --------------------------
  // Params (GET)
  // --------------------------
  $cve_articulo     = trim((string)getv('cve_articulo', ''));
  $cve_lote         = trim((string)getv('cve_lote', ''));
  $nivel            = strtoupper(trim((string)getv('nivel', ''))); // PZ | CJ | TR (o vacío = todos)
  $cve_almac        = getv('cve_almac', null);
  $idy_ubica        = getv('idy_ubica', null);
  $solo_disponible  = getb('solo_disponible', false); // si=1, filtra existencia_disponible>0
  $incluir_cero     = getb('incluir_cero', false);    // si=1, incluye registros con 0
  $limit            = geti('limit', 500);
  $offset           = geti('offset', 0);

  if ($limit <= 0) $limit = 500;
  if ($limit > 5000) $limit = 5000;
  if ($offset < 0) $offset = 0;

  // --------------------------
  // SQL base:
  // v_inv_existencia_multinivel: nivel, cve_almac, idy_ubica, bl, cve_articulo, cve_lote,
  //                              Id_Caja, nTarima, cantidad, epc, code, fuente
  // --------------------------
  // Cuarentena: t_movcuarentena (Fec_Libera IS NULL) por Articulo/Lote/Ubica
  // Reservado picking: vs_apartadoparasurtido por Articulo/Lote/Ubica
  // Obsolescencia: c_lotes.Caducidad < CURDATE()
  //
  // Nota: Métricas QA/RP/Obsoleto solo aplican a PZ (por ahora).
  $sql = "
    SELECT
      v.nivel,
      v.cve_almac,
      v.idy_ubica,
      v.bl,
      v.cve_articulo,
      v.cve_lote,
      v.Id_Caja,
      v.nTarima,
      v.cantidad AS existencia_total,
      v.epc,
      v.code,
      v.fuente,

      CASE WHEN v.nivel='PZ' THEN IFNULL(q.cantidad_q,0) ELSE 0 END AS en_cuarentena,
      CASE WHEN v.nivel='PZ' THEN IFNULL(rp.apartadas,0)  ELSE 0 END AS reservado_picking,
      CASE
        WHEN v.nivel='PZ' AND v.cve_lote IS NOT NULL AND v.cve_lote<>'' AND l.Caducidad IS NOT NULL AND l.Caducidad < CURDATE()
        THEN v.cantidad
        ELSE 0
      END AS obsoleto,

      CASE
        WHEN v.nivel='PZ' THEN
          ( v.cantidad
            - IFNULL(q.cantidad_q,0)
            - IFNULL(rp.apartadas,0)
            - CASE
                WHEN v.cve_lote IS NOT NULL AND v.cve_lote<>'' AND l.Caducidad IS NOT NULL AND l.Caducidad < CURDATE()
                THEN v.cantidad
                ELSE 0
              END
          )
        ELSE
          v.cantidad
      END AS existencia_disponible

    FROM v_inv_existencia_multinivel v

    LEFT JOIN (
      SELECT
        Cve_Articulo,
        Cve_Lote,
        Idy_Ubica,
        SUM(Cantidad) AS cantidad_q
      FROM t_movcuarentena
      WHERE Fec_Libera IS NULL
      GROUP BY Cve_Articulo, Cve_Lote, Idy_Ubica
    ) q
      ON q.Cve_Articulo = v.cve_articulo
     AND IFNULL(q.Cve_Lote,'') = IFNULL(v.cve_lote,'')
     AND q.Idy_Ubica = v.idy_ubica

    LEFT JOIN vs_apartadoparasurtido rp
      ON rp.Idy_Ubica = v.idy_ubica
     AND rp.Cve_Articulo = v.cve_articulo
     AND IFNULL(rp.cve_lote,'') = IFNULL(v.cve_lote,'')

    LEFT JOIN c_lotes l
      ON l.cve_articulo = v.cve_articulo
     AND l.Lote = v.cve_lote

    WHERE 1=1
  ";

  // --------------------------
  // WHERE dinámico (sin HY093)
  // --------------------------
  $params = [];

  if ($cve_articulo !== '') {
    $sql .= " AND v.cve_articulo = :cve_articulo";
    $params[':cve_articulo'] = $cve_articulo;
  }
  if ($cve_lote !== '') {
    $sql .= " AND IFNULL(v.cve_lote,'') = :cve_lote";
    $params[':cve_lote'] = $cve_lote;
  }
  if ($nivel !== '' && in_array($nivel, ['PZ','CJ','TR'], true)) {
    $sql .= " AND v.nivel = :nivel";
    $params[':nivel'] = $nivel;
  }
  if ($cve_almac !== null && $cve_almac !== '') {
    $sql .= " AND v.cve_almac = :cve_almac";
    $params[':cve_almac'] = (int)$cve_almac;
  }
  if ($idy_ubica !== null && $idy_ubica !== '') {
    $sql .= " AND v.idy_ubica = :idy_ubica";
    $params[':idy_ubica'] = (int)$idy_ubica;
  }

  if (!$incluir_cero) {
    $sql .= " AND v.cantidad <> 0";
  }

  if ($solo_disponible) {
    // Repite lógica en WHERE para evitar alias en algunos motores
    $sql .= " AND (
      CASE
        WHEN v.nivel='PZ' THEN
          ( v.cantidad
            - IFNULL(q.cantidad_q,0)
            - IFNULL(rp.apartadas,0)
            - CASE
                WHEN v.cve_lote IS NOT NULL AND v.cve_lote<>'' AND l.Caducidad IS NOT NULL AND l.Caducidad < CURDATE()
                THEN v.cantidad
                ELSE 0
              END
          )
        ELSE
          v.cantidad
      END
    ) > 0";
  }

  $sql .= " ORDER BY v.cve_articulo, v.cve_lote, v.cve_almac, v.idy_ubica, v.nivel";
  $sql .= " LIMIT :_limit OFFSET :_offset";
  $params[':_limit']  = $limit;
  $params[':_offset'] = $offset;

  // Ejecutar
  $rows = db_all($sql, $params);

  // Totales (KPI rápido)
  $tot_exist = 0.0;
  $tot_disp  = 0.0;
  $tot_q     = 0.0;
  $tot_rp    = 0.0;
  $tot_obs   = 0.0;

  foreach ($rows as $r) {
    $tot_exist += (float)$r['existencia_total'];
    $tot_disp  += (float)$r['existencia_disponible'];
    $tot_q     += (float)$r['en_cuarentena'];
    $tot_rp    += (float)$r['reservado_picking'];
    $tot_obs   += (float)$r['obsoleto'];
  }

  echo json_encode([
    "ok" => 1,
    "service" => "existencias_ubicacion_total",
    "filters" => [
      "cve_articulo" => $cve_articulo,
      "cve_lote" => $cve_lote,
      "nivel" => $nivel,
      "cve_almac" => $cve_almac,
      "idy_ubica" => $idy_ubica,
      "solo_disponible" => $solo_disponible,
      "incluir_cero" => $incluir_cero,
      "limit" => $limit,
      "offset" => $offset
    ],
    "kpis" => [
      "existencia_total" => $tot_exist,
      "en_cuarentena" => $tot_q,
      "reservado_picking" => $tot_rp,
      "obsoleto" => $tot_obs,
      "existencia_disponible" => $tot_disp
    ],
    "rows" => $rows
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode([
    "ok" => 0,
    "error" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
