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
if (!defined('DC_RC_PATH')) {
    return;
}

if (!defined('DC_SC_CACHE_ENABLE')) {
    define('DC_SC_CACHE_ENABLE', false);
}

if (!defined('DC_SC_CACHE_DIR')) {
    define('DC_SC_CACHE_DIR', DC_TPL_CACHE . '/dcstaticcache');
}

if (!DC_SC_CACHE_ENABLE) {
    return;
}

# We need touch function
if (!function_exists('touch')) {
    return;
}

$GLOBALS['__autoload']['dcStaticCache']        = __DIR__ . '/class.cache.php';
$GLOBALS['__autoload']['dcStaticCacheControl'] = __DIR__ . '/class.cache.php';

dcCore::app()->addBehavior('urlHandlerServeDocument', ['dcStaticCacheBehaviors', 'urlHandlerServeDocument']);
dcCore::app()->addBehavior('publicBeforeDocument', ['dcStaticCacheBehaviors', 'publicBeforeDocument']);
dcCore::app()->addBehavior('coreBlogAfterTriggerBlog', ['dcStaticCacheBehaviors', 'coreBlogAfterTriggerBlog']);

class dcStaticCacheBehaviors
{
    public static function coreBlogAfterTriggerBlog($cur)
    {
        if (!dcStaticCacheControl::cacheCurrentBlog()) {
            return;
        }

        try {
            $cache = dcStaticCache::initFromURL(DC_SC_CACHE_DIR, dcCore::app()->blog->url);
            $cache->storeMtime(strtotime($cur->blog_upddt));
        } catch (Exception $e) {
        }
    }

    public static function urlHandlerServeDocument($result)
    {
        if (!dcStaticCacheControl::cacheCurrentBlog()) {
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
            $cache = new dcStaticCache(DC_SC_CACHE_DIR, md5(http::getHost()));

            $do_cache = true;

            # We have POST data, no cache
            if (!empty($_POST)) {
                $do_cache = false;
            }

            # This is a post with a password, no cache
            if (($result['tpl'] == 'post.html' || $result['tpl'] == 'page.html') && $GLOBALS['_ctx']->posts->post_password) {
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
        }
    }

    public static function publicBeforeDocument($core = null)
    {
        if (!dcStaticCacheControl::cacheCurrentBlog()) {
            return;
        }

        if (!empty($_POST)) {
            return;
        }

        try {
            $cache = new dcStaticCache(DC_SC_CACHE_DIR, md5(http::getHost()));
            $file  = $cache->getPageFile($_SERVER['REQUEST_URI']);

            if ($file !== false) {
                if (dcCore::app()->blog->url == http::getSelfURI()) {
                    dcCore::app()->blog->publishScheduledEntries();
                }
                http::cache([$file], $GLOBALS['mod_ts']);
                if ($cache->fetchPage($_SERVER['REQUEST_URI'], dcCore::app()->blog->upddt)) {
                    exit;
                }
            }
        } catch (Exception $e) {
        }
    }
}
