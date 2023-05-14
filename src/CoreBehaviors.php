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
use Dotclear\Helper\Network\Http;
use Exception;

class CoreBehaviors
{
    public static function coreBlogAfterTriggerBlog($cur)
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return;
        }

        try {
            $cache = StaticCache::initFromURL(DC_SC_CACHE_DIR, dcCore::app()->blog->url);
            $cache->storeMtime(strtotime($cur->blog_upddt));
        } catch (Exception $e) {
            // Ignore exceptions
        }
    }

    public static function urlHandlerServeDocument($result)
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return;
        }

        # Check requested URL
        $excluded = ['preview', 'pagespreview'];
        if (defined('DC_SC_EXCLUDED_URL')) {
            $excluded = array_merge($excluded, explode(',', DC_SC_EXCLUDED_URL));
        }
        if (in_array(dcCore::app()->url->type, $excluded)) {
            return;
        }

        try {
            $cache = new StaticCache(DC_SC_CACHE_DIR, md5(Http::getHost()));

            $do_cache = true;

            # We have POST data, no cache
            if (!empty($_POST)) {
                $do_cache = false;
            }

            # This is a post with a password, no cache
            if (($result['tpl'] == 'post.html' || $result['tpl'] == 'page.html') && dcCore::app()->ctx->posts->post_password) {
                $do_cache = false;
            }

            if ($do_cache) {
                # No POST data or COOKIE, do cache
                $cache->storePage(
                    $_SERVER['REQUEST_URI'],
                    $result['content_type'],
                    $result['content'],
                    $result['blogupddt'],
                    $result['headers']
                );
            } else {
                # Remove cache file
                $cache->dropPage($_SERVER['REQUEST_URI']);
            }
        } catch (Exception $e) {
            // Ignore exceptions
        }
    }

    public static function publicBeforeDocument()
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return;
        }

        if (!empty($_POST)) {
            return;
        }

        try {
            $cache = new StaticCache(DC_SC_CACHE_DIR, md5(Http::getHost()));
            $file  = $cache->getPageFile($_SERVER['REQUEST_URI']);

            if ($file !== false) {
                if (dcCore::app()->blog->url == Http::getSelfURI()) {
                    dcCore::app()->blog->publishScheduledEntries();
                }
                Http::cache([$file], dcCore::app()->cache['mod_ts']);
                if ($cache->fetchPage($_SERVER['REQUEST_URI'], dcCore::app()->blog->upddt)) {
                    exit;
                }
            }
        } catch (Exception $e) {
            // Ignore exceptions
        }
    }
}