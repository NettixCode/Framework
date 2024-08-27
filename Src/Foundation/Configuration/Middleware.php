<?php

namespace Nettixcode\Framework\Foundation\Configuration;

class Middleware
{
    protected $middleware = [
        \Nettixcode\Framework\Middleware\AdminRestricted::class,
        \Nettixcode\Framework\Middleware\RateLimit::class
    ];

    protected $middlewareGroups = [
        'web' => [
            \Application\Middleware\CheckLoginStatus::class,
            \Nettixcode\Framework\Middleware\GenerateJwtToken::class
        ],
        'api' => [
            \Nettixcode\Framework\Middleware\VerifyCsrfToken::class,
            \Nettixcode\Framework\Middleware\VerifyJwtToken::class
        ],
    ];

    public function addMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
    }

    public function addMiddlewareToGroup($group, $middleware)
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }
        $this->middlewareGroups[$group][] = $middleware;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    public function redirectGuestsTo(callable|string $redirect)
    {
        return $this->redirectTo($redirect);
    }

    public function redirectTo($redirect)
    {
        if (is_callable($redirect)) {
            return call_user_func($redirect);
        } elseif (is_string($redirect)) {
            header('Location: ' . $redirect);
            exit;
        }
    
        throw new InvalidArgumentException('Invalid redirect parameter. Must be a callable or a string.');
    }
    
}
