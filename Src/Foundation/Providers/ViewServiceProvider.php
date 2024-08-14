<?php

namespace Nettixcode\Framework\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Nettixcode\Framework\Facades\Config;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBladeCompiler();
        $this->registerEngineResolver();
        $this->registerViewFinder();
        $this->registerViewFactory();
    }

    protected function registerBladeCompiler()
    {
        $this->app->singleton('blade.compiler', function ($app) {
            $cachePath = Config::get('views.compiled');
            return new BladeCompiler($app['files'], $cachePath);
        });
    }

    protected function registerEngineResolver()
    {
        $this->app->singleton('view.engine.resolver', function ($app) {
            $resolver = new EngineResolver;

            $resolver->register('file', function () {
                return new FileEngine;
            });

            $resolver->register('php', function () use ($app) {
                return new PhpEngine($app['files']);
            });

            $resolver->register('blade', function () use ($app) {
                return new CompilerEngine($app['blade.compiler']);
            });

            return $resolver;
        });
    }

    protected function registerViewFinder()
    {
        $this->app->singleton('view.finder', function ($app) {
            $paths = Config::get('views.paths');
            $extensions = [Config::get('views.extension')];

            return new FileViewFinder($app['files'], $paths, $extensions);
        });
    }

    protected function registerViewFactory()
    {
        $this->app->singleton('view', function ($app) {
            $factory = new ViewFactory(
                $app['view.engine.resolver'],
                $app['view.finder'],
                $app['events']
            );
            $factory->addExtension(Config::get('views.extension'), 'blade');

            // $app['events']->listen('composing:*', function ($eventName, $data) use ($app) {
            //     global $debugbar;
            //     if (isset($debugbar)) {
            //         foreach ($data as $dataItem) {
            //             if ($dataItem instanceof \Illuminate\View\View) {
            //                 $debugbar['views']->addView($dataItem);
            //             }
            //         }
            //     }
            // });

            return $factory;
        });
    }
}
