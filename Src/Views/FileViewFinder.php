<?php

namespace Nettixcode\Framework\Views;

use Illuminate\View\FileViewFinder as BaseFileViewFinder;
use Nettixcode\Framework\Facades\Config;

class FileViewFinder extends BaseFileViewFinder
{
    protected $extensions = [];

    protected function __construct(){
        parent::__construct();
        $extensions = [Config::get('views.extension')];
    }

    protected function getPossibleViewFiles($name)
    {
        $possibleFiles = array_map(fn ($extension) => str_replace('.', '/', $name).'.'.$extension, $this->extensions);
        
        return $possibleFiles;
    }
}
