/* ============================================================
   ASSISTPRO ETL — VISTAS DE INVENTARIOS FÍSICOS Y CÍCLICOS
   Version: Consolidada AO (2025-11-12)
   Compatibilidad: MySQL 5.7 / 8.x
   Autor: AO
   ============================================================ */


/* ============================================================
   1. v_inv_fisico_resumen_ao
   ------------------------------------------------------------
   Resumen de inventarios físicos por folio.
   Fuentes:
     - v_administracioninventario  (encabezado)
     - t_invpiezas                 (detalle piezas)
     - c_articulo                  (costos)
   ============================================================ */
DROP VIEW IF EXISTS v_inv_fisico_resumen_ao;

CREATE VIEW v_inv_fisico_resumen_ao AS
SELECT
    a.consecutivo AS folio_inventario,
    a.almacen,
    a.zona,
    a.fecha_inicio,
    a.fecha_final,
    a.usuario,
    a.status,
    a.diferencia AS diferencia_texto,

    /* Métricas cuantitativas (t_invpiezas) */
    SUM(COALESCE(p.Cantidad,0)) AS piezas_contadas,
    SUM(COALESCE(p.Cantidad,0) - COALESCE(p.ExistenciaTeorica,0)) AS diferencia_piezas,

    /* Valores con costo (c_articulo) */
    SUM(COALESCE(p.Cantidad,0) * COALESCE(art.costoPromedio, art.imp_costo, 0)) AS valor_inventariado,
    SUM((COALESCE(p.Cantidad,0) - COALESCE(p.ExistenciaTeorica,0)) * COALESCE(art.costoPromedio, art.imp_costo, 0)) AS valor_diferencia

FROM v_administracioninventario a
LEFT JOIN t_invpiezas p
       ON p.ID_Inventario = a.consecutivo
LEFT JOIN c_articulo art
       ON art.cve_articulo = p.cve_articulo
GROUP BY
    a.consecutivo, a.almacen, a.zona, a.fecha_inicio, a.fecha_final, a.usuario, a.status, a.diferencia
ORDER BY a.fecha_inicio DESC;
/* Autor: AO */


/* ============================================================
   2. v_inv_ciclico_detalle_ao
   ------------------------------------------------------------
   Detalle unificado de inventarios cíclicos (solo piezas).
   Fuentes:
     - t_invpiezasciclico
   ============================================================ */
DROP VIEW IF EXISTS v_inv_ciclico_detalle_ao;

CREATE VIEW v_inv_ciclico_detalle_ao AS
SELECT
    t.ID_PLAN                           AS folio_plan,
    t.NConteo,
    t.idy_ubica,
    t.cve_articulo,
    t.cve_lote,
    'PIEZA'                             AS tipo_unidad,
    NULL                                AS piezas_x_caja,
    t.Cantidad                          AS cantidad_conteo,
    t.ExistenciaTeorica                 AS existencia_teorica,
    (COALESCE(t.Cantidad,0) -
     COALESCE(t.ExistenciaTeorica,0))   AS diferencia_piezas,
    t.Id_Proveedor,
    t.Cuarentena,
    t.ClaveEtiqueta,
    t.cve_usuario,
    t.fecha
FROM t_invpiezasciclico t;
/* Autor: AO */


/* ============================================================
   3. v_dashboard_inv_fisico_ao
   ------------------------------------------------------------
   Dashboard de inventarios físicos.
   Fuentes:
     - th_inventario, c_almacen
     - t_ubicacionesainventariar, t_ubicacioninventario
     - t_invpiezas
   ============================================================ */
DROP VIEW IF EXISTS v_dashboard_inv_fisico_ao;

CREATE VIEW v_dashboard_inv_fisico_ao AS
SELECT
    th.ID_Inventario                        AS folio_inventario,
    th.Fecha                                AS fecha_creacion,
    th.Nombre                               AS nombre_inventario,
    th.Status                               AS status_inventario,
    th.cve_almacen,
    a.des_almac                             AS almacen,
    th.cve_zona,
    th.Inv_Inicial                          AS es_inicial,

    /* Avance por ubicaciones */
    COUNT(DISTINCT uai.idy_ubica)           AS ubicaciones_planeadas,
    COUNT(DISTINCT ui.idy_ubica)            AS ubicaciones_contadas,
    CASE 
        WHEN COUNT(DISTINCT uai.idy_ubica) = 0 THEN 0
        ELSE ROUND((COUNT(DISTINCT ui.idy_ubica) / COUNT(DISTINCT uai.idy_ubica)) * 100, 2)
    END                                     AS avance_porcentual,

    /* Cuantitativos */
    SUM(COALESCE(p.Cantidad,0))             AS piezas_contadas,
    SUM(COALESCE(p.ExistenciaTeorica,0))    AS piezas_teoricas,
    (SUM(COALESCE(p.Cantidad,0)) - SUM(COALESCE(p.ExistenciaTeorica,0))) AS diferencia_piezas,

    /* Estado lógico del proceso */
    CASE 
        WHEN th.Status IN ('CERRADO','FINALIZADO') THEN 'Cerrado'
        WHEN COUNT(DISTINCT ui.idy_ubica) = 0 THEN 'Planeado'
        WHEN COUNT(DISTINCT ui.idy_ubica) < COUNT(DISTINCT uai.idy_ubica) THEN 'En ejecución'
        ELSE 'Completado'
    END AS estado_proceso

FROM th_inventario th
JOIN c_almacen a 
      ON a.cve_almac = th.cve_almacen
LEFT JOIN t_ubicacionesainventariar uai
      ON uai.ID_Inventario = th.ID_Inventario
LEFT JOIN t_ubicacioninventario ui
      ON ui.ID_Inventario = th.ID_Inventario
LEFT JOIN t_invpiezas p
      ON p.ID_Inventario = th.ID_Inventario
GROUP BY
    th.ID_Inventario, th.Fecha, th.Nombre, th.Status,
    th.cve_almacen, a.des_almac, th.cve_zona, th.Inv_Inicial
ORDER BY th.Fecha DESC;
/* Autor: AO */


/* ============================================================
   4. v_dashboard_inv_ciclico_ao
   ------------------------------------------------------------
   Dashboard de inventarios cíclicos.
   Fuentes:
     - cab_planifica_inventario, c_almacenp
     - ubicacionesinventareadas, t_invpiezasciclico
   ============================================================ */
DROP VIEW IF EXISTS v_dashboard_inv_ciclico_ao;

CREATE VIEW v_dashboard_inv_ciclico_ao AS
SELECT
    p.ID_PLAN                                 AS folio_plan,
    p.FECHA_INI                               AS fecha_inicio,
    p.FECHA_FIN                               AS fecha_fin,
    p.Activo,
    ap.id                                     AS id_almacen,
    ap.clave                                  AS cve_almacen,
    ap.nombre                                 AS des_almacen,

    /* Avance por ubicaciones */
    COUNT(DISTINCT uinv.idy_ubica)            AS ubicaciones_planeadas,
    COUNT(DISTINCT pz.idy_ubica)              AS ubicaciones_contadas,
    CASE 
        WHEN COUNT(DISTINCT uinv.idy_ubica) = 0 THEN 0
        ELSE ROUND((COUNT(DISTINCT pz.idy_ubica) / COUNT(DISTINCT uinv.idy_ubica)) * 100, 2)
    END                                       AS avance_porcentual,

    /* Cuantitativos */
    SUM(COALESCE(pz.Cantidad,0))              AS piezas_contadas,
    SUM(COALESCE(pz.ExistenciaTeorica,0))     AS piezas_teoricas,
    (SUM(COALESCE(pz.Cantidad,0)) - SUM(COALESCE(pz.ExistenciaTeorica,0))) AS diferencia_piezas,

    /* Estado lógico */
    CASE 
        WHEN p.Activo = 0 THEN 'Cerrado'
        WHEN COUNT(DISTINCT pz.idy_ubica) = 0 THEN 'Planeado'
        WHEN COUNT(DISTINCT pz.idy_ubica) < COUNT(DISTINCT uinv.idy_ubica) THEN 'En ejecución'
        ELSE 'Completado'
    END AS estado_proceso

FROM cab_planifica_inventario p
JOIN c_almacenp ap
      ON ap.id = p.id_almacen
LEFT JOIN ubicacionesinventareadas uinv
      ON uinv.ID_PLAN = p.ID_PLAN
LEFT JOIN t_invpiezasciclico pz
      ON pz.ID_PLAN = p.ID_PLAN
GROUP BY
    p.ID_PLAN, p.FECHA_INI, p.FECHA_FIN, p.Activo,
    ap.id, ap.clave, ap.nombre
ORDER BY p.FECHA_INI DESC;
/* Autor: AO */
