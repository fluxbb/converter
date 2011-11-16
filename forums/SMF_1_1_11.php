<?php

define('FORUM_VERSION', '1.4');
define('FORUM_DB_REVISION', 2);

class SMF_1_1_11 extends Forum
{
	function initialize()
	{
		$this->db->set_names('utf8');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, username, ip, email, message, expire, ban_creator',
			'FROM'		=> 'bans',
		)) or error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		message('Processing %d bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	// done
	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_CAT AS id, name AS cat_name, catOrder AS disp_position',
			'FROM'		=> 'categories',
		)) or error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		message('Processing %d categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_censoring()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, search_for, replace_with',
			'FROM'		=> 'censoring',
		)) or error('Unable to fetch censoring', __FILE__, __LINE__, $this->db->error());

		message('Processing %d censors', $this->db->num_rows($result));
		while ($cur_censor = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('censoring', $cur_censor);
		}
	}

//	function convert_config()
//	{
//		$old_config = array();

//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'conf_name, conf_value',
//			'FROM'		=> 'config',
//		));

//		message('Processing config');
//		while ($cur_config = $this->db->fetch_assoc($result))
//			$this->new_config[$cur_config['conf_name']] = $cur_config['conf_value'];

//		foreach ($this->new_config as $key => $value)
//		{
//			$this->fluxbb->add_row('config', array(
//				'conf_name'		=> $key,
//				'conf_value'	=> $value,
//			));
//		}
//	}

	// done
	function convert_forums()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.ID_BOARD AS id, b.name AS forum_name, b.description AS forum_desc, b.numTopics AS num_topics, b.numPosts AS num_posts, m.posterTime AS last_post, b.ID_LAST_MSG AS last_post_id, m.posterName AS last_poster, b.boardOrder AS disp_position, b.ID_CAT AS cat_id',
			'FROM'		=> 'boards AS b',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'messages AS m',
					'ON'		=> 'm.ID_MSG = b.ID_LAST_MSG'
				)
			)
		)) or error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		message('Processing %d forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$cur_forum['redirect_url'] = NULL;
			$cur_forum['moderators'] = NULL;
			$cur_forum['sort_by'] = 0;

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	function convert_forum_perms()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
			'FROM'		=> 'forum_perms',
		)) or error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

		message('Processing %d forum_perms', $this->db->num_rows($result));
		while ($cur_perm = $this->db->fetch_assoc($result))
		{
			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

			$this->fluxbb->add_row('forum_perms', $cur_perm);
		}
	}

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'g_id, g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'groups',
		)) or error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		message('Processing %d groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	// done
	function convert_posts()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MSG AS id, posterName AS poster, ID_MEMBER AS poster_id, posterIP AS poster_ip, posterEmail AS poster_email, body AS message, IF(smileysEnabled = 1, 0, 1) AS hide_smilies, posterTime AS posted, ID_TOPIC AS topic_id',
			'FROM'		=> 'messages',
		)) or error('Unable to fetch messages', __FILE__, __LINE__, $this->db->error());

		message('Processing %d posts', $this->db->num_rows($result));
		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$cur_post['edited'] = NULL;
			$cur_post['edited_by'] = NULL;

			$this->fluxbb->add_row('posts', $cur_post);
		}
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, rank, min_posts',
			'FROM'		=> 'ranks',
		)) or error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		message('Processing %d ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, post_id, topic_id, forum_id, reported_by, created, message, zapped, zapped_by',
			'FROM'		=> 'reports',
		)) or error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		message('Processing %d reports', $this->db->num_rows($result));
		while ($cur_report = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('reports', $cur_report);
		}
	}

	// done
	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MEMBER AS user_id, ID_TOPIC AS topic_id',
			'FROM'		=> 'log_notify',
		)) or error('Unable to fetch log notify', __FILE__, __LINE__, $this->db->error());

		message('Processing %d topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	// TODO
	function convert_forum_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'user_id, forum_id',
			'FROM'		=> 'forum_subscriptions',
		)) or error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

		message('Processing %d forum subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('forum_subscriptions', $cur_sub);
		}
	}

	// done
	function convert_topics()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 't.ID_TOPIC AS id, mf.posterName AS poster, mf.subject, mf.posterTime AS posted, t.ID_FIRST_MSG AS first_post_id, ml.posterTime AS last_post, t.ID_LAST_MSG AS last_post_id, last_poster, t.numViews AS num_views, t.numReplies AS num_replies, t.locked AS closed, t.isSticky AS sticky, t.ID_BOARD AS forum_id',
			'FROM'		=> 'topics AS t',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'messages AS mf',
					'ON'		=> 'mf.ID_MSG = t.ID_FIRST_MSG',
				),
				array(
					'LEFT JOIN'	=> 'messages AS ml',
					'ON'		=> 'ml.ID_MSG = t.ID_LAST_MSG'
				)
			)
		)) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		message ('Processing %d topics', $this->db->num_rows($result));
		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$cur_topic['moved_to'] = NULL;

			$this->fluxbb->add_row('topics', $cur_topic);
		}
	}

	function convert_users()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, group_id, username, email, title, realname, url, jabber, icq, msn, aim, yahoo, location, signature, disp_topics, disp_posts, email_setting, notify_with_post, auto_notify, show_smilies, show_img, show_img_sig, show_avatars, show_sig, timezone, dst, time_format, date_format, language, style, num_posts, last_post, last_search, last_email_sent, registered, registration_ip, last_visit, admin_note, activate_string, activate_key',
			'FROM'		=> 'users',
		)) or error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		message('Processing %d users', $this->db->num_rows($result));
		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['password'] = $this->fluxbb->pass_hash($this->fluxbb->random_pass(20));
			$cur_user['language'] = $this->default_lang;
			$cur_user['style'] = $this->default_style;

			$this->fluxbb->add_row('users', $cur_user);
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
