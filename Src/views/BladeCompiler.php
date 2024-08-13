<?php

namespace Nettixcode\Framework\Views;

use Illuminate\View\Compilers\BladeCompiler as BaseBladeCompiler;

class BladeCompiler extends BaseBladeCompiler
{
    public function __construct($files, $cachePath)
    {
        parent::__construct($files, $cachePath);
        
    }

    public function getCompiledPath($path)
    {
        $compiled = parent::getCompiledPath($path);
        
        return $compiled;
    }

    public function compile($path = null)
    {
        parent::compile($path);
        
    }
}
