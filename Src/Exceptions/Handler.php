<?php

namespace Nettixcode\Framework\Exceptions;

use Exception;
use Throwable;
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

    public function report(Throwable $exception)
    {
        error_log($exception->getMessage());
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->handleNotFound($exception);
        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return $this->handleForbidden($exception);
        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $exception->getStatusCode() >= 500) {
            $handler->handleServerError($e);
        }
}

    public function handleError(Throwable $exception)
    {
        http_response_code(500);
        NxEngine::redirectToErrorPage(500);
        error_log($exception->getMessage()); // Log the exception message
        error_log($exception->getTraceAsString()); // Log the stack trace for more details (optional)
    }
    
    public function handleNotFound(Throwable $exception)
    {
        http_response_code(404);
        NxEngine::redirectToErrorPage(404);
        error_log($exception->getMessage()); // Log the exception message
        error_log($exception->getTraceAsString()); // Log the stack trace for more details (optional)
    }
    
    public function handleForbidden(Throwable $exception)
    {
        http_response_code(403);
        NxEngine::redirectToErrorPage(403);
        error_log($exception->getMessage());
        error_log($exception->getTraceAsString()); // Log the stack trace for more details (optional)
    }
    
    public function handleServerError(Throwable $exception)
    {
        http_response_code(500);
        NxEngine::redirectToErrorPage(500);
        error_log($exception->getMessage());
        error_log($exception->getTraceAsString()); // Log the stack trace for more details (optional)
    }
}
