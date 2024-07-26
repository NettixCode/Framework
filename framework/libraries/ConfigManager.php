<?php

namespace Nettixcode\Framework\Libraries;

class ConfigManager
{
    private static $instance = null;

    protected static $configs = [];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get($file = 'app')
    {
        if (!isset(self::$configs[$file])) {
            $file != 'framework' ? 
            self::$configs[$file] = require realpath(__DIR__ . "/../../../../config/{$file}.php"):
            self::$configs[$file] = require realpath(__DIR__ . "/sources/config/{$file}.php");
        }

        return self::$configs[$file];
    }

    public static function load($file, $key = null)
    {
        $config = self::get($file);

        if ($key === null) {
            return $config;
        }

        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (isset($config[$k])) {
                $config = $config[$k];
            } else {
                return null;
            }
        }

        return $config;
    }
}
