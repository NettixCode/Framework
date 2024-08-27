<?php

namespace Nettixcode\Framework\Collectors;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Nettixcode\Framework\Collectors\SimpleFormatter;
use Illuminate\View\View;
use Nettixcode\Framework\Facades\NxLog;

class ViewCollector extends DataCollector implements Renderable, AssetProvider
{
    protected $name;
    protected $templates = [];
    protected $collect_data;
    protected $exclude_paths;
    protected $group;

    /**
     * Create a ViewCollector
     *
     * @param bool|string $collectData Collects view data when true
     * @param string[] $excludePaths Paths to exclude from collection
     * @param int|bool $group Group the same templates together
     */
    public function __construct($collectData = true, $excludePaths = [], $group = true)
    {
        $this->setDataFormatter(new SimpleFormatter());
        $this->collect_data = $collectData;
        $this->templates = [];
        $this->exclude_paths = $excludePaths;
        $this->group = $group;
        NxLog::alert("FROM CONSTRUCT: " . json_encode($this->templates));
        NxLog::alert("FROM CONSTRUCT: " . count($this->templates));
    }
    
    public function getName()
    {
        return 'views';
    }

    public function getWidgets()
    {
        return [
            'views' => [
                'icon' => 'leaf',
                'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
                'map' => 'views',
                'default' => '[]'
            ],
            'views:badge' => [
                'map' => 'views.nb_templates',
                'default' => 0
            ]
        ];
    }

    public function getAssets()
    {
        return [
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js',
        ];
    }

    public function addView(View $view)
    {
        $name = $view->getName();
        $path = $view->getPath();
        $type = 'blade';
    
        // Hanya mengumpulkan kunci data jika collect_data = false
        $params = !$this->collect_data ? array_keys($view->getData()) : [];
        
        $hash = md5($type . $path . $name . implode('', $params));
        
        $template = [
            'name' => $name,
            'param_count' => count($params),
            'params' => $params,
            'start' => microtime(true),
            'type' => $type,
            'hash' => $hash,
        ];
        
        $this->templates[] = $template;
    }
    
    public function collect()
    {
        $templates = $this->templates;
    
        if ($this->group === true && count($templates) > 0) {
            $groupedTemplates = [];
            foreach ($templates as $template) {
                $hash = $template['hash'];
                if (!isset($groupedTemplates[$hash])) {
                    $template['render_count'] = 0;
                    $template['name_original'] = $template['name'];
                    $groupedTemplates[$hash] = $template;
                }
    
                $groupedTemplates[$hash]['render_count']++;
                $groupedTemplates[$hash]['name'] = $groupedTemplates[$hash]['render_count'] . 'x ' . $groupedTemplates[$hash]['name_original'];
            }
            $templates = array_values($groupedTemplates);
        }
    
        return [
            'nb_templates' => count($templates),
            'templates' => $templates,
        ];
    }
}
