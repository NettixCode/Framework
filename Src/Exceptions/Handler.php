<?php

namespace Nettixcode\Framework\Exceptions;

use Throwable;
use Illuminate\Support\Facades\Log;
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

    protected $reportCallbacks = [];
    protected $renderCallbacks = [];

    public function report(Throwable $exception)
    {
        if ($this->shouldReport($exception)) {
            foreach ($this->reportCallbacks as $callback) {
                if ($callback($exception) === false) {
                    return;
                }
            }
            Log::error($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function render($request, Throwable $exception)
    {
        foreach ($this->renderCallbacks as $callback) {
            if ($response = $callback($exception, $request)) {
                return $response;
            }
        }
    
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->handleNotFound($exception);
        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return $this->handleForbidden($exception);
        } elseif ($this->isHttpException($exception) && $exception->getStatusCode() >= 500) {
            return $this->handleServerError($exception);
        }
    
        if (!app('config')->get('app.app_debug')){
            $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
    
            return response()->view('errors.error-401', [
                'code' => $statusCode,
                'message' => $exception->getMessage(),
            ], $statusCode);
        }
    }
    
    public function reportable(callable $callback)
    {
        $this->reportCallbacks[] = $callback;
    }

    public function renderable(callable $callback)
    {
        $this->renderCallbacks[] = $callback;
    }

    public function dontReport($exceptionClass)
    {
        $this->dontReport[] = $exceptionClass;
    }

    public function dontFlash(array $attributes)
    {
        $this->dontFlash = array_merge($this->dontFlash, $attributes);
    }

    protected function shouldReport(Throwable $exception)
    {
        return !in_array(get_class($exception), $this->dontReport);
    }

    protected function isHttpException(Throwable $exception)
    {
        return $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException;
    }

    public function handleNotFound(Throwable $exception)
    {
        http_response_code(404);
        NxEngine::redirectToErrorPage(404);
        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString());
        return response()->view('errors.error-404', [], 404);
    }

    public function handleForbidden(Throwable $exception)
    {
        http_response_code(403);
        NxEngine::redirectToErrorPage(403);
        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString());
        return response()->view('errors.error-403', [], 403);
    }

    public function handleServerError(Throwable $exception)
    {
        http_response_code(500);
        NxEngine::redirectToErrorPage(500);
        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString());
        return response()->view('errors.error-500', [], 500);
    }
}
