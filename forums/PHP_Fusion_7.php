<?php

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.7');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class PHP_Fusion_7 extends Forum
{
	function initialize()
	{
		$this->db->set_names('utf8');
	}

	function validate()
	{
		if (!$this->db->field_exists('forums', 'forum_cat'))
			error('Selected database does not contain valid PHP Fusion installation');
	}

//	function convert_bans()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'b.ban_id AS id, u.username AS username, b.ban_ip AS ip, b.ban_email AS email, b.ban_reason AS message, b.ban_end AS expire',
//			'JOINS'        => array(
//				array(
//					'LEFT JOIN'	=> 'users AS u',
//					'ON'		=> 'u.user_id=b.ban_userid'
//				),
//			),
//			'FROM'		=> 'banlist AS b',
//		)) or error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'bans', $this->db->num_rows($result));
//		while ($cur_ban = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('bans', $cur_ban);
//		}
//	}

	function convert_categories()
	{
		// FIXME: Subforums might cause difficulties
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forum_id AS id, forum_name AS cat_name, forum_order AS disp_position',
			'FROM'		=> 'forums',
			'WHERE'		=> 'forum_cat = 0',
		)) or error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

//	function convert_censoring()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'word_id AS id, word AS search_for, replacement AS replace_with',
//			'FROM'		=> 'words',
//		)) or error('Unable to fetch words', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'censors', $this->db->num_rows($result));
//		while ($cur_censor = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('censoring', $cur_censor);
//		}
//	}

	//function convert_config()
//	{
//		$old_config = array();

//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'config_name, config_value',
//			'FROM'		=> 'config',
//		)) or error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'config');
//		while ($cur_config = $this->db->fetch_assoc($result))
//			$old_config[$cur_config['config_name']] = $cur_config['config_value'];

//		$new_config = array(
//			'o_board_title'			=> $old_config['sitename'],
//			'o_board_desc'			=> $old_config['site_desc'],
//			'o_admin_email'			=> $old_config['board_email'],
//			'o_server_timezone'		=> $old_config['board_timezone'],
//			'o_disp_topics_default'	=> $old_config['topics_per_page'],
//			'o_disp_posts_default'	=> $old_config['posts_per_page'],
//			'o_webmaster_email'		=> $old_config['board_email'],
//			'o_smtp_host'			=> $old_config['smtp_host'],
//			'o_smtp_user'			=> $old_config['smtp_username'],
//			'o_smtp_pass'			=> $old_config['smtp_password']
//		);

//		foreach ($new_config as $key => $value)
//		{
//			$this->fluxbb->db->query_build(array(
//				'UPDATE'	=> 'config',
//				'SET' 		=> 'conf_value = \''.$this->db->escape($value).'\'',
//				'WHERE'		=> 'conf_name = \''.$this->db->escape($key).'\'',
//			)) or error('Unable to update config', __FILE__, __LINE__, $this->fluxbb->db->error());
//		}
//	}

	function convert_forums()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'f.forum_id AS id, f.forum_name, f.forum_description AS forum_desc, f.forum_threadcount AS num_topics, f.forum_postcount AS num_posts, f.forum_order AS disp_position, u.user_name AS last_poster, f.forum_lastpost AS last_post, f.forum_cat AS cat_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id = f.forum_lastuser'
				),
			),
			'FROM'		=> 'forums AS f',
			'WHERE'		=> 'f.forum_cat <> 0'
		)) or error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$result_last_post_id = $this->db->query_build(array(
				'SELECT'	=> 'thread_lastpostid',
				'FROM'		=> 'threads',
				'WHERE'		=> 'forum_id = '.$cur_forum['id']
			)) or error('Unable to fetch forum last post', __FILE__, __LINE__, $this->db->error());

			$cur_forum['last_post_id'] = $this->db->result($result_last_post_id);

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	// TODO!!!
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

//	function convert_groups()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'group_id AS g_id, group_name AS g_title, group_name AS g_user_title',
//			'FROM'		=> 'groups',
//			'WHERE'		=> 'group_id > 7'
//		)) or error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'groups', $this->db->num_rows($result));
//		while ($cur_group = $this->db->fetch_assoc($result))
//		{
//			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

//			$this->fluxbb->add_row('groups', $cur_group);
//		}
//	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'p.post_id AS id, u.user_name AS poster, p.post_author AS poster_id, p.post_datestamp AS posted, p.post_message AS message, p.thread_id AS topic_id, IF(p.post_smileys=1, 0, 1) AS hide_smilies, p.post_ip AS poster_ip, IF(p.post_edittime=0, NULL, p.post_edittime) AS edited, eu.user_name AS edited_by',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=p.post_author'
				),
				array(
					'LEFT JOIN'	=> 'users AS eu',
					'ON'		=> 'eu.user_id=p.post_edituser'
				),
			),
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.post_id > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing rows', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['message'] = $this->convert_message($cur_post['message']);
			$cur_post['hide_smilies'] = !$cur_post['hide_smilies'];
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		$this->redirect('posts', 'post_id', $start_at);
	}

//	function convert_ranks()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'rank_id AS id, rank_title AS rank, rank_min AS min_posts',
//			'FROM'		=> 'ranks',
//		)) or error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'ranks', $this->db->num_rows($result));
//		while ($cur_rank = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('ranks', $cur_rank);
//		}
//	}

//	function convert_reports()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'r.report_id AS id, r.post_id, p.topic_id, p.forum_id, r.user_notify AS reported_by, r.report_time AS created, r.report_text AS message, r.report_closed AS zapped, NULL AS zapped_by',
//			'JOINS'        => array(
//				array(
//					'LEFT JOIN'	=> 'posts AS p',
//					'ON'		=> 'r.post_id=p.post_id'
//				),
//			),
//			'FROM'		=> 'reports AS r',
//		)) or error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'reports', $this->db->num_rows($result));
//		while ($cur_report = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('reports', $cur_report);
//		}
//	}

//	function convert_topic_subscriptions()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'user_id, topic_id',
//			'FROM'		=> 'topics_watch',
//		)) or error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'topic subscriptions', $this->db->num_rows($result));
//		while ($cur_sub = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
//		}
//	}

//	function convert_forum_subscriptions()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'user_id, forum_id',
//			'FROM'		=> 'forums_watch',
//		)) or error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing', 'forum subscriptions', $this->db->num_rows($result));
//		while ($cur_sub = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('forum_subscriptions', $cur_sub);
//		}
//	}

	function convert_topics($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 't.thread_id AS id, u.user_name AS poster, t.thread_subject AS subject, t.thread_lastpost AS last_post, t.thread_lastpostid AS last_post_id, lu.user_name AS last_poster, t.thread_views AS num_views, t.thread_postcount AS num_replies, t.thread_sticky AS sticky, t.thread_locked AS closed, t.forum_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=t.thread_author'
				),
				array(
					'LEFT JOIN'	=> 'users AS lu',
					'ON'		=> 'lu.user_id=t.thread_lastuser'
				),
			),
			'FROM'		=> 'threads AS t',
			'WHERE'		=> 'thread_id > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing rows', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];

			$result_posted = $this->db->query_build(array(
				'SELECT'	=> 'post_id AS first_post_id, post_datestamp AS posted',
				'FROM'		=> 'posts',
				'WHERE'		=> 'thread_id = '.$cur_topic['id'],
			)) or error('Unable to fetch topic posted', __FILE__, __LINE__, $this->db->error());

			$cur_topic = array_merge($cur_topic, $this->db->fetch_assoc($result_posted));

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		$this->redirect('threads', 'thread_id', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'user_id AS id, user_name AS username, user_password AS password, user_email AS email, user_web AS url, user_icq AS icq, user_msn AS msn, user_yahoo AS yahoo, user_sig AS signature, user_offset AS timezone, user_posts AS num_posts, user_joined AS registered, user_lastvisit AS last_visit, user_location AS location, IF(user_hide_email=0, 1, 0) AS email_setting',
			'FROM'		=> 'users',
			'WHERE'		=> 'user_id > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing rows', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];

			$result_last_post = $this->db->query_build(array(
				'SELECT'	=> 'MAX(post_id)',
				'FROM'		=> 'posts',
				'WHERE'		=> 'post_author = '.$cur_user['id'],
			)) or error('Unable to fetch user last post', __FILE__, __LINE__, $this->db->error());
			$cur_user['last_post'] = $this->db->result($result_last_post);

//			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['group_id'] = $cur_user['id'] == 1 ? 1 : 4;
			$cur_user['id'] = $this->uid2uid($cur_user['id']);

//			$cur_user['password'] = $this->fluxbb->pass_hash($this->fluxbb->random_pass(20));
			$cur_user['email_setting'] = !$cur_user['email_setting'];
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		$this->redirect('users', 'user_id', $start_at);
	}

	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(1 => 3, 2 => 4, 3 => 4, 4 => 2, 5 => 1);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
	}

	function uid2uid($id)
	{
		static $last_uid;

		// Fetch new user id (id=1 is reserved for guest user)
		if ($id == 1)
		{
			if (!isset($last_uid))
			{
				$result = $this->db->query_build(array(
					'SELECT'	=> 'MAX(user_id)',
					'FROM'		=> 'users',
				)) or error('Unable to fetch last user', __FILE__, __LINE__, $this->db->error());
				$last_uid = $this->db->result($result) + 1;
			}
			return $last_uid;
		}
		return $id;
	}


	// Convert posts BB-code
	function convert_message($message)
	{
		$pattern = array(
			// Other
			'#\\[quote author=(.*?) link(.*?)\](.*?)\[/QUOTE\]#is',
			'#\\[flash=(.*?)\](.*?)\[/flash\]#is',
			'#\\[ftp=(.*?)\](.*?)\[/ftp\]#is',
			'#\\[font=(.*?)\](.*?)\[/font\]#is',
			'#\\[size=(.*?)\](.*?)\[/size\]#is',
//			'#\\[list\](.*?)\[/list\]#is',
			'#\\[li\](.*?)\[/li\]#is',

			// Table
			'#\\[table\](.*?)\[/table\]#is',
			'#\\[tr\]#is',
			'#\\[/tr\]#is',
			'#\\[td\](.*?)\[/td\]#is',

			// Removed tags
			'#\\[glow=(.*?)\](.*?)\[/glow\]#is',
			'#\\[s\](.*?)\[/s\]#is',
			'#\\[shadow=(.*?)\](.*?)\[/shadow\]#is',
			'#\\[move\](.*?)\[/move\]#is',
			'#\\[pre\](.*?)\[/pre\]#is',

			'#\\[left\](.*?)\[/left\]#is',
			'#\\[right\](.*?)\[/right\]#is',
			'#\\[center\](.*?)\[/center\]#is',
			'#\\[sup\](.*?)\[/sup\]#is',
			'#\\[sub\](.*?)\[/sub\]#is',

			'#\\[hr\]#is',
			'#\\[tt\](.*?)\[/tt\]#is',
		);

		$replace = array(
			// Other
			'[quote=$1]$3[/quote]',
			'Flash: $2',
			'[url=$1]$2[/url]',
			'$2',
			'$2',
//			'[list]$1[/list]',
			'[*]$1[/*]',

			// Table
			'$1',
			'------------------------------------------------------------------'."\n",
			'------------------------------------------------------------------'."\n",
			"* $1\n",

			// Removed tags
			'$2',
			'$1',
			'$2',
			'$1',
			'$1',

			'$1',
			'$1',
			'$1',
			'$1',
			'$1',

			'$1'."\n",
			'$1',
		);

		$message = str_replace('<br />', "\n", $message);
		$message = str_replace("&gt;:(", ':x', $message);
		$message = str_replace('::)', ':rolleyes:', $message);
		$message = str_replace('&nbsp;', ' ', $message);

		return preg_replace($pattern, $replace, $message);
	}
}
