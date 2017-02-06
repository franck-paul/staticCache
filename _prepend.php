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

if (!defined('DC_RC_PATH')) { return; }

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

$GLOBALS['__autoload']['dcStaticCache'] = dirname(__FILE__).'/class.cache.php';
$GLOBALS['__autoload']['dcStaticCacheControl'] = dirname(__FILE__).'/class.cache.php';

$core->addBehavior('urlHandlerServeDocument',array('dcStaticCacheBehaviors','urlHandlerServeDocument'));
$core->addBehavior('publicBeforeDocument',array('dcStaticCacheBehaviors','publicBeforeDocument'));
$core->addBehavior('coreBlogAfterTriggerBlog',array('dcStaticCacheBehaviors','coreBlogAfterTriggerBlog'));

class dcStaticCacheBehaviors
{
	public static function coreBlogAfterTriggerBlog($cur)
	{
		if (!dcStaticCacheControl::cacheCurrentBlog()) {
			return;
		}

		try
		{
			$cache = dcStaticCache::initFromURL(DC_SC_CACHE_DIR,$GLOBALS['core']->blog->url);
			$cache->storeMtime(strtotime($cur->blog_upddt));
		}
		catch (Exception $e) {}
	}

	public static function urlHandlerServeDocument($result)
	{
		if (!dcStaticCacheControl::cacheCurrentBlog()) {
			return;
		}

		# Check requested URL
		$excluded = array('preview','pagespreview');
		if (defined('DC_SC_EXCLUDED_URL')) {
			$excluded = array_merge($excluded,explode(',',DC_SC_EXCLUDED_URL));
		}
		if (in_array($GLOBALS['core']->url->type,$excluded)) {
			return;
		}

		try
		{
			$cache = new dcStaticCache(DC_SC_CACHE_DIR,md5(http::getHost()));

			$do_cache = true;

			# We have POST data, no cache
			if (!empty($_POST)) {
				$do_cache = false;
			}

			# This is a post with a password, no cache
			if (($result['tpl'] == 'post.html' || $result['tpl'] == 'page.html') && $GLOBALS['_ctx']->posts->post_password) {
				$do_cache = false;
			}

			if ($do_cache)
			{
				# No POST data or COOKIE, do cache
				$cache->storePage(
					$_SERVER['REQUEST_URI'],
					$result['content_type'],
					$result['content'],
					$result['blogupddt'],
					$result['headers']);
			}
			else
			{
				# Remove cache file
				$cache->dropPage($_SERVER['REQUEST_URI']);
			}
		}
		catch (Exception $e) {}
	}

	public static function publicBeforeDocument($core)
	{
		if (!dcStaticCacheControl::cacheCurrentBlog()) {
			return;
		}

		if (!empty($_POST)) {
			return;
		}

		try
		{
			$cache = new dcStaticCache(DC_SC_CACHE_DIR,md5(http::getHost()));
			$file = $cache->getPageFile($_SERVER['REQUEST_URI']);

			if ($file !== false)
			{
				if ($core->blog->url == http::getSelfURI()) {
					$core->blog->publishScheduledEntries();
				}
				http::cache(array($file),$GLOBALS['mod_ts']);
				if ($cache->fetchPage($_SERVER['REQUEST_URI'],$GLOBALS['core']->blog->upddt)) {
					exit;
				}
			}
		}
		catch (Exception $e) {}
	}
}
