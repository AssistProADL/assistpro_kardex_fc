<?php

namespace AssistPro\Http\Controllers;

use AssistPro\Helpers\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;

class AlmacenController
{
    /**
     * Obtener almacén predeterminado del usuario
     * Equivalente a: api/almacenPredeterminado/index.php
     */
    public function getPredeterminado(Request $request)
    {
        try {
            // Obtener ID de usuario desde sesión unificada
            $userId = \AssistPro\Helpers\SessionManager::get('id_user');


            $usuario = DB::table('c_usuario')->where('id_user', $userId)->first();


            if (!$usuario) {
                return ApiResponse::notFound('Usuario no encontrado');
            }

            $almacenId = $usuario->cve_almacen;

            if (!$almacenId) {
                //si no tiene, traemos todos los almacenes
                $almacenes = DB::table('c_almacen')->get();
                return ApiResponse::success($almacenes);
            }

            $almacen = DB::table('c_almacen')->where('cve_almac', $almacenId)->get();

            if (count($almacen) == 0) {
                return ApiResponse::notFound('Almacén no encontrado');
            }

            return ApiResponse::success($almacen);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al obtener almacén predeterminado', $e->getMessage());
        }
    }

    /**
     * Obtener zonas de un almacén
     * Equivalente a: api/almacenp/update/index.php
     */
    public function getZonas(Request $request)
    {
        try {
            $almacenId = $request->input('almacen_id');

            if (!$almacenId) {
                return ApiResponse::error('ID de almacén requerido', null, 400);
            }

            // Query original: SELECT * FROM c_almacen WHERE cve_almacenp = '$almacen'
            $zonas = DB::table('c_almacen')
                ->where('cve_almacenp', $almacenId)
                ->get();

            return ApiResponse::success($zonas);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al obtener zonas', $e->getMessage());
        }
    }
    /**
     * Obtener almacenes padres (que tienen zonas)
     */
    public function getPadres(Request $request)
    {
        try {
            // Seleccionar almacenes que son padres (su ID aparece en cve_almacenp de otros)
            $padres = DB::table('c_almacen')
                ->whereIn('cve_almac', function ($q) {
                    $q->select('cve_almacenp')->from('c_almacen')->distinct()->whereNotNull('cve_almacenp');
                })
                ->orderBy('des_almac')
                ->get();

            return ApiResponse::success($padres);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al obtener almacenes padres', $e->getMessage());
        }
    }
}
