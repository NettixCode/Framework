<?php

namespace Nettixcode\Framework\Foundation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Libraries\ConfigManager;
use Nettixcode\Framework\Libraries\AliasManager;
use Nettixcode\Framework\Libraries\SessionManager;
use Symfony\Component\Console\Application as ConsoleApplication;

class Application extends Container
{
    protected $basePath;
    protected $config = [];
    protected $middleware = [];
    protected $middlewareGroups = [];
    protected $routes = [];
    protected $exceptions = [];
    protected $consoleApplication;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        SessionManager::getInstance();

        Facade::setFacadeApplication($this);

        // Generate aliases using AliasManager
        AliasManager::generate();

        // Load aliases
        $this->loadAliases();

        // Register service providers
        $this->registerConfiguredProviders();

        // Initialize console application with dynamic version
        $appName = $this->make('config')->load('app', 'app_name');
        $appVersion = $this->getVersion();
        $this->consoleApplication = new ConsoleApplication($appName, $appVersion);
    }

    protected function loadAliases()
    {
        $aliases = ConfigManager::load('aliases', 'default');
        $controllerAliases = ConfigManager::load('aliases', 'default_controller');
        $customAliases = ConfigManager::load('aliases', 'controller');

        // Gabungkan semua alias
        $allAliases = array_merge($aliases, $controllerAliases, $customAliases);

        AliasLoader::getInstance($allAliases)->register();
    }

    protected function registerConfiguredProviders()
    {
        $providers = ConfigManager::load('providers');

        foreach ($providers as $provider) {
            $this->register(new $provider($this));
        }
    }

    public function register($provider)
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
    }

    public function basePath()
    {
        return $this->basePath;
    }

    public static function configure($basePath)
    {
        $app = new static($basePath);
        $configPath = $basePath . '/config/app.php';
        if (file_exists($configPath)) {
            $config = include $configPath;
            $app->config = array_merge($app->config, $config);
        } else {
            throw new \Exception("Configuration file not found: " . $configPath);
        }

        return $app;
    }

    protected function getVersion()
    {
        $composerJsonPath = $this->basePath . '/composer.json';
        if (file_exists($composerJsonPath)) {
            $composerJson = json_decode(file_get_contents($composerJsonPath), true);
            if (isset($composerJson['version'])) {
                return $composerJson['version'];
            }
        }
        return 'unknown';
    }

    public function withRouting(array $routes)
    {
        $this->routes = $routes;
        return $this;
    }

    public function loadRoutes()
    {
        foreach ($this->routes as $key => $routeFile) {
            if (file_exists($routeFile)) {
                if ($key === 'commands') {
                    $app = $this; // Pastikan $app tersedia di konteks file route
                    require $routeFile;
                } else {
                    require $routeFile;
                }
            } else {
                throw new \Exception("Route file not found: " . $routeFile);
            }
        }
    }

    public function addCommand($command)
    {
        $this->consoleApplication->add($command);
    }

    public function withMiddleware(callable $callback)
    {
        $callback($this);
        return $this;
    }

    public function addMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
    }

    public function addMiddlewareToGroup($group, $middleware)
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }
        $this->middlewareGroups[$group][] = $middleware;
    }

    public function withExceptions(callable $callback)
    {
        $callback($this);
        return $this;
    }

    public function addExceptionHandler($exceptionHandler)
    {
        $this->exceptions[] = $exceptionHandler;
    }

    public function create()
    {
        // Load the routes
        $this->loadRoutes();
        return $this;
    }

    public function runConsole()
    {
        return $this->consoleApplication;
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    public function getExceptions()
    {
        return $this->exceptions;
    }
}
