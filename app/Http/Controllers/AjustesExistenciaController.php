<?php

namespace AssistPro\Http\Controllers;

use AssistPro\Helpers\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;

class AjustesExistenciaController
{
    /**
     * Listado principal (Grid)
     * Equivalente a: api/ajustesexistencias/lista/index.php (Action: loadGrid)
     */
    public function index(Request $request)
    {
        try {
            $almacen = $request->input('almacen'); // Almacén Padre
            $almacenaje = $request->input('almacenaje'); // Zona
            $search = $request->input('search');
            $tipo = $request->input('tipo');
            $articulo = $request->input('articulo'); // Nuevo filtro sugerido

            $limit = $request->input('limit', 100);
            $offset = $request->input('offset', 0);

            $query = DB::table('c_ubicacion as u')
                ->select([
                    'u.idy_ubica',
                    'u.PesoMaximo as PesoMax',
                    'a.des_almac as zona_almacenaje',
                    'u.cve_pasillo as pasillo',
                    'u.cve_rack as rack',
                    'u.cve_nivel as nivel',
                    'u.Seccion as seccion',
                    'u.Ubicacion as posicion',
                    'u.CodigoCSD as BL',
                    DB::raw("GROUP_CONCAT(DISTINCT ch.clave_contenedor SEPARATOR ',') as clave_contenedor"),
                    DB::raw("GROUP_CONCAT(DISTINCT ch.CveLP SEPARATOR ',') as CveLP"),
                    'u.num_alto',
                    'u.num_ancho',
                    'u.num_largo',
                    'u.AcomodoMixto',
                    'u.AreaProduccion',
                    'u.picking',
                    'u.TECNOLOGIA',
                    DB::raw("(CASE
                        WHEN u.Tipo = 'L' THEN 'Libre'
                        WHEN u.Tipo = 'R' THEN 'Restringida'
                        WHEN u.Tipo = 'Q' THEN 'Cuarentena'
                        ELSE '--'
                    END) as tipo_ubicacion"),
                    DB::raw("IFNULL(TRUNCATE((u.num_ancho / 1000) * (u.num_alto / 1000) * (u.num_largo / 1000), 2), 0) as volumen_m3"),
                    DB::raw("IFNULL(u.orden_secuencia, '--') as surtido"),
                    DB::raw("if(u.TECNOLOGIA='PTL','S','N') as Ptl"),
                    DB::raw("if(u.Tipo='L','S','N') as li"),
                    DB::raw("if(u.Tipo='R','S','N') as re"),
                    DB::raw("if(u.Tipo='Q','S','N') as cu")
                ])
                ->leftJoin('c_almacen as a', 'a.cve_almac', '=', 'u.cve_almac')
                ->leftJoin('V_ExistenciaGralProduccion as ve', function ($join) {
                    $join->on('ve.cve_ubicacion', '=', 'u.idy_ubica')
                        ->on('ve.cve_almac', '=', 'a.cve_almacenp');
                })
                ->leftJoin('c_charolas as ch', 'ch.clave_contenedor', '=', 've.Cve_Contenedor')
                ->where('ve.Existencia', '>', 0);

            // Filtros Dinámicos
            if ($almacen) {
                $query->where('ve.cve_almac', $almacen);
            }

            if ($almacenaje) {
                $query->where('u.cve_almac', $almacenaje);
            }

            if ($search) {
                $query->where('u.CodigoCSD', 'like', "%{$search}%");
            }

            if ($tipo) {
                $query->where('u.Tipo', $tipo);
            }

            if ($articulo) {
                $query->where('ve.cve_articulo', $articulo);
            }

            $query->groupBy('u.CodigoCSD');

            // Calcular página actual basada en offset y limit
            $page = ($limit > 0) ? floor($offset / $limit) + 1 : 1;

            $paginator = $query->paginate($limit, ['*'], 'page', $page);

            return ApiResponse::paginated($paginator);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al cargar grid', $e->getMessage());
        }
    }

    /**
     * Detalle de ubicación
     * Equivalente a: api/ajustesexistencias/lista/index.php (Action: loadDetails)
     */
    public function show(Request $request)
    {
        try {
            $ubicacion = $request->input('ubicacion');
            $almacen = $request->input('almacen');
            $areaProduccion = $request->input('areaProduccion');

            if (!$ubicacion || !$almacen) {
                return ApiResponse::error('Ubicación y Almacén requeridos', null, 400);
            }

            $table = ($areaProduccion === 'S') ? 'V_ExistenciaGralProduccion' : 'V_ExistenciaGral';

            $query = DB::table($table . ' as v')
                ->select([
                    DB::raw("IFNULL(c_proveedores.ID_Proveedor, '') as id_proveedor"),
                    'v.cve_almac',
                    'v.cve_ubicacion',
                    'v.cve_articulo',
                    DB::raw("IFNULL(v.cve_lote, '') as lote"), // Ajuste para V_ExistenciaGral que usa cve_lote o null
                    DB::raw("ifnull(if(c_articulo.control_lotes = 'S', date_format(if(DATE_FORMAT(c_lotes.Caducidad, '%Y-%m-%d')='0000-00-00','',c_lotes.Caducidad),'%d-%m-%Y'),''),'') as caducidad"),
                    DB::raw("if(c_articulo.control_numero_series = 'S',v.cve_lote,'') as serie"),
                    DB::raw("ROUND(SUM(v.Existencia)/COUNT(v.Existencia), 2) as Existencia_Total"),
                    'c_articulo.des_articulo as descripcion',
                    'v.Cve_Contenedor as contenedor',
                    DB::raw("IFNULL(c_proveedores.Nombre, '') as proveedor"),
                    DB::raw("IF(c_articulo.control_peso = 'S',ROUND(SUM(c_articulo.peso), 2),TRUNCATE(IFNULL(ROUND(c_articulo.peso, 2),0),2)) AS peso_unitario"),
                    DB::raw("TRUNCATE(((c_articulo.alto*c_articulo.ancho*c_articulo.fondo)/1000000000),2) as volumen_unitario"),
                    DB::raw("IF (c_articulo.control_peso = 'S',TRUNCATE((IFNULL(c_articulo.peso,0)*SUM(v.Existencia)),2),TRUNCATE((IFNULL(c_articulo.peso,0)*SUM(v.Existencia)),2)) AS peso_total"),
                    DB::raw("TRUNCATE((((c_articulo.alto*c_articulo.ancho*c_articulo.fondo)/1000000000)*SUM(v.Existencia)),2)as volumen_total")
                ])
                ->leftJoin('c_articulo', 'c_articulo.cve_articulo', '=', 'v.cve_articulo')
                ->leftJoin('c_lotes', function ($join) {
                    $join->on('c_lotes.LOTE', '=', 'v.cve_lote')
                        ->on('c_lotes.cve_articulo', '=', 'c_articulo.cve_articulo');
                })
                ->leftJoin('c_proveedores', 'c_proveedores.ID_Proveedor', '=', 'v.Id_Proveedor');

            if ($areaProduccion !== 'S') {
                $query->leftJoin('c_serie', function ($join) {
                    $join->on('c_serie.numero_serie', '=', 'v.cve_lote')
                        ->on('c_serie.cve_articulo', '=', 'c_articulo.cve_articulo');
                });
            }

            $query->where('v.cve_ubicacion', $ubicacion)
                ->where('v.cve_almac', $almacen)
                ->whereNotNull('v.Existencia');

            // Condición extra de producción
            if ($areaProduccion === 'S') {
                $query->whereRaw("v.cve_articulo = c_articulo.cve_articulo AND IFNULL(v.cve_lote, '') = IFNULL(c_lotes.Lote, '')");
            }

            $query->groupBy('v.cve_articulo', 'v.cve_lote', 'v.Cve_Contenedor', 'id_proveedor')
                ->orderBy('descripcion');

            $items = $query->get();

            return ApiResponse::success($items);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al cargar detalles', $e->getMessage());
        }
    }

    /**
     * Actualizar existencias
     * Equivalente a: api/ajustesexistencias/lista/index.php (Action: actualizar_existencias)
     */
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $existencia = $request->input('existencia');
            $id_almacen = $request->input('id_almacen');
            $id_ubica = $request->input('id_ubica');
            $clave = $request->input('clave'); // cve_articulo
            $lote = $request->input('lote');
            $id_proveedor = $request->input('id_proveedor');
            $contenedor = $request->input('contenedor');

            // Datos adicionales para Kardex
            $cve_usuario = $request->input('cve_usuario', 'SYSTEM'); // Debería venir de sesión
            $motivos = $request->input('motivos');
            $existencia_actual = $request->input('existencia_actual');
            $nueva_existencia = $existencia; // Alias
            $folio = $request->input('folio', uniqid('AJ')); // Generar folio si no viene
            $costo_promedio = 0; // Asumido 0 si no se calcula

            // 1. Actualizar Existencia Física (Tarima)
            // Subquery para ntarima
            $ntarima = DB::table('c_charolas')
                ->where('clave_contenedor', $contenedor)
                ->value('IDContenedor');

            if ($ntarima) {
                DB::table('ts_existenciatarima')
                    ->where('cve_almac', $id_almacen)
                    ->where('idy_ubica', $id_ubica)
                    ->where('cve_articulo', $clave)
                    ->where('lote', $lote)
                    ->where('ID_Proveedor', $id_proveedor)
                    ->where('ntarima', $ntarima)
                    ->update(['existencia' => $existencia]);
            }

            // 2. Actualizar Existencia Física (Cajas)
            DB::table('ts_existenciacajas')
                ->where('cve_almac', $id_almacen)
                ->where('idy_ubica', $id_ubica)
                ->where('cve_articulo', $clave)
                ->where('cve_lote', $lote)
                ->update(['Existencia' => $existencia]);

            // 3. Actualizar Existencia Física (Piezas)
            DB::table('ts_existenciapiezas')
                ->where('cve_almac', $id_almacen)
                ->where('idy_ubica', $id_ubica)
                ->where('cve_articulo', $clave)
                ->where('cve_lote', $lote)
                ->where('ID_Proveedor', $id_proveedor)
                ->update(['Existencia' => $existencia]);

            // 4. Registrar Kardex
            $cantidad_diff = abs($existencia_actual - $nueva_existencia);
            $tipo_mov = ($existencia_actual > $nueva_existencia) ? 10 : 9; // 10: Salida (Ajuste -), 9: Entrada (Ajuste +)

            DB::table('t_cardex')->insert([
                'cve_articulo' => $clave,
                'cve_lote' => $lote,
                'fecha' => DB::raw('now()'),
                'origen' => $id_ubica,
                'destino' => $id_ubica,
                'cantidad' => $cantidad_diff,
                'id_TipoMovimiento' => $tipo_mov,
                'cve_usuario' => $cve_usuario,
                'Cve_Almac' => $id_almacen,
                'Activo' => '1',
                'Fec_Ingreso' => DB::raw('now()'),
                'Id_Motivo' => $motivos
            ]);

            // 5. Registrar Histórico (Cabecera)
            // Verificar si ya existe el folio para no duplicar cabecera en ajustes masivos
            if (!DB::table('th_ajusteexist')->where('Folio', $folio)->exists()) {
                DB::table('th_ajusteexist')->insert([
                    'Folio' => $folio,
                    'Cve_Almac' => $id_almacen,
                    'Fecha' => DB::raw('now()'),
                    'Id_Usuario' => $cve_usuario,
                    'Observaciones' => '',
                    'Estatus' => '1'
                ]);
            }

            // 6. Registrar Histórico (Detalle)
            // Usamos updateOrInsert o raw query para ON DUPLICATE KEY UPDATE
            // Eloquent updateOrInsert no soporta ON DUPLICATE KEY UPDATE con lógica de suma/reemplazo custom facilmente
            // Usaremos DB::statement para ser fieles al original

            $sql = "INSERT INTO td_ajusteexist (Folio, Cve_Almac, Id_Ubicacion, Cve_Articulo, Lote, Cant_Sistema, Cant_Fisica, Costo_Promedio, Id_Motivo, Tipo, Cve_Contenedor) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'A', ?) 
                    ON DUPLICATE KEY UPDATE num_cantant=?";

            DB::statement($sql, [
                $folio,
                $id_almacen,
                $id_ubica,
                $clave,
                $lote,
                $existencia_actual,
                $nueva_existencia,
                $costo_promedio,
                $motivos,
                $contenedor,
                $existencia // num_cantant update value
            ]);

            // 7. Limpieza
            DB::table('ts_existenciapiezas')->where('existencia', '<=', 0)->delete();
            DB::table('ts_existenciatarima')->where('existencia', '<=', 0)->delete();

            // 8. Desactivar Contenedor si está vacío
            if ($ntarima) {
                $remainingItems = DB::table('ts_existenciatarima')
                    ->where('ntarima', $ntarima)
                    ->count();

                if ($remainingItems == 0) {
                    DB::table('c_charolas')
                        ->where('IDContenedor', $ntarima)
                        ->update(['Activo' => '0']);
                }
            }

            DB::commit();
            return ApiResponse::success(null, 'Ajuste realizado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Error al realizar ajuste', $e->getMessage());
        }
    }
    /**
     * Obtener KPIs
     */
    public function kpis(Request $request)
    {
        try {
            $almacen = $request->input('almacen');
            $almacenaje = $request->input('almacenaje');
            $tipo = $request->input('tipo');

            // Query base para Ubicaciones
            $query = DB::table('c_ubicacion as u')
                ->leftJoin('c_almacen as a', 'a.cve_almac', '=', 'u.cve_almac');

            if ($almacen) {
                // Filtrar por almacén padre (a.cve_almacenp = $almacen)
                $query->where('a.cve_almacenp', $almacen);
            }

            if ($almacenaje) {
                $query->where('u.cve_almac', $almacenaje);
            }

            if ($tipo) {
                $query->where('u.Tipo', $tipo);
            }

            // Total Ubicaciones
            $totalUbicaciones = $query->count();

            // Ubicaciones Ocupadas (Existencia > 0)
            // Necesitamos unir con V_ExistenciaGralProduccion para saber si tiene stock
            // Pero ojo, V_ExistenciaGralProduccion puede tener múltiples registros por ubicación (varios productos)
            // Así que contamos distinct idy_ubica
            $ocupadasQuery = clone $query;
            $ocupadas = $ocupadasQuery
                ->join('V_ExistenciaGralProduccion as ve', function ($join) {
                    $join->on('ve.cve_ubicacion', '=', 'u.idy_ubica')
                        ->on('ve.cve_almac', '=', 'a.cve_almacenp');
                })
                ->where('ve.Existencia', '>', 0)
                ->distinct('u.idy_ubica')
                ->count('u.idy_ubica');

            $vacias = $totalUbicaciones - $ocupadas;
            $porcentajeOcupacion = ($totalUbicaciones > 0) ? round(($ocupadas / $totalUbicaciones) * 100, 2) : 0;

            return ApiResponse::success([
                'total_ubicaciones' => $totalUbicaciones,
                'ocupadas' => $ocupadas,
                'vacias' => $vacias,
                'porcentaje_ocupacion' => $porcentajeOcupacion
            ]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al calcular KPIs', $e->getMessage());
        }
    }
}
