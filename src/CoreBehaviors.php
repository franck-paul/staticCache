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

use ArrayObject;
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
            if (defined('DC_SC_CACHE_DIR')) {
                $cache = StaticCache::initFromURL(DC_SC_CACHE_DIR, App::blog()->url());
                $cache->storeMtime((int) strtotime($cur->blog_upddt));

                // Add a log entry
                $curlog = App::log()->openLogCursor();
                $curlog->setField('log_msg', sprintf('Trigger blog for: %s at %s', App::blog()->url(), $cur->blog_upddt));
                $curlog->setField('log_table', My::id());
                $curlog->setField('user_id', App::auth()->userID());
                App::log()->addLog($curlog);
            }
        } catch (Exception) {
            // Ignore exceptions
        }

        return '';
    }

    /**
     * @param      ArrayObject<string, mixed>   $result  The result
     */
    public static function urlHandlerServeDocument(ArrayObject $result): string
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return '';
        }

        # Check requested URL
        $excluded = ['preview', 'pagespreview'];
        if (defined('DC_SC_EXCLUDED_URL')) {
            $excluded = array_merge($excluded, explode(',', (string) DC_SC_EXCLUDED_URL));
        }

        if (in_array(App::url()->getType(), $excluded)) {
            return '';
        }

        try {
            if (defined('DC_SC_CACHE_DIR')) {
                $cache = new StaticCache(DC_SC_CACHE_DIR, md5(Http::getHost()));

                $do_cache = true;

                # We have POST data, no cache
                if ($_POST !== []) {
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

        if ($_POST !== []) {
            return '';
        }

        try {
            if (defined('DC_SC_CACHE_DIR')) {
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
            }
        } catch (Exception) {
            // Ignore exceptions
        }

        return '';
    }
}
