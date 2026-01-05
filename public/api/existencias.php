<?php
/*************************************************
 * API PRIVADA DE EXISTENCIAS Y MOVIMIENTOS
 * PHP + MySQL (Legacy Codebase Adaptation)
 *************************************************/

require_once '../../app/db.php';

header('Content-Type: application/json');

/* =======================
   RESPUESTA JSON
   ======================= */
function jsonResponse($data, int $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/* =======================
   BASE DE DATOS (ADAPTED)
   ======================= */
function getDB()
{
    static $conn;
    if (!$conn) {
        $cfg = db_config();
        $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int) $cfg['port']);
        if ($conn->connect_error) {
            jsonResponse(['error' => 'DB_ERROR', 'mensaje' => 'Error de conexión'], 500);
        }
        $conn->set_charset($cfg['charset'] ?? 'utf8mb4');
    }
    return $conn;
}

/* =======================
   VALIDACIONES
   ======================= */
function requireFields(array $data, array $fields)
{
    foreach ($fields as $f) {
        if (!isset($data[$f]) || $data[$f] === '' || $data[$f] === null) {
            jsonResponse([
                'error' => 'VALIDATION_ERROR',
                'mensaje' => "Campo obligatorio: $f"
            ], 400);
        }
    }
}

/* =======================
   REPOSITORY (MAPPED)
   ======================= */
class ExistenciasRepository
{
    protected $conn;

    public function __construct()
    {
        $this->conn = getDB();
    }

    /* 
     * MAPPING:
     * existencias_ubicacion -> mv_tmp_existencias
     *   almacen_id -> cve_almac
     *   ubicacion -> cve_ubicacion
     *   producto -> cve_articulo
     *   stock -> Existencia
     * productos -> c_tipo_producto
     *   codigo -> clave
     *   descripcion -> descripcion
     */

    public function obtener(array $f): array
    {
        $sql = "
            SELECT
                e.cve_articulo AS producto,
                p.descripcion,
                e.cve_ubicacion AS ubicacion,
                SUM(e.Existencia) AS stock
            FROM mv_tmp_existencias e
            LEFT JOIN c_tipo_producto p ON p.clave = e.cve_articulo
            WHERE e.cve_almac = ?
        ";

        $types = "i"; // almacen is int
        $params = [$f['almacen']];

        if (!empty($f['producto'])) {
            $sql .= " AND e.cve_articulo = ?";
            $types .= "s";
            $params[] = $f['producto'];
        }

        if (!empty($f['ubicacion'])) {
            $sql .= " AND e.cve_ubicacion = ?";
            $types .= "s";
            $params[] = $f['ubicacion'];
        }

        $sql .= " GROUP BY e.cve_articulo, e.cve_ubicacion";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error prepare: " . $this->conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function stockActual(array $d): float
    {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(Existencia),0)
            FROM mv_tmp_existencias
            WHERE cve_almac = ?
              AND cve_articulo = ?
              AND cve_ubicacion = ?
        ");
        $stmt->bind_param("iss", $d['almacen'], $d['producto'], $d['ubicacion']);
        $stmt->execute();
        $stmt->bind_result($stock);
        $stmt->fetch();
        $stmt->close();
        return (float) $stock;
    }

    public function actualizarStock(array $d)
    {
        // Check if record exists
        $stmtCheck = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM mv_tmp_existencias
            WHERE cve_almac = ?
              AND cve_articulo = ?
              AND cve_ubicacion = ?
        ");
        $stmtCheck->bind_param("iss", $d['almacen'], $d['producto'], $d['ubicacion']);
        $stmtCheck->execute();
        $stmtCheck->bind_result($exists);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($exists > 0) {
            // Update existing
            $stmt = $this->conn->prepare("
                UPDATE mv_tmp_existencias
                SET Existencia = Existencia + ?
                WHERE cve_almac = ?
                  AND cve_articulo = ?
                  AND cve_ubicacion = ?
            ");
            $stmt->bind_param("diss", $d['cantidad'], $d['almacen'], $d['producto'], $d['ubicacion']);
            $stmt->execute();
        } else {
            // Insert new (defaults for other fields)
            $stmt = $this->conn->prepare("
                INSERT INTO mv_tmp_existencias 
                (cve_almac, cve_articulo, cve_ubicacion, Existencia, cve_lote, tipo, Id_Proveedor, Cuarentena, Cve_Contenedor, Lote_Alterno)
                VALUES (?, ?, ?, ?, 'SIN-LOTE', '', 0, 0, '', '')
            ");
            $stmt->bind_param("issd", $d['almacen'], $d['producto'], $d['ubicacion'], $d['cantidad']);
            $stmt->execute();
        }
    }

    public function insertarMovimiento(array $d): int
    {
        // TODO: The 'movimientos' table creation DDL was not provided.
        // For now, we skip inserting history to avoid breaking the flow.
        return 0;

        /*
        $stmt = $this->conn->prepare("
            INSERT INTO movimientos ...
        ");
        ...
        */
    }
}

/* =======================
   SERVICE
   ======================= */
class ExistenciasService
{
    private ExistenciasRepository $repo;

    public function __construct()
    {
        $this->repo = new ExistenciasRepository();
    }

    public function consultar(array $q): array
    {
        requireFields($q, ['almacen']);
        return $this->repo->obtener($q);
    }

    public function movimiento(array $d): int
    {
        requireFields($d, [
            'almacen',
            'producto',
            'ubicacion',
            'tipo',
            'cantidad'
        ]);

        $conn = getDB();
        $conn->begin_transaction();

        try {
            $stock = $this->repo->stockActual($d);
            $cantAbs = abs((float) $d['cantidad']);

            if ($d['tipo'] === 'SALIDA') {
                if ($stock < $cantAbs) {
                    jsonResponse([
                        'error' => 'STOCK_INSUFICIENTE',
                        'mensaje' => "No hay stock suficiente (Actual: $stock, Solicitado: $cantAbs)"
                    ], 409);
                }
                $delta = -$cantAbs;
            } else {
                $delta = $cantAbs;
            }

            // Prepared data for update
            $dUpdate = $d;
            $dUpdate['cantidad'] = $delta;

            $this->repo->actualizarStock($dUpdate);

            // Insert history (Skipped as per missing table, implies success)
            $movId = $this->repo->insertarMovimiento($d);

            $conn->commit();
            return $movId;

        } catch (Throwable $e) {
            $conn->rollback();
            jsonResponse([
                'error' => 'INTERNAL_ERROR',
                'mensaje' => 'Error procesando movimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}

/* =======================
   ROUTER SIMPLE
   ======================= */
$method = $_SERVER['REQUEST_METHOD'];

$service = new ExistenciasService();

/* ===== GET EXISTENCIAS (Default GET) ===== */
if ($method === 'GET') {

    try {
        $data = $service->consultar([
            'almacen' => $_GET['almacen_id'] ?? null,
            'producto' => $_GET['producto'] ?? null,
            'ubicacion' => $_GET['ubicacion'] ?? null
        ]);

        jsonResponse([
            'total' => count($data),
            'existencias' => $data
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'SERVER_ERROR', 'mensaje' => $e->getMessage()], 500);
    }
}

/* ===== POST MOVIMIENTOS (Default POST) ===== */
if ($method === 'POST') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $id = $service->movimiento([
        'almacen' => $body['almacen_id'] ?? null,
        'producto' => $body['producto'] ?? null,
        'ubicacion' => $body['ubicacion'] ?? null,
        'tipo' => $body['tipo'] ?? null,
        'cantidad' => $body['cantidad'] ?? null,
        'referencia' => $body['referencia'] ?? null
    ]);

    jsonResponse([
        'status' => 'OK',
        'movimiento_id' => $id,
        'mensaje' => 'Movimiento registrado (Histórico no disponible)'
    ], 201);
}

/* ===== 405 Method Not Allowed ===== */
jsonResponse([
    'error' => 'METHOD_NOT_ALLOWED',
    'mensaje' => "Metodo $method no soportado"
], 405);
?>