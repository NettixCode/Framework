<?php

namespace Nettixcode\Framework\Foundation\Services;

use DebugBar\StandardDebugBar;
use DebugBar\JavascriptRenderer;
use Nettixcode\Framework\Collectors\MemoryCollector;
use Nettixcode\Framework\Collectors\TimeDataCollector;
use Nettixcode\Framework\Collectors\MessagesCollector;
use Nettixcode\Framework\Collectors\ConfigCollector;
use Nettixcode\Framework\Collectors\PhpInfoCollector;
use Nettixcode\Framework\Collectors\ExceptionsCollector;
use Nettixcode\Framework\Collectors\RequestCollector;
use Nettixcode\Framework\Collectors\SessionCollector;
use Nettixcode\Framework\Collectors\HttpRequestCollector;
use Nettixcode\Framework\Collectors\PDO\PDOCollector;
use Nettixcode\Framework\Collectors\PDO\TraceablePDO;
use Nettixcode\Framework\Foundation\Manager\ViewManager;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Foundation\Manager\SessionManagerAdaptor;
use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Collectors\RouteCollector;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Debugbar
{
    protected $debugbar;
    protected $renderer;
    protected $traceablePdo;
    protected $events;

    public function __construct()
    {
        if (Config::get('app.app_debug')) {
            $this->debugbar = new StandardDebugBar();
            $this->renderer = $this->debugbar->getJavascriptRenderer('/debugbar');
            $this->initializeCollectors();
        }
    }

    protected function initializeCollectors()
    {
        if (!Config::get('app.app_debug')) {
            return;
        }

        if (!$this->debugbar->hasCollector('php')) {
            $this->debugbar->addCollector(new PhpInfoCollector());
        }
        if (!$this->debugbar->hasCollector('messages')) {
            $this->debugbar->addCollector(new MessagesCollector());
        }
        if (!$this->debugbar->hasCollector('memory')) {
            $this->debugbar->addCollector(new MemoryCollector());
        }
        if (!$this->debugbar->hasCollector('time')) {
            $this->debugbar->addCollector(new TimeDataCollector());
        }
        if (!$this->debugbar->hasCollector('exceptions')) {
            $this->debugbar->addCollector(new ExceptionsCollector());
        }
        if (!$this->debugbar->hasCollector('request')) {
            $this->debugbar->addCollector(new RequestDataCollector());
        }
        if (!$this->debugbar->hasCollector('HttpRequest') && Config::get('debugbar.widgets.httpRequest')) {
            $request = new Request();
            $response = new Response();
            $sessionManager = SessionManager::getInstance();
            $session = new SessionManagerAdaptor($sessionManager);
            $this->debugbar->addCollector(new HttpRequestCollector($request, $response, $session));
        }
        if (!$this->debugbar->hasCollector('route') && Config::get('debugbar.widgets.route')) {
            $this->debugbar->addCollector(new RouteCollector());
        }
        if (!$this->debugbar->hasCollector('pdo') && Config::get('debugbar.widgets.query')) {
            $pdo = DB::connection()->getPdo();
            $this->traceablePdo = new TraceablePDO($pdo);
            $this->debugbar->addCollector(new PDOCollector($this->traceablePdo));
        }
        if (!$this->debugbar->hasCollector('session') && Config::get('debugbar.widgets.session')) {
            $sessionManager = SessionManager::getInstance();
            $sessionAdaptor = new SessionManagerAdaptor($sessionManager);
            $this->debugbar->addCollector(new SessionCollector($sessionAdaptor));
        }
        if (!$this->debugbar->hasCollector('config') && Config::get('debugbar.widgets.config')) {
            $this->debugbar->addCollector(new ConfigCollector($_SERVER));
        }
    }

    public function enable()
    {
        if (!Config::get('app.app_debug')) {
            return;
        }
        return $this;
    }

    public function getTraceablePdo()
    {
        return $this->traceablePdo;
    }

    public function addMessage($message, $label = 'info')
    {
        if (Config::get('app.app_debug') && isset($this->debugbar)) {
            $this->debugbar["messages"]->addMessage($message, $label);
        }
    }

    public function getDebugBar()
    {
        return $this->debugbar;
    }

    public function renderHead()
    {
        return Config::get('app.app_debug') ? $this->renderer->renderHead() : '';
    }

    public function render()
    {
        return Config::get('app.app_debug') ? $this->renderer->render() : '';
    }
}
