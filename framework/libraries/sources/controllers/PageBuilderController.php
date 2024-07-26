<?php

namespace Nettixcode\Framework\Libraries\Sources\Controllers;

use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Libraries\Sources\Facades\Config;
use Nettixcode\Framework\Libraries\NxEngine;
use Nettixcode\Framework\Libraries\Sources\Facades\User;

class PageBuilderController
{
    public function index()
    {
        User::has('role','admin') ?
        NxEngine::view('admin.page-builder'):
        NxEngine::view('errors.error-403');
    }

    public function page_builder_save(Request $request)
    {
        $pageName = $request->input('name');
        $content  = $request->input('content');
        $jscontent  = $request->input('jscontent');

        $filename = strtolower(str_replace(' ', '_', $pageName)) . '.nxcode.php';
        $jsname = strtolower(str_replace(' ', '_', $pageName)) . '.js';
        $filepath = Config::load('app', 'paths.public_pages') . '/' . $filename;
        $jspath = Config::load('app', 'paths.public_js') . '/' . $jsname;
        

        file_put_contents($filepath, $content);
        file_put_contents($jspath, $jscontent);

        return response()->json(['success' => true, 'filename' => $filename]);
    }
}
