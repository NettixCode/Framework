<?php

namespace Nettixcode\Framework\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Collects info about the current request
 */
class RequestCollector extends DataCollector implements Renderable
{
    protected $cloner;
    protected $dumper;

    public function __construct()
    {
        $this->cloner = new VarCloner();
        $this->dumper = new HtmlDumper();
        $this->dumper->setDisplayOptions(['maxDepth' => 1]);
    }

    /**
     * @return array
     */
    public function collect()
    {
        $vars = array('_GET', '_POST', '_SESSION', '_COOKIE');
        $data = array();

        foreach ($vars as $var) {
            if (isset($GLOBALS[$var])) {
                $key = "$" . $var;
                $data[$key] = $this->renderVarDumper($GLOBALS[$var]);
            }
        }

        return $data;
    }

    protected function renderVarDumper($var)
    {
        $output = fopen('php://memory', 'r+b');
        $this->dumper->dump($this->cloner->cloneVar($var), $output);
        $contents = stream_get_contents($output, -1, 0);
        fclose($output);

        return $contents;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'request';
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        return array(
            "request" => array(
                "icon" => "tags",
                "widget" => "PhpDebugBar.Widgets.HtmlVariableListWidget",
                "map" => "request",
                "default" => "{}"
            )
        );
    }
}
