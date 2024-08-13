<?php

namespace Nettixcode\Framework\Foundation\Configuration;

use Closure;
use Exception;
use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Exceptions
{
    protected $app;
    protected $handler;
    protected $reportCallbacks = [];
    protected $renderCallbacks = [];
    protected $exceptions = [];

    // Define the exceptions that should not be reported
    protected $dontReport = [
        HttpException::class,
        // Add other exceptions here
    ];

    // Define the inputs that should not be flashed to session on validation errors
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function __construct(Handler $handler)
    {
        $this->app = Application::getInstance();
        $this->handler = $handler;
    }

    public function addExceptionHandler($exceptionHandler)
    {
        $this->exceptions[] = $exceptionHandler;
        $this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, $exceptionHandler);
        $this->app->exceptions = new $exceptionHandler;
    }

    public function getExceptions()
    {
        return $this->exceptions;
    }

    // Register a callback to report exceptions
    public function reportable(Closure $callback)
    {
        $this->reportCallbacks[] = $callback;
    }

    // Register a callback to render exceptions
    public function renderable(Closure $callback)
    {
        $this->renderCallbacks[] = $callback;
    }

    // Check if an exception should be reported
    protected function shouldReport(Exception $e)
    {
        return !in_array(get_class($e), $this->dontReport);
    }

    // Handle reporting the exception
    public function report(Exception $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        foreach ($this->reportCallbacks as $callback) {
            if ($callback($e) === false) {
                return;
            }
        }

        // Default reporting behavior
        $this->handler->report($e);
    }

    // Handle rendering the exception
    public function render($request, Exception $e)
    {
        foreach ($this->renderCallbacks as $callback) {
            if ($response = $callback($e, $request)) {
                return $response;
            }
        }

        // Default rendering behavior
        return $this->handler->render($request, $e);
    }

    // Handle the reporting process
    public function handleReport(Exception $e)
    {
        $this->report($e);
    }

    // Handle the rendering process
    public function handleRender($request, Exception $e)
    {
        return $this->render($request, $e);
    }

    // Add contextual information to the exception report
    protected function context()
    {
        return [
            'user_id' => $this->app->auth()->id(),
            // Add other contextual data here
        ];
    }
}
