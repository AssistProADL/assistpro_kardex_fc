<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
  @session_start();
}

require_once __DIR__ . '/../../../app/db.php';

function jexit(array $arr, int $code = 200): void
{
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db(); // si en tu db.php es db_pdo(), cambia aquí a $pdo = db_pdo();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
  jexit(['success' => false, 'error' => 'No hay conexión BD', 'detalle' => $e->getMessage()], 500);
}

$empresa_id = (int)($_SESSION['empresa_id'] ?? 1); // dev default; prod debe venir de sesión/login
$action = strtolower(trim((string)($_GET['action'] ?? $_POST['action'] ?? '')));

if ($action === '') jexit(['success' => false, 'error' => 'Acción requerida'], 400);

try {

  switch ($action) {

    /* ======================================================
       LISTAR INSTALACIONES
    ====================================================== */
    case 'list': {
        $sql = "
        SELECT
          i.id_instalacion,
          i.folio,
          i.estado,
          i.fecha_compromiso,
          i.porcentaje_avance,
          i.aprobada,
          u.nombre_completo AS tecnico,
          p.Fol_folio AS pedido,
          p.Cve_clte AS cliente
        FROM t_instalaciones i
        LEFT JOIN c_usuario u ON u.id_user = i.id_tecnico
        LEFT JOIN th_pedido p ON p.id_pedido = i.id_pedido
        WHERE i.empresa_id = ?
        ORDER BY i.id_instalacion DESC
        LIMIT 500
      ";
        $st = $pdo->prepare($sql);
        $st->execute([$empresa_id]);
        jexit(['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
      }

      /* ======================================================
       CONSULTAR PEDIDO PARA INSTALACIÓN (header + dirección + detalle embarcado)
    ====================================================== */
    case 'consultar_pedido': {
        $id_pedido = (int)($_GET['id_pedido'] ?? 0);
        if ($id_pedido <= 0) jexit(['success' => false, 'error' => 'id_pedido inválido'], 400);

        // HEADER + cliente + destinatario por CLAVE (varchar)
        $sqlH = "
        SELECT
          h.id_pedido,
          h.Fol_folio,
          h.Cve_clte,
          h.status,
          h.destinatario,

          c.RazonSocial AS cliente_nombre,
          c.direccion   AS cliente_direccion,
          c.colonia     AS cliente_colonia,
          c.ciudad      AS cliente_ciudad,
          c.estado      AS cliente_estado,

          d.id_destinatario,
          d.razonsocial AS destinatario_nombre,
          d.direccion   AS dest_direccion,
          d.colonia     AS dest_colonia,
          d.ciudad      AS dest_ciudad,
          d.estado      AS dest_estado
        FROM th_pedido h
        LEFT JOIN c_cliente c
          ON c.Cve_Clte = h.Cve_clte
        LEFT JOIN c_destinatarios d
          ON d.clave_destinatario = h.destinatario
        WHERE h.id_pedido = ?
        LIMIT 1
      ";
        $stH = $pdo->prepare($sqlH);
        $stH->execute([$id_pedido]);
        $h = $stH->fetch(PDO::FETCH_ASSOC);
        if (!$h) jexit(['success' => false, 'error' => 'Pedido no encontrado'], 404);

        // Dirección inteligente: si hay destinatario válido, usarlo; si no, cliente
        $usaDest = !empty($h['destinatario_nombre']);
        $direccion = $usaDest ? [
          'origen'    => 'DESTINATARIO',
          'nombre'    => $h['destinatario_nombre'],
          'direccion' => $h['dest_direccion'],
          'colonia'   => $h['dest_colonia'],
          'ciudad'    => $h['dest_ciudad'],
          'estado'    => $h['dest_estado'],
          'clave'     => $h['destinatario'] ?? null,
          'id_destinatario' => $h['id_destinatario'] ?? null
        ] : [
          'origen'    => 'CLIENTE',
          'nombre'    => $h['cliente_nombre'],
          'direccion' => $h['cliente_direccion'],
          'colonia'   => $h['cliente_colonia'],
          'ciudad'    => $h['cliente_ciudad'],
          'estado'    => $h['cliente_estado'],
          'clave'     => null,
          'id_destinatario' => null
        ];

        // DETALLE con EMBARCADO consolidado (piezas)
        $sqlD = "
        SELECT
          p.id,
          p.Cve_articulo,
          p.Num_cantidad AS cantidad_pedida,
          IFNULL(SUM(s.Cantidad),0) AS cantidad_embarcada
        FROM td_pedido p
        LEFT JOIN td_surtidopiezas s
          ON s.fol_folio = p.Fol_folio
         AND s.Cve_articulo = p.Cve_articulo
         AND s.embarcado = 'S'
        WHERE p.Fol_folio = ?
        GROUP BY p.id, p.Cve_articulo, p.Num_cantidad
        ORDER BY p.itemPos ASC, p.id ASC
      ";
        $stD = $pdo->prepare($sqlD);
        $stD->execute([$h['Fol_folio']]);
        $detail = $stD->fetchAll(PDO::FETCH_ASSOC);

        // elegible si existe al menos 1 línea embarcada > 0
        $elegible = false;
        foreach ($detail as $r) {
          if ((float)$r['cantidad_embarcada'] > 0) {
            $elegible = true;
            break;
          }
        }

        jexit([
          'success' => true,
          'header' => [
            'id_pedido' => $h['id_pedido'],
            'Fol_folio' => $h['Fol_folio'],
            'Cve_clte' => $h['Cve_clte'],
            'cliente_nombre' => $h['cliente_nombre'],
            'status' => $h['status'],
            'destinatario' => $h['destinatario'],
            'direccion' => $direccion
          ],
          'detail' => $detail,
          'elegible_instalacion' => $elegible
        ]);
      }

      /* ======================================================
       CREAR INSTALACIÓN (folio viene del front/API folios)
       Valida: cantidad_instalada <= cantidad_embarcada por artículo
    ====================================================== */
    case 'create': {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) jexit(['success' => false, 'error' => 'Body inválido'], 400);

        $folio = trim((string)($input['folio'] ?? ''));
        $id_pedido  = (int)($input['id_pedido'] ?? 0);
        $id_tecnico = (int)($input['id_tecnico'] ?? 0);
        $fecha_compromiso = trim((string)($input['fecha_compromiso'] ?? ''));
        $partidas = $input['partidas'] ?? [];

        if ($folio === '' || $id_pedido <= 0 || $id_tecnico <= 0 || $fecha_compromiso === '' || !is_array($partidas) || count($partidas) === 0) {
          jexit(['success' => false, 'error' => 'Datos incompletos'], 400);
        }

        $pdo->beginTransaction();

        // 1) Obtener Fol_folio (pedido) y validar existe
        $stP = $pdo->prepare("SELECT id_pedido, Fol_folio FROM th_pedido WHERE id_pedido=? LIMIT 1");
        $stP->execute([$id_pedido]);
        $pedido = $stP->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) {
          $pdo->rollBack();
          jexit(['success' => false, 'error' => 'Pedido no existe'], 400);
        }

        $Fol_folio = $pedido['Fol_folio'];

        // 2) Construir mapa embarcado por artículo del pedido (solo embarcado='S')
        $stE = $pdo->prepare("
        SELECT s.Cve_articulo, SUM(s.Cantidad) qty
        FROM td_surtidopiezas s
        WHERE s.fol_folio = ? AND s.embarcado='S'
        GROUP BY s.Cve_articulo
      ");
        $stE->execute([$Fol_folio]);
        $emb = [];
        foreach ($stE->fetchAll(PDO::FETCH_ASSOC) as $r) {
          $emb[$r['Cve_articulo']] = (float)$r['qty'];
        }

        // 3) Insert header instalación
        $stI = $pdo->prepare("
        INSERT INTO t_instalaciones
          (empresa_id, folio, id_pedido, id_tecnico, fecha_compromiso, estado)
        VALUES
          (?, ?, ?, ?, ?, 'BORRADOR')
      ");
        $stI->execute([$empresa_id, $folio, $id_pedido, $id_tecnico, $fecha_compromiso]);
        $id_instalacion = (int)$pdo->lastInsertId();

        // 4) Insert detalle (validación por artículo vs embarcado)
        $stDet = $pdo->prepare("
        SELECT id, Fol_folio, Cve_articulo, IFNULL(cve_lote,'') AS cve_lote, Num_cantidad
        FROM td_pedido
        WHERE id = ?
        LIMIT 1
      ");

        $stInsD = $pdo->prepare("
        INSERT INTO t_instalacion_detalle
          (empresa_id, id_instalacion, fol_folio, Cve_articulo, lote, id_pedido_detalle, cantidad_instalada, estado_material)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, 'PENDIENTE')
      ");

        foreach ($partidas as $p) {
          $id_det = (int)($p['id_pedido_detalle'] ?? 0);
          $qty_inst = (float)($p['cantidad'] ?? 0);

          if ($id_det <= 0 || $qty_inst <= 0) {
            $pdo->rollBack();
            jexit(['success' => false, 'error' => 'Partida inválida'], 400);
          }

          $stDet->execute([$id_det]);
          $d = $stDet->fetch(PDO::FETCH_ASSOC);
          if (!$d) {
            $pdo->rollBack();
            jexit(['success' => false, 'error' => "Detalle pedido no existe (id=$id_det)"], 400);
          }

          // Validar que sea del mismo pedido
          if ($d['Fol_folio'] !== $Fol_folio) {
            $pdo->rollBack();
            jexit(['success' => false, 'error' => 'Partida no pertenece al pedido'], 400);
          }

          $art = (string)$d['Cve_articulo'];
          $qty_emb = (float)($emb[$art] ?? 0);

          if ($qty_emb <= 0) {
            $pdo->rollBack();
            jexit(['success' => false, 'error' => "No hay embarque para artículo $art. No se puede instalar."], 400);
          }
          if ($qty_inst > $qty_emb) {
            $pdo->rollBack();
            jexit(['success' => false, 'error' => "Cantidad a instalar ($qty_inst) excede embarcado ($qty_emb) para $art"], 400);
          }

          $stInsD->execute([
            $empresa_id,
            $id_instalacion,
            $Fol_folio,
            $art,
            (string)$d['cve_lote'],
            (int)$d['id'],
            $qty_inst
          ]);
        }

        $pdo->commit();
        jexit(['success' => true, 'id' => $id_instalacion, 'folio' => $folio]);
      }

      /* ======================================================
       DASHBOARD OPERATIVO (KPIs)
    ====================================================== */
    case 'dashboard': {

        $data = [];

        /* =============================
     1) KPI por estado
  ============================= */
        $st = $pdo->prepare("
    SELECT estado, COUNT(*) total
    FROM t_instalaciones
    WHERE empresa_id=?
    GROUP BY estado
  ");
        $st->execute([$empresa_id]);
        $data['por_estado'] = $st->fetchAll(PDO::FETCH_ASSOC);

        /* =============================
     2) Próximas
  ============================= */
        $st = $pdo->prepare("
    SELECT
      i.id_instalacion, i.folio, i.estado, i.fecha_compromiso,
      u.nombre_completo AS tecnico,
      p.Fol_folio AS pedido,
      p.Cve_clte AS cliente
    FROM t_instalaciones i
    LEFT JOIN c_usuario u ON u.id_user=i.id_tecnico
    LEFT JOIN th_pedido p ON p.id_pedido=i.id_pedido
    WHERE i.empresa_id=?
    ORDER BY i.fecha_compromiso ASC
    LIMIT 10
  ");
        $st->execute([$empresa_id]);
        $data['proximas'] = $st->fetchAll(PDO::FETCH_ASSOC);

        /* =============================
     3) Backlog vencidas
  ============================= */
        $st = $pdo->prepare("
    SELECT COUNT(*)
    FROM t_instalaciones
    WHERE empresa_id=? 
      AND estado='BORRADOR'
      AND fecha_compromiso < CURDATE()
  ");
        $st->execute([$empresa_id]);
        $data['backlog_vencidas'] = (int)$st->fetchColumn();

        /* =============================
     4) SLA Cumplimiento
  ============================= */
        $st = $pdo->prepare("
    SELECT
      COUNT(*) total,
      SUM(
        CASE 
          WHEN estado='COMPLETADO'
           AND fecha_fin_real IS NOT NULL
           AND fecha_fin_real <= fecha_compromiso
          THEN 1 ELSE 0 END
      ) cumplidas
    FROM t_instalaciones
    WHERE empresa_id=?
      AND estado='COMPLETADO'
  ");
        $st->execute([$empresa_id]);
        $sla = $st->fetch(PDO::FETCH_ASSOC);
        $data['sla_total'] = (int)$sla['total'];
        $data['sla_cumplidas'] = (int)$sla['cumplidas'];
        $data['sla_pct'] = $sla['total'] > 0
          ? round(($sla['cumplidas'] / $sla['total']) * 100, 2)
          : 0;

        /* =============================
     5) Productividad por técnico (mes actual)
  ============================= */
        $st = $pdo->prepare("
    SELECT 
      u.nombre_completo tecnico,
      COUNT(*) completadas
    FROM t_instalaciones i
    JOIN c_usuario u ON u.id_user=i.id_tecnico
    WHERE i.empresa_id=?
      AND i.estado='COMPLETADO'
      AND MONTH(i.fecha_fin_real)=MONTH(CURDATE())
      AND YEAR(i.fecha_fin_real)=YEAR(CURDATE())
    GROUP BY u.nombre_completo
    ORDER BY completadas DESC
  ");
        $st->execute([$empresa_id]);
        $data['productividad_tecnico'] = $st->fetchAll(PDO::FETCH_ASSOC);

        /* =============================
     6) Backlog por técnico
  ============================= */
        $st = $pdo->prepare("
    SELECT 
      u.nombre_completo tecnico,
      COUNT(*) pendientes
    FROM t_instalaciones i
    JOIN c_usuario u ON u.id_user=i.id_tecnico
    WHERE i.empresa_id=?
      AND i.estado='BORRADOR'
    GROUP BY u.nombre_completo
    ORDER BY pendientes DESC
  ");
        $st->execute([$empresa_id]);
        $data['backlog_tecnico'] = $st->fetchAll(PDO::FETCH_ASSOC);

        /* =============================
     7) Lead Time Promedio
  ============================= */
        $st = $pdo->prepare("
    SELECT 
      AVG(DATEDIFF(fecha_fin_real, fecha_inicio_real)) avg_dias
    FROM t_instalaciones
    WHERE empresa_id=?
      AND estado='COMPLETADO'
      AND fecha_inicio_real IS NOT NULL
      AND fecha_fin_real IS NOT NULL
  ");
        $st->execute([$empresa_id]);
        $data['lead_time_promedio'] = round((float)$st->fetchColumn(), 2);

        jexit(['success' => true, 'data' => $data]);
      }

      /* ======================================================
         DASHBOARD AVANZADO 360°
      ====================================================== */
    case 'dashboard_avanzado': {

        $data = [];

        /* =============================
           1️⃣ MES ACTUAL
        ============================= */
        $st = $pdo->prepare("
  SELECT 
    COUNT(*) total,
    SUM(CASE WHEN estado='COMPLETADO' THEN 1 ELSE 0 END) completadas
  FROM t_instalaciones
  WHERE empresa_id=?
    AND fecha_registro >= DATE_FORMAT(CURDATE(),'%Y-%m-01')
    AND fecha_registro < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH),'%Y-%m-01')
");
        $st->execute([$empresa_id]);
        $mesActual = $st->fetch(PDO::FETCH_ASSOC);

        $data['mes_actual_total'] = (int)$mesActual['total'];
        $data['mes_actual_completadas'] = (int)$mesActual['completadas'];


        /* =============================
           2️⃣ MES ANTERIOR
        ============================= */
        $st = $pdo->prepare("
  SELECT 
    COUNT(*) total,
    SUM(CASE WHEN estado='COMPLETADO' THEN 1 ELSE 0 END) completadas
  FROM t_instalaciones
  WHERE empresa_id=?
    AND fecha_registro >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m-01')
    AND fecha_registro < DATE_FORMAT(CURDATE(),'%Y-%m-01')
");
        $st->execute([$empresa_id]);
        $mesAnterior = $st->fetch(PDO::FETCH_ASSOC);

        $data['mes_anterior_total'] = (int)$mesAnterior['total'];
        $data['mes_anterior_completadas'] = (int)$mesAnterior['completadas'];


        /* =============================
           3️⃣ SLA GLOBAL
        ============================= */
        $st = $pdo->prepare("
          SELECT
            COUNT(*) total,
            SUM(
              CASE 
                WHEN estado='COMPLETADO'
                 AND fecha_fin_real IS NOT NULL
                 AND fecha_fin_real <= fecha_compromiso
                THEN 1 ELSE 0 END
            ) cumplidas
          FROM t_instalaciones
          WHERE empresa_id=?
            AND estado='COMPLETADO'
        ");
        $st->execute([$empresa_id]);
        $sla = $st->fetch(PDO::FETCH_ASSOC);

        $total = (int)$sla['total'];
        $cumplidas = (int)$sla['cumplidas'];

        $data['sla_pct'] = $total > 0
          ? round(($cumplidas / $total) * 100, 2)
          : 0;


        /* =============================
           4️⃣ RANKING TÉCNICOS (MES ACTUAL)
        ============================= */
        $st = $pdo->prepare("
          SELECT 
            u.nombre_completo tecnico,
            COUNT(*) completadas
          FROM t_instalaciones i
          JOIN c_usuario u ON u.id_user=i.id_tecnico
          WHERE i.empresa_id=?
            AND i.estado='COMPLETADO'
            AND MONTH(i.fecha_fin_real)=MONTH(CURDATE())
            AND YEAR(i.fecha_fin_real)=YEAR(CURDATE())
          GROUP BY u.nombre_completo
          ORDER BY completadas DESC
          LIMIT 10
        ");
        $st->execute([$empresa_id]);
        $data['ranking_tecnicos'] = $st->fetchAll(PDO::FETCH_ASSOC);


        /* =============================
           5️⃣ TENDENCIA MENSUAL (6 meses)
        ============================= */
        $st = $pdo->prepare("
  SELECT 
    DATE_FORMAT(fecha_fin_real,'%Y-%m') mes,
    COUNT(*) total
  FROM t_instalaciones
  WHERE empresa_id=?
    AND fecha_fin_real IS NOT NULL
    AND fecha_fin_real >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY DATE_FORMAT(fecha_fin_real,'%Y-%m')
  ORDER BY DATE_FORMAT(fecha_fin_real,'%Y-%m') ASC
");
        $st->execute([$empresa_id]);
        $data['tendencia_mensual'] = $st->fetchAll(PDO::FETCH_ASSOC);


        /* =============================
           6️⃣ VARIACIÓN MES VS MES
        ============================= */
        $actual = $data['mes_actual_total'];
        $anterior = $data['mes_anterior_total'];

        if ($anterior > 0) {
          $data['variacion_pct'] = round((($actual - $anterior) / $anterior) * 100, 2);
        } else {
          $data['variacion_pct'] = 0;
        }

        jexit([
          'success' => true,
          'data' => $data
        ]);
      }

    default:
      jexit(['success' => false, 'error' => 'Acción no soportada'], 400);
  }
} catch (Throwable $e) {
  if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  jexit(['success' => false, 'error' => 'Error servidor', 'detalle' => $e->getMessage()], 500);
}
