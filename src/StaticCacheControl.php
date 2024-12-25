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

use Dotclear\App;

class StaticCacheControl
{
    public static function cacheCurrentBlog(): bool
    {
        // DC_BLOG_ID defined : public, otherwise admin
        $blog_id = (defined('DC_BLOG_ID') ? DC_BLOG_ID : App::blog()->id());

        // Only some blogs should be cached
        if (defined('DC_SC_CACHE_BLOGS_ON') && constant('DC_SC_CACHE_BLOGS_ON') != '' && !in_array($blog_id, explode(',', (string) constant('DC_SC_CACHE_BLOGS_ON')))) {
            // Current blog is not in the "ON" list
            return false;
        }

        // Some blogs should not be cached
        if (defined('DC_SC_CACHE_BLOGS_OFF') && constant('DC_SC_CACHE_BLOGS_OFF') != '' && in_array($blog_id, explode(',', (string) constant('DC_SC_CACHE_BLOGS_OFF')))) {
            // Current blog is in the "OFF" list
            return false;
        }

        // All blogs should be cached
        return true;
    }
}
