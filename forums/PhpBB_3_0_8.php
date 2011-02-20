<?php

define('FORUM_VERSION', '1.4');
define('FORUM_DB_REVISION', 2);

class PhpBB_3_0_8 extends Forum
{
	// TODO: Prefix!!!
	function initialize($db)
	{
		$db->set_names('utf8');
	}

	function query_build($db, $array)
	{
		$array['PARAMS']['NO_PREFIX'] = true;

		return $db->query_build($array);
	}

	function convert_bans($db, $fluxbb)
	{
		$result = $this->query_build($db, array(
			'SELECT'	=> 'ban_id AS id, username, ban_ip AS ip, ban_email AS email, ban_reason AS message, ban_end AS expire, ban_creator',
			'FROM'		=> 'phpbb_banlist',
		));

		message('Processing %d bans', $db->num_rows($result));
		while ($cur_ban = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories($db, $fluxbb)
	{
		// FIXME: Subforums might cause difficulties
		$result = $db->query_build(array(
			'SELECT'	=> 'forum_id AS id, forum_name AS cat_name',
			'FROM'		=> 'phpbb_forums',
			'WHERE'		=> 'forum_type = 0',
			'ORDER BY'	=> 'left_id ASC'
		));

		message('Processing %d categories', $db->num_rows($result));
		$i = 1;
		while ($cur_cat = $db->fetch_assoc($result))
		{
			$cur_cat['disp_position'] = $i;
			$fluxbb->add_row('categories', $cur_cat);
			$i++;
		}
	}

	function convert_censoring($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'word_id AS id, word AS search_for, replacement AS replace_with',
			'FROM'		=> 'phpbb_words',
		));

		message('Processing %d censors', $db->num_rows($result));
		while ($cur_censor = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('censoring', $cur_censor);
		}
	}

	// TODO
	function convert_config($db, $fluxbb)
	{
		$old_config = array();
		
		$result = $db->query_build(array(
			'SELECT'	=> 'conf_name, conf_value',
			'FROM'		=> 'config',
		));
		
		message('Processing config');
		while ($cur_config = $db->fetch_assoc($result))
			$old_config[$cur_config['conf_name']] = $cur_config['conf_value'];
		
		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_board_title'				=> $old_config['o_board_title'],
			'o_board_desc'				=> $old_config['o_board_desc'],
			'o_default_timezone'		=> $old_config['o_default_timezone'],
			'o_time_format'				=> $old_config['o_time_format'],
			'o_date_format'				=> $old_config['o_date_format'],
			'o_timeout_visit'			=> $old_config['o_timeout_visit'],
			'o_timeout_online'			=> $old_config['o_timeout_online'],
			'o_redirect_delay'			=> $old_config['o_redirect_delay'],
			'o_show_version'			=> $old_config['o_show_version'],
			'o_show_user_info'			=> $old_config['o_show_user_info'],
			'o_show_post_count'			=> $old_config['o_show_post_count'],
			'o_signatures'				=> $old_config['o_signatures'],
			'o_smilies'					=> $old_config['o_smilies'],
			'o_smilies_sig'				=> $old_config['o_smilies_sig'],
			'o_make_links'				=> $old_config['o_make_links'],
			'o_default_lang'			=> $this->default_lang,
			'o_default_style'			=> $this->default_style,
			'o_default_user_group'		=> $this->grp2grp($old_config['o_default_user_group']),
			'o_topic_review'			=> $old_config['o_topic_review'],
			'o_disp_topics_default'		=> $old_config['o_disp_topics_default'],
			'o_disp_posts_default'		=> $old_config['o_disp_posts_default'],
			'o_indent_num_spaces'		=> $old_config['o_indent_num_spaces'],
			'o_quote_depth'				=> $old_config['o_quote_depth'],
			'o_quickpost'				=> $old_config['o_quickpost'],
			'o_users_online'			=> $old_config['o_users_online'],
			'o_censoring'				=> $old_config['o_censoring'],
			'o_ranks'					=> $old_config['o_ranks'],
			'o_show_dot'				=> $old_config['o_show_dot'],
			'o_topic_views'				=> $old_config['o_topic_views'],
			'o_quickjump'				=> $old_config['o_quickjump'],
			'o_gzip'					=> $old_config['o_gzip'],
			'o_additional_navlinks'		=> $old_config['o_additional_navlinks'],
			'o_report_method'			=> $old_config['o_report_method'],
			'o_regs_report'				=> $old_config['o_regs_report'],
			'o_default_email_setting'	=> $old_config['o_default_email_setting'],
			'o_mailing_list'			=> $old_config['o_mailing_list'],
			'o_avatars'					=> $old_config['o_avatars'],
			'o_avatars_dir'				=> $old_config['o_avatars_dir'],
			'o_avatars_width'			=> $old_config['o_avatars_width'],
			'o_avatars_height'			=> $old_config['o_avatars_height'],
			'o_avatars_size'			=> $old_config['o_avatars_size'],
			'o_search_all_forums'		=> $old_config['o_search_all_forums'],
			'o_base_url'				=> $this->base_url,
			'o_admin_email'				=> $old_config['o_admin_email'],
			'o_webmaster_email'			=> $old_config['o_webmaster_email'],
			'o_subscriptions'			=> $old_config['o_subscriptions'],
			'o_smtp_host'				=> $old_config['o_smtp_host'],
			'o_smtp_user'				=> $old_config['o_smtp_user'],
			'o_smtp_pass'				=> $old_config['o_smtp_pass'],
			'o_smtp_ssl'				=> $old_config['o_smtp_ssl'],
			'o_regs_allow'				=> $old_config['o_regs_allow'],
			'o_regs_verify'				=> $old_config['o_regs_verify'],
			'o_announcement'			=> $old_config['o_announcement'],
			'o_announcement_message'	=> $old_config['o_announcement_message'],
			'o_rules'					=> $old_config['o_rules'],
			'o_rules_message'			=> $old_config['o_rules_message'],
			'o_maintenance'				=> $old_config['o_maintenance'],
			'o_maintenance_message'		=> $old_config['o_maintenance_message'],
			'o_default_dst'				=> $old_config['o_default_dst'],
			'p_message_bbcode'			=> $old_config['p_message_bbcode'],
			'p_message_img_tag'			=> $old_config['p_message_img_tag'],
			'p_message_all_caps'		=> $old_config['p_message_all_caps'],
			'p_subject_all_caps'		=> $old_config['p_subject_all_caps'],
			'p_sig_all_caps'			=> $old_config['p_sig_all_caps'],
			'p_sig_bbcode'				=> $old_config['p_sig_bbcode'],
			'p_sig_img_tag'				=> $old_config['p_sig_img_tag'],
			'p_sig_length'				=> $old_config['p_sig_length'],
			'p_sig_lines'				=> $old_config['p_sig_lines'],
			'p_allow_banned_email'		=> $old_config['p_allow_banned_email'],
			'p_allow_dupe_email'		=> $old_config['p_allow_dupe_email'],
			'p_force_guest_email'		=> $old_config['p_force_guest_email'],
		);
		
		foreach ($new_config as $key => $value)
		{
			$fluxbb->add_row('config', array(
				'conf_name'		=> $key,
				'conf_value'	=> $value,
			));
		}
	}
	
	// TODO!!!
	function convert_forums($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, forum_name, forum_desc, redirect_url, moderators, num_topics, num_posts, last_post, last_post_id, last_poster, sort_by, disp_position, cat_id',
			'FROM'		=> 'forums',
		));

		message('Processing %d forums', $db->num_rows($result));
		while ($cur_forum = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('forums', $cur_forum);
		}
	}
	
	// TODO!!!
	function convert_forum_perms($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
			'FROM'		=> 'forum_perms',
		));

		message('Processing %d forum_perms', $db->num_rows($result));
		while ($cur_perm = $db->fetch_assoc($result))
		{
			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

			$fluxbb->add_row('forum_perms', $cur_perm);
		}
	}

	// TODO!!!
	function convert_groups($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'g_id, g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'groups',
		));
		
		message('Processing %d groups', $db->num_rows($result));
		while ($cur_group = $db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

			$fluxbb->add_row('groups', $cur_group);
		}
	}

	// TODO
	function convert_posts($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, poster, poster_id, poster_ip, poster_email, message, hide_smilies, posted, edited, edited_by, topic_id',
			'FROM'		=> 'posts',
		));
		
		message('Processing %d posts', $db->num_rows($result));
		while ($cur_post = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('posts', $cur_post);
		}
	}

	// TODO
	function convert_ranks($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, rank, min_posts',
			'FROM'		=> 'ranks',
		));
		
		message('Processing %d ranks', $db->num_rows($result));
		while ($cur_rank = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('ranks', $cur_rank);
		}
	}

	// TODO
	function convert_reports($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, post_id, topic_id, forum_id, reported_by, created, message, zapped, zapped_by',
			'FROM'		=> 'reports',
		));
		
		message('Processing %d reports', $db->num_rows($result));
		while ($cur_report = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('reports', $cur_report);
		}
	}

	// TODO
	function convert_topic_subscriptions($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'user_id, topic_id',
			'FROM'		=> 'topic_subscriptions',
		));
		
		message('Processing %d topic subscriptions', $db->num_rows($result));
		while ($cur_sub = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}
	
	// TODO
	function convert_forum_subscriptions($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'user_id, forum_id',
			'FROM'		=> 'forum_subscriptions',
		));
		
		message('Processing %d forum subscriptions', $db->num_rows($result));
		while ($cur_sub = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('forum_subscriptions', $cur_sub);
		}
	}

	function convert_topics($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'topic_id AS id, topic_first_poster_name AS poster, topic_title AS subject, topic_time AS posted, topic_first_post_id AS first_post_id, topic_last_post_time AS last_post, topic_last_post_id AS last_post_id, topic_last_poster_name AS last_poster, topic_views AS num_views, topic_replies AS num_replies, IF(topic_status=1, 1, 0) AS closed, IF(topic_type=1, 1, 0) AS sticky, topic_moved_id AS moved_to, forum_id',
			'FROM'		=> 'phpbb_topics',
		));
		
		message ('Processing %d topics', $db->num_rows($result));
		while ($cur_topic = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('topics', $cur_topic);
		}
	}

	// TODO
	function convert_users($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, group_id, username, email, title, realname, url, jabber, icq, msn, aim, yahoo, location, signature, disp_topics, disp_posts, email_setting, notify_with_post, auto_notify, show_smilies, show_img, show_img_sig, show_avatars, show_sig, timezone, dst, time_format, date_format, language, style, num_posts, last_post, last_search, last_email_sent, registered, registration_ip, last_visit, admin_note, activate_string, activate_key',
			'FROM'		=> 'users',
		));

		message('Processing %d users', $db->num_rows($result));
		while ($cur_user = $db->fetch_assoc($result))
		{
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['password'] = $fluxbb->pass_hash($fluxbb->random_pass(20));
			$cur_user['language'] = $this->default_lang;
			$cur_user['style'] = $this->default_style;

			$fluxbb->add_row('users', $cur_user);
		}
	}
	
	// TODO
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(0 => 0, 1 => 1, 2 => 3, 3 => 4, 4 => 2);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
	}
}
