<?php

namespace Nettixcode\Framework\Foundation;

use Dotenv\Dotenv;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Application as ConsoleApplication;
use DebugBar\StandardDebugBar;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Foundation\Manager\ConfigManager;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Http\Kernel;
use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Foundation\Configuration\ApplicationBuilder;
use Nettixcode\Framework\Foundation\Services\Alias;
use Nettixcode\Framework\Foundation\Services\Singleton;
use Nettixcode\Framework\Foundation\Services\Debugbar;

class Application extends Container
{
    protected static $instance;
    protected $basePath;
    // protected $config = [];
    protected $serviceProviders = [];
    protected $debugbar;
    public $consoleApplication;
    public $exceptions;

    public function __construct($basePath)
    {
        $this->configure_bootstrap($basePath);

        $this->load_environtment();

        $this->registerBaseService();
        
        $this->configure_dev();
    }

    public static function configure(?string $basePath = null)
    {
        // $app = new static($basePath);
        // $app->config = new \Nettixcode\Framework\Foundation\Manager\ConfigManager();

        $basePath = match (true) {
            is_string($basePath) => $basePath,
            default => static::inferBasePath(),
        };

        return (new ApplicationBuilder(new static($basePath)))
            ->withRouting()
            ->withMiddleware()
            ->withExceptions();
    }

    public static function inferBasePath()
    {
        return match (true) {
            isset($_ENV['APP_BASE_PATH']) => $_ENV['APP_BASE_PATH'],
            default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
        };
    }

    private function configure_bootstrap($basePath){
        $this->basePath = $basePath;

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

        new Singleton($this);        
    }

    private function load_environtment(){
        $dotenv = Dotenv::createImmutable($this->basePath);
        $dotenv->load();

        date_default_timezone_set($this['config']->get('app.timezone'));

        new Alias($this);
    }

    protected function registerBaseService()
    {
        $this->register(\Illuminate\Routing\RoutingServiceProvider::class);        
        $this->register(\Nettixcode\Framework\Foundation\Providers\DatabaseServiceProvider::class);        
    }
   
    private function configure_dev(){
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

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
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

    public static function getInstance()
    {
        return static::$instance;
    }

    public static function setInstance(?ContainerContract $container = null)
    {
        static::$instance = $container;
    }

    public function handleRequest(Request $request)
    {
        $kernel = $this->make(Kernel::class);

        $response = $kernel->handle($request);
    }

    public function boot()
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $this->call([$provider, 'boot']);
            }
        }
    }

    public function register($provider)
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $this->serviceProviders[] = $provider;
        $provider->register();

        $this->boot();
    }

    public function runConsole()
    {
        return $this->consoleApplication;
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
}
