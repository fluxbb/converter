<?php

class Forum
{
	var $default_lang;
	var $default_style;
	var $base_url;
	var $new_config = array();

	function init_config($db, $forum_config)
	{
		$this->default_lang = $forum_config['default_lang'];
		$this->default_style = $forum_config['default_style'];
		$this->base_url = $forum_config['base_url'];

		$this->initialize($db);

		$this->new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
//			'o_searchindex_revision'	=> FORUM_SI_REVISION,
//			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> 'My FluxBB Forum',
			'o_board_desc'				=> 'Unfortunately no one can be told what FluxBB is - you have to see it for yourself.',
			'o_default_timezone'		=> 0,
			'o_time_format'				=> 'H:i:s',
			'o_date_format'				=> 'Y-m-d',
			'o_timeout_visit'			=> 1800,
			'o_timeout_online'			=> 300,
			'o_redirect_delay'			=> 1,
			'o_show_version'			=> 0,
			'o_show_user_info'			=> 1,
			'o_show_post_count'			=> 1,
			'o_signatures'				=> 1,
			'o_smilies'					=> 1,
			'o_smilies_sig'				=> 1,
			'o_make_links'				=> 1,
			'o_default_lang'			=> $this->default_lang,
			'o_default_style'			=> $this->default_style,
			'o_default_user_group'		=> 4,
			'o_topic_review'			=> 15,
			'o_disp_topics_default'		=> 30,
			'o_disp_posts_default'		=> 25,
			'o_indent_num_spaces'		=> 4,
			'o_quote_depth'				=> 3,
			'o_quickpost'				=> 1,
			'o_users_online'			=> 1,
			'o_censoring'				=> 0,
			'o_ranks'					=> 1,
			'o_show_dot'				=> 0,
			'o_topic_views'				=> 1,
			'o_quickjump'				=> 1,
			'o_gzip'					=> 0,
			'o_additional_navlinks'		=> '',
			'o_report_method'			=> 0,
			'o_regs_report'				=> 0,
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> '',
			'o_avatars'					=> '',// TODO: $avatars,
			'o_avatars_dir'				=> 'img/avatars',
			'o_avatars_width'			=> 60,
			'o_avatars_height'			=> 60,
			'o_avatars_size'			=> 10240,
			'o_search_all_forums'		=> 1,
			'o_base_url'				=> $this->base_url,
			'o_admin_email'				=> '',// TODO: $email,
			'o_webmaster_email'			=> '',// TODO: $email,
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> "NULL",
			'o_smtp_user'				=> "NULL",
			'o_smtp_pass'				=> "NULL",
			'o_smtp_ssl'				=> 0,
			'o_regs_allow'				=> 1,
			'o_regs_verify'				=> 0,
			'o_announcement'			=> 0,
			'o_announcement_message'	=> 'Enter your announcement here.',
			'o_rules'					=> 0,
			'o_rules_message'			=> 'Enter your rules here',
			'o_maintenance'				=> 0,
			'o_maintenance_message'		=> 'The forums are temporarily down for maintenance. Please try again in a few minutes.',
			'o_default_dst'				=> 0,
			'o_feed_type'				=> 2,
			'o_feed_ttl'				=> 0,
			'p_message_bbcode'			=> 1,
			'p_message_img_tag'			=> 1,
			'p_message_all_caps'		=> 1,
			'p_subject_all_caps'		=> 1,
			'p_sig_all_caps'			=> 1,
			'p_sig_bbcode'				=> 1,
			'p_sig_img_tag'				=> 0,
			'p_sig_length'				=> 400,
			'p_sig_lines'				=> 4,
			'p_allow_banned_email'		=> 1,
			'p_allow_dupe_email'		=> 0,
			'p_force_guest_email'		=> 1
		);
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
