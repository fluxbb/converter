<?php

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.7');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class SMF_1_1_11 extends Forum
{
	function initialize()
	{
//		$this->db->set_names('utf8');
	}

	function validate()
	{
		if (!$this->db->field_exists('categories', 'ID_CAT'))
			error('Selected database does not contain valid SMF installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.ID_BAN AS id, bg.name AS username, b.ip_low1, b.ip_low2, b.ip_low3, b.ip_low4, b.email_address AS email, bg.reason AS message, bg.expire_time AS expire',//, ban_creator',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'ban_groups AS bg',
					'ON'		=> 'bg.ID_BAN_GROUP=b.ID_BAN_GROUP'
				),
			),
			'FROM'		=> 'ban_items AS b',
		)) or error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$cur_ban['ip'] = implode('.', array($cur_ban['ip_low1'], $cur_ban['ip_low2'], $cur_ban['ip_low3'], $cur_ban['ip_low4']));
			unset ($cur_ban['ip_low1'], $cur_ban['ip_low2'], $cur_ban['ip_low3'], $cur_ban['ip_low4']);

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

		conv_message('Processing', 'categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_censoring()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'variable, value',
			'FROM'		=> 'settings',
			'WHERE'		=> 'variable IN (\'censor_vulgar\', \'censor_proper\')'
		)) or error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing censoring');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['variable']] = $cur_config['value'];

		$censor_words = array_combine(explode("\n", $old_config['censor_vulgar']), explode("\n", $old_config['censor_proper']));
		foreach ($censor_words as $vulgar => $valid)
		{
			$this->fluxbb->add_row('censoring', array(
				'search_for'	=> $vulgar,
				'replace_with'	=> $valid,
			));
		}
	}

//	function convert_config()
//	{
//		$old_config = array();

//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'conf_name, conf_value',
//			'FROM'		=> 'config',
//		));

//		conv_message('Processing config');
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

		conv_message('Processing', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$cur_forum['redirect_url'] = NULL;
			$cur_forum['moderators'] = NULL;
			$cur_forum['sort_by'] = 0;

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

//	function convert_forum_perms()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
//			'FROM'		=> 'forum_perms',
//		)) or error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'forum_perms', $this->db->num_rows($result));
//		while ($cur_perm = $this->db->fetch_assoc($result))
//		{
//			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

//			$this->fluxbb->add_row('forum_perms', $cur_perm);
//		}
//	}

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_GROUP AS g_id, groupName AS g_title',//, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'minPosts = -1 AND ID_GROUP > 3'
		)) or error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);
			$cur_group['g_moderator'] = $cur_group['g_title'] == 'Moderator';

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MSG AS id, posterName AS poster, ID_MEMBER AS poster_id, posterIP AS poster_ip, posterEmail AS poster_email, body AS message, IF(smileysEnabled = 1, 0, 1) AS hide_smilies, posterTime AS posted, ID_TOPIC AS topic_id',
			'FROM'		=> 'messages',
			'WHERE'		=> 'ID_MSG > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch messages', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['edited'] = NULL;
			$cur_post['edited_by'] = NULL;
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);
			$cur_post['message'] = $this->convert_message($cur_post['message']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		$this->redirect('messages', 'ID_MSG', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_GROUP AS id, groupName AS rank, minPosts AS min_posts',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'minPosts <> -1',
		)) or error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

//	function convert_reports()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'id_report AS id, id_msg AS post_id, id_topic AS topic_id, id_board AS forum_id, membername AS reported_by, time_started AS created, body AS message, closed AS zapped',
//			'FROM'		=> 'log_reported',
//		)) or error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'reports', $this->db->num_rows($result));
//		while ($cur_report = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('reports', $cur_report);
//		}
//	}


	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MEMBER AS user_id, ID_TOPIC AS topic_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'ID_TOPIC > 0',
		)) or error('Unable to fetch log notify', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	function convert_forum_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MEMBER AS user_id, ID_BOARD AS forum_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'ID_BOARD > 0',
		)) or error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'forum subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('forum_subscriptions', $cur_sub);
		}
	}

	function convert_topics($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 't.ID_TOPIC AS id, mf.posterName AS poster, mf.subject, mf.posterTime AS posted, t.ID_FIRST_MSG AS first_post_id, ml.posterTime AS last_post, t.ID_LAST_MSG AS last_post_id, ml.posterName AS last_poster, t.numViews AS num_views, t.numReplies AS num_replies, t.locked AS closed, t.isSticky AS sticky, t.ID_BOARD AS forum_id',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'messages AS mf',
					'ON'		=> 'mf.ID_MSG = t.ID_FIRST_MSG',
				),
				array(
					'LEFT JOIN'	=> 'messages AS ml',
					'ON'		=> 'ml.ID_MSG = t.ID_LAST_MSG'
				)
			),
			'FROM'		=> 'topics AS t',
			'WHERE'		=> 't.ID_TOPIC > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];
			$cur_topic['moved_to'] = NULL;

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		$this->redirect('topics', 'ID_TOPIC', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MEMBER AS id, ID_GROUP AS group_id, memberName AS username, passwd AS password, passwordSalt AS salt, websiteUrl AS url, ICQ AS icq, MSN AS msn, AIM AS aim, YIM AS yahoo, signature AS signature, timeOffset AS timezone, posts AS num_posts, dateRegistered AS registered, lastLogin AS last_visit, location AS location, emailAddress AS email',
			'FROM'		=> 'members',
			'WHERE'		=> 'id_member > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
//			$cur_user['password'] = $this->fluxbb->pass_hash($this->fluxbb->random_pass(20));
//			$cur_user['language'] = $this->default_lang;
//			$cur_user['style'] = $this->default_style;
			$cur_user['id'] = $this->uid2uid($cur_user['id']);
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		$this->redirect('members', 'ID_MEMBER', $start_at);
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

	function uid2uid($id, $new_uid = false)
	{
		static $last_uid;

		// id=0 is a SMF's guest user
		if ($id == 0)
			return 1;

		// id=1 is reserved for the guest user
		else if ($id == 1)
		{
			if (!isset($last_uid))
			{
				$result = $this->db->query_build(array(
					'SELECT'	=> 'MAX(ID_MEMBER)',
					'FROM'		=> 'members',
				)) or error('Unable to fetch last user id', __FILE__, __LINE__, $this->db->error());

				$last_uid = $this->db->result($result) + 1;
			}
			return $last_uid;
		}

		return $id;
	}

	function convert_message($message)
	{
		$message = $this->convert_to_utf8($message);

		$replace = array(
			'<br />'		=>	"\n",
			'[li]'			=>	'[*]',
			'[/li]'			=>	'[/*]'
		);

		$message = str_replace(array_keys($replace), array_values($replace), $message);
		return $message;
	}
}
