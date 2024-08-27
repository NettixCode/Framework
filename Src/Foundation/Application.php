<?php

namespace Nettixcode\Framework\Foundation;

use Dotenv\Dotenv;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Application as ConsoleApplication;
use DebugBar\StandardDebugBar;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Nettixcode\Framework\Foundation\Manager\ConfigManager;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Http\Kernel;
use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Foundation\Configuration\ApplicationBuilder;
use Nettixcode\Framework\Foundation\Services\Alias;
use Nettixcode\Framework\Foundation\Services\Singleton;
use Nettixcode\Framework\Foundation\Services\Debugbar;
use Nettixcode\Framework\Facades\NxLog;

class Application extends Container
{
    protected static $instance;
    protected $basePath;
    protected $serviceProviders = [];
    protected $debugbar;
    public $consoleApplication;
    public $exceptions;
    protected $isBooted = false;
    public $isBooting = false;
    protected $bootingCallbacks = [];
    protected $bootedCallbacks = [];
    
    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        $this->configure_bootstrap();

        $this->load_environtment();

        $this->registerBaseService();
        
        $this->configure_debuging();
    }

    public static function configure(?string $basePath = null)
    {
        define('NETTIXCODE_START', microtime(true));
        
        $basePath = match (true) {
            is_string($basePath) => $basePath,
            default => static::inferBasePath(),
        };

        return (new ApplicationBuilder(new static($basePath)))
            ->withKernels()
            ->withProviders();
    }

    public static function inferBasePath()
    {
        return match (true) {
            isset($_ENV['APP_BASE_PATH']) => $_ENV['APP_BASE_PATH'],
            default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
        };
    }

    private function configure_bootstrap()
    {
        self::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);

        Container::setInstance($this);

        Facade::setFacadeApplication($this);

        SessionManager::getInstance();

        $this->app->singleton('config', function () {
            $configPath = $this->getCachedConfigPath();

            if (file_exists($configPath)) {
                $configItems = require $configPath;
                return new ConfigManager($configItems);
            } else {
                return new ConfigManager();
            }
        });

        $this->isBooting = true;

        new Singleton($this);
    }

    private function load_environtment()
    {
        $dotenv = Dotenv::createImmutable($this->basePath);
        $dotenv->load();

        date_default_timezone_set($this['config']->get('app.timezone'));

        new Alias($this);
    }

    protected function registerBaseService()
    {
        $this->register(\Illuminate\Routing\RoutingServiceProvider::class);        
        $this->register(\Nettixcode\Framework\Foundation\Providers\DatabaseServiceProvider::class);        
        $this->register(\Illuminate\Events\EventServiceProvider::class);
        $this->register(\Illuminate\Log\LogServiceProvider::class);
    }
   
    private function configure_debuging()
    {
        if ($this['config']->get('app.app_debug')) {
            if (!isset($GLOBALS['debugbar'])) {
                $debugBarProvider = new DebugBar();
                $debugBarProvider->enable();
                $debugbar = $debugBarProvider->getDebugBar();
                
                $GLOBALS['debugbar'] = $debugbar;
                if (isset($debugbar['time'])){
                    $debugbar['time']->startMeasure('Booting', 'Booting','time');
                }
            }
            
            $appName = $this['config']->get('app.app_name');
            $appVersion = $this->getVersion();
            $this->consoleApplication = new ConsoleApplication($appName, $appVersion);
        }
    }

    // public function basePath($path = '')
    // {
    //     return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    // }

    public function getVersion()
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

    public static function getInstance()
    {
        return static::$instance;
    }

    public static function setInstance(?ContainerContract $container = null)
    {
        static::$instance = $container;
    }

    public function boot()
    {
        // Memanggil semua callback booting yang terdaftar
        foreach ($this->bootingCallbacks as $callback) {
            call_user_func($callback);
        }
    
        // Booting aplikasi
        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }
    
        $this->isBooted = true;
        $this->isBooting = false;
        // Memanggil semua callback booted yang terdaftar
        foreach ($this->bootedCallbacks as $callback) {
            call_user_func($callback);
        }
    }
    
    public function booting(callable $callback)
    {
        $this->bootingCallbacks[] = $callback;
    }
    
    public function isBooting(callable $callback)
    {
        if ($this->isBooting) {
            return $callback($this);
        }
        return null;
    }
    
    public function booted(callable $callback)
    {
        if ($this->isBooted) {
            call_user_func($callback);
        } else {
            $this->bootedCallbacks[] = $callback;
        }
    }

    public function isBooted()
    {
        return $this->isBooted;
    }

    public function register($provider, $force = false)
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (in_array($provider, $this->serviceProviders) && !$force) {
            return;
        }
    
        $provider->register();
        $this->serviceProviders[] = $provider;
    
        if ($this->isBooted) {
            $this->bootProvider($provider);
        }
    }

    protected function bootProvider($provider)
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
    }
    
    public function handleRequest(Request $request)
    {
        if (!$this->isBooted()) {
            $this->boot();
        }

        if ($this['config']->get('app.app_debug')){
            $kernel = $this->make(\Nettixcode\Framework\Http\Kernel::class);
            $kernel->handle($request);
        } else {
            try {
                $kernel = $this->make(\Nettixcode\Framework\Http\Kernel::class);
                $kernel->handle($request);
            } catch (\Throwable $e) {
                $handler = $this->make(ExceptionHandler::class);
                $handler->report($e);
                $handler->render($request, $e)->send();
            }
        }
    }

    protected function handleException(Request $request, \Throwable $e)
    {
        $handler = $this->make(ExceptionHandler::class);
        $handler->report($e);
        $response = $handler->render($request, $e);

        if ($response) {
            $response->send();
        }
    }

    public function runConsole()
    {
        $this->boot();
        return $this->consoleApplication;
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached()
    {
        return $this['files']->exists($this->getCachedRoutesPath());
    }

    public function getCachedConfigPath()
    {
        return $this->basePath . '/storage/framework/cache/data/config.php';
    }

    public function getCachedRoutesPath()
    {
        return $this->basePath . '/storage/framework/cache/data/routes-v7.php';
    }

    public function runningUnitTests()
    {
        return false;
    }
    
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    public function getRouter()
    {
        return $this['router'];
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path = '')
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    /**
     * Set the application directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useAppPath($path)
    {
        $this->appPath = $path;

        $this->instance('path', $path);

        return $this;
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->joinPaths($this->basePath, $path);
    }

    /**
     * Join the given paths together.
     *
     * @param  string  $basePath
     * @param  string  $path
     * @return string
     */
    public function joinPaths($basePath, $path = '')
    {
        return join_paths($basePath, $path);
    }


}
