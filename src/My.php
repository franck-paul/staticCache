<?php
/**
 * @brief staticCache, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Jean-Christian Denis, Franck Paul and contributors
 *
 * @copyright Jean-Christian Denis, Franck Paul
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\staticCache;

use dcCore;
use Dotclear\Module\MyPlugin;

/**
 * Plugin definitions
 */
class My extends MyPlugin
{
    /**
     * Check permission depending on given context
     *
     * @param      int   $context  The context
     *
     * @return     bool  true if allowed, else false
     */
    public static function checkCustomContext(int $context): ?bool
    {
        switch ($context) {
            case self::BACKEND:
                // Backend context
                // ---------------
                // As soon as a connected user should have access to at least one functionnality of the module
                // Note that PERMISSION_ADMIN implies all permissions on current blog

                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->auth->isSuperAdmin()   // Super-admin only
                ;

            case self::MANAGE:
                // Main page of module
                // -------------------
                // In almost all cases, only blog admin and super-admin should be able to manage a module

                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->auth->isSuperAdmin()   // Super-admin only
                ;

            case self::MENU:
                // Admin menu
                // ----------
                // In almost all cases, only blog admin and super-admin should be able to add a menuitem if
                // the main page of module is used for configuration, but it may be necessary to modify this
                // if the page is used to manage anything else

                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->auth->isSuperAdmin()   // Super-admin only
                ;

            case self::WIDGETS:
                // Blog widgets
                // ------------
                // In almost all cases, only blog admin and super-admin should be able to manage blog's widgets

                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->auth->isSuperAdmin()   // Super-admin only
                ;
        }

        return null;
    }
}
