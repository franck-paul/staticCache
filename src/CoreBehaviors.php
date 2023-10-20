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
use Dotclear\Database\Cursor;
use Dotclear\Helper\Network\Http;
use Exception;

class CoreBehaviors
{
    public static function coreBlogAfterTriggerBlog(Cursor $cur): string
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return '';
        }

        try {
            $cache = StaticCache::initFromURL(DC_SC_CACHE_DIR, App::blog()->url());
            $cache->storeMtime(strtotime($cur->blog_upddt));
        } catch (Exception) {
            // Ignore exceptions
        }

        return '';
    }

    /**
     * @param      array<string, mixed>   $result  The result
     *
     * @return     string
     */
    public static function urlHandlerServeDocument(array $result): string
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return '';
        }

        # Check requested URL
        $excluded = ['preview', 'pagespreview'];
        if (defined('DC_SC_EXCLUDED_URL')) {
            $excluded = array_merge($excluded, explode(',', DC_SC_EXCLUDED_URL));
        }
        if (in_array(App::url()->type, $excluded)) {
            return '';
        }

        try {
            $cache = new StaticCache(DC_SC_CACHE_DIR, md5(Http::getHost()));

            $do_cache = true;

            # We have POST data, no cache
            if (!empty($_POST)) {
                $do_cache = false;
            }

            # This is a post with a password, no cache
            if (($result['tpl'] == 'post.html' || $result['tpl'] == 'page.html') && App::frontend()->context()->posts->post_password) {
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
        } catch (Exception) {
            // Ignore exceptions
        }

        return '';
    }

    public static function publicBeforeDocumentV2(): string
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return '';
        }

        if (!empty($_POST)) {
            return '';
        }

        try {
            $cache = new StaticCache(DC_SC_CACHE_DIR, md5(Http::getHost()));
            $file  = $cache->getPageFile($_SERVER['REQUEST_URI']);

            if ($file !== false) {
                if (App::blog()->url() == Http::getSelfURI()) {
                    App::blog()->publishScheduledEntries();
                }
                Http::cache([(string) $file], App::cache()->getTimes());
                if ($cache->fetchPage($_SERVER['REQUEST_URI'], App::blog()->upddt())) {
                    exit;
                }
            }
        } catch (Exception) {
            // Ignore exceptions
        }

        return '';
    }
}
