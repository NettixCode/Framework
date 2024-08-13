<?php

namespace Nettixcode\Framework\Collectors;

use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use Illuminate\View\View;

class ViewCollector extends DataCollector implements Renderable, AssetProvider
{
    protected $views = [];

    public function addView(View $view)
    {
        $this->views[] = [
            'name' => $view->getName(),
            'path' => $view->getPath(),
        ];
        error_log('Adding view: ' . $view->getName() . ' with path: ' . $view->getPath());
    }

    public function collect()
    {
        error_log('Collecting views data...');
        $templates = array_map(function($view) {
            return $view['name'] . ' (' . $view['path'] . ')';
        }, $this->views);

        error_log('Templates: ' . json_encode($templates));

        $data = [
            'nb_templates' => count($this->views),
            'templates' => $templates,
        ];

        error_log('Collected data: ' . json_encode($data));
        return $data;
    }

    public function getName()
    {
        return 'views';
    }

    public function getWidgets()
    {
        return [
            "Views" => [
                "icon" => "leaf",
                "widget" => "PhpDebugBar.Widgets.TemplatesWidget",
                "map" => "views.templates",
                "default" => "{}"
            ],
            "Views:badge" => [
                "map" => "views.nb_templates",
                "default" => 0
            ]
        ];
    }

    public function getAssets()
    {
        return [
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js'
        ];
    }
}
