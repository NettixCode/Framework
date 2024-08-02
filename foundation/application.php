<?php

namespace Nettixcode\Framework\Foundation;

use Dotenv\Dotenv;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Nettixcode\Framework\Libraries\AliasManager;
use Nettixcode\Framework\Libraries\AuthManager;
use Nettixcode\Framework\Libraries\ConfigManager;
use Nettixcode\Framework\Libraries\SessionManager;
use Nettixcode\Framework\Libraries\UserManager;
use Nettixcode\Framework\Libraries\ViewManager;
use Nettixcode\Framework\Routes\Route;
use Symfony\Component\Console\Application as ConsoleApplication;

class Application extends Container
{
    protected static $instance;
    protected $basePath;
    protected $config = [];
    protected $middleware = [];
    protected $middlewareGroups = [];
    protected $routes = [];
    protected $exceptions = [];
    protected $consoleApplication;
    protected $configs;
    
    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        Facade::setFacadeApplication($this);

        require realpath(__DIR__ . '/helpers.php');

        $this->registerSingleton();

        $this->configs = new ConfigManager();
        
        date_default_timezone_set($this->configs->get('app.timezone'));

        $dotenv = Dotenv::createImmutable($this->basePath);
        $dotenv->load();

        SessionManager::getInstance();

        AliasManager::generate();

        $this->loadAliases();

        $this->registerConfiguredProviders();

        $appName = $this->configs->get('app.app_name');
        $appVersion = $this->getVersion();
        $this->consoleApplication = new ConsoleApplication($appName, $appVersion);
    }

    public static function getInstance()
    {
        return static::$instance;
    }

    public static function setInstance(?ContainerContract $container = null)
    {
        static::$instance = $container;
    }

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    protected function registerSingleton()
    {
        static::setInstance($this);

        $this->singleton('config', function () {
            return new ConfigManager();
        });
        
        $this->singleton('files', function () {
            return new Filesystem();
        });
        
        $this->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });
        
        $this->singleton('user', function($app) {
            return new UserManager();
        });
        
        $this->singleton('auth', function($app) {
            return new AuthManager();
        });
        
        $this->singleton('nxengine', function($app) {
            return new ViewManager();
        });
        
        $this->singleton('route', function () {
            return new Route();
        });
    }

    protected function loadAliases()
    {
        $aliases = $this->configs->get('aliases.default');
        $controllerAliases = $this->configs->get('aliases.default_controller');
        $customAliases = $this->configs->get('aliases.controller');

        $allAliases = array_merge($aliases, $controllerAliases, $customAliases);

        AliasLoader::getInstance($allAliases)->register();
    }

    protected function registerConfiguredProviders()
    {
        $providers = $this->configs->get('app.providers');

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
