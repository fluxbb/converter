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

	function convert_topic_subscriptions($db, $fluxbb)
	{
		message('%s: Not implemented', __FUNCTION__);
	}

	function convert_forum_subscriptions($db, $fluxbb)
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

	// Add default guest user when it does not exist
	function check_users($db, $fluxbb)
	{
		$result = $fluxbb->db->query_build(array(
			'SELECT'	=> 'id',
			'FROM'		=> 'users',
			'WHERE'		=> 'id <> 1'
		));

		if (!$fluxbb->db->num_rows($result))
		{
			$guest_user = array(
				'id'		=> 1,
				'group_id'	=> 3,
				'username'	=> 'Guest',
			);

			$fluxbb->add_row('users', $guest_user, true);
		}
	}

	// Add default user groups when they do not exist
	function check_groups($db, $fluxbb)
	{
		$default_groups = array(
			1 => array(
				'g_id'		=> 1,
				'g_title'	=> 'Administrator'
			),
			2 => array(
				'g_id'		=> 2,
				'g_title'	=> 'Moderator',
				'g_moderator'=> 1,
			),
			3 => array(
				'g_id'		=> 3,
				'g_title'	=> 'Guest'
			),
			4 => array(
				'g_id'		=> 4,
				'g_title'	=> 'Member'
			),
		);

		$result = $fluxbb->db->query_build(array(
			'SELECT'	=> 'g_id',
			'FROM'		=> 'groups',
			'WHERE'		=> 'g_id IN (1, 2, 3, 4)'
		));

		$existing_groups = array();
		while ($cur_group = $fluxbb->db->fetch_assoc($result))
			$existing_groups[] = $cur_group['g_id'];

		foreach ($default_groups as $g_id => $cur_group)
		{
			if (!in_array($g_id, $existing_groups))
				$fluxbb->add_row('groups', $cur_group, true);
		}
	}

}
