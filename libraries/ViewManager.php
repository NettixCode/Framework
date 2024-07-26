<?php

namespace Nettixcode\Framework\Libraries;

use Nettixcode\Framework\Core\Blade;
use Nettixcode\Framework\Libraries\Sources\Facades\User;

class ViewManager extends Blade
{
    public static function view($view, $data = [])
    {
        $instace = new Static;
        $data['SessionManager'] = SessionManager::class;
        // $blade = new Blade();
        $instace->render($view, $data);
    }

    public static function redirectToErrorPage($code = 404)
    {
        http_response_code($code);
        self::viewErrorPage($code);
        exit();
    }

    public static function viewErrorPage($code = 404)
    {
        $instace = new Static;
        $view  = 'errors.error-' . $code;
        // $blade = new Blade();
        $instace->render($view, []);
    }
}
