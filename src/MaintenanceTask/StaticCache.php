<?php
/**
 * @brief staticCache, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\staticCache\MaintenanceTask;

use Dotclear\Helper\File\Files;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class StaticCache extends MaintenanceTask
{
    protected string $group = 'purge';

    protected function init(): void
    {
        $this->task    = __('Empty static cache directory');
        $this->success = __('Static cache directory emptied.');
        $this->error   = __('Failed to empty static cache directory.');

        $this->description = __("It may be useful to empty this cache when modifying a theme's .html or .css files (or when updating a theme or plugin). Notice : with some hosters, the templates cache cannot be emptied with this plugin.");
    }

    public function execute()
    {
        if (is_dir(DC_SC_CACHE_DIR)) {
            Files::deltree(DC_SC_CACHE_DIR);
        }

        return true;
    }
}
