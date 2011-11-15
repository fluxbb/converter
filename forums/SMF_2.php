<?php

define('FORUM_VERSION', '1.4');
define('FORUM_DB_REVISION', 2);

class SMF_2 extends Forum
{
	function initialize($db)
	{
		$db->set_names('utf8');
	}

	function convert_bans($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id_ban AS id, member_name AS username, ip_low1, ip_low2, ip_low3, ip_low4, email_address AS email, reason AS message, expire_time AS expire',//, ban_creator',
			'FROM'		=> 'ban_items',
		));

		message('Processing %d bans', $db->num_rows($result));
		while ($cur_ban = $db->fetch_assoc($result))
		{
			$cur_ban['ip'] = implode('.', array($cur_ban['ip_low1'], $cur_ban['ip_low2'], $ob['ip_low3'], $ob['ip_low4']));
			$fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id_cat AS id, name AS cat_name, cat_order AS disp_position',
			'FROM'		=> 'categories',
		));

		message('Processing %d categories', $db->num_rows($result));
		while ($cur_cat = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('categories', $cur_cat);
		}
	}

//	function convert_censoring($db, $fluxbb)
//	{
//		$result = $db->query_build(array(
//			'SELECT'	=> 'id, search_for, replace_with',
//			'FROM'		=> 'censoring',
//		));

//		message('Processing %d censors', $db->num_rows($result));
//		while ($cur_censor = $db->fetch_assoc($result))
//		{
//			$fluxbb->add_row('censoring', $cur_censor);
//		}
//	}

	function convert_config($db, $fluxbb)
	{
		$old_config = array();

		$result = $db->query_build(array(
			'SELECT'	=> 'variable, value',
			'FROM'		=> 'settings',
		));

		message('Processing config');
		while ($cur_config = $db->fetch_assoc($result))
			$old_config[$cur_config['variable']] = $cur_config['value'];

		$this->new_config['o_smtp_host'] 			= $old_config['smtp_host'].(!empty($old_config['smtp_host']) && !empty($old_config['smtp_port'])) ? ':'.$old_config['smtp_port'] : '';
		$this->new_config['o_smtp_user'] 			= $old_config['smtp_username'];
		$this->new_config['o_smtp_pass'] 			= $old_config['smtp_password'];

		foreach ($this->new_config as $key => $value)
		{
			$fluxbb->add_row('config', array(
				'conf_name'		=> $key,
				'conf_value'	=> $value,
			));
		}
	}

	function convert_forums($db, $fluxbb)
	{
		// TODO: last post/poster
		$result = $db->query_build(array(
			'SELECT'	=> 'id_board AS id, name AS forum_name, description AS forum_desc, num_topics AS num_topics, num_posts AS num_posts, board_order AS disp_position, last_poster AS last_poster, last_post AS last_post, id_last_msg AS last_post_id, id_cat AS cat_id',
			'FROM'		=> 'boards',
		));

		message('Processing %d forums', $db->num_rows($result));
		while ($cur_forum = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('forums', $cur_forum);
		}
	}

//	function convert_forum_perms($db, $fluxbb)
//	{
//		$result = $db->query_build(array(
//			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
//			'FROM'		=> 'forum_perms',
//		));

//		message('Processing %d forum_perms', $db->num_rows($result));
//		while ($cur_perm = $db->fetch_assoc($result))
//		{
//			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

//			$fluxbb->add_row('forum_perms', $cur_perm);
//		}
//	}

	function convert_groups($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id_group AS g_id, group_name AS g_title',//, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'groups',
		));

		message('Processing %d groups', $db->num_rows($result));
		while ($cur_group = $db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);
			$cur_group['g_mod'] = $cur_group['g_title'] == 'Moderator';

			$fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id_msg AS id, poster_name AS poster, id_member AS poster_id, poster_time AS posted, poster_ip AS poster_ip, './*convert_posts(body)*/'body AS message, id_topic AS topic_id',
			'FROM'		=> 'posts',
		));

		message('Processing %d posts', $db->num_rows($result));
		while ($cur_post = $db->fetch_assoc($result))
		{

			$fluxbb->add_row('posts', $cur_post);
		}
	}

//	function convert_ranks($db, $fluxbb)
//	{
//		$result = $db->query_build(array(
//			'SELECT'	=> 'id, rank, min_posts',
//			'FROM'		=> 'ranks',
//		));

//		message('Processing %d ranks', $db->num_rows($result));
//		while ($cur_rank = $db->fetch_assoc($result))
//		{
//			$fluxbb->add_row('ranks', $cur_rank);
//		}
//	}

//	function convert_reports($db, $fluxbb)
//	{
//		$result = $db->query_build(array(
//			'SELECT'	=> 'id, post_id, topic_id, forum_id, reported_by, created, message, zapped, zapped_by',
//			'FROM'		=> 'reports',
//		));

//		message('Processing %d reports', $db->num_rows($result));
//		while ($cur_report = $db->fetch_assoc($result))
//		{
//			$fluxbb->add_row('reports', $cur_report);
//		}
//	}

//	function convert_topic_subscriptions($db, $fluxbb)
//	{
//		$result = $db->query_build(array(
//			'SELECT'	=> 'user_id, topic_id',
//			'FROM'		=> 'subscriptions',
//		));

//		message('Processing %d subscriptions', $db->num_rows($result));
//		while ($cur_sub = $db->fetch_assoc($result))
//		{
//			$fluxbb->add_row('topic_subscriptions', $cur_sub);
//		}
//	}

//	function convert_forum_subscriptions($db, $fluxbb)
//	{
//		message('No forum subscriptions', $db->num_rows($result));
//	}

	function convert_topics($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 't.id_topic AS id, t.num_views AS num_views, t.num_replies AS num_replies, t.is_sticky AS sticky, t.locked AS closed, t.id_board AS forum_id, m.subject AS subject, m.poster_time AS posted, m.id_msg AS first_post_id, lm.poster_time AS last_post, lm.poster_name AS last_poster, lm.id_msg AS last_post_id',
			'FROM'		=> 'topics AS t',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'id_msg AS m',
					'ON'		=> 'm.id_msg=t.id_first_msg'
				),
				array(
					'LEFT JOIN'	=> 'id_msg AS lm',
					'ON'		=> 'm.id_msg=t.id_last_msg'
				),
			)
		));

		message ('Processing %d topics', $db->num_rows($result));
		while ($cur_topic = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('topics', $cur_topic);
		}
	}

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

	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(1 => 1, 2 => 3, 3 => 2, 4 => 4);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
	}
}
