USE assistpro_etl_fc;
SET NAMES utf8mb4;
SET sql_safe_updates = 0;

-- ===========================================================
--  Vista: v_kardex_general
--  Consolidado de movimientos de Kardex (Entradas, Salidas,
--  Acomodo, Traslados) tomando t_cardex como tabla fuente.
--
--  Columnas alineadas al layout de la hoja "Cardex" del Excel:
--   Folio_Ent, OC_Factura, Protocolo, Fecha, Clave, Articulo,
--   Lote_Serie, Caducidad, Pallet_Contenedor, LP, Movimiento,
--   Alm_Origen, BL_Origen, UM, Stock_Ini, Ajuste, Stock_Fin,
--   Alm_Dest, BL_Destino, Stock_Ini_Dest, Ajuste_Dest, Stock_Fin_Dest, Usuario
--
--  Notas:
--   - cat_mov_tipos clasifica el movimiento (p.ej. ENTRADA, SALIDA, TRASLADO, ACOMODO)
--   - c_almacenp: se usa des_almac por cada cve_almac origen/destino
--   - c_ubicacion: se usa codigocsd (BL) por idy_ubica origen/destino
--   - c_articulo/c_unimed: descripción y UM
--   - c_usuario: Nombre_Completo
--   - th_entalmacen (si aplica): para OC_Factura (Fol_OEP) cuando exista relación por folio de entrada
--
--  Ajusta los nombres de campos entre /*?*/ si tu tabla difiere.
-- ===========================================================
DROP VIEW IF EXISTS v_kardex_general;

CREATE VIEW v_kardex_general AS
SELECT
  -- Encabezados
  COALESCE(tc.folio_ent,                          /*?*/ NULL)                 AS Folio_Ent,
  COALESCE(hea.Fol_OEP,                           /*?*/ NULL)                 AS OC_Factura,
  COALESCE(tc.protocolo,                          /*?*/ NULL)                 AS Protocolo,
  tc.fecha                                                                     AS Fecha,

  -- Producto
  tc.cve_articulo                                                             AS Clave,
  ca.des_articulo                                                             AS Articulo,

  -- Lote / Serie / Caducidad
  COALESCE(tc.cve_lote, tc.serie, /*?*/ NULL)                                  AS Lote_Serie,
  COALESCE(tc.caducidad, /*?*/ NULL)                                           AS Caducidad,

  -- Contenedor / LP
  COALESCE(tc.pallet, /*?*/ NULL)                                              AS Pallet_Contenedor,
  COALESCE(tc.lp_codigo, /*?*/ NULL)                                           AS `License Plate (LP)`,

  -- Movimiento
  mt.descripcion                                                              AS Movimiento,

  -- Origen
  aO.des_almac                                                                AS `Alm Origen`,
  blO.codigocsd                                                               AS `Zona|BL Origen`,

  -- UM
  cu.des_umed                                                                 AS UM,

  -- Saldos Origen (si el modelo maneja dos lados)
  COALESCE(tc.stock_inicial_origen,  tc.stock_inicial, /*?*/ NULL)            AS `Stock Ini`,
  COALESCE(tc.ajuste_origen,                    0)                             AS Ajuste,
  COALESCE(
    tc.stock_final_origen,
    CASE
      WHEN tc.stock_inicial IS NOT NULL AND tc.cantidad IS NOT NULL
        THEN tc.stock_inicial + tc.cantidad + COALESCE(tc.ajuste,0)
      ELSE NULL
    END
  )                                                                           AS `Stock Fin`,

  -- Destino
  aD.des_almac                                                                AS `Alm Dest`,
  blD.codigocsd                                                               AS `BL Destino`,

  -- Saldos Destino (si el modelo maneja dos lados)
  COALESCE(tc.stock_inicial_destino, /*?*/ NULL)                               AS `Stock Ini `,
  COALESCE(tc.ajuste_destino,        /*?*/ 0)                                  AS `Ajuste `,
  COALESCE(
    tc.stock_final_destino,
    CASE
      WHEN tc.stock_inicial_destino IS NOT NULL AND tc.cantidad IS NOT NULL
        THEN tc.stock_inicial_destino + tc.cantidad + COALESCE(tc.ajuste_destino,0)
      ELSE NULL
    END
  )                                                                           AS `Stock Fin `,

  -- Usuario
  u.Nombre_Completo                                                           AS Usuario

FROM t_cardex               tc
LEFT JOIN c_articulo        ca  ON ca.cve_articulo   = tc.cve_articulo
LEFT JOIN c_unimed          cu  ON cu.id_umed        = ca.id_umed                  /*? si tu artículo guarda UM directo, cambia aquí */
LEFT JOIN cat_mov_tipos     mt  ON mt.id_tipo_mov    = tc.id_tipo_movimiento       /*? nombre exacto */
LEFT JOIN c_almacenp        aO  ON aO.clave          = tc.cve_almac_origen         /*? */
LEFT JOIN c_almacenp        aD  ON aD.clave          = tc.cve_almac_destino        /*? */
LEFT JOIN c_ubicacion       blO ON blO.idy_ubica     = tc.idy_ubica_origen         /* usa codigocsd como BL */
LEFT JOIN c_ubicacion       blD ON blD.idy_ubica     = tc.idy_ubica_destino        /* usa codigocsd como BL */
LEFT JOIN c_usuario         u   ON u.cve_usuario     = tc.cve_usuario              /*? o u.id = tc.cve_usuario */
-- Folio de entrada / OC cuando aplique (Entradas con OC)
LEFT JOIN th_entalmacen     hea ON hea.Fol_Folio     = tc.folio_ent                /*? si existe relación; si no, elimina esta línea */
;

-- Índices recomendados (opcional pero MUY útil para el filtro en UI)
-- Crea si no existen; ajusta a tus nombres reales
CREATE INDEX IF NOT EXISTS idx_tcardex_fecha           ON t_cardex(fecha);
CREATE INDEX IF NOT EXISTS idx_tcardex_articulo        ON t_cardex(cve_articulo);
CREATE INDEX IF NOT EXISTS idx_tcardex_lote            ON t_cardex(cve_lote);
CREATE INDEX IF NOT EXISTS idx_tcardex_mov             ON t_cardex(id_tipo_movimiento);
CREATE INDEX IF NOT EXISTS idx_tcardex_alm_origen      ON t_cardex(cve_almac_origen);
CREATE INDEX IF NOT EXISTS idx_tcardex_alm_destino     ON t_cardex(cve_almac_destino);
CREATE INDEX IF NOT EXISTS idx_ubicacion_codigocsd     ON c_ubicacion(codigocsd);
