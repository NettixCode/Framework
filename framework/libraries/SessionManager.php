<?php

namespace Nettixcode\Framework\Libraries;

class SessionManager
{
    private static $instance = null;

    private function __construct()
    {
        // Konfigurasi session
        self::configureSession();

        // Memulai sesi jika belum dimulai
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->addSecurity();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy()
    {
        session_unset();
        session_destroy();
        self::clearCookies();
    }

    private static function clearCookies()
    {
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name  = trim($parts[0]);
                setcookie($name, '', time() - 3600, '/');
                unset($_COOKIE[$name]);
            }
        }
    }

    private function addSecurity()
    {
        // Set initial session creation time if not set
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 3600) {
            // Session started more than 1 hour ago
            self::destroy();
            session_start();
            $_SESSION['created'] = time();
        }
    
        // Check and set user agent
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (!isset($_SESSION['user_agent'])) {
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            } elseif ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                self::destroy();
                session_start();
                $_SESSION['created'] = time();
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
        }
    
        // Check and set IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            if (!isset($_SESSION['ip_address'])) {
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            } elseif ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                self::destroy();
                session_start();
                $_SESSION['created'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            }
        }
    }
    
    private static function configureSession()
    {
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.gc_maxlifetime', 3600); // 1 hour
    }    

    private function __clone()
    {
        // Prevent cloning
    }

    public function __wakeup()
    {
        // Prevent unserializing
    }
}
