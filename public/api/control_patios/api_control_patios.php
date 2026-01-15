<?php
// public/api/control_patios/api_control_patios.php
declare(strict_types=1);

/**
 * API CONTROL PATIOS (YMS)
 * Endpoint fachada por "accion"
 *
 * Acciones:
 * - tablero (GET)
 * - nueva_visita (POST)
 * - ocs_pendientes (GET)
 * - vincular_oc (POST)
 * - estado_oc (GET)
 * - cambiar_estado (POST)
 */

require_once __DIR__ . '/../../../app/api_base.php';

try {

    $accion = api_str($_REQUEST, 'accion', '');
    if ($accion === '') {
        throw new RuntimeException("Acción no especificada");
    }

    switch ($accion) {

        /* ===============================
         * TABLERO (t_patio_visita REAL)
         * ===============================*/
        case 'tablero':
            api_method('GET');

            $empresa_id  = api_int($_GET, 'empresa_id', 0);
            $almacenp_id = api_str($_GET, 'almacenp_id', '');

            if ($empresa_id <= 0) throw new RuntimeException("empresa_id inválido");
            if ($almacenp_id === '') throw new RuntimeException("almacenp_id inválido");

            $rows = db_all("
                SELECT
                    v.id_visita,
                    v.id_cita,
                    v.id_transporte,
                    v.empresa_id,
                    v.almacenp_id,
                    v.id_zona,
                    v.id_anden_actual,
                    v.estatus,
                    v.fecha_llegada,
                    v.fecha_salida,
                    v.observaciones,
                    v.usuario_checkin,
                    v.usuario_checkout,
                    v.usuario_asigna,
                    v.fecha_asigna
                FROM t_patio_visita v
                WHERE v.empresa_id = :empresa_id
                  AND v.almacenp_id = :almacenp_id
                ORDER BY v.id_visita DESC
                LIMIT 500
            ", [
                'empresa_id'  => $empresa_id,
                'almacenp_id' => $almacenp_id
            ]);

            api_ok(['data' => $rows]);
            break;

        /* ===============================
         * NUEVA VISITA
         * ===============================*/
        case 'nueva_visita':
            api_method('POST');

            $usuario       = api_user();
            $empresa_id    = api_int($_POST, 'empresa_id', 0);
            $almacenp_id   = api_str($_POST, 'almacenp_id', '');
            $id_transporte = api_int($_POST, 'id_transporte', 0);
            $id_cita       = api_int($_POST, 'id_cita', 0);
            $id_zona       = api_int($_POST, 'id_zona', 0);
            $observaciones = api_str($_POST, 'observaciones', '');

            if ($empresa_id <= 0) throw new RuntimeException("Falta/Inválido empresa_id");
            if ($almacenp_id === '') throw new RuntimeException("Falta almacenp_id");
            if ($id_transporte <= 0) throw new RuntimeException("Falta/Inválido id_transporte");

            // Nota: tu enum de t_patio_visita muestra EN_PATIO como predeterminado.
            // Para nueva visita dejamos EN_PATIO (operación inmediata) o ajusta si quieres "PROGRAMADO".
            $estatus_inicial = 'EN_PATIO';

            db_exec("
                INSERT INTO t_patio_visita
                (id_cita, id_transporte, empresa_id, almacenp_id, id_zona, estatus, observaciones, usuario_asigna, fecha_asigna)
                VALUES
                (:id_cita, :id_transporte, :empresa_id, :almacenp_id, :id_zona, :estatus, :observaciones, :usuario_asigna, NOW())
            ", [
                'id_cita'        => ($id_cita > 0 ? $id_cita : null),
                'id_transporte'  => $id_transporte,
                'empresa_id'     => $empresa_id,
                'almacenp_id'    => $almacenp_id,
                'id_zona'        => ($id_zona > 0 ? $id_zona : null),
                'estatus'        => $estatus_inicial,
                'observaciones'  => $observaciones,
                'usuario_asigna' => $usuario,
            ]);

            $id_visita = (int)db_one("SELECT LAST_INSERT_ID() AS id")['id'];

            // Bitácora real: t_patio_mov (según tu estructura)
            db_exec("
                INSERT INTO t_patio_mov
                (id_visita, id_anden, estatus, fecha, usuario, comentario)
                VALUES
                (:id_visita, NULL, :estatus, NOW(), :usuario, :comentario)
            ", [
                'id_visita'   => $id_visita,
                'estatus'     => $estatus_inicial,
                'usuario'     => $usuario,
                'comentario'  => 'CREACION_VISITA'
            ]);

            api_ok([
                'msg'       => 'Visita creada correctamente',
                'id_visita' => $id_visita
            ]);
            break;

        /* ===============================
         * OCs PENDIENTES
         * ===============================*/
        case 'ocs_pendientes':
            api_method('GET');

            $almacenp_id  = api_str($_GET, 'almacenp_id', '');
            $proveedor_id = api_int($_GET, 'proveedor_id', 0);

            if ($almacenp_id === '') throw new RuntimeException("Falta almacenp_id");
            if ($proveedor_id <= 0) throw new RuntimeException("Falta proveedor_id");

            $rows = db_all("
                SELECT
                    oc.id AS oc_id,
                    oc.folio,
                    DATE(oc.fecha) AS fecha,
                    (SUM(d.cantidad) - SUM(d.recibida)) AS pendiente
                FROM th_oc oc
                INNER JOIN td_oc d ON d.id_oc = oc.id
                WHERE oc.almacenp_id = :almacenp_id
                  AND oc.proveedor_id = :proveedor_id
                  AND oc.estatus IN ('ABIERTA','PARCIAL')
                GROUP BY oc.id, oc.folio, DATE(oc.fecha)
                HAVING pendiente > 0
                ORDER BY oc.fecha DESC
                LIMIT 200
            ", [
                'almacenp_id'  => $almacenp_id,
                'proveedor_id' => $proveedor_id
            ]);

            api_ok(['data' => $rows]);
            break;

        /* ===============================
         * VINCULAR OC(s)
         * ===============================*/
        case 'vincular_oc':
            api_method('POST');

            $usuario   = api_user();
            $id_visita = api_int($_POST, 'id_visita', 0);
            $oc_ids    = api_str($_POST, 'oc_ids', '');

            if ($id_visita <= 0) throw new RuntimeException("id_visita inválido");
            if ($oc_ids === '') throw new RuntimeException("Sin OCs");

            $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $oc_ids))));
            if (!$ids) throw new RuntimeException("Lista de OC(s) inválida");

            foreach ($ids as $oc_id) {
                $oc = db_one("SELECT id, folio, fecha, estatus FROM th_oc WHERE id=:id", ['id' => $oc_id]);
                if (!$oc) continue;
