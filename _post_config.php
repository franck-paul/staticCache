<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of staticCache, a plugin for Dotclear 2.
#
# Copyright (c) Olivier Meunier and contributors
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

# This file needs to be called at the end of your configuration
# file. See README for more details

if (!defined('DC_SC_CACHE_ENABLE')) {
	define('DC_SC_CACHE_ENABLE',false);
}

if (!defined('DC_SC_CACHE_DIR')) {
	define('DC_SC_CACHE_DIR',DC_TPL_CACHE.'/dcstaticcache');
}

if (!DC_SC_CACHE_ENABLE) {
	return;
}

# We need touch function
if (!function_exists('touch')) {
	return;
}

if (defined('DC_BLOG_ID')) // Public area detection
{
	require dirname(__FILE__).'/class.cache.php';

	if (!dcStaticCacheControl::cacheCurrentBlog()) {
		return;
	}

	if (!empty($_POST)) {
		return;
	}

	try
	{
		$cache = new dcStaticCache(DC_SC_CACHE_DIR,md5(http::getHost()));

		if (($mtime = $cache->getMtime()) === false) {
			throw new Exception;
		}

		$file = $cache->getPageFile($_SERVER['REQUEST_URI']);

		if ($file !== false)
		{
			http::cache(array($file),array($mtime));
			if ($cache->fetchPage($_SERVER['REQUEST_URI'],$mtime)) {
				exit;
			}
		}

		unset($cache);
	}
	catch (Exception $e) {
		unset($cache);
	}
}
