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

$core->addBehavior('urlHandlerServeDocument',array('dcStaticCacheBehaviors','urlHandlerServeDocument'));
$core->addBehavior('publicBeforeDocument',array('dcStaticCacheBehaviors','publicBeforeDocument'));
$core->addBehavior('coreBlogAfterTriggerBlog',array('dcStaticCacheBehaviors','coreBlogAfterTriggerBlog'));

class dcStaticCacheBehaviors
{
	public static function cacheCurrentBlog()
	{
		$ret = true;	// All blogs should be cached

		if (defined('DC_SC_CACHE_BLOGS_ON')) {
			if (DC_SC_CACHE_BLOGS_ON != '') {
				// Only some blogs should be cached
				if (!in_array(DC_BLOG_ID, explode(',', DC_SC_CACHE_BLOGS_ON))) {
					// Current blog is not in the "ON" list
					$ret = false;
				}
			}
		}
		if (defined('DC_SC_CACHE_BLOGS_OFF')) {
			if (DC_SC_CACHE_BLOGS_OFF != '') {
				// Some blogs should not be cached
				if (in_array(DC_BLOG_ID, explode(',', DC_SC_CACHE_BLOGS_OFF))) {
					// Current blog is in the "OFF" list
					$ret = false;
				}
			}
		}

		return $ret;
	}

	public static function coreBlogAfterTriggerBlog($cur)
	{
		if (!dcStaticCacheBehaviors::cacheCurrentBlog()) {
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
		if (!dcStaticCacheBehaviors::cacheCurrentBlog()) {
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
			if ($result['tpl'] == 'post.html' && $GLOBALS['_ctx']->posts->post_password) {
				$do_cache = false;
			}

			if ($do_cache)
			{
				# No POST data or COOKIE, do cache
				$cache->storePage($_SERVER['REQUEST_URI'],$result['content_type'],$result['content'],$result['blogupddt']);
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
		if (!dcStaticCacheBehaviors::cacheCurrentBlog()) {
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
?>
