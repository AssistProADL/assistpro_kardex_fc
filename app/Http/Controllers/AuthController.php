<?php

namespace AssistPro\Http\Controllers;

use AssistPro\Helpers\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;

class AuthController
{
    public function login(Request $request)
    {
        try {
            $username = trim($request->input('user', ''));
            $pass = (string) $request->input('pass', '');

            if (empty($username) || empty($pass)) {
                return ApiResponse::error('Usuario y contraseña requeridos', null, 400);
            }

            // Query user
            $row = DB::table('c_usuario')
                ->select([
                    'id_user',
                    'cve_usuario',
                    'nombre_completo',
                    'perfil',
                    'pwd_usuario',
                    'Activo'
                ])
                ->whereRaw('TRIM(cve_usuario) = ?', [$username])
                ->whereIn(DB::raw("COALESCE(Activo,'1')"), ['1', 'S', 'SI', 'TRUE'])
                ->first();

            if (!$row) {
                return ApiResponse::error('Usuario no encontrado o inactivo', null, 401);
            }

            // Validate password
            $dbpwd = (string) ($row->pwd_usuario ?? '');
            if ($dbpwd !== '' && $dbpwd !== $pass) {
                return ApiResponse::error('Contraseña incorrecta', null, 401);
            }

            // Success! Guardar datos en sesión unificada
            \AssistPro\Helpers\SessionManager::set('username', $row->cve_usuario);
            \AssistPro\Helpers\SessionManager::set('id_user', $row->id_user);
            \AssistPro\Helpers\SessionManager::set('nombre_completo', $row->nombre_completo ?? $row->cve_usuario);
            \AssistPro\Helpers\SessionManager::set('perfil', $row->perfil ?? '');

            // Initialize context variables
            \AssistPro\Helpers\SessionManager::set('cve_almac', '');
            \AssistPro\Helpers\SessionManager::set('empresas', []);

            return ApiResponse::success([
                'redirect' => '/assistpro_kardex_fc/public/dashboard/index.php',
                'user' => [
                    'username' => $row->cve_usuario,
                    'nombre' => $row->nombre_completo
                ]
            ], 'Login exitoso');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Error en el servidor', $e->getMessage());
        }
    }
}
