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

# This file needs to be called at the end of your configuration
# file. See README for more details

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

if (defined('DC_BLOG_ID')) { // Public area detection
    require __DIR__ . '/class.cache.php';

    if (!dcStaticCacheControl::cacheCurrentBlog()) {
        return;
    }

    if (!empty($_POST)) {
        return;
    }

    try {
        $cache = new dcStaticCache(DC_SC_CACHE_DIR, md5(http::getHost()));

        if (($mtime = $cache->getMtime()) === false) {
            throw new Exception();
        }

        $file = $cache->getPageFile($_SERVER['REQUEST_URI']);

        if ($file !== false) {
            http::cache([$file], [$mtime]);
            if ($cache->fetchPage($_SERVER['REQUEST_URI'], $mtime)) {
                exit;
            }
        }

        unset($cache);
    } catch (Exception $e) {
        unset($cache);
    }
}
