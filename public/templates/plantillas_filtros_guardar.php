<?php
// public/templates/plantillas_filtros_guardar.php
// Guarda una plantilla de filtros AssistPro, validando módulo

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

// ========= 1. Datos básicos de la plantilla =========
$modulo      = $_POST['modulo']      ?? '';
$nombre      = $_POST['nombre']      ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$vistaSql    = $_POST['vista_sql']   ?? '';

$modulo      = trim($modulo);
$nombre      = trim($nombre);
$descripcion = trim($descripcion);
$vistaSql    = trim($vistaSql);

if ($modulo === '' || $nombre === '') {
    echo "Módulo y nombre de plantilla son obligatorios.";
    exit;
}

// ========= 2. Validar que el módulo exista en ap_plantillas_modulos =========
$modRow = db_one("
    SELECT id
    FROM ap_plantillas_modulos
    WHERE clave_modulo = ? AND activo = 1
", [$modulo]);

if (!$modRow || !isset($modRow['id'])) {
    echo "El módulo [$modulo] no existe o está inactivo en ap_plantillas_modulos. ".
         "Primero crea el módulo en plantillas_modulos.php.";
    exit;
}

// ========= 3. Definir filtros que conocemos =========

$namesSimple = [
    'empresa','almacen','zona','bl',
    'lp','producto','lote',
    'ruta','cliente','proveedor',
    'vendedor','usuario',
    'zona_recep','zona_qa','zona_emb',
    'proyecto',
    'ubica_mfg',   // Ubicaciones en Manufactura
];

$namesUse = [
    'empresa','almacen','zona','bl',
    'lp','producto','lote',
    'ruta','cliente','proveedor',
    'vendedor','usuario',
    'zona_recep','zona_qa','zona_emb',
    'proyecto',
    'ubica_mfg',
];

// ========= 4. Construir el JSON de filtros =========

// a) cuáles filtros se usan en la plantilla (use_*)
$use = [];
foreach ($namesUse as $n) {
    $key     = 'use_' . $n;
    $use[$n] = isset($_POST[$key]) && $_POST[$key] === '1';
}

// b) valores por defecto de cada filtro
$defaults = [];
foreach ($namesSimple as $n) {
    $defaults[$n] = $_POST[$n] ?? '';
}

$filtrosJson = json_encode(
    [
        'use'      => $use,
        'defaults' => $defaults,
    ],
    JSON_UNESCAPED_UNICODE
);

// ========= 5. Insertar en BD =========
// Estructura esperada de ap_plantillas_filtros:
//  ap_plantillas_filtros(id, modulo, nombre, descripcion, vista_sql,
//                        filtros_json, es_default, activo, creado_por, creado_en, actualizado_en)

$sql = "
    INSERT INTO ap_plantillas_filtros
        (modulo, nombre, descripcion, vista_sql, filtros_json, es_default, activo, creado_por)
    VALUES
        (:modulo, :nombre, :descripcion, :vista_sql, :filtros_json, 0, 1, NULL)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':modulo'       => $modulo,
    ':nombre'       => $nombre,
    ':descripcion'  => $descripcion,
    ':vista_sql'    => $vistaSql !== '' ? $vistaSql : null,
    ':filtros_json' => $filtrosJson,
]);

// ========= 6. Regresar al administrador de plantillas =========

header('Location: plantillas_filtros.php?modulo=' . urlencode($modulo));
exit;
