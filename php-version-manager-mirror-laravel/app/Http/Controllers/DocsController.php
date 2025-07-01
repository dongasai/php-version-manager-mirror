<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocsController extends Controller
{
    /**
     * 显示文档页面
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('docs', [
            'breadcrumbs' => [
                ['name' => '首页', 'path' => '/'],
                ['name' => '文档', 'path' => '/docs'],
            ],
        ]);
    }
}
