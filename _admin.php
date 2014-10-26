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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('dcMaintenanceInit', array('dcStaticCacheAdmin', 'dcMaintenanceInit'));

class dcStaticCacheAdmin
{
	 public static function dcMaintenanceInit($maintenance)
	{
		$maintenance->addTask('dcMaintenanceCacheStatic');
	}
}

class dcMaintenanceCacheStatic extends dcMaintenanceTask
{
	protected $group = 'purge';

	protected function init()
	{
		$this->task 		= __('Empty static cache directory');
		$this->success 		= __('Static cache directory emptied.');
		$this->error 		= __('Failed to empty static cache directory.');

		$this->description	= __("It may be useful to empty this cache when modifying a theme's .html or .css files (or when updating a theme or plugin). Notice : with some hosters, the templates cache cannot be emptied with this plugin.");
	}

	public function execute()
	{

		if (is_dir(DC_SC_CACHE_DIR)) {
			files::deltree(DC_SC_CACHE_DIR);
		}

		return true;
	}
}
