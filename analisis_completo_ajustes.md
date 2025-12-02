# Análisis Completo: Módulo de Ajustes de Existencia

Este documento consolida toda la información técnica, lógica de negocio, consultas SQL y filtros dinámicos del módulo de Ajustes de Existencia (`lists.php` y sus APIs).

---

## 1. Funcionalidades de la Vista (`lists.php`)
El archivo `app/template/page/ajustesexistencia/lists.php` es la interfaz principal.

1.  **Tablero de Control**: Muestra % de Ocupación, Total de Ubicaciones y Ubicaciones Vacías.
2.  **Explorador (Grid)**: Lista ubicaciones con filtros por Almacén, Zona, Tipo (Picking, Cuarentena, etc.) y Búsqueda por BL.
3.  **Auditoría (Modal Detalle)**: Permite ver el contenido exacto de una ubicación y comparar la existencia teórica vs. real.
4.  **Ajustes de Inventario**: Permite modificar cantidades, requiriendo un "Motivo" para trazabilidad.
5.  **Navegación Inteligente**: Preselección automática de almacén y filtrado en cascada de zonas.

---

## 2. APIs y Dependencias
El frontend consume los siguientes endpoints:

1.  **`/api/almacenPredeterminado/index.php`**: Configuración de usuario.
2.  **`/api/almacenp/update/index.php`**: Obtención de zonas (combos).
3.  **`/api/ajustesexistencias/lista/index.php`**: **Core**. Maneja el grid, detalles y guardado.
4.  **`/api/reportes/lista/existenciaubica.php`**: Catálogos (Motivos) y reportes complejos.

---

## 3. Documentación Exhaustiva de Queries SQL

### A. Archivo: `api/ajustesexistencias/lista/index.php`

#### 1. Acción: `loadGrid` (Listado Principal)
Obtiene las ubicaciones para el grid.
```sql
SELECT
    u.idy_ubica,
    u.PesoMaximo as PesoMax,
    a.des_almac as zona_almacenaje,
    u.cve_pasillo AS pasillo,
    u.cve_rack AS rack,
    u.cve_nivel AS nivel,
    u.Seccion AS seccion,
    u.Ubicacion AS posicion,
    u.CodigoCSD AS BL,
    GROUP_CONCAT(DISTINCT ch.clave_contenedor SEPARATOR ',') AS clave_contenedor,
    GROUP_CONCAT(DISTINCT ch.CveLP SEPARATOR ',') AS CveLP,
    u.num_alto,
    u.num_ancho,
    u.num_largo,
    u.AcomodoMixto,
    u.AreaProduccion,
    u.picking,
    u.TECNOLOGIA,
    (CASE
        WHEN u.Tipo = 'L' THEN 'Libre'
        WHEN u.Tipo = 'R' THEN 'Restringida'
        WHEN u.Tipo = 'Q' THEN 'Cuarentena'
        ELSE '--'
    END) AS tipo_ubicacion,
    IFNULL(TRUNCATE((u.num_ancho / 1000) * (u.num_alto / 1000) * (u.num_largo / 1000), 2), 0) AS volumen_m3,
    IFNULL(u.orden_secuencia, '--') AS surtido,
    if(u.TECNOLOGIA='PTL','S','N') as Ptl,
    if(u.Tipo='L','S','N') as li,
    if(u.Tipo='R','S','N') as re,
    if(u.Tipo='Q','S','N') as cu
FROM c_ubicacion u
LEFT JOIN c_almacen a ON a.cve_almac = u.cve_almac
LEFT JOIN V_ExistenciaGralProduccion ve ON ve.cve_ubicacion = u.idy_ubica AND ve.cve_almac = a.cve_almacenp
LEFT JOIN c_charolas ch ON ch.clave_contenedor = ve.Cve_Contenedor
WHERE ve.Existencia > 0 
-- Filtros Dinámicos:
-- AND ve.cve_almac = '$almacen' (Si es Almacén Padre)
-- AND u.cve_almac = '$almacenaje' (Si es Zona)
-- AND (u.CodigoCSD like '%$search%') (Búsqueda)
-- AND u.Tipo='$tipo' (Filtro Tipo)
GROUP BY u.CodigoCSD 
LIMIT $offset, $limit;
```

#### 2. Acción: `loadDetails` (Detalle de Ubicación)
Varía según si es Área de Producción o General.

*   **Producción (`areaProduccion == 'S'`)**:
    ```sql
    SELECT 
      IFNULL(c_proveedores.ID_Proveedor, '') as id_proveedor,
      V_ExistenciaGralProduccion.cve_almac, 
      V_ExistenciaGralProduccion.cve_ubicacion, 
      V_ExistenciaGralProduccion.cve_articulo, 
      V_ExistenciaGralProduccion.cve_lote as lote, 
      ifnull(if(c_articulo.control_lotes = 'S', date_format(if(DATE_FORMAT(c_lotes.Caducidad, '%Y-%m-%d')='0000-00-00','',c_lotes.Caducidad),'%d-%m-%Y'),''),'') as caducidad,
      if(c_articulo.control_numero_series = 'S',V_ExistenciaGralProduccion.cve_lote,'') as serie,
      ROUND(SUM(V_ExistenciaGralProduccion.Existencia)/COUNT(V_ExistenciaGralProduccion.Existencia), 2) as Existencia_Total,
      c_articulo.des_articulo as descripcion,
      V_ExistenciaGralProduccion.Cve_Contenedor as contenedor,
      IFNULL(c_proveedores.Nombre, '') as proveedor,
      IF(c_articulo.control_peso = 'S',ROUND(SUM(c_articulo.peso), 2),TRUNCATE(IFNULL(ROUND(c_articulo.peso, 2),0),2)) AS peso_unitario,
      TRUNCATE(((c_articulo.alto*c_articulo.ancho*c_articulo.fondo)/1000000000),2) as volumen_unitario,
      IF (c_articulo.control_peso = 'S',TRUNCATE((IFNULL(c_articulo.peso,0)*SUM(V_ExistenciaGralProduccion.Existencia)),2),TRUNCATE((IFNULL(c_articulo.peso,0)*SUM(V_ExistenciaGralProduccion.Existencia)),2)) AS peso_total,
      TRUNCATE((((c_articulo.alto*c_articulo.ancho*c_articulo.fondo)/1000000000)*SUM(V_ExistenciaGralProduccion.Existencia)),2)as volumen_total
    FROM V_ExistenciaGralProduccion 
    LEFT JOIN c_articulo ON c_articulo.cve_articulo = V_ExistenciaGralProduccion.cve_articulo
    LEFT JOIN c_lotes ON c_lotes.LOTE = V_ExistenciaGralProduccion.cve_lote and c_lotes.cve_articulo = c_articulo.cve_articulo
    LEFT JOIN c_proveedores ON c_proveedores.ID_Proveedor = V_ExistenciaGralProduccion.Id_Proveedor
    WHERE cve_ubicacion = '{$ubicacion}' 
    AND V_ExistenciaGralProduccion.cve_almac = '{$almacen}' 
    AND V_ExistenciaGralProduccion.Existencia IS NOT NULL 
    AND V_ExistenciaGralProduccion.cve_articulo = c_articulo.cve_articulo AND IFNULL(V_ExistenciaGralProduccion.cve_lote, '') = IFNULL(c_lotes.Lote, '')
    GROUP BY V_ExistenciaGralProduccion.cve_articulo,V_ExistenciaGralProduccion.cve_lote, V_ExistenciaGralProduccion.Cve_Contenedor, id_proveedor
    ORDER BY descripcion;
    ```

*   **General (`areaProduccion != 'S'`)**:
    ```sql
    SELECT 
    IFNULL(c_proveedores.ID_Proveedor, '') as id_proveedor,
      V_ExistenciaGral.cve_almac, 
      V_ExistenciaGral.cve_ubicacion, 
      V_ExistenciaGral.cve_articulo, 
      IFNULL(V_ExistenciaGral.cve_lote, '') AS lote, 
      ifnull(if(c_articulo.control_lotes = 'S', date_format(if(DATE_FORMAT(c_lotes.Caducidad, '%Y-%m-%d')='0000-00-00','',c_lotes.Caducidad),'%d-%m-%Y'),''),'') as caducidad,
      if(c_articulo.control_numero_series = 'S',V_ExistenciaGral.cve_lote,'') as serie,
      ROUND(SUM(V_ExistenciaGral.Existencia)/COUNT(V_ExistenciaGral.Existencia), 2) as Existencia_Total,
      c_articulo.des_articulo as descripcion,
      V_ExistenciaGral.Cve_Contenedor as contenedor,
      IFNULL(c_proveedores.Nombre, '') as proveedor,
      IF(c_articulo.control_peso = 'S',ROUND(SUM(c_articulo.peso), 2),TRUNCATE(IFNULL(ROUND(c_articulo.peso, 2),0),2)) AS peso_unitario,
      TRUNCATE(((c_articulo.alto*c_articulo.ancho*c_articulo.fondo)/1000000000),2) as volumen_unitario,
      IF (c_articulo.control_peso = 'S',TRUNCATE((IFNULL(c_articulo.peso,0)*SUM(V_ExistenciaGral.Existencia)),2),TRUNCATE((IFNULL(c_articulo.peso,0)*SUM(V_ExistenciaGral.Existencia)),2)) AS peso_total,
      TRUNCATE((((c_articulo.alto*c_articulo.ancho*c_articulo.fondo)/1000000000)*SUM(V_ExistenciaGral.Existencia)),2)as volumen_total
    FROM V_ExistenciaGral 
    LEFT JOIN c_articulo ON c_articulo.cve_articulo = V_ExistenciaGral.cve_articulo
    LEFT JOIN c_lotes ON c_lotes.LOTE = V_ExistenciaGral.cve_lote and c_lotes.cve_articulo = c_articulo.cve_articulo
    LEFT JOIN c_serie ON c_serie.numero_serie = V_ExistenciaGral.cve_lote AND c_serie.cve_articulo = c_articulo.cve_articulo
    LEFT JOIN c_proveedores ON c_proveedores.ID_Proveedor = V_ExistenciaGral.Id_Proveedor
    WHERE cve_ubicacion = '{$ubicacion}' 
    AND V_ExistenciaGral.cve_almac = '{$almacen}' 
    AND V_ExistenciaGral.Existencia IS NOT NULL 
    GROUP BY V_ExistenciaGral.cve_articulo,V_ExistenciaGral.cve_lote, V_ExistenciaGral.Cve_Contenedor, id_proveedor
    ORDER BY descripcion;
    ```

#### 3. Acción: `actualizar_existencias` (Transacción Compleja)
Secuencia de operaciones para guardar un ajuste:

1.  **Actualizar Existencia Física (Tarima)**:
    ```sql
    UPDATE ts_existenciatarima SET existencia = '$existencia' WHERE cve_almac = '$id_almacen' and idy_ubica = '$id_ubica' and cve_articulo = '$clave' and lote = '$lote' and ID_Proveedor = '$id_proveedor' and ntarima = (SELECT IDContenedor FROM c_charolas WHERE clave_contenedor = '$contenedor');
    ```
2.  **Actualizar Existencia Física (Cajas)**:
    ```sql
    UPDATE ts_existenciacajas SET Existencia = '$existencia' WHERE cve_almac = '$id_almacen' and idy_ubica = '$id_ubica' and cve_articulo = '$clave' and cve_lote = '$lote';
    ```
3.  **Actualizar Existencia Física (Piezas)**:
    ```sql
    UPDATE ts_existenciapiezas SET Existencia = '$existencia' WHERE cve_almac = '$id_almacen' and idy_ubica = '$id_ubica' and cve_articulo = '$clave' and cve_lote = '$lote' and ID_Proveedor = '$id_proveedor';
    ```
4.  **Registrar Kardex**:
    ```sql
    INSERT INTO t_cardex(cve_articulo, cve_lote, fecha, origen, destino, cantidad, id_TipoMovimiento, cve_usuario, Cve_Almac, Activo, Fec_Ingreso, Id_Motivo) VALUES('$clave_articulo', '$lote', now(), '$id_ubica', '$id_ubica', ABS($existencia_actual - $nueva_existencia), CASE WHEN $existencia_actual > $nueva_existencia THEN 10 ELSE 9 END, '$cve_usuario', '$id_almacen', '1', now(), '$motivos');
    ```
5.  **Registrar Histórico (Cabecera)**:
    ```sql
    INSERT INTO th_ajusteexist VALUES('$folio', '$id_almacen', now(), '$idUser', '', '1');
    ```
6.  **Registrar Histórico (Detalle)**:
    ```sql
    INSERT INTO td_ajusteexist VALUES('$folio', '$id_almacen', '$id_ubica', '$clave_articulo', '$lote', '$existencia_actual', '$nueva_existencia', '$costo_promedio', '$motivos', 'A', '$contenedor') ON DUPLICATE KEY UPDATE num_cantant='$existencia';
    ```
7.  **Limpieza**:
    ```sql
    DELETE FROM ts_existenciapiezas WHERE existencia <= 0;
    DELETE FROM ts_existenciatarima WHERE existencia <= 0;
    ```

### B. Archivo: `api/reportes/lista/existenciaubica.php`
Este archivo genera la "Mega Query" de reportes con múltiples filtros dinámicos.

#### Consulta Principal (Estructura Completa)
```sql
SELECT 
    x.acciones, x.cve_almacen, x.id_almacen, x.almacen, x.folio_OT, x.NCaja, x.zona, x.codigo, 
    x.cve_ubicacion, x.zona_recepcion, x.QA, x.contenedor, x.LP, x.clave, x.descripcion, 
    x.des_grupo, x.des_clasif, x.cajasxpallets, x.piezasxcajas, x.Pallet, x.Caja, x.Piezas, 
    x.control_lotes, x.control_numero_series, x.lote, x.lote_alterno, x.caducidad, x.nserie, 
    x.peso, (x.cantidad) AS cantidad, (x.cantidad_kg) AS cantidad_kg, x.id_proveedor, 
    (x.proveedor) AS proveedor, MAX(x.empresa_proveedor) AS empresa_proveedor, x.tipo_ubicacion, 
    x.control_abc, x.clasif_abc, x.um, x.control_peso, x.referencia_well, x.pedimento_well, 
    x.codigo_barras_pieza, x.ntarima, x.codigo_barras_caja, x.codigo_barras_pallet 
FROM(
    SELECT DISTINCT 
        IF(IFNULL(e.Cuarentena, '') = 0, '<input class=\"column-asignar\" type=\"checkbox\">', '') as acciones, 
        ap.clave AS cve_almacen,
        e.cve_almac AS id_almacen,
        ap.nombre as almacen,
        $field_folio_ot AS folio_OT,
        $field_NCaja AS NCaja,
        z.des_almac as zona,
        u.CodigoCSD as codigo,
        IFNULL(a.cve_codprov, '') as codigo_barras_pieza, 
        IFNULL(a.barras2, '') as codigo_barras_caja, 
        IFNULL(a.barras3, '') as codigo_barras_pallet, 
        e.cve_ubicacion,
        zona.desc_ubicacion AS zona_recepcion,
        IF(IFNULL(e.Cuarentena, 0) = 1, 'Si','No') as QA,
        IF(e.Cve_Contenedor != '', ch.Clave_Contenedor, '') as Tarima,
        IF(e.Cve_Contenedor != '', ch.CveLP, '') as LP_Pallet,
        $field_contenedores
        a.cve_articulo as clave,
        a.des_articulo as descripcion,
        gr.des_gpoart as des_grupo,
        cl.cve_sgpoart as des_clasif,
        IFNULL(a.cajas_palet, 0) as cajasxpallets,
        IFNULL(a.num_multiplo, 0) as piezasxcajas, 
        0 as Pallet,
        0 as Caja, 
        0 as Piezas,
        ta.recurso as referencia_well,
        ta.Pedimento as pedimento_well,
        a.control_lotes as control_lotes,
        a.control_numero_series as control_numero_series,
        IFNULL(e.cve_lote,'') AS lote,
        IFNULL(e.Lote_Alterno,'') AS lote_alterno,
        COALESCE(IF(IFNULL(a.Caduca, 'N') = 'S' AND IFNULL(e.cve_lote,'') != '',IF(DATE_FORMAT(l.Caducidad,'%Y-%m-%d') = '0000-00-00','',DATE_FORMAT(l.Caducidad,'%d-%m-%Y')),'')) AS caducidad,
        COALESCE(IF(a.control_numero_series = 'S',e.cve_lote,'' ), ' ') AS nserie,
        a.peso,
        IF((a.control_peso = 'S' AND um.mav_cveunimed = 'H87') OR a.control_peso = 'S', TRUNCATE(IF(e.Id_Caja = 0, e.Existencia, e.Cantidad_Caja), 4), IF(e.Id_Caja = 0, e.Existencia, e.Cantidad_Caja)) as cantidad,
        TRUNCATE(IF(e.Id_Caja = 0, e.Existencia, e.Cantidad_Caja)*a.peso*(IF(IFNULL(a.num_multiplo, 0) = 0, 1, a.num_multiplo)), 4) AS cantidad_kg,
        IFNULL(p.ID_proveedor, '') AS id_proveedor,
        IFNULL(p.Nombre, '') AS proveedor,
        IFNULL(ch.IDContenedor, '') as ntarima,
        IFNULL(poc.Nombre, '') AS empresa_proveedor,
        CASE 
            WHEN u.Picking = 'S' THEN 'Si'
            WHEN u.Picking = 'N' THEN 'No'
        END AS tipo_ubicacion,
        a.control_abc,
        z.clasif_abc,
        IFNULL(um.cve_umed, '') as um,
        a.control_peso
    FROM $tabla_from e
    INNER JOIN c_articulo a ON a.cve_articulo = e.cve_articulo
    LEFT JOIN c_unimed um ON a.unidadMedida = um.id_umed
    LEFT JOIN c_gpoarticulo gr ON gr.cve_gpoart = a.grupo
    LEFT JOIN c_sgpoarticulo cl ON cl.cve_sgpoart = a.clasificacion
    LEFT JOIN c_ubicacion u ON u.idy_ubica = e.cve_ubicacion
    LEFT JOIN c_lotes l ON l.LOTE = e.cve_lote AND l.cve_articulo = e.cve_articulo
    LEFT JOIN c_almacen z ON z.cve_almac = u.cve_almac
    LEFT JOIN c_charolas ch ON IFNULL(ch.clave_contenedor, '') = IFNULL(e.Cve_Contenedor, '') AND IFNULL(ch.clave_contenedor, '') != ''
    LEFT JOIN tubicacionesretencion zona ON CONVERT(zona.cve_ubicacion, CHAR) = CONVERT(e.cve_ubicacion, CHAR) $sqlCollation
    LEFT JOIN c_almacenp ap ON ap.id = IFNULL(e.cve_almac, z.cve_almacenp)
    $sql_existencia_cajas
    $SQL_FolioOT
    LEFT JOIN rel_articulo_proveedor rap ON rap.Cve_Articulo = e.cve_articulo AND e.ID_Proveedor = rap.Id_Proveedor AND rap.Id_Proveedor != 0
    LEFT JOIN c_proveedores p ON p.ID_Proveedor = e.ID_Proveedor AND p.ID_Proveedor = IFNULL(rap.Id_Proveedor, e.ID_Proveedor) 
    LEFT JOIN td_entalmacen td ON td.cve_articulo = e.cve_articulo AND td.cve_lote = e.cve_lote 
    LEFT JOIN th_entalmacen th ON th.Cve_Proveedor = e.ID_Proveedor AND th.Fol_folio = td.fol_folio 
    LEFT JOIN th_aduana ta ON ta.num_pedimento = th.id_ocompra 
    LEFT JOIN c_proveedores poc ON poc.ID_Proveedor = ta.ID_Proveedor 
    LEFT JOIN t_trazabilidad_existencias tr ON ...
    $sqlCliente
    WHERE $sqlAlmacen $tipo_prod e.Existencia > 0 $sqlArticulo $sqlContenedor $sqlZona $sqlFactura $sqlProyecto 
    $sqlProveedor $sqlGrupo $sqlClasif $sql_obsoletos $SQLrefWell $SQLpedimentoW $sqlPicking
    $zona_rts $sqlIncluirProduccion 
    $sqlbl $sqlLP $sqlLotes $sqlLotes_alt $sqlproveedor_tipo 
    $sql_cajas_existentes 
    $group_by_existencias
    ORDER BY descripcion, folio_OT, (NCaja+0) ASC
)x
WHERE 1 AND x.id_almacen = '$almacen'
$sqlbl $sqlLP $sqlLotes $sqlproveedor_tipo $sqlProveedor2
GROUP BY cve_almacen, cve_ubicacion, contenedor, clave, lote, nserie
```

---

## 4. Filtros Dinámicos y Lógica de Negocio

### En `existenciaubica.php`
| Variable PHP | Condición SQL | Descripción |
| :--- | :--- | :--- |
| `$sqlZona` | `AND e.cve_ubicacion IN (...)` | Filtra por zona. Lógica especial para "RTS" y "RTM". |
| `$sqlArticulo` | `AND e.cve_articulo = '$articulo'` | Filtro exacto de artículo. |
| `$sqlbl` | `AND u.CodigoCSD like '%$bl%'` | Búsqueda parcial de ubicación. |
| `$sqlLP` | `AND ch.CveLP like '%$lp%'` | Búsqueda parcial de License Plate. |
| `$sql_obsoletos` | `AND l.Caducidad < CURDATE()` | Filtra caducados. |
| `$sqlIncluirProduccion` | `AND u.AreaProduccion = 'S'` | Switch para incluir producción. |

### En `lists/index.php`
| Variable | Condición | Descripción |
| :--- | :--- | :--- |
| `$almacen` (Padre) | `u.cve_almac IN (SELECT ...)` | Filtra todo el almacén principal. |
| `$almacenaje` (Zona) | `u.cve_almac = '$almacenaje'` | Filtra una zona específica. |
| `$tipo` | `AND u.Tipo='$tipo'` | Filtra Libre, Restringida, Cuarentena. |
| `$split` | `AND u.picking = 'S'` | Filtros especiales como Picking, PTL, Mixto. |

---

## 5. Áreas de Mejora y Sugerencias

### A. Seguridad y Rendimiento
1.  **Inyección SQL**: Implementar `Prepared Statements` urgentemente. Actualmente se concatenan variables directas (`$almacen`, `$search`).
2.  **Transacciones**: Envolver `actualizar_existencias` en una transacción de BD para evitar inconsistencias si falla un paso intermedio.
3.  **Optimización**: Las vistas `V_Existencia...` son costosas. Evaluar índices en `c_ubicacion(CodigoCSD)` y `c_charolas(CveLP)`.

### B. Sugerencia de Funcionalidad: Búsqueda por Artículo
Se sugiere agregar la capacidad de buscar ubicaciones que contengan un artículo específico directamente desde el listado principal.

**Implementación Propuesta:**
1.  **Frontend (`lists.php`)**: Agregar un input "Buscar Artículo" que envíe el parámetro `articulo` a la función `buscar()`.
2.  **Backend (`api/ajustesexistencias/lista/index.php`)**:
    *   En la acción `loadGrid`, recibir `$_POST['articulo']`.
    *   Modificar la query principal agregando:
        ```sql
        AND ve.cve_articulo = '$articulo_buscado'
        ```
    *   Esto filtrará el grid para mostrar solo las ubicaciones donde existe ese SKU, facilitando la localización de inventario disperso.
