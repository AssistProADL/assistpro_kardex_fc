<?php

namespace AssistPro\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Respuesta exitosa estándar
     *
     * @param mixed $data Datos a retornar
     * @param string $message Mensaje opcional
     * @param int $statusCode Código HTTP
     * @return JsonResponse
     */
    public static function success($data = null, string $message = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== '') {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Respuesta de error estándar
     *
     * @param string $message Mensaje de error
     * @param mixed $errors Errores específicos (validación, etc)
     * @param int $statusCode Código HTTP
     * @param string|null $errorCode Código de error personalizado
     * @return JsonResponse
     */
    public static function error(string $message, $errors = null, int $statusCode = 400, ?string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Respuesta de validación fallida
     *
     * @param mixed $errors Errores de validación
     * @param string $message Mensaje personalizado
     * @return JsonResponse
     */
    public static function validationError($errors, string $message = 'Error de validación'): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    /**
     * Respuesta no autorizado
     *
     * @param string $message Mensaje de error
     * @param string|null $errorCode Código de error
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'No autorizado', ?string $errorCode = null): JsonResponse
    {
        return self::error($message, null, 401, $errorCode);
    }

    /**
     * Respuesta no encontrado
     *
     * @param string $message Mensaje de error
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    /**
     * Respuesta prohibido
     *
     * @param string $message Mensaje de error
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Acceso prohibido'): JsonResponse
    {
        return self::error($message, null, 403);
    }

    /**
     * Respuesta de error del servidor
     *
     * @param string $message Mensaje de error
     * @param mixed $errors Detalles del error (solo en desarrollo)
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Error interno del servidor', $errors = null): JsonResponse
    {
        // Solo mostrar detalles si estamos en un entorno de desarrollo o si se pasa explícitamente
        // En este entorno mixto, asumimos que si se pasan errores es porque se quieren mostrar
        return self::error($message, $errors, 500);
    }

    /**
     * Respuesta con paginación
     *
     * @param mixed $items Colección paginada
     * @param string $message Mensaje opcional
     * @return JsonResponse
     */
    public static function paginated($items, string $message = ''): JsonResponse
    {
        $data = [
            'items' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ];

        return self::success($data, $message);
    }
}
