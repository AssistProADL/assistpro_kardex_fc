<?php
/**
 * Helpers Globales
 * Funciones auxiliares disponibles en toda la aplicación
 */

if (!function_exists('asset')) {
    /**
     * Genera una URL para un asset (archivo estático)
     * Detecta automáticamente la ruta base del proyecto
     * 
     * @param string $path Ruta relativa al asset (ej: 'assets/js/script.js')
     * @return string URL completa al asset
     */
    function asset($path)
    {
        // Detectar la ruta base automáticamente desde la URL actual
        $scriptName = $_SERVER['SCRIPT_NAME'];

        // Obtener el directorio base (elimina /router.php, /index.php, etc.)
        $baseUrl = dirname($scriptName);

        // Si estamos en /public/router.php, el baseUrl será /public
        // Normalizar para que siempre termine sin /
        $baseUrl = rtrim($baseUrl, '/');

        // Limpiar la ruta del asset
        $path = ltrim($path, '/');

        // Retornar la URL completa
        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('base_url')) {
    /**
     * Obtiene la URL base del proyecto
     * 
     * @param string $path Ruta opcional para agregar a la base
     * @return string URL base del proyecto
     */
    function base_url($path = '')
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseUrl = rtrim(dirname($scriptName), '/');

        if ($path) {
            $path = ltrim($path, '/');
            return $baseUrl . '/' . $path;
        }

        return $baseUrl;
    }
}
