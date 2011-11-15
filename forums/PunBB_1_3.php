<?php

define('FORUM_VERSION', '1.4');
define('FORUM_DB_REVISION', 2);

class PunBB_1_3 extends Forum
{
	function initialize($db)
	{
		$db->set_names('utf8');
	}

	function convert_bans($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, username, ip, email, message, expire, ban_creator',
			'FROM'		=> 'bans',
		));

		message('Processing %d bans', $db->num_rows($result));
		while ($cur_ban = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, cat_name, disp_position',
			'FROM'		=> 'categories',
		));

		message('Processing %d categories', $db->num_rows($result));
		while ($cur_cat = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_censoring($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, search_for, replace_with',
			'FROM'		=> 'censoring',
		));

		message('Processing %d censors', $db->num_rows($result));
		while ($cur_censor = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('censoring', $cur_censor);
		}
	}

	function convert_config($db, $fluxbb)
	{
		$old_config = array();

		$result = $db->query_build(array(
			'SELECT'	=> 'conf_name, conf_value',
			'FROM'		=> 'config',
		));

		message('Processing config');
		while ($cur_config = $db->fetch_assoc($result))
			$this->new_config[$cur_config['conf_name']] = $cur_config['conf_value'];

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

	function convert_topic_subscriptions($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'user_id, topic_id',
			'FROM'		=> 'subscriptions',
		));

		message('Processing %d subscriptions', $db->num_rows($result));
		while ($cur_sub = $db->fetch_assoc($result))
		{
			$fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

//	function convert_forum_subscriptions($db, $fluxbb)
//	{
//		message('No forum subscriptions', $db->num_rows($result));
//	}

	function convert_topics($db, $fluxbb)
	{
		$result = $db->query_build(array(
			'SELECT'	=> 'id, poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to, forum_id',
			'FROM'		=> 'topics',
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
			$mapping = array(0 => 0, 1 => 1, 2 => 3, 3 => 4, 4 => 2);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
	}
}
