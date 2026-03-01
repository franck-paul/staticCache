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
        $blog_id = is_string($blog_id) ? $blog_id : 'default';

        // DC_SC_CACHE_BLOGS_ON list blogs which MUST be statically cached
        $blogs_on = defined('DC_SC_CACHE_BLOGS_ON') && is_string($blogs_on = constant('DC_SC_CACHE_BLOGS_ON')) ? trim($blogs_on) : '';
        if ($blogs_on !== '' && !in_array($blog_id, explode(',', $blogs_on))) {
            // Current blog is not in the "ON" list
            return false;
        }

        // DC_SC_CACHE_BLOGS_OFF list blogs which MUST NOT be statically cached
        $blogs_off = defined('DC_SC_CACHE_BLOGS_OFF') && is_string($blogs_off = constant('DC_SC_CACHE_BLOGS_OFF')) ? trim($blogs_off) : '';
        if ($blogs_off !== '' && in_array($blog_id, explode(',', $blogs_off))) {
            // Current blog is in the "OFF" list
            return false;
        }

        // All unlisted blogs must be cached
        return true;
    }
}
