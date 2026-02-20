<?php
require_once __DIR__ . "/../../app/db.php";
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$action = $_GET['action'] ?? '';

function json_exit($arr, $code = 200)
{
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

try {

  switch ($action) {

    /* ======================================================
           LISTAR INSTALACIONES
        ====================================================== */
    case 'list':

      $data = db_all("
                SELECT 
                    i.id_instalacion,
                    i.folio,
                    p.Fol_folio AS pedido,
                    p.Cve_clte,
                    p.cve_ubicacion,
                    u.nombre_completo AS tecnico,
                    DATE_FORMAT(i.fecha_registro, '%d/%m/%Y %H:%i') AS fecha_registro,
                    i.fecha_compromiso,
                    i.estado
                FROM t_instalaciones i
                INNER JOIN th_pedido p ON i.id_pedido = p.id_pedido
                INNER JOIN c_usuario u ON i.id_tecnico = u.id_user
                ORDER BY i.id_instalacion DESC
            ");

      json_exit(["success" => true, "data" => $data]);
      break;


    /* ======================================================
           CREAR INSTALACIÓN
        ====================================================== */
    case 'create':

      $input = json_decode(file_get_contents("php://input"), true);

      $folio            = trim($input['folio'] ?? '');
      $id_pedido        = (int)($input['id_pedido'] ?? 0);
      $id_tecnico       = (int)($input['id_tecnico'] ?? 0);
      $fecha_compromiso = $input['fecha_compromiso'] ?? null;
      $partidas         = $input['partidas'] ?? [];

      if (
        !$folio ||
        !$id_pedido ||
        !$id_tecnico ||
        !$fecha_compromiso ||
        !is_array($partidas) ||
        count($partidas) === 0
      ) {
        json_exit(["success" => false, "error" => "Datos incompletos"], 400);
      }

      db_tx(function () use (
        $folio,
        $id_pedido,
        $id_tecnico,
        $fecha_compromiso,
        $partidas,
        &$idInstalacion
      ) {

        global $pdo;

        /* ===== VALIDAR PEDIDO ===== */

        $pedidoExiste = db_row("
                    SELECT id_pedido
                    FROM th_pedido
                    WHERE id_pedido = ?
                    LIMIT 1
                ", [$id_pedido]);

        if (!$pedidoExiste) {
          throw new Exception("Pedido no encontrado");
        }

        /* ===== VALIDAR TÉCNICO ===== */

        $tecnicoExiste = db_row("
                    SELECT id_user
                    FROM c_usuario
                    WHERE id_user = ?
                    LIMIT 1
                ", [$id_tecnico]);

        if (!$tecnicoExiste) {
          throw new Exception("Técnico no válido");
        }

        /* ===== INSERTAR INSTALACIÓN ===== */

        dbq("
                    INSERT INTO t_instalaciones
                    (folio, id_pedido, id_tecnico, fecha_compromiso, estado)
                    VALUES (?,?,?,?, 'BORRADOR')
                ", [
          $folio,
          $id_pedido,
          $id_tecnico,
          $fecha_compromiso
        ]);

        $idInstalacion = $pdo->lastInsertId();

        /* ===== VALIDAR E INSERTAR DETALLE ===== */

        foreach ($partidas as $row) {

          $id_pedido_detalle = (int)$row['id_pedido_detalle'];
          $cantidadNueva     = (float)$row['cantidad'];

          if ($cantidadNueva <= 0) {
            throw new Exception("Cantidad inválida para partida $id_pedido_detalle");
          }

          /* Obtener cantidad pedida */
          $pedidoRow = db_row("
                        SELECT Num_cantidad
                        FROM td_pedido
                        WHERE id = ?
                        LIMIT 1
                    ", [$id_pedido_detalle]);

          if (!$pedidoRow) {
            throw new Exception("Partida $id_pedido_detalle no encontrada en pedido");
          }

          $cantidadPedido = (float)$pedidoRow['Num_cantidad'];

          /* Obtener cantidad ya instalada */
          $instaladoRow = db_row("
                        SELECT COALESCE(SUM(cantidad_instalada),0) AS total_instalado
                        FROM t_instalacion_detalle
                        WHERE id_pedido_detalle = ?
                    ", [$id_pedido_detalle]);

          $cantidadInstaladaActual = (float)$instaladoRow['total_instalado'];

          /* Validar que no exceda */
          if (($cantidadInstaladaActual + $cantidadNueva) > $cantidadPedido) {

            $faltante = $cantidadPedido - $cantidadInstaladaActual;

            throw new Exception(
              "Partida $id_pedido_detalle: disponible $faltante, solicitado $cantidadNueva"
            );
          }

          /* Insertar detalle */
          dbq("
                        INSERT INTO t_instalacion_detalle
                        (id_instalacion, id_pedido_detalle, cantidad_instalada)
                        VALUES (?,?,?)
                    ", [
            $idInstalacion,
            $id_pedido_detalle,
            $cantidadNueva
          ]);
        }
      });

      json_exit(["success" => true]);
      break;


    default:
      json_exit(["success" => false, "error" => "Acción no válida"], 400);
  }
} catch (Throwable $e) {

  json_exit([
    "success" => false,
    "error" => $e->getMessage()
  ], 500);
}
