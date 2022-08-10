<?php
/**
 * @brief staticCache, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Olivier
 * @author Franck Paul
 *
 * @copyright Olivier Meunier
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->addBehavior('dcMaintenanceInit', ['dcStaticCacheAdmin', 'dcMaintenanceInit']);

class dcStaticCacheAdmin
{
    public static function dcMaintenanceInit($maintenance)
    {
        $maintenance->addTask('dcMaintenanceCacheStatic');
    }
}

class dcMaintenanceCacheStatic extends dcMaintenanceTask
{
    protected $group = 'purge';

    protected function init()
    {
        $this->task    = __('Empty static cache directory');
        $this->success = __('Static cache directory emptied.');
        $this->error   = __('Failed to empty static cache directory.');

        $this->description = __("It may be useful to empty this cache when modifying a theme's .html or .css files (or when updating a theme or plugin). Notice : with some hosters, the templates cache cannot be emptied with this plugin.");
    }

    public function execute()
    {
        if (is_dir(DC_SC_CACHE_DIR)) {
            files::deltree(DC_SC_CACHE_DIR);
        }

        return true;
    }
}
