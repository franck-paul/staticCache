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

namespace Dotclear\Plugin\staticCache;

use Dotclear\Plugin\staticCache\MaintenanceTask\StaticCache;

class BackendBehaviors
{
    /**
     * Add maintenance task to delete static cache
     *
     * @param  \dcMaintenance $maintenance
     */
    public static function dcMaintenanceInit($maintenance)
    {
        $maintenance->addTask(StaticCache::class);
    }
}
