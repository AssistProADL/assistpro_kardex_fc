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
            $estado = $request->input('estado');
            $activo = $request->input('activo');
            $articulo = $request->input('articulo');
            $limit = $request->input('limit', 100);
            $offset = $request->input('offset', 0);
            $order = $request->input('order', 'ASC');
            $query = DB::table('c_ubicacion as u')
                ->leftJoin('c_almacen as a', 'a.cve_almac', '=', 'u.cve_almac')
                ->leftJoin('c_almacenp as p', 'a.cve_almacenp', '=', 'p.id')
                ->leftJoin('V_ExistenciaGralProduccion as ve', function ($join) {
                    $join->on('ve.cve_ubicacion', '=', 'u.idy_ubica')
                        ->on('ve.cve_almac', '=', 'a.cve_almacenp');
                })
                ->leftJoin('c_articulo as art', 'art.cve_articulo', '=', 've.cve_articulo')
                ->leftJoin('c_charolas as ch', 'ch.clave_contenedor', '=', 've.Cve_Contenedor')
                ->select([
                    'u.idy_ubica',
                    'u.cve_almac', // Added for details
                    'a.cve_almacenp', // Parent Warehouse ID for details
                    'u.PesoMaximo as PesoMax',
                    'a.des_almac as zona_almacenaje',
                    DB::raw("CONCAT(COALESCE(p.nombre, 'N/A'), ' / ', a.des_almac) as almacen_zona"),
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
                    DB::raw("(CASE WHEN SUM(IFNULL(ve.Existencia, 0)) > 0 THEN 'Ocupado' ELSE 'Vacío' END) as estado_ubicacion"),
                    DB::raw("SUM(ve.Existencia * IFNULL(art.peso, 0)) as peso_ocupado"),
                    DB::raw("SUM(ve.Existencia * ((art.alto * art.ancho * art.fondo)/1000000000)) as volumen_ocupado"),
                    'u.Activo',
                    DB::raw("IFNULL(TRUNCATE((u.num_ancho / 1000) * (u.num_alto / 1000) * (u.num_largo / 1000), 2), 0) as volumen_m3"),
                    DB::raw("IFNULL(u.orden_secuencia, '--') as surtido"),
                    DB::raw("if(u.TECNOLOGIA='PTL','S','N') as Ptl"),
                    DB::raw("if(u.Tipo='L','S','N') as li"),
                    DB::raw("if(u.Tipo='R','S','N') as re"),
                    DB::raw("if(u.Tipo='Q','S','N') as cu")
                ]);

            // Filtros Dinámicos
            if ($almacen) {
                $query->where('p.id', $almacen);
            }

            if ($almacenaje) {
                $query->where('u.cve_almac', $almacenaje);
            }

            // DataTables envía search como array, extraer el valor
            if ($search) {
                $searchValue = is_array($search) ? ($search['value'] ?? '') : $search;
                if ($searchValue) {
                    $query->where('u.CodigoCSD', 'like', "%{$searchValue}%");
                }
            }

            if ($tipo) {
                $query->where('u.Tipo', $tipo);
            }

            if ($articulo) {
                $query->where('ve.cve_articulo', $articulo);
            }

            // Group by ubicación completa
            $query->groupBy([
                'u.idy_ubica',
                'u.cve_almac',
                'a.cve_almacenp',
                'u.PesoMaximo',
                'a.des_almac',
                'p.nombre',
                'u.cve_pasillo',
                'u.cve_rack',
                'u.cve_nivel',
                'u.Seccion',
                'u.Ubicacion',
                'u.CodigoCSD',
                'u.num_alto',
                'u.num_ancho',
                'u.num_largo',
                'u.AcomodoMixto',
                'u.AreaProduccion',
                'u.picking',
                'u.TECNOLOGIA',
                'u.Tipo',
                'u.Activo',
                'u.orden_secuencia'
            ]);

            // Aplicar filtro de estado DESPUÉS del GROUP BY usando HAVING
            if ($estado) {
                if ($estado === 'ocupado') {
                    $query->havingRaw('SUM(IFNULL(ve.Existencia, 0)) > 0');
                } elseif ($estado === 'vacio') {
                    $query->havingRaw('SUM(IFNULL(ve.Existencia, 0)) = 0');
                }
            }

            // Calcular página actual basada en offset y limit
            $page = ($limit > 0) ? floor($offset / $limit) + 1 : 1;

            $paginator = $query->paginate($limit, ['*'], 'page', $page);

            // Formato compatible con DataTables
            return new \Illuminate\Http\JsonResponse([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => $paginator->total(),
                'recordsFiltered' => $paginator->total(),
                'data' => $paginator->items()
            ]);

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

            if (!$ubicacion) {
                return ApiResponse::error('Ubicación requerida', null, 400);
            }

            // Si no viene almacén, obtenerlo de la ubicación
            if (!$almacen) {
                $ubicacionData = DB::table('c_ubicacion')
                    ->where('idy_ubica', $ubicacion)
                    ->select('cve_almac')
                    ->first();

                if ($ubicacionData) {
                    $almacen = $ubicacionData->cve_almac;
                } else {
                    return ApiResponse::error('Ubicación no encontrada', null, 404);
                }
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

            // Condiciones Legacy eliminadas para coincidir con la consulta del usuario
            // $query->whereRaw("v.cve_articulo = c_articulo.cve_articulo");
            // $query->whereRaw("IFNULL(v.cve_lote, '') = IFNULL(c_lotes.Lote, '')");

            $query->groupBy('v.cve_articulo', 'v.cve_lote', 'v.Cve_Contenedor', 'id_proveedor')
                ->orderBy('descripcion');

            // Clonar para totales
            $queryTotales = clone $query;
            $itemsAll = $queryTotales->get();

            $pesoTotal = 0;
            $volumenTotal = 0;
            foreach ($itemsAll as $item) {
                $pesoTotal += floatval($item->peso_total ?? 0);
                $volumenTotal += floatval($item->volumen_total ?? 0);
            }

            // Paginación
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);
            $page = ($limit > 0) ? ($start / $limit) + 1 : 1;

            $paginator = $query->paginate($limit, ['*'], 'page', $page);

            // Codificar caracteres especiales en el nombre del proveedor
            $items = $paginator->items();
            foreach ($items as $item) {
                if (isset($item->proveedor)) {
                    $item->proveedor = mb_convert_encoding($item->proveedor, 'UTF-8', 'ISO-8859-1');
                }
            }

            // Obtener datos de ubicación para el header
            $ubicacionInfo = DB::table('c_ubicacion')
                ->where('idy_ubica', $ubicacion)
                ->select(
                    'PesoMaximo',
                    'num_volumenDisp as VolumenMaximo',
                    'CodigoCSD'
                )
                ->first();

            return new \Illuminate\Http\JsonResponse([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $paginator->total(),
                'recordsFiltered' => $paginator->total(),
                'data' => $items,
                'extra_data' => [
                    'peso_total' => $pesoTotal,
                    'volumen_total' => $volumenTotal,
                    'ubicacion' => $ubicacionInfo
                ]
            ]);

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
            $items = $request->input('items', []);
            $motivos = $request->input('motivos');
            $cve_usuario = $request->input('cve_usuario', 'SYSTEM'); // Debería venir de sesión
            $folio = $request->input('folio', uniqid('AJ')); // Generar folio único para el lote de ajustes

            // Validar que haya items
            if (empty($items)) {
                return ApiResponse::error('No se recibieron items para ajustar', null, 400);
            }

            // Registrar Histórico (Cabecera) una sola vez por lote
            if (!DB::table('th_ajusteexist')->where('fol_folio', $folio)->exists()) {
                // Obtener almacén del primer item (asumiendo todos son del mismo almacén por ahora, o validar)
                $firstItem = $items[0];
                $id_almacen_header = $firstItem['id_almacen'] ?? null;

                DB::table('th_ajusteexist')->insert([
                    'fol_folio' => $folio,
                    'cve_almac' => $id_almacen_header,
                    'fec_ajuste' => DB::raw('now()'),
                    'cve_usuario' => $cve_usuario,
                    'des_observ' => 'Ajuste masivo desde web',
                    'Activo' => 1
                ]);
            }

            foreach ($items as $item) {
                $existencia = $item['existencia'];

                // Validar que la existencia no sea negativa
                if ($existencia < 0) {
                    throw new \Exception("La existencia no puede ser negativa para el artículo {$item['clave']}");
                }

                $id_almacen = $item['id_almacen'];
                $id_ubica = $item['id_ubica'];
                $clave = $item['clave']; // cve_articulo
                $lote = $item['lote'];
                $id_proveedor = $item['id_proveedor'];
                $contenedor = $item['contenedor'];
                $existencia_actual = $item['existencia_actual'];

                $nueva_existencia = $existencia;
                $costo_promedio = 0;

                // 1. Actualizar Existencia Física (Tarima)
                $ntarima = DB::table('c_charolas')
                    ->where('Clave_Contenedor', $contenedor)
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
                // DB::table('ts_existenciacajas')
                //     ->where('cve_almac', $id_almacen)
                //     ->where('idy_ubica', $id_ubica)
                //     ->where('cve_articulo', $clave)
                //     ->where('cve_lote', $lote)
                //     ->update(['Existencia' => $existencia]);

                // // 3. Actualizar Existencia Física (Piezas)
                // DB::table('ts_existenciapiezas')
                //     ->where('cve_almac', $id_almacen)
                //     ->where('idy_ubica', $id_ubica)
                //     ->where('cve_articulo', $clave)
                //     ->where('cve_lote', $lote)
                //     ->where('ID_Proveedor', $id_proveedor)
                //     ->update(['Existencia' => $existencia]);

                // 4. Registrar Kardex
                $cantidad_diff = abs($existencia_actual - $nueva_existencia);
                $tipo_mov = ($existencia_actual > $nueva_existencia) ? 10 : 9; // 10: Salida, 9: Entrada

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

                // 6. Registrar Histórico (Detalle)
                // 6. Registrar Histórico (Detalle)
                $sql = "INSERT INTO td_ajusteexist (fol_folio, cve_almac, Idy_ubica, cve_articulo, cve_lote, num_cantant, num_cantnva, imp_cosprom, Id_Motivo, Tipo_Cat, ntarima) 
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
                    $ntarima,
                    $existencia
                ]);
            }

            // 7. Limpieza General
            DB::table('ts_existenciapiezas')->where('existencia', '<=', 0)->delete();
            DB::table('ts_existenciatarima')->where('existencia', '<=', 0)->delete();

            // 8. Desactivar Contenedores vacíos
            // Obtener contenedores únicos afectados
            $contenedoresAfectados = collect($items)->pluck('contenedor')->unique()->filter();

            foreach ($contenedoresAfectados as $contenedor) {
                // Obtener IDContenedor (ntarima)
                $ntarima = DB::table('c_charolas')
                    ->where('clave_contenedor', $contenedor)
                    ->value('IDContenedor');

                if ($ntarima) {
                    // Verificar si quedan items en este contenedor en CUALQUIER tabla de existencia
                    // Usamos V_ExistenciaGral para una verificación completa
                    $remainingItems = DB::table('V_ExistenciaGral')
                        ->where('Cve_Contenedor', $contenedor)
                        ->where('Existencia', '>', 0)
                        ->count();

                    if ($remainingItems == 0) {
                        // Si no quedan items, desactivar la charola/contenedor
                        DB::table('c_charolas')
                            ->where('IDContenedor', $ntarima)
                            ->update(['Activo' => '0']);
                    }
                }
            }

            DB::commit();
            return ApiResponse::success(['folio' => $folio], 'Existencias actualizadas correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Error al actualizar existencias', $e->getMessage());
        }
    }

    /**
     * Buscar artículos con existencia
     * Equivalente a carga diferida para select2
     */
    public function searchArticulos(Request $request)
    {
        try {
            $search = $request->input('q');
            $almacen = $request->input('almacen');
            $almacenaje = $request->input('almacenaje');
            $tipo = $request->input('tipo');
            $page = $request->input('page', 1);
            $limit = 20;

            $query = DB::table('V_ExistenciaGral as ve')
                ->join('c_ubicacion as u', 'u.idy_ubica', '=', 've.cve_ubicacion')
                ->join('c_articulo as a', 'a.cve_articulo', '=', 've.cve_articulo')
                ->select('ve.cve_articulo as id', DB::raw("CONCAT(ve.cve_articulo, ' - ', a.des_articulo) as text"))
                ->where('ve.Existencia', '>', 0)
                ->distinct();

            if ($almacen) {
                $query->where('p.id', $almacen);
            }

            if ($almacenaje) {
                $query->where('u.cve_almac', $almacenaje);
            }

            if ($tipo) {
                $query->where('u.Tipo', $tipo);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ve.cve_articulo', 'like', "%{$search}%")
                        ->orWhere('a.des_articulo', 'like', "%{$search}%");
                });
            }

            $paginator = $query->paginate($limit, ['*'], 'page', $page);

            return new \Illuminate\Http\JsonResponse([
                'results' => $paginator->items(),
                'pagination' => [
                    'more' => $paginator->hasMorePages()
                ]
            ]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al buscar artículos', $e->getMessage());
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
            $estado = $request->input('estado');
            $activo = $request->input('activo');

            // Query base para contar ubicaciones
            $baseQuery = DB::table('c_ubicacion as u')
                ->leftJoin('c_almacen as a', 'a.cve_almac', '=', 'u.cve_almac')
                ->leftJoin('c_almacenp as p', 'a.cve_almacenp', '=', 'p.id')
                ->leftJoin('V_ExistenciaGralProduccion as ve', function ($join) {
                    $join->on('ve.cve_ubicacion', '=', 'u.idy_ubica')
                        ->on('ve.cve_almac', '=', 'a.cve_almacenp');
                })
                ->leftJoin('c_articulo as art', 'art.cve_articulo', '=', 've.cve_articulo')
                ->leftJoin('c_charolas as ch', 'ch.clave_contenedor', '=', 've.Cve_Contenedor')
                ->select('u.idy_ubica'); // Solo seleccionar el ID único

            // Aplicar filtros
            if ($almacen) {
                $baseQuery->where('p.id', $almacen);
            }

            if ($almacenaje) {
                $baseQuery->where('u.cve_almac', $almacenaje);
            }

            if ($tipo) {
                $baseQuery->where('u.Tipo', $tipo);
            }

            if ($activo !== null && $activo !== '') {
                $baseQuery->where('u.Activo', $activo);
            }

            // Agrupar por ubicación
            $baseQuery->groupBy([
                'u.idy_ubica',
                'u.cve_almac',
                'a.cve_almacenp',
                'u.PesoMaximo',
                'a.des_almac',
                'p.nombre',
                'u.cve_pasillo',
                'u.cve_rack',
                'u.cve_nivel',
                'u.Seccion',
                'u.Ubicacion',
                'u.CodigoCSD',
                'u.num_alto',
                'u.num_ancho',
                'u.num_largo',
                'u.AcomodoMixto',
                'u.AreaProduccion',
                'u.picking',
                'u.TECNOLOGIA',
                'u.Tipo',
                'u.Activo',
                'u.orden_secuencia'
            ]);

            // Clonar queries ANTES de aplicar filtros de estado
            $queryTotal = clone $baseQuery;
            $queryOcupadas = clone $baseQuery;

            // Total: aplicar filtro de estado si existe
            if ($estado) {
                if ($estado === 'ocupado') {
                    $queryTotal->havingRaw('SUM(IFNULL(ve.Existencia, 0)) > 0');
                } elseif ($estado === 'vacio') {
                    $queryTotal->havingRaw('SUM(IFNULL(ve.Existencia, 0)) = 0');
                }
            }

            // Ocupadas: siempre con existencia > 0
            $queryOcupadas->havingRaw('SUM(IFNULL(ve.Existencia, 0)) > 0');

            // Contar usando subqueries para contar correctamente después del GROUP BY
            $totalUbicaciones = DB::table(DB::raw("({$queryTotal->toSql()}) as sub"))
                ->mergeBindings($queryTotal)
                ->count();

            $ocupadas = DB::table(DB::raw("({$queryOcupadas->toSql()}) as sub"))
                ->mergeBindings($queryOcupadas)
                ->count();

            // Calcular vacías
            $vacias = $totalUbicaciones - $ocupadas;
            if ($vacias < 0)
                $vacias = 0;

            // Calcular porcentaje
            $porcentajeOcupacion = 0;
            if ($totalUbicaciones > 0) {
                $porcentajeOcupacion = round(($ocupadas / $totalUbicaciones) * 100, 2);
            }

            return ApiResponse::success([
                'total_ubicaciones' => $totalUbicaciones,
                'ocupadas' => $ocupadas,
                'vacias' => $vacias,
                'porcentaje_ocupacion' => $porcentajeOcupacion
            ]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al obtener KPIs', $e->getMessage());
        }
    }

}
