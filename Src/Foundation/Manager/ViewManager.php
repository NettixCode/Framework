<?php

namespace Nettixcode\Framework\Foundation\Manager;

class ViewManager
{
    protected $viewFactory;
    protected $debugbar;

    public function __construct()
    {
        global $debugbar;
        $this->viewFactory = app('view');

        if (isset($debugbar)) {
            $this->debugbar = $debugbar;
            $debugbarRenderer = $this->debugbar->getJavascriptRenderer();
            $debugbarRenderer->setIncludeVendors(true);
        } else {
            $this->debugbar = null;
        }
    }

    private function render($view, $data = [])
    {
        $output = $this->viewFactory->make($view, $data);
        echo $output->render();
    }
    
    private function make($view, $data = [])
    {
        $output = $this->viewFactory->make($view, $data);
        return $output;
    }

    public static function view($view, $data = [])
    {
        $instance = new static;
        $data['SessionManager'] = SessionManager::class;
        if ($instance->debugbar) {
            $data['debugbarHead'] = $instance->debugbar->getJavascriptRenderer()->renderHead();
            $data['debugbarRender'] = $instance->debugbar->getJavascriptRenderer()->render();
        } else {
            $data['debugbarHead'] = '';
            $data['debugbarRender'] = '';
        }
        $instance->render($view, $data);
    }

    public static function redirectToErrorPage($code = 404)
    {
        http_response_code($code);
        self::viewErrorPage($code);
        exit();
    }

    public static function viewErrorPage($code = 404)
    {
        $instance = new static;
        $view  = 'errors.error-' . $code;
        $instance->render($view, []);
    }
}
