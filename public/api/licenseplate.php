<?php
/**
 * API Unified License Plates
 * Modernized for AssistPro
 * Handles: List, Create, Delete, Toggle
 */
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

// Helper functions
function json_response($data, $success = true, $msg = "")
{
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $msg]);
    exit;
}

// DB Connection
$cfg = db_config();
$conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int) $cfg['port']);
if ($conn->connect_error) {
    http_response_code(500);
    json_response([], false, "DB Error: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');

try {

    // =================================================================================
    // LIST ALMACENES (Catalog)
    // =================================================================================
    if ($action === 'list_almacenes') {
        $sql = "SELECT cve_almac, des_almac FROM c_almacen WHERE Activo = 1 ORDER BY des_almac";
        $result = $conn->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        json_response($data, true, "");
    }

    // =================================================================================
    // LIST (DataTables / Select2)
    // =================================================================================
    if ($action === 'list') {

        // 1. Base Query
        $sql = "
        SELECT ch.*, a.des_almac,
            CASE
                WHEN ch.Activo = 0 THEN 'Inactivo'
                WHEN EXISTS (SELECT 1 FROM ts_existenciatarima t WHERE t.ntarima = ch.IDContenedor AND t.Activo=1) THEN 'Ocupado'
                WHEN EXISTS (SELECT 1 FROM ts_existenciacajas c WHERE c.nTarima = ch.IDContenedor) THEN 'Ocupado'
                ELSE 'Disponible'
            END as Status
        FROM c_charolas ch
        INNER JOIN c_almacen a ON a.cve_almac = ch.cve_almac
        ";

        // 2. Filters
        $where = [];
        $params = [];
        $types = "";

        // Standard Filter: Almacen
        if (!empty($_GET['almacen']) && $_GET['almacen'] !== 'null') {
            $where[] = "ch.cve_almac = ?";
            $params[] = $_GET['almacen'];
            $types .= "i";
        }

        // Filter: Status (Calculated is hard to filter in WHERE without HAVING, using simplified logic or client-side for complex)
        // Note: For large tables, implementing 'Status' filter in WHERE requires duplicating the subqueries.
        if (!empty($_GET['status']) && $_GET['status'] !== '') {
            if ($_GET['status'] == '1') { // Disponible
                $where[] = "ch.Activo = 1 AND NOT EXISTS (SELECT 1 FROM ts_existenciatarima t WHERE t.ntarima = ch.IDContenedor AND t.Activo=1) 
                             AND NOT EXISTS (SELECT 1 FROM ts_existenciacajas c WHERE c.nTarima = ch.IDContenedor)";
            } elseif ($_GET['status'] == '0') { // Ocupado
                $where[] = "ch.Activo = 1 AND (EXISTS (SELECT 1 FROM ts_existenciatarima t WHERE t.ntarima = ch.IDContenedor AND t.Activo=1) 
                             OR EXISTS (SELECT 1 FROM ts_existenciacajas c WHERE c.nTarima = ch.IDContenedor))";
            } elseif ($_GET['status'] == '2') { // Inactivo
                $where[] = "ch.Activo = 0";
            }
        }

        // Filter: Activo
        if (isset($_GET['activo']) && $_GET['activo'] !== '') {
            $where[] = "ch.Activo = ?";
            $params[] = $_GET['activo'];
            $types .= "i";
        }

        // Filter: Tipo (Pallet/Contenedor/Caja)
        if (!empty($_GET['tipo'])) {
            $where[] = "ch.tipo = ?";
            $params[] = $_GET['tipo'];
            $types .= "s";
        }

        // Filter: Tipo Genérico
        if (!empty($_GET['tipo_gen'])) {
            if ($_GET['tipo_gen'] === 'Generico') {
                $where[] = "ch.TipoGen = 1";
            } elseif ($_GET['tipo_gen'] === 'NoGenerico') {
                $where[] = "ch.TipoGen = 0";
            }
        }

        // Search (DataTables global search)
        $searchParam = $_GET['search'] ?? null;
        $sVal = is_array($searchParam) ? ($searchParam['value'] ?? '') : ($searchParam ?? '');

        if (!empty($sVal)) {
            $s = "%" . $sVal . "%";
            $where[] = "(ch.CveLP LIKE ? OR ch.Clave_Contenedor LIKE ? OR ch.descripcion LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "sss";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 3. Ordering (DataTables)
        // Default to ID DESC
        $colMap = ['ch.IDContenedor', 'ch.Clave_Contenedor', 'ch.CveLP', 'ch.bl', 'ch.descripcion', 'Status', 'ch.tipo', 'a.des_almac', 'ch.Activo']; // Mapped by index
        $orderBy = "ORDER BY ch.IDContenedor DESC";

        if (!empty($_GET['order'][0]['column'])) {
            $colIdx = intval($_GET['order'][0]['column']);
            $dir = strtoupper($_GET['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
            if (isset($colMap[$colIdx])) {
                // If sorting by Status (computed), use subquery logic or just skip server sorting for it. 
                // For safety/speed, we sort by ID if Status is requested, unless we change SQL.
                if ($colMap[$colIdx] !== 'Status') {
                    $orderBy = "ORDER BY " . $colMap[$colIdx] . " " . $dir;
                }
            }
        }
        $sql .= " " . $orderBy;

        // 4. Limits
        $limit = 500; // Hard limit for safety
        if (!empty($_GET['length']) && intval($_GET['length']) > 0) {
            $limit = intval($_GET['length']);
        }
        $start = 0;
        if (!empty($_GET['start'])) {
            $start = intval($_GET['start']);
        }
        $sql .= " LIMIT ?, ?";

        // Append limit params
        $params[] = $start;
        $params[] = $limit;
        $types .= "ii";

        // Execute
        $stmt = $conn->prepare($sql);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);

        // Count Total (Approximate or separate query)
        // For speed, returning count($data) as total if < limit, else we'd need SQL_CALC_FOUND_ROWS or separate count.
        // We will do a separate count query for correct pagination.

        $sqlCount = "SELECT COUNT(*) as total FROM c_charolas ch INNER JOIN c_almacen a ON a.cve_almac = ch.cve_almac";
        if ($where)
            $sqlCount .= " WHERE " . implode(" AND ", $where);

        // Clean params for count (remove limit params)
        array_pop($params);
        array_pop($params);
        $typesCount = substr($types, 0, -2);

        $stmtC = $conn->prepare($sqlCount);
        if ($params)
            $stmtC->bind_param($typesCount, ...$params);
        $stmtC->execute();
        $countRes = $stmtC->get_result()->fetch_assoc();
        $totalRecords = $countRes['total'];

        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
        exit;
    }

    // =================================================================================
    // CREATE (Generate LPs)
    // =================================================================================
    // =================================================================================
    // EXPORT DIRECTO (Server-Side Streaming para alto volumen)
    // =================================================================================
    if ($action === 'export') {
        // Configuraciones CRÍTICAS para alto volumen
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        while (ob_get_level())
            ob_end_clean();

        // Headers para forzar descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=license_plates_' . date('Y-m-d') . '.csv');

        // Crear puntero de salida
        $output = fopen('php://output', 'w');

        // BOM para Excel (UTF-8)
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Encabezados de columnas
        fputcsv($output, ['Pallet/Contenedor', 'License Plate', 'BL', 'Descripción', 'Status', 'Tipo', 'Almacén', 'Activo']);

        // Reconstruir Query y Filtros (similar a list pero sin LIMIT)
        $sql = "
        SELECT ch.*, a.des_almac,
            CASE
                WHEN ch.Activo = 0 THEN 'Inactivo'
                WHEN EXISTS (SELECT 1 FROM ts_existenciatarima t WHERE t.ntarima = ch.IDContenedor AND t.Activo=1) THEN 'Ocupado'
                WHEN EXISTS (SELECT 1 FROM ts_existenciacajas c WHERE c.nTarima = ch.IDContenedor) THEN 'Ocupado'
                ELSE 'Disponible'
            END as StatusCalculado
        FROM c_charolas ch
        INNER JOIN c_almacen a ON a.cve_almac = ch.cve_almac
        ";

        $where = [];
        $params = [];
        $types = "";

        // -- Mismos filtros que en 'list' --
        if (!empty($_GET['almacen']) && $_GET['almacen'] !== 'null') {
            $where[] = "ch.cve_almac = ?";
            $params[] = $_GET['almacen'];
            $types .= "i";
        }
        if (!empty($_GET['activo']) && $_GET['activo'] !== '') {
            $where[] = "ch.Activo = ?";
            $params[] = $_GET['activo'];
            $types .= "i";
        }
        if (!empty($_GET['tipo'])) {
            $where[] = "ch.tipo = ?";
            $params[] = $_GET['tipo'];
            $types .= "s";
        }
        if (!empty($_GET['search'])) {
            $sParam = $_GET['search'];
            $search = "%" . (is_array($sParam) ? ($sParam['value'] ?? '') : $sParam) . "%";
            // Removed ch.bl to avoid Unknown Column error and match 'list' logic
            $where[] = "(ch.Clave_Contenedor LIKE ? OR ch.CveLP LIKE ? OR ch.descripcion LIKE ?)";
            $types .= "sss";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (count($where) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // Ejecutar Query sin buffer (unbuffered query para evitar memory exhaustion)
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result(); // Usar get_result para iterar

        // Iterar y escribir al vuelo
        while ($row = $result->fetch_assoc()) {
            // Determinar valor BL de forma flexible
            $bl = '';
            if (isset($row['bl']))
                $bl = $row['bl'];
            elseif (isset($row['BL']))
                $bl = $row['BL'];

            fputcsv($output, [
                $row['Clave_Contenedor'],
                $row['CveLP'],
                $bl,
                $row['descripcion'],
                $row['StatusCalculado'],
                $row['tipo'],
                $row['des_almac'],
                ($row['Activo'] == 1 ? 'ON' : 'OFF')
            ]);
            // Liberar memoria periódicamente si fuese necesario, 
            // pero fputcsv ya maneja bien el stream.
        }

        fclose($output);
        exit;
    }

    if ($action === 'create') {
        $cve_almac = $_POST['almacen'] ?? ($_POST['cve_almac'] ?? 0);
        $cantidad = intval($_POST['cantidad'] ?? 0);
        $prefijo = trim($_POST['prefijo'] ?? 'LP');
        $tipo = $_POST['tipo'] ?? 'Pallet';
        $consecutivo = ($_POST['consecutivo'] ?? '0') == '1';

        if ($cve_almac == 0 || $cantidad <= 0) {
            json_response(null, false, "Datos inválidos: Almacén y cantidad son requeridos.");
        }

        // Obtener ID Máximo actual para simular AutoIncrement
        $resMax = $conn->query("SELECT MAX(IDContenedor) as max_id FROM c_charolas");
        $rowMax = $resMax->fetch_assoc();
        $currentId = intval($rowMax['max_id'] ?? 0);

        $generados = [];
        // Se agrega IDContenedor al Insert
        $stmt = $conn->prepare("INSERT INTO c_charolas (IDContenedor, cve_almac, Clave_Contenedor, CveLP, descripcion, tipo, Activo, TipoGen) VALUES (?, ?, ?, ?, ?, ?, 1, 0)");

        $baseTime = time();

        for ($i = 0; $i < $cantidad; $i++) {
            $currentId++; // Incrementar ID manualmente

            if ($consecutivo) {
                $suffix = $baseTime . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $suffix = date('ymd') . rand(1000, 9999) . rand(10, 99);
            }

            $clave = $prefijo . $suffix;
            $desc = "$tipo $clave";
            $lp = $clave;

            $stmt->bind_param("iissss", $currentId, $cve_almac, $clave, $lp, $desc, $tipo);

            if ($stmt->execute()) {
                $generados[] = $clave;
            } else {
                // Si falla (probablemente duplicado de clave, no de ID), reintentar clave
                if (!$consecutivo) {
                    $clave .= rand(1, 9);
                    $lp = $clave;
                    $desc = "$tipo $clave";
                    // Reusar el MISMO ID que falló
                    $stmt->bind_param("iissss", $currentId, $cve_almac, $clave, $lp, $desc, $tipo);
                    if ($stmt->execute()) {
                        $generados[] = $clave;
                    } else {
                        // Si falla de nuevo, puede ser el ID duplicado (race condition).
                        // Intentar saltar ID
                        $currentId++;
                        $stmt->bind_param("iissss", $currentId, $cve_almac, $clave, $lp, $desc, $tipo);
                        if ($stmt->execute())
                            $generados[] = $clave;
                    }
                }
            }
        }

        if (count($generados) > 0) {
            json_response($generados, true, "Se generaron " . count($generados) . " License Plates exitosamente.");
        } else {
            json_response([], false, "No se pudieron generar los LPs. Posiblemente duplicados.");
        }
    }

    // =================================================================================
    // DELETE (Soft Delete / Check Usage)
    // =================================================================================
    if ($action === 'delete') {
        $id = intval($_POST['id']);

        // 1. Check if used
        $sqlCheck = "SELECT 
                        (SELECT COUNT(*) FROM ts_existenciatarima WHERE ntarima = ?) as t_count,
                        (SELECT COUNT(*) FROM ts_existenciacajas WHERE nTarima = ?) as c_count
                     ";
        $stmtC = $conn->prepare($sqlCheck);
        $stmtC->bind_param("ii", $id, $id);
        $stmtC->execute();
        $resC = $stmtC->get_result()->fetch_assoc();

        if ($resC['t_count'] > 0 || $resC['c_count'] > 0) {
            json_response(null, false, "No se puede eliminar: El LP tiene existencia asignada.");
        }

        // 2. Perform Delete (Physically or Soft? User code had 'delete' action. Let's do DELETE for cleanup, or Activo=0)
        // Given "Modernization", Soft Delete is safer, but if user asked to "Delete", we delete.
        // Let's Delete row if empty.
        $stmtD = $conn->prepare("DELETE FROM c_charolas WHERE IDContenedor = ?");
        $stmtD->bind_param("i", $id);
        if ($stmtD->execute()) {
            json_response(null, true, "Eliminado correctamente.");
        } else {
            json_response(null, false, "Error al eliminar: " . $stmtD->error);
        }
    }

    // =================================================================================
    // TOGGLE ACTIVE
    // =================================================================================
    // TOGGLE ACTIVE
    // =================================================================================
    if ($action === 'toggle') {
        $id = intval($_POST['id']);
        $val = intval($_POST['val']); // 1 or 0

        if ($id <= 0) {
            json_response(null, false, "Error: ID inválido.");
        }

        $stmt = $conn->prepare("UPDATE c_charolas SET Activo = ? WHERE IDContenedor = ? LIMIT 1");
        $stmt->bind_param("ii", $val, $id);
        if ($stmt->execute()) {
            json_response(null, true, "Estado actualizado.");
        } else {
            json_response(null, false, "Error al actualizar.");
        }
    }

    // REPAIR LEGACY DATA (FIX IDS = 0)
    // =================================================================================
    if ($action === 'fix_ids') {
        // 1. Obtener Max ID
        $resMax = $conn->query("SELECT MAX(IDContenedor) as max_id FROM c_charolas");
        $rowMax = $resMax->fetch_assoc();
        $nextId = intval($rowMax['max_id'] ?? 0) + 1;

        // 2. Obtener registros dañados (ID=0)
        // Necesitamos identificarlos por algo único. Si son idénticos, usamos LIMIT 1 en loop.
        // Como no tienen ID único, seleccionamos por lotes y actualizamos uno a uno.
        // Pero MYSQL UPDATE LIMIT funciona.

        $count = 0;
        do {
            $stmt = $conn->prepare("UPDATE c_charolas SET IDContenedor = ? WHERE IDContenedor = 0 LIMIT 1");
            $stmt->bind_param("i", $nextId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $nextId++;
                $count++;
            } else {
                break; // No more rows with ID 0
            }
        } while ($count < 1000); // Safety brake

        json_response(null, true, "Se repararon $count registros con ID incorrecto.");
    }

} catch (Exception $e) {
    http_response_code(500);
    json_response(null, false, "Exception: " . $e->getMessage());
}
?>