<?php
// public/procesos/servicio_depot/servicio_generar_cotizacion.php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/db.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        //@session_start();
    }

    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuarioActual = $_SESSION['usuario'] ?? 'SYSTEM';

    $servicioId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($servicioId <= 0) {
        throw new Exception('ID de servicio inválido.');
    }

    // 1) Traer datos del servicio + cliente
    $sqlServ = "
        SELECT s.*,
               cli.id_cliente,
               cli.Cve_Clte,
               cli.RazonSocial
        FROM th_servicio_caso s
        LEFT JOIN c_cliente cli ON cli.id_cliente = s.cliente_id
        WHERE s.id = :id
    ";
    $stServ = $pdo->prepare($sqlServ);
    $stServ->execute([':id' => $servicioId]);
    $servicio = $stServ->fetch(PDO::FETCH_ASSOC);

    if (!$servicio) {
        throw new Exception('No se encontró el caso de servicio.');
    }

    // Opcional: sólo permitir generar cotización si es servicio con cobro
    if (isset($servicio['motivo']) && strtoupper($servicio['motivo']) === 'GARANTIA') {
        throw new Exception('El caso está marcado como GARANTÍA; normalmente no genera cotización.');
    }

    $id_cliente = (int) ($servicio['id_cliente'] ?? 0);
    $cve_clte = $servicio['Cve_Clte'] ?? null;

    if ($id_cliente <= 0) {
        throw new Exception('El caso no tiene un cliente válido ligado; no se puede generar cotización.');
    }

    // 2) Traer partes registradas en laboratorio para este servicio
    $sqlPartes = "
        SELECT p.cve_articulo,
               p.cantidad,
               p.almacen_origen
        FROM t_servicio_parte p
        WHERE p.servicio_id = :id
          AND p.cantidad > 0
    ";
    $stPar = $pdo->prepare($sqlPartes);
    $stPar->execute([':id' => $servicioId]);
    $partes = $stPar->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partes)) {
        throw new Exception('No hay partes registradas para este servicio. Registra al menos una parte antes de generar cotización.');
    }

    // 3) Armar lista única de artículos
    $articulos = [];
    foreach ($partes as $p) {
        $cve = trim((string) $p['cve_articulo']);
        if ($cve !== '') {
            $articulos[$cve] = true;
        }
    }
    $keys = array_keys($articulos);
    if (empty($keys)) {
        throw new Exception('No se encontraron artículos válidos en las partes.');
    }

    // 4) Traer info de c_articulo (precio + descripción)
    $inArt = implode(',', array_fill(0, count($keys), '?'));
    $sqlArt = "
        SELECT cve_articulo, des_articulo, PrecioVenta
        FROM c_articulo
        WHERE cve_articulo IN ($inArt)
    ";
    $stArt = $pdo->prepare($sqlArt);
    $stArt->execute($keys);
    $infoArt = [];
    while ($row = $stArt->fetch(PDO::FETCH_ASSOC)) {
        $infoArt[$row['cve_articulo']] = $row;
    }

    // 5) Traer existencias reales desde mv_existencia_gral (sin cuarentena)
    $sqlEx = "
        SELECT cve_articulo, SUM(Existencia) AS ExistenciaTotal
        FROM mv_existencia_gral
        WHERE IFNULL(Cuarentena, 0) = 0
          AND cve_articulo IN ($inArt)
        GROUP BY cve_articulo
    ";
    $stEx = $pdo->prepare($sqlEx);
    $stEx->execute($keys);
    $existencias = [];
    while ($row = $stEx->fetch(PDO::FETCH_ASSOC)) {
        $existencias[$row['cve_articulo']] = (float) $row['ExistenciaTotal'];
    }

    // 6) Construir items (similares a cotizaciones.php)
    $items = [];
    $total = 0.0;

    foreach ($partes as $p) {
        $cve = trim((string) $p['cve_articulo']);
        $cant = (float) $p['cantidad'];

        if ($cve === '' || $cant <= 0) {
            continue;
        }

        $art = $infoArt[$cve] ?? null;
        $desc = $art['des_articulo'] ?? $cve;
        $precio = isset($art['PrecioVenta']) ? (float) $art['PrecioVenta'] : 0.0;
        $exist = $existencias[$cve] ?? 0.0;

        $subtotal = $cant * $precio;
        $total += $subtotal;

        $items[] = [
            'cve_articulo' => $cve,
            'descripcion' => $desc,
            'cantidad' => $cant,
            'precio_unitario' => $precio,
            'existencia' => $exist,
            'subtotal' => $subtotal,
        ];
    }

    if (empty($items)) {
        throw new Exception('Las partes del caso no generaron renglones válidos para cotización.');
    }

    // 7) Insertar en crm_cotizacion + crm_cotizacion_det (misma estructura que cotizaciones.php)
    $pdo->beginTransaction();

    $folio_cotizacion = 'COT-' . date('Ymd-His');

    $sqlInsHead = "
        INSERT INTO crm_cotizacion (
            folio_cotizacion,
            fecha,
            id_cliente,
            cve_clte,
            fuente_id,
            fuente_detalle,
            total,
            estado
        ) VALUES (
            :folio,
            NOW(),
            :id_cliente,
            :cve_clte,
            :fuente_id,
            :fuente_detalle,
            :total,
            'BORRADOR'
        )
    ";
    $stmtHead = $pdo->prepare($sqlInsHead);

    // fuente_id lo dejamos NULL por ahora; fuente_detalle amarra al servicio
    $fuente_id = null;
    $fuente_detalle = 'Servicio/Garantía folio ' . ($servicio['folio'] ?? ('SRV-' . $servicioId));

    $stmtHead->execute([
        ':folio' => $folio_cotizacion,
        ':id_cliente' => $id_cliente ?: null,
        ':cve_clte' => $cve_clte ?: null,
        ':fuente_id' => $fuente_id,
        ':fuente_detalle' => $fuente_detalle,
        ':total' => $total,
    ]);

    $cotizacion_id = (int) $pdo->lastInsertId();

    $sqlInsDet = "
        INSERT INTO crm_cotizacion_det (
            cotizacion_id,
            cve_articulo,
            descripcion,
            cantidad,
            precio_unitario,
            subtotal,
            existencia
        ) VALUES (
            :cotizacion_id,
            :cve_articulo,
            :descripcion,
            :cantidad,
            :precio_unitario,
            :subtotal,
            :existencia
        )
    ";
    $stmtDet = $pdo->prepare($sqlInsDet);

    foreach ($items as $it) {
        $stmtDet->execute([
            ':cotizacion_id' => $cotizacion_id,
            ':cve_articulo' => $it['cve_articulo'],
            ':descripcion' => $it['descripcion'],
            ':cantidad' => $it['cantidad'],
            ':precio_unitario' => $it['precio_unitario'],
            ':subtotal' => $it['subtotal'],
            ':existencia' => $it['existencia'],
        ]);
    }

    // 8) Opcional: actualizar status del servicio (sin tocar columnas nuevas)
    $sqlUpdServ = "
        UPDATE th_servicio_caso
        SET status = 'EN_ESPERA_AUTORIZACION'
        WHERE id = :id
    ";
    $stUpd = $pdo->prepare($sqlUpdServ);
    $stUpd->execute([':id' => $servicioId]);

    $pdo->commit();

    // 9) Redirigir a la vista de cotizaciones
    // Desde /public/procesos/servicio_depot/ subimos dos niveles a /public/crm/cotizaciones.php
    $qs = http_build_query([
        'from_servicio' => 1,
        'cot_id' => $cotizacion_id,
        'folio' => $folio_cotizacion,
    ]);
    header('Location: ../../crm/cotizaciones.php?' . $qs);
    exit;

} catch (Throwable $e) {
    // En caso de error, mostramos algo simple (puedes luego embellecerlo)
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<h4>Error al generar cotización desde Servicio</h4>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="laboratorio_servicio.php">&laquo; Volver a laboratorio</a></p>';
}
