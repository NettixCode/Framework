<?php

namespace Nettixcode\Framework\Views;

use eftec\bladeone\BladeOne;
use Nettixcode\Framework\Facades\Config;
use Application\Providers\DebugBarProvider;

abstract class Blade
{
    protected $blade;
    protected $debugbar;
    protected $debugEnabled;

    public function __construct()
    {
        $views = Config::get('views.paths.pages');
        $cache = Config::get('views.paths.cache');
        $this->debugEnabled = Config::get('app.app_debug');

        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }

        // Buat instance BladeOne dengan beberapa direktori views
        $this->blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);
        $this->blade->setFileExtension(Config::get('views.extension'));

        // Tambahkan direktif custom untuk route
        $this->blade->directive('route', function ($expression) {
            return "<?php echo route($expression); ?>";
        });

        // Tambahkan direktif custom untuk asset
        $this->blade->directive('asset', function ($expression) {
            return "<?php echo asset($expression); ?>";
        });

        // Inisialisasi DebugBarProvider jika debug diaktifkan
        if ($this->debugEnabled) {
            $this->debugbar = new DebugBarProvider();
            $this->debugbar->enable();
        }
    }

    public function render($view, $data = [])
    {
        if ($this->debugEnabled) {
            $data['debugbarHead'] = $this->debugbar->renderHead();
            $data['debugbarRender'] = $this->debugbar->render();
        } else {
            $data['debugbarHead'] = '';
            $data['debugbarRender'] = '';
        }
        echo $this->blade->run($view, $data);
    }

    public function make($view, $data = [])
    {
        if ($this->debugEnabled) {
            $data['debugbarHead'] = $this->debugbar->renderHead();
            $data['debugbarRender'] = $this->debugbar->render();
        } else {
            $data['debugbarHead'] = '';
            $data['debugbarRender'] = '';
        }
        return $this->blade->run($view, $data);
    }
}
