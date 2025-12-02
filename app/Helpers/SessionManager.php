<?php

namespace AssistPro\Helpers;

/**
 * Manejador unificado de sesiones para compatibilidad entre
 * sistema legacy (PHP puro) y nuevo sistema (Illuminate)
 */
class SessionManager
{
    private static $initialized = false;
    private static $sessionName = 'ASSISTPRO_SESSION';
    private static $timeout = 900; // 15 minutos en segundos

    /**
     * Inicializar sesión una sola vez por request
     * Compatible con ambos sistemas (legacy y nuevo)
     */
    public static function init()
    {
        // Evitar múltiples inicializaciones en el mismo request
        if (self::$initialized) {
            return;
        }

        // Solo iniciar si no hay sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar nombre de sesión personalizado
            session_name(self::$sessionName);

            // Configurar parámetros de cookie
            session_set_cookie_params([
                'lifetime' => 0,           // Cookie expira al cerrar navegador
                'path' => '/',             // Disponible en todo el sitio
                'domain' => '',            // Dominio actual
                'secure' => false,         // Cambiar a true si usas HTTPS
                'httponly' => true,        // No accesible desde JavaScript
                'samesite' => 'Lax'        // Protección CSRF
            ]);

            // Iniciar sesión
            session_start();

            // Validar timeout de inactividad
            self::validateTimeout();

            // Regenerar ID periódicamente para seguridad
            self::regenerateIdIfNeeded();
        }

        self::$initialized = true;
    }

    /**
     * Validar timeout de sesión (15 minutos de inactividad)
     */
    private static function validateTimeout()
    {
        if (isset($_SESSION['LAST_ACTIVITY'])) {
            $inactive = time() - $_SESSION['LAST_ACTIVITY'];

            // Si pasaron más de 15 minutos, destruir sesión
            if ($inactive > self::$timeout) {
                self::destroy();
                return;
            }
        }

        // Actualizar timestamp de última actividad
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    /**
     * Regenerar ID de sesión cada 5 minutos para seguridad
     */
    private static function regenerateIdIfNeeded()
    {
        if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        } elseif (time() - $_SESSION['CREATED'] > 300) { // 5 minutos
            session_regenerate_id(true);
            $_SESSION['CREATED'] = time();
        }
    }

    /**
     * Destruir sesión completamente
     */
    public static function destroy()
    {
        // Iniciar sesión si no está activa para poder destruirla
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::$sessionName);
            session_start();
        }

        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Destruir cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"] ?: '/',
                $params["domain"] ?: '',
                $params["secure"] ?: false,
                $params["httponly"] ?: true
            );
        }

        // Destruir la sesión
        session_destroy();

        self::$initialized = false;
    }

    /**
     * Obtener valor de sesión
     */
    public static function get($key, $default = null)
    {
        self::init();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Establecer valor de sesión
     */
    public static function set($key, $value)
    {
        self::init();
        $_SESSION[$key] = $value;
    }

    /**
     * Verificar si existe una clave en sesión
     */
    public static function has($key)
    {
        self::init();
        return isset($_SESSION[$key]);
    }

    /**
     * Eliminar una clave de sesión
     */
    public static function remove($key)
    {
        self::init();
        unset($_SESSION[$key]);
    }

    /**
     * Obtener ID de usuario autenticado
     */
    public static function getUserId()
    {
        return self::get('id_user');
    }

    /**
     * Verificar si hay usuario autenticado
     */
    public static function isAuthenticated()
    {
        return self::has('id_user') && self::get('id_user') !== null;
    }

    /**
     * Obtener todos los datos de sesión
     */
    public static function all()
    {
        self::init();
        return $_SESSION;
    }

    /**
     * Obtener tiempo restante de sesión en segundos
     */
    public static function getTimeRemaining()
    {
        self::init();
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            return self::$timeout;
        }

        $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
        return max(0, self::$timeout - $elapsed);
    }
}
