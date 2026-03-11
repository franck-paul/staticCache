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

use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\staticCache\StaticCache;
use Dotclear\Plugin\staticCache\StaticCacheControl;

// Add plugin namespace as it is still not loaded yet
Autoloader::me()->addNamespace(
    implode(Autoloader::NS_SEP, ['', 'Dotclear', 'Plugin', basename(__DIR__)]),
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);

# This file needs to be called at the end of your configuration
# file. See README for more details

if (!defined('DC_SC_CACHE_ENABLE')) {
    define('DC_SC_CACHE_ENABLE', false);
}

if (!defined('DC_SC_CACHE_DIR') && defined('DC_TPL_CACHE') && is_string(DC_TPL_CACHE)) {
    define('DC_SC_CACHE_DIR', DC_TPL_CACHE . DIRECTORY_SEPARATOR . 'dcstaticcache');
}

if (!DC_SC_CACHE_ENABLE) {
    return;
}

# We need touch function
if (!function_exists('touch')) {
    return;
}

if (defined('DC_BLOG_ID')) { // Public area detection
    if (!StaticCacheControl::cacheCurrentBlog()) {
        return;
    }

    if ($_POST !== []) {
        return;
    }

    try {
        $static_cache_dir = is_string($static_cache_dir = constant('DC_SC_CACHE_DIR')) ? $static_cache_dir : '';
        if ($static_cache_dir !== '') {
            $static_cache = new StaticCache($static_cache_dir, md5(Http::getHost()));

            if (($static_cache_mtime = $static_cache->getMtime()) === false) {
                throw new Exception();
            }

            $static_cache_request_uri = isset($_SERVER['REQUEST_URI']) && is_string($static_cache_request_uri = $_SERVER['REQUEST_URI']) ? $static_cache_request_uri : '';
            if ($static_cache_request_uri !== '') {
                $static_cache_file = $static_cache->getPageFile($static_cache_request_uri);
                if ($static_cache_file !== false) {
                    Http::cache([$static_cache_file], [$static_cache_mtime]);
                    if ($static_cache->fetchPage($static_cache_request_uri, $static_cache_mtime)) {
                        exit;
                    }
                }
            }
        }
    } catch (Exception) {
    } finally {
        unset($static_cache, $static_cache_dir, $static_cache_mtime, $static_cache_file, $static_cache_request_uri);
    }
}
