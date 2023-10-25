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

use Dotclear\App;
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

if (!defined('DC_SC_CACHE_DIR') && defined('DC_TPL_CACHE')) {
    define('DC_SC_CACHE_DIR', DC_TPL_CACHE /* App::config()->cacheRoot() */ . DIRECTORY_SEPARATOR . 'dcstaticcache');
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

    if (!empty($_POST)) {
        return;
    }

    try {
        if (defined('DC_SC_CACHE_DIR')) {
            $cache = new StaticCache(DC_SC_CACHE_DIR, md5(Http::getHost()));

            if (($mtime = $cache->getMtime()) === false) {
                throw new Exception();
            }

            $file = $cache->getPageFile($_SERVER['REQUEST_URI']);

            if ($file !== false) {
                Http::cache([(string) $file], [$mtime]);
                if ($cache->fetchPage($_SERVER['REQUEST_URI'], $mtime)) {
                    exit;
                }
            }
        }
    } catch (Exception) {
    } finally {
        unset($cache);
    }
}
