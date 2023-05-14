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

use dcCore;
use dcNsProcess;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        if (!defined('DC_SC_CACHE_ENABLE')) {
            define('DC_SC_CACHE_ENABLE', false);
        }

        if (!defined('DC_SC_CACHE_DIR')) {
            define('DC_SC_CACHE_DIR', DC_TPL_CACHE . DIRECTORY_SEPARATOR . 'dcstaticcache');
        }

        static::$init = My::checkContext(My::PREPEND) && DC_SC_CACHE_ENABLE && function_exists('touch');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'urlHandlerServeDocument'  => [CoreBehaviors::class, 'urlHandlerServeDocument'],
            'publicBeforeDocumentV2'   => [CoreBehaviors::class, 'publicBeforeDocument'],
            'coreBlogAfterTriggerBlog' => [CoreBehaviors::class, 'coreBlogAfterTriggerBlog'],
        ]);

        return true;
    }
}
