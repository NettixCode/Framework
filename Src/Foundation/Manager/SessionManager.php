<?php

namespace Nettixcode\Framework\Foundation\Manager;

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

    public static function all(){
        return isset($_SESSION) ? $_SESSION : null;
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

    private function create_token()
    {
        $current_uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (!self::has('_token')) {
            self::set('_token', self::generateToken());
        }

        // Jangan lakukan pengecekan untuk file statis
        if (self::isStaticFile($current_uri)) {
            return;
        }
    }

    private static function generateToken()
    {
        $key = env('APP_KEY');
        
        if ($key !== null && strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }
    
        return hash_hmac('sha256', session_id(), $key ?: 'fallback_key');
    }
    
    private static function isStaticFile($uri)
    {
        $staticFileExtensions = ['.css', '.js', '.map', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
        foreach ($staticFileExtensions as $extension) {
            if (strpos($uri, $extension) !== false) {
                return true;
            }
        }

        return false;
    }

    private function addSecurity()
    {
        $this->create_token();
        // Set initial session creation time if not set
        if (!$this->has('created')) {
            $this->set('created' ,time());
        } elseif (time() - $this->get('created') > 3600) {
            // Session started more than 1 hour ago
            self::destroy();
            session_start();
            $this->set('created' ,time());
        }
        
        // Check and set IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            if (!$this->has('ip_address')) {
                $this->set('ip_address',$_SERVER['REMOTE_ADDR']);
            } elseif ($this->get('ip_address') !== $_SERVER['REMOTE_ADDR']) {
                self::destroy();
                session_start();
                $this->set('created' ,time());
                $this->set('ip_address',$_SERVER['REMOTE_ADDR']);
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
