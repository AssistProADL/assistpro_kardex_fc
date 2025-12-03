<?php

namespace AssistPro\Http\Controllers;

use AssistPro\Helpers\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;

class CatalogosController
{
    /**
     * Obtener lista de motivos
     * Legacy: api/reportes/lista/existenciaubica.php (action: traermotivos)
     */
    public function getMotivos(Request $request)
    {
        try {
            // Default 'Q' como en el legacy, pero permitiendo override
            $status = $request->input('status', 'Q');

            $motivos = DB::table('c_motivo')
                ->select('id', 'Tipo_Cat', 'Des_Motivo as descri')
                ->where('Tipo_Cat', $status)
                ->where('Activo', 1)
                ->get();

            return ApiResponse::success($motivos);

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error al obtener motivos', $e->getMessage());
        }
    }
}
