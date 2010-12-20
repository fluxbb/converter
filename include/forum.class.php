<?php

class Forum
{
	var $default_lang;
	var $default_style;
	var $base_url;

	function init_config($db, $forum_config)
	{
		$this->default_lang = $forum_config['default_lang'];
		$this->default_style = $forum_config['default_style'];
		$this->base_url = $forum_config['base_url'];

		$this->initialize($db);
	}

	function initialize($db)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_bans($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_categories($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_censoring($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_config($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_forums($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_forum_perms($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_groups($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_posts($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_ranks($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_reports($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_subscriptions($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_topics($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_users($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}
}
