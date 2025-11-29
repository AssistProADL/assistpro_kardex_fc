<?php
// public/api/importador_ingresos.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'none';

try {
    if ($action === 'layout') {
        action_layout();
        exit;
    }

    // Para las demás acciones respondemos JSON
    header('Content-Type: application/json; charset=utf-8');

    switch ($action) {
        case 'previsualizar':
            action_previsualizar();
            break;
        case 'procesar':
            action_procesar();
            break;
        default:
            send_json([
                'ok' => false,
                'error' => 'Acción no soportada en importador_ingresos.php: ' . $action
            ]);
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    send_json([
        'ok' => false,
        'error' => 'Error general en importador_ingresos.php: ' . $e->getMessage()
    ]);
}

/**
 * Envía respuesta JSON estándar.
 */
function send_json(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * Genera layout CSV según tipo de ingreso.
 * action=layout&tipo_ingreso=OC|OC_PUT|RL|XD|ASN|INV_INI
 */
function action_layout(): void
{
    $tipo = $_GET['tipo_ingreso'] ?? '';

    if (!$tipo) {
        header('Content-Type: application/json; charset=utf-8');
        send_json([
            'ok' => false,
            'error' => 'Debe indicar tipo_ingreso para generar el layout.'
        ]);
        return;
    }

    $tipo = strtoupper($tipo);

    // Definimos columnas base comunes (superconjunto)
    $cols_comunes = [
        'empresa_id',
        'almacen_clave',
        'tipo_ingreso',      // OC, OC_PUT, RL, XD, ASN, INV_INI (puede venir fijo o en archivo)
        'origen',            // OC/RL/ASN/INV_INI (folio de origen, opcional según tipo)
        'producto',
        'cantidad',
        'uom',
        'bl_destino',        // CodigoCSD
        'nivel',             // PZ / CJ / PL
        'id_contenedor',     // CT...
        'id_pallet',         // LP...
        'epc',
        'code',
        'lote',
        'caducidad'
    ];

    $cols = $cols_comunes;
    $filename = 'layout_importador_' . strtolower($tipo) . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $cols);

    $ejemplo = ejemplo_por_tipo($tipo, $cols);
    if (!empty($ejemplo)) {
        fputcsv($out, $ejemplo);
    }

    fclose($out);
}

/**
 * Fila de ejemplo por tipo.
 */
function ejemplo_por_tipo(string $tipo, array $cols): array
{
    $row = array_fill(0, count($cols), '');
    $map = array_flip($cols);

    if (isset($map['empresa_id']))
        $row[$map['empresa_id']] = '1';
    if (isset($map['almacen_clave']))
        $row[$map['almacen_clave']] = 'ALM01';
    if (isset($map['tipo_ingreso']))
        $row[$map['tipo_ingreso']] = $tipo;
    if (isset($map['producto']))
        $row[$map['producto']] = 'PROD001';
    if (isset($map['cantidad']))
        $row[$map['cantidad']] = '100';
    if (isset($map['uom']))
        $row[$map['uom']] = 'PZ';
    if (isset($map['bl_destino']))
        $row[$map['bl_destino']] = 'BL-001-01-01';
    if (isset($map['nivel']))
        $row[$map['nivel']] = 'PZ';
    if (isset($map['id_contenedor']))
        $row[$map['id_contenedor']] = 'CT0001';
    if (isset($map['id_pallet']))
        $row[$map['id_pallet']] = 'LP0001';
    if (isset($map['epc']))
        $row[$map['epc']] = 'E2801160600002A3B4C5D6E7';
    if (isset($map['code']))
        $row[$map['code']] = '7500000000001';
    if (isset($map['lote']))
        $row[$map['lote']] = 'L202511';
    if (isset($map['caducidad']))
        $row[$map['caducidad']] = '2026-12-31';

    switch ($tipo) {
        case 'OC':
        case 'OC_PUT':
        case 'ASN':
        case 'XD':
            if (isset($map['origen'])) {
                $row[$map['origen']] = 'OC12345';
            }
            break;
        case 'RL':
            if (isset($map['origen'])) {
                $row[$map['origen']] = 'RL2025-0001';
            }
            break;
        case 'INV_INI':
            if (isset($map['origen'])) {
                $row[$map['origen']] = 'INV_INI_2025';
            }
            break;
    }

    return $row;
}

/**
 * Previsualiza contenido del archivo (CSV) y arma estructura para la tabla.
 */
function action_previsualizar(): void
{
    $empresa_id = $_POST['empresa_id'] ?? '';
    $almacen_id = $_POST['almacen_id'] ?? '';
    $tipo_ingreso = $_POST['tipo_ingreso'] ?? '';

    if (!$empresa_id || !$almacen_id || !$tipo_ingreso) {
        send_json([
            'ok' => false,
            'error' => 'Debe seleccionar Empresa, Almacén y Tipo de ingreso.'
        ]);
        return;
    }

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        send_json([
            'ok' => false,
            'error' => 'No se recibió el archivo a importar o hubo un error en la carga.'
        ]);
        return;
    }

    $tmpName = $_FILES['archivo']['tmp_name'];
    $name = $_FILES['archivo']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv'])) {
        send_json([
            'ok' => false,
            'error' => 'Por el momento solo se soportan archivos CSV para previsualización.'
        ]);
        return;
    }

    $handle = fopen($tmpName, 'r');
    if (!$handle) {
        send_json([
            'ok' => false,
            'error' => 'No se pudo abrir el archivo recibido.'
        ]);
        return;
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        send_json([
            'ok' => false,
            'error' => 'El archivo está vacío o no tiene encabezados.'
        ]);
        return;
    }

    $headers_norm = array_map(function ($h) {
        return strtolower(trim($h));
    }, $headers);
    $map = array_flip($headers_norm);

    $filas = [];
    $total = 0;
    $total_ok = 0;
    $total_err = 0;
    $max_filas = 500;

    while (($data = fgetcsv($handle)) !== false) {
        $total++;
        if ($total > $max_filas) {
            break;
        }

        $producto = get_by_col($data, $map, 'producto');
        $cantidad = get_by_col($data, $map, 'cantidad');
        $uom = get_by_col($data, $map, 'uom');
        $bl_destino = get_by_col($data, $map, 'bl_destino');
        $nivel = get_by_col($data, $map, 'nivel');
        $id_cont = get_by_col($data, $map, 'id_contenedor');
        $id_pallet = get_by_col($data, $map, 'id_pallet');
        $epc = get_by_col($data, $map, 'epc');
        $code = get_by_col($data, $map, 'code');
        $lote = get_by_col($data, $map, 'lote');
        $caducidad = get_by_col($data, $map, 'caducidad');
        $origen = get_by_col($data, $map, 'origen');
        $tipo_arch = get_by_col($data, $map, 'tipo_ingreso');

        $estado = 'OK';
        $mensaje = [];

        if (!$producto) {
            $estado = 'ERROR';
            $mensaje[] = 'Producto vacío.';
        }
        if (!$cantidad || !is_numeric($cantidad) || $cantidad <= 0) {
            $estado = 'ERROR';
            $mensaje[] = 'Cantidad inválida.';
        }
        if (!$uom) {
            $mensaje[] = 'UOM vacía.';
            if ($estado === 'OK') {
                $estado = 'WARNING';
            }
        }
        if (!$nivel) {
            $mensaje[] = 'Nivel no especificado (PZ/CJ/PL).';
            if ($estado === 'OK') {
                $estado = 'WARNING';
            }
        }

        if ($estado === 'OK')
            $total_ok++;
        if ($estado === 'ERROR')
            $total_err++;

        $filas[] = [
            'estado' => $estado,
            'mensaje' => implode(' ', $mensaje),
            'tipo_ingreso' => $tipo_arch ?: $tipo_ingreso,
            'origen' => $origen,
            'producto' => $producto,
            'cantidad' => $cantidad,
            'uom' => $uom,
            'bl_destino' => $bl_destino,
            'nivel' => $nivel,
            'id_contenedor' => $id_cont,
            'id_pallet' => $id_pallet,
            'epc' => $epc,
            'code' => $code,
            'lote' => $lote,
            'caducidad' => $caducidad,
        ];
    }

    fclose($handle);

    $mensaje_global = 'Previsualización generada. Se leyeron ' . $total . ' filas (máx ' . $max_filas . ').';

    send_json([
        'ok' => true,
        'total' => $total,
        'total_ok' => $total_ok,
        'total_err' => $total_err,
        'filas' => $filas,
        'mensaje_global' => $mensaje_global
    ]);
}

/**
 * Procesa definitivamente el archivo:
 * - Relee CSV
 * - Valida básico
 * - Inyecta existencias (simplificado)
 * - Inyecta movimiento al Kardex (bidireccional de entrada)
 */
function action_procesar(): void
{
    try {
        $pdo = db_pdo();
    } catch (Throwable $e) {
        send_json([
            'ok' => false,
            'error' => 'No existe la conexión PDO en db.php: ' . $e->getMessage()
        ]);
        return;
    }

    $empresa_id = $_POST['empresa_id'] ?? '';
    $almacen_id = $_POST['almacen_id'] ?? '';
    $tipo_ingreso = strtoupper($_POST['tipo_ingreso'] ?? '');

    if (!$empresa_id || !$almacen_id || !$tipo_ingreso) {
        send_json([
            'ok' => false,
            'error' => 'Debe seleccionar Empresa, Almacén y Tipo de ingreso antes de procesar.'
        ]);
        return;
    }

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        send_json([
            'ok' => false,
            'error' => 'No se recibió el archivo a procesar o hubo un error en la carga.'
        ]);
        return;
    }

    $tmpName = $_FILES['archivo']['tmp_name'];
    $name = $_FILES['archivo']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv'])) {
        send_json([
            'ok' => false,
            'error' => 'Por el momento solo se soportan archivos CSV para procesar.'
        ]);
        return;
    }

    $handle = fopen($tmpName, 'r');
    if (!$handle) {
        send_json([
            'ok' => false,
            'error' => 'No se pudo abrir el archivo recibido.'
        ]);
        return;
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        send_json([
            'ok' => false,
            'error' => 'El archivo está vacío o no tiene encabezados.'
        ]);
        return;
    }

    $headers_norm = array_map(function ($h) {
        return strtolower(trim($h));
    }, $headers);
    $map = array_flip($headers_norm);

    $total = 0;
    $total_ok = 0;
    $total_err = 0;
    $errores = [];

    $usuario = $_SESSION['usuario'] ?? 'SYSTEM'; // ajusta al nombre real de campo de sesión

    // TODO: si quieres, aquí inicia la corrida en etl_runs (proceso=IMP_INGRESOS)

    $pdo->beginTransaction();

    try {
        while (($data = fgetcsv($handle)) !== false) {
            $total++;

            $producto = get_by_col($data, $map, 'producto');
            $cantidad = (float) get_by_col($data, $map, 'cantidad');
            $uom = get_by_col($data, $map, 'uom');
            $bl_destino = get_by_col($data, $map, 'bl_destino');
            $nivel = strtoupper(get_by_col($data, $map, 'nivel'));
            $id_cont = get_by_col($data, $map, 'id_contenedor');
            $id_pallet = get_by_col($data, $map, 'id_pallet');
            $epc = get_by_col($data, $map, 'epc');
            $code = get_by_col($data, $map, 'code');
            $lote = get_by_col($data, $map, 'lote');
            $caducidad = get_by_col($data, $map, 'caducidad');
            $origen = get_by_col($data, $map, 'origen');
            $tipo_file = strtoupper(get_by_col($data, $map, 'tipo_ingreso') ?: $tipo_ingreso);

            // Validaciones mínimas
            $estado = 'OK';
            $mensaje = [];

            if (!$producto) {
                $estado = 'ERROR';
                $mensaje[] = 'Producto vacío.';
            }
            if (!$cantidad || $cantidad <= 0) {
                $estado = 'ERROR';
                $mensaje[] = 'Cantidad inválida.';
            }
            if (!$nivel) {
                $estado = 'ERROR';
                $mensaje[] = 'Nivel vacío (PZ/CJ/PL).';
            }

            if ($estado === 'ERROR') {
                $total_err++;
                $errores[] = 'Fila ' . $total . ': ' . implode(' ', $mensaje);
                continue;
            }

            // TODO: validar producto vs catálogo (c_producto / c_articulo, etc.)
            // TODO: validar BL vs c_ubicacion (obtener id_ubicacion a partir de CodigoCSD)

            // Para este primer cierre de ciclo:
            // 1) Insertamos existencia simplificada
            // 2) Insertamos movimiento en t_cardex (origen EXTERNO -> destino ALMACEN/BL)

            // 1) Insertar existencia
            $id_existencia = insertar_existencia_simple(
                $pdo,
                (int) $empresa_id,
                (int) $almacen_id,
                $bl_destino,
                $producto,
                $lote,
                $cantidad,
                $uom,
                $nivel,
                $id_pallet,
                $id_cont,
                $epc,
                $code,
                $caducidad
            );

            // 2) Insertar movimiento en Kardex
            insertar_kardex_entrada(
                $pdo,
                (int) $empresa_id,
                (int) $almacen_id,
                $producto,
                $lote,
                $cantidad,
                $uom,
                $tipo_file,
                $origen,
                $bl_destino,
                $nivel,
                $id_pallet,
                $id_cont,
                $epc,
                $code,
                $usuario
            );

            $total_ok++;
        }

        fclose($handle);

        if ($total_ok === 0) {
            // nada válido, hacemos rollback
            $pdo->rollBack();
            send_json([
                'ok' => false,
                'error' => 'No se pudo procesar ninguna fila válida.',
                'total' => $total,
                'err' => $errores
            ]);
            return;
        }

        $pdo->commit();

        send_json([
            'ok' => true,
            'mensaje' => 'Procesadas ' . $total_ok . ' filas. Errores: ' . $total_err,
            'total' => $total,
            'total_ok' => $total_ok,
            'total_err' => $total_err,
            'errores' => $errores
        ]);

    } catch (Throwable $e) {
        $pdo->rollBack();
        fclose($handle);

        send_json([
            'ok' => false,
            'error' => 'Error al procesar importación: ' . $e->getMessage(),
            'total' => $total,
            'errores' => $errores
        ]);
    }
}

/**
 * Inserta existencia en tablas de stock según nivel (simplificado).
 * TODO: ajustar a tu modelo real de ts_existenciatarima / ts_existenciapiezas y amarrar a c_charolas.
 */
function insertar_existencia_simple(
    PDO $pdo,
    int $empresa_id,
    int $almacen_id,
    string $bl_destino,
    string $producto,
    ?string $lote,
    float $cantidad,
    string $uom,
    string $nivel,
    ?string $id_pallet,
    ?string $id_contenedor,
    ?string $epc,
    ?string $code,
    ?string $caducidad
) {
    // TODO: resolver id_producto real a partir de $producto
    $id_producto = $producto; // placeholder, si tu id es numérico, haz un SELECT previo

    // TODO: resolver id_ubicacion a partir de BL (CodigoCSD) en c_ubicacion
    $id_ubicacion = null;

    // TODO: si manejas c_charolas: obtener id_charola a partir de LP/CT
    $id_charola = null;

    $fecha_cad = $caducidad ? $caducidad : null;

    if ($nivel === 'PZ') {
        // Inserta en ts_existenciapiezas (ajusta nombres)
        $sql = "
            INSERT INTO ts_existenciapiezas
                (empresa_id, cve_almac, id_ubicacion, bl,
                 id_producto, lote, cantidad, uom,
                 epc, code, existencia, fecha_caducidad, fecha_alta)
            VALUES
                (:empresa_id, :almacen_id, :id_ubicacion, :bl,
                 :id_producto, :lote, :cantidad, :uom,
                 :epc, :code, :existencia, :fecha_cad, NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':almacen_id' => $almacen_id,
            ':id_ubicacion' => $id_ubicacion,
            ':bl' => $bl_destino,
            ':id_producto' => $id_producto,
            ':lote' => $lote,
            ':cantidad' => $cantidad,
            ':uom' => $uom,
            ':epc' => $epc,
            ':code' => $code,
            ':existencia' => $cantidad,
            ':fecha_cad' => $fecha_cad,
        ]);
        return $pdo->lastInsertId();
    } else {
        // CJ o PL → tratamos como charola (tarima/contenedor)
        // Inserta en ts_existenciatarima (ajusta nombres)
        $sql = "
            INSERT INTO ts_existenciatarima
                (empresa_id, cve_almac, id_ubicacion, bl,
                 id_charola, id_producto, lote,
                 cantidad, uom, epc, code, existencia, fecha_alta)
            VALUES
                (:empresa_id, :almacen_id, :id_ubicacion, :bl,
                 :id_charola, :id_producto, :lote,
                 :cantidad, :uom, :epc, :code, :existencia, NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':almacen_id' => $almacen_id,
            ':id_ubicacion' => $id_ubicacion,
            ':bl' => $bl_destino,
            ':id_charola' => $id_charola,
            ':id_producto' => $id_producto,
            ':lote' => $lote,
            ':cantidad' => $cantidad,
            ':uom' => $uom,
            ':epc' => $epc,
            ':code' => $code,
            ':existencia' => $cantidad,
        ]);
        return $pdo->lastInsertId();
    }
}

/**
 * Inserta movimiento de ENTRADA en Kardex (bidireccional básico).
 * TODO: ajustar a tu estructura real de t_cardex / v_kardex_doble_partida.
 */
function insertar_kardex_entrada(
    PDO $pdo,
    int $empresa_id,
    int $almacen_id,
    string $producto,
    ?string $lote,
    float $cantidad,
    string $uom,
    string $tipo_ingreso,
    ?string $folio_origen,
    string $bl_destino,
    string $nivel,
    ?string $id_pallet,
    ?string $id_contenedor,
    ?string $epc,
    ?string $code,
    string $usuario
) {
    // TODO: mapear tipo_ingreso → tipo_mov de tu catálogo (cat_mov_tipos)
    $tipo_mov = 'EN_' . $tipo_ingreso; // ej. EN_OC, EN_RL, EN_ASN, EN_INV_INI, EN_XD

    // TODO: resolver id_producto real a partir de $producto
    $id_producto = $producto; // placeholder

    // Para entrada general:
    //   ORIGEN: externo (proveedor / ajuste / inventario inicial)
    //   DESTINO: almacén / BL
    $sql = "
        INSERT INTO t_cardex
            (empresa_id,
             cve_almac_origen, bl_origen,
             cve_almac_destino, bl_destino,
             id_producto, lote, cantidad, uom,
             tipo_mov, folio_mov,
             nivel, id_pallet, id_contenedor,
             epc, code,
             fecha_mov, usuario_mov)
        VALUES
            (:empresa_id,
             NULL, NULL,
             :cve_almac_destino, :bl_destino,
             :id_producto, :lote, :cantidad, :uom,
             :tipo_mov, :folio_mov,
             :nivel, :id_pallet, :id_contenedor,
             :epc, :code,
             NOW(), :usuario_mov)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':empresa_id' => $empresa_id,
        ':cve_almac_destino' => $almacen_id,
        ':bl_destino' => $bl_destino,
        ':id_producto' => $id_producto,
        ':lote' => $lote,
        ':cantidad' => $cantidad,
        ':uom' => $uom,
        ':tipo_mov' => $tipo_mov,
        ':folio_mov' => $folio_origen,
        ':nivel' => $nivel,
        ':id_pallet' => $id_pallet,
        ':id_contenedor' => $id_contenedor,
        ':epc' => $epc,
        ':code' => $code,
        ':usuario_mov' => $usuario,
    ]);
}

/**
 * Helper para tomar un valor por nombre de columna (normalizada en minúsculas).
 */
function get_by_col(array $data, array $map, string $nombre_col)
{
    $key = strtolower($nombre_col);
    if (!isset($map[$key])) {
        return '';
    }
    $idx = $map[$key];
    return $data[$idx] ?? '';
}
