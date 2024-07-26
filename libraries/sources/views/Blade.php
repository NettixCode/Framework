<?php

namespace Nettixcode\Framework\Libraries\Sources\Views;

use eftec\bladeone\BladeOne;
use Nettixcode\Framework\Libraries\Sources\Facades\Config;

abstract class Blade
{
    protected $blade;

    public function __construct()
    {
        $viewsBase = Config::load('view','paths.base');
        $viewsPage = Config::load('view','paths.pages');
        $cache     = Config::load('view','paths.cache');

        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }

        // Buat instance BladeOne dengan beberapa direktori views
        $this->blade = new BladeOne([$viewsBase, $viewsPage], $cache, BladeOne::MODE_DEBUG);
        $this->blade->setFileExtension(Config::load('view','extension'));

        // Tambahkan direktif custom untuk route
        $this->blade->directive('route', function ($expression) {
            return "<?php echo route($expression); ?>";
        });

        // Tambahkan direktif custom untuk asset
        $this->blade->directive('asset', function ($expression) {
            return "<?php echo asset($expression); ?>";
        });
    }

    public function render($view, $data = [])
    {
        echo $this->blade->run($view, $data);
    }

    public function make($view, $data = [])
    {
        return $this->blade->run($view, $data);
    }
}
