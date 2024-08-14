<?php

namespace Nettixcode\Framework\Controllers;

use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Facades\NxEngine;
use Nettixcode\Framework\Facades\User;

class PageBuilderController
{
    public function index()
    {
        debug_send('messages','viewing page builder pages.');
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
        $filepath = Config::get('app.paths.public_pages') . '/' . $filename;
        $jspath = Config::get('app.paths.public_js') . '/' . $jsname;
        

        file_put_contents($filepath, $content);
        file_put_contents($jspath, $jscontent);

        return response()->json(['success' => true, 'filename' => $filename]);
    }
}
