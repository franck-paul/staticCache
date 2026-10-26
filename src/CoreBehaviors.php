<?php

/**
 * @brief staticCache, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\staticCache;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Network\Http;
use Exception;

class CoreBehaviors
{
    public static function coreBlogAfterTriggerBlog(Cursor $cursor): string
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return '';
        }

        try {
            $cache_dir = defined('DC_SC_CACHE_DIR') && is_string($cache_dir = constant('DC_SC_CACHE_DIR')) ? $cache_dir : '';
            if ($cache_dir !== '') {
                $cache    = StaticCache::initFromURL($cache_dir, App::blog()->url());
                $datetime = is_string($datetime = $cursor->strField('blog_upddt', true)) ? $datetime : 'now';
                $cache->storeMtime((int) strtotime($datetime));

                // Add a log entry
                $curlog = App::log()->openLogCursor();
                $curlog->setField('log_msg', sprintf('Trigger blog for: %s at %s', App::blog()->url(), $datetime));
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
     * @param      ArrayObject<string, mixed>   $arrayObject  The result
     */
    public static function urlHandlerServeDocument(ArrayObject $arrayObject): string
    {
        if (!StaticCacheControl::cacheCurrentBlog()) {
            return '';
        }

        // Check requested URL
        $excluded = ['preview', 'pagespreview'];
        // @phpstan-ignore booleanAnd.rightAlwaysTrue (false positive about is_string() and already string constant)
        $config_excluded = defined('DC_SC_EXCLUDED_URL') && is_string($config_excluded = constant('DC_SC_EXCLUDED_URL')) ? $config_excluded : '';
        if ($config_excluded !== '') {
            $excluded = array_merge($excluded, explode(',', $config_excluded));
        }

        if (App::url()->isType($excluded)) {
            return '';
        }

        try {
            $cache_dir   = defined('DC_SC_CACHE_DIR')     && is_string($cache_dir = constant('DC_SC_CACHE_DIR')) ? $cache_dir : '';
            $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($request_uri = $_SERVER['REQUEST_URI']) ? $request_uri : '';
            if ($cache_dir !== '' && $request_uri !== '') {
                $staticCache = new StaticCache($cache_dir, md5(Http::getHost()));

                $do_cache = true;

                if ($_POST !== []) {
                    // We have POST data, no cache
                    $do_cache = false;
                } else {
                    $tpl = isset($arrayObject['tpl']) && is_string($tpl = $arrayObject['tpl']) ? $tpl : '';
                    if ($tpl !== '' && in_array($tpl, ['post.html', 'page.html'], true)) {
                        $password = App::frontend()->context()->posts instanceof MetaRecord ? App::frontend()->context()->posts->strField('post_password') : '';
                        if ($password !== '') {
                            // This is a post with a password, no cache
                            $do_cache = false;
                        }
                    }
                }

                if ($do_cache) {
                    $content_type = is_string($content_type = $arrayObject['content_type'] ?? '') ? $content_type : '';
                    $content      = is_string($content = $arrayObject['content'] ?? '') ? $content : '';
                    $blogupddt    = is_numeric($blogupddt = $arrayObject['blogupddt'] ?? null) ? (int) $blogupddt : null;

                    /**
                     * @var array<string>
                     */
                    $headers = is_array($headers = $arrayObject['headers'] ?? []) ? $headers : [];

                    if ($content_type !== ''
                        && $content   !== ''
                        && !is_null($blogupddt)
                    ) {
                        $staticCache->storePage(
                            $request_uri,
                            $content_type,
                            $content,
                            $blogupddt,
                            $headers
                        );
                    }
                } else {
                    // Remove cache file
                    $staticCache->dropPage($request_uri);
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
            $cache_dir   = defined('DC_SC_CACHE_DIR')     && is_string($cache_dir = constant('DC_SC_CACHE_DIR')) ? $cache_dir : '';
            $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($request_uri = $_SERVER['REQUEST_URI']) ? $request_uri : '';
            if ($cache_dir !== '' && $request_uri !== '') {
                $staticCache = new StaticCache($cache_dir, md5(Http::getHost()));
                $file        = $staticCache->getPageFile($request_uri);

                if ($file !== false) {
                    if (App::blog()->url() == Http::getSelfURI()) {
                        App::blog()->publishScheduledEntries();
                    }

                    Http::cache([$file], App::cache()->getTimes());
                    if ($staticCache->fetchPage($request_uri, App::blog()->upddt())) {
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
