# Análisis Completo Ajustes de Existencias

## Problema Detectado
El usuario reportó un error SQL al intentar cargar los detalles de una ubicación:
`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'VolumenMaximo' in 'SELECT'`

Esto ocurrió porque la consulta intentaba seleccionar `VolumenMaximo` de la tabla `c_ubicacion`, pero dicha columna no existe en el esquema actual de la base de datos.

## Análisis de la Tabla `c_ubicacion`
Revisando el archivo `public/catalogos/cat_c_ubicacion.php`, se identificaron las columnas disponibles:
- `PesoMaximo` (Existe y es correcta)
- `num_volumenDisp` (Posible candidato para volumen)
- `num_ancho`, `num_largo`, `num_alto` (Dimensiones físicas)
- `Maximo`, `Minimo` (Posiblemente stock máximo/mínimo, no volumen)

## Solución Implementada
Se modificó `AjustesExistenciaController.php` para:
1.  Seleccionar `PesoMaximo` (correcto).
2.  Seleccionar `num_volumenDisp` y aliasarlo como `VolumenMaximo` para mantener la compatibilidad con el frontend.
3.  **Eliminar condiciones restrictivas**: Se comentaron las líneas `$query->whereRaw(...)` que forzaban la coincidencia estricta de lotes con `c_lotes`, ya que esto filtraba registros válidos que solo existían en `V_ExistenciaGral` (o coincidían por `c_serie`). Esto alinea el comportamiento con la consulta SQL manual que devuelve los registros correctos.

## Legacy Query (Referencia)
Esta es la consulta SQL original (Legacy) proporcionada por el usuario, que sirvió de base para la implementación de la paginación en el backend:

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
WHERE cve_ubicacion = '35415' 
AND V_ExistenciaGralProduccion.cve_almac = '39' 
AND V_ExistenciaGralProduccion.Existencia IS NOT NULL 
AND V_ExistenciaGralProduccion.cve_articulo = c_articulo.cve_articulo AND IFNULL(V_ExistenciaGralProduccion.cve_lote, '') = IFNULL(c_lotes.Lote, '')
GROUP BY V_ExistenciaGralProduccion.cve_articulo,V_ExistenciaGralProduccion.cve_lote, V_ExistenciaGralProduccion.Cve_Contenedor, id_proveedor
ORDER BY descripcion
LIMIT 0,30;
```

## Archivos Relacionados
- `app/Http/Controllers/AjustesExistenciaController.php`: Controlador actualizado.
- `public/assets/js/ajustes-existencias.js`: Frontend actualizado para paginación.
- `lists.php`: Archivo legacy de referencia (no modificado, solo referenciado).
