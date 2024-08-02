<?php

namespace Nettixcode\Framework\Exceptions;

use Exception;
use Nettixcode\Framework\Facades\NxEngine;

class Handler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'new_password',
        'old_password',
        'password_confirmation',
    ];

    public function report(Exception $exception)
    {
        error_log($exception->getMessage());
    }

    public function render($request, Exception $exception)
    {
        if ($exception instanceof NotFoundHttpException) {
            return $this->handleNotFound($exception->getMessage());
        }

        return $this->handleServerError($exception->getMessage());
    }

    public function handleNotFound($message = '404 Not Found')
    {
        http_response_code(404);
        NxEngine::redirectToErrorPage(404);
        error_log($message);
    }

    public function handleForbidden($message = '403 Forbidden')
    {
        http_response_code(403);
        NxEngine::redirectToErrorPage(403);
        error_log($message);
    }

    public function handleServerError($message = '500 Internal Server Error')
    {
        http_response_code(500);
        NxEngine::redirectToErrorPage(500);
        error_log($message);
    }
}
