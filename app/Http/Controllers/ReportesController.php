<?php

namespace AssistPro\Http\Controllers;

use AssistPro\Helpers\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;

class ReportesController
{
    /**
     * Reporte de Existencia por Ubicación (Mega Query)
     * Equivalente a: api/reportes/lista/existenciaubica.php
     */
    public function existenciaUbica(Request $request)
    {
        try {
            // Parámetros de paginación
            $limit = $request->input('limit', 100);
            $page = $request->input('page', 1);

            // Filtros
            $almacen = $request->input('almacen');
            $zona = $request->input('zona');
            $articulo = $request->input('articulo');
            $bl = $request->input('bl');
            $lp = $request->input('lp');
            $obsoletos = $request->input('obsoletos');
            $incluirProduccion = $request->input('incluir_produccion');
            $proveedor = $request->input('proveedor');
            $lote = $request->input('lote');

            if (!$almacen) {
                return ApiResponse::error('Almacén requerido', null, 400);
            }

            // Determinar tabla base
            $table = ($incluirProduccion === 'S') ? 'V_ExistenciaGralProduccion' : 'V_ExistenciaGral';

            // Construcción de la Query
            $query = DB::table($table . ' as e')
                ->select([
                    DB::raw("IF(IFNULL(e.Cuarentena, '') = 0, '<input class=\"column-asignar\" type=\"checkbox\">', '') as acciones"),
                    'ap.clave as cve_almacen',
                    'e.cve_almac as id_almacen',
                    'ap.nombre as almacen',
                    'z.des_almac as zona',
                    'u.CodigoCSD as codigo',
                    DB::raw("IFNULL(a.cve_codprov, '') as codigo_barras_pieza"),
                    DB::raw("IFNULL(a.barras2, '') as codigo_barras_caja"),
                    DB::raw("IFNULL(a.barras3, '') as codigo_barras_pallet"),
                    'e.cve_ubicacion',
                    'zona.desc_ubicacion as zona_recepcion',
                    DB::raw("IF(IFNULL(e.Cuarentena, 0) = 1, 'Si','No') as QA"),
                    DB::raw("IF(e.Cve_Contenedor != '', ch.Clave_Contenedor, '') as Tarima"),
                    DB::raw("IF(e.Cve_Contenedor != '', ch.CveLP, '') as LP_Pallet"),
                    'a.cve_articulo as clave',
                    'a.des_articulo as descripcion',
                    'gr.des_gpoart as des_grupo',
                    'cl.cve_sgpoart as des_clasif',
                    DB::raw("IFNULL(a.cajas_palet, 0) as cajasxpallets"),
                    DB::raw("IFNULL(a.num_multiplo, 0) as piezasxcajas"),
                    DB::raw("0 as Pallet"),
                    DB::raw("0 as Caja"),
                    DB::raw("0 as Piezas"),
                    'ta.recurso as referencia_well',
                    'ta.Pedimento as pedimento_well',
                    'a.control_lotes',
                    'a.control_numero_series',
                    DB::raw("IFNULL(e.cve_lote,'') as lote"),
                    DB::raw("IFNULL(e.Lote_Alterno,'') as lote_alterno"),
                    DB::raw("COALESCE(IF(IFNULL(a.Caduca, 'N') = 'S' AND IFNULL(e.cve_lote,'') != '',IF(DATE_FORMAT(l.Caducidad,'%Y-%m-%d') = '0000-00-00','',DATE_FORMAT(l.Caducidad,'%d-%m-%Y')),'')) as caducidad"),
                    DB::raw("COALESCE(IF(a.control_numero_series = 'S',e.cve_lote,'' ), ' ') as nserie"),
                    'a.peso',
                    DB::raw("IF((a.control_peso = 'S' AND um.mav_cveunimed = 'H87') OR a.control_peso = 'S', TRUNCATE(IF(e.Id_Caja = 0, e.Existencia, e.Cantidad_Caja), 4), IF(e.Id_Caja = 0, e.Existencia, e.Cantidad_Caja)) as cantidad"),
                    DB::raw("TRUNCATE(IF(e.Id_Caja = 0, e.Existencia, e.Cantidad_Caja)*a.peso*(IF(IFNULL(a.num_multiplo, 0) = 0, 1, a.num_multiplo)), 4) as cantidad_kg"),
                    DB::raw("IFNULL(p.ID_proveedor, '') as id_proveedor"),
                    DB::raw("IFNULL(p.Nombre, '') as proveedor"),
                    DB::raw("IFNULL(ch.IDContenedor, '') as ntarima"),
                    DB::raw("IFNULL(poc.Nombre, '') as empresa_proveedor"),
                    DB::raw("CASE WHEN u.Picking = 'S' THEN 'Si' WHEN u.Picking = 'N' THEN 'No' END as tipo_ubicacion"),
                    'a.control_abc',
                    'z.clasif_abc',
                    DB::raw("IFNULL(um.cve_umed, '') as um"),
                    'a.control_peso'
                ])
                ->join('c_articulo as a', 'a.cve_articulo', '=', 'e.cve_articulo')
                ->leftJoin('c_unimed as um', 'a.unidadMedida', '=', 'um.id_umed')
                ->leftJoin('c_gpoarticulo as gr', 'gr.cve_gpoart', '=', 'a.grupo')
                ->leftJoin('c_sgpoarticulo as cl', 'cl.cve_sgpoart', '=', 'a.clasificacion')
                ->leftJoin('c_ubicacion as u', 'u.idy_ubica', '=', 'e.cve_ubicacion')
                ->leftJoin('c_lotes as l', function ($join) {
                    $join->on('l.LOTE', '=', 'e.cve_lote')
                        ->on('l.cve_articulo', '=', 'e.cve_articulo');
                })
                ->leftJoin('c_almacen as z', 'z.cve_almac', '=', 'u.cve_almac')
                ->leftJoin('c_charolas as ch', function ($join) {
                    $join->on(DB::raw("IFNULL(ch.clave_contenedor, '')"), '=', DB::raw("IFNULL(e.Cve_Contenedor, '')"))
                        ->where(DB::raw("IFNULL(ch.clave_contenedor, '')"), '!=', '');
                })
                ->leftJoin('tubicacionesretencion as zona', function ($join) {
                    $join->on(DB::raw("CONVERT(zona.cve_ubicacion, CHAR)"), '=', DB::raw("CONVERT(e.cve_ubicacion, CHAR)"));
                })
                ->leftJoin('c_almacenp as ap', function ($join) {
                    $join->on('ap.id', '=', DB::raw("IFNULL(e.cve_almac, z.cve_almacenp)"));
                })
                ->leftJoin('rel_articulo_proveedor as rap', function ($join) {
                    $join->on('rap.Cve_Articulo', '=', 'e.cve_articulo')
                        ->on('e.ID_Proveedor', '=', 'rap.Id_Proveedor')
                        ->where('rap.Id_Proveedor', '!=', 0);
                })
                ->leftJoin('c_proveedores as p', function ($join) {
                    $join->on('p.ID_Proveedor', '=', 'e.ID_Proveedor')
                        ->on('p.ID_Proveedor', '=', DB::raw("IFNULL(rap.Id_Proveedor, e.ID_Proveedor)"));
                })
                ->leftJoin('td_entalmacen as td', function ($join) {
                    $join->on('td.cve_articulo', '=', 'e.cve_articulo')
                        ->on('td.cve_lote', '=', 'e.cve_lote');
                })
                ->leftJoin('th_entalmacen as th', function ($join) {
                    $join->on('th.Cve_Proveedor', '=', 'e.ID_Proveedor')
                        ->on('th.Fol_folio', '=', 'td.fol_folio');
                })
                ->leftJoin('th_aduana as ta', 'ta.num_pedimento', '=', 'th.id_ocompra')
                ->leftJoin('c_proveedores as poc', 'poc.ID_Proveedor', '=', 'ta.ID_Proveedor');

            // Filtros
            $query->whereIn('e.cve_almac', function ($q) use ($almacen) {
                $q->select('cve_almac')->from('c_almacen')->where('cve_almacenp', $almacen);
            });

            if ($zona) {
                $query->where('e.cve_ubicacion', $zona);
            }

            if ($articulo) {
                $query->where('e.cve_articulo', $articulo);
            }

            if ($bl) {
                $query->where('u.CodigoCSD', 'like', "%{$bl}%");
            }

            if ($lp) {
                $query->where('ch.CveLP', 'like', "%{$lp}%");
            }

            if ($obsoletos === 'S') {
                $query->whereDate('l.Caducidad', '<', date('Y-m-d'));
            }

            if ($incluirProduccion === 'S') {
                $query->where('u.AreaProduccion', 'S');
            }

            if ($proveedor) {
                $query->where('e.ID_Proveedor', $proveedor);
            }

            if ($lote) {
                $query->where('e.cve_lote', 'like', "%{$lote}%");
            }

            // Ordenamiento
            $query->orderBy('a.des_articulo');

            // Paginación
            $paginator = $query->paginate($limit, ['*'], 'page', $page);

            return ApiResponse::paginated($paginator);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al generar reporte', $e->getMessage());
        }
    }
}
