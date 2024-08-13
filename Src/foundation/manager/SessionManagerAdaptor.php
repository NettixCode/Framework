<?php

namespace Nettixcode\Framework\Foundation\Manager;

use Nettixcode\Framework\Foundation\Manager\SessionManager;

class SessionManagerAdaptor
{
    protected $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function all()
    {
        return $this->sessionManager::all();
    }
}
