<?php

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.7');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class SMF_2 extends Forum
{
	function initialize()
	{
		$this->db->set_names('utf8');
	}

	function validate()
	{
		if (!$this->db->field_exists('ban_items', 'id_ban'))
			error('Selected database does not contain valid SMF installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.id_ban AS id, bg.name AS username, b.ip_low1, b.ip_low2, b.ip_low3, b.ip_low4, b.email_address AS email, bg.reason AS message, bg.expire_time AS expire',//, ban_creator',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'ban_groups AS bg',
					'ON'		=> 'bg.id_ban_group=b.id_ban_group'
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

	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_cat AS id, name AS cat_name, cat_order AS disp_position',
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

	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'variable, value',
			'FROM'		=> 'settings',
		)) or error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['variable']] = $cur_config['value'];

		$new_config = array(
			'o_smtp_host' 			=> $old_config['smtp_host'].(!empty($old_config['smtp_host']) && !empty($old_config['smtp_port'])) ? ':'.$old_config['smtp_port'] : '',
			'o_smtp_user' 			=> $old_config['smtp_username'],
			'o_smtp_pass' 			=> $old_config['smtp_password']
		);

		foreach ($new_config as $key => $value)
		{
			$this->fluxbb->db->query_build(array(
				'UPDATE'	=> 'config',
				'SET' 		=> 'conf_value = \''.$this->db->escape($value).'\'',
				'WHERE'		=> 'conf_name = \''.$this->db->escape($key).'\'',
			)) or error('Unable to update config', __FILE__, __LINE__, $this->fluxbb->db->error());
		}
	}

	function convert_forums()
	{
		// TODO: last post/poster
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.id_board AS id, b.name AS forum_name, b.description AS forum_desc, b.num_topics AS num_topics, b.num_posts AS num_posts, b.board_order AS disp_position, u.member_name AS last_poster, m.poster_time AS last_post, b.id_last_msg AS last_post_id, b.id_cat AS cat_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'messages AS m',
					'ON'		=> 'm.id_msg=b.id_last_msg'
				),
				array(
					'LEFT JOIN'	=> 'members AS u',
					'ON'		=> 'u.id_member=m.id_member'
				),
			),
			'FROM'		=> 'boards AS b',
		)) or error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
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
			'SELECT'	=> 'id_group AS g_id, group_name AS g_title',//, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'min_posts = -1 AND id_group > 3'
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
			'SELECT'	=> 'id_msg AS id, poster_name AS poster, id_member AS poster_id, poster_time AS posted, poster_ip AS poster_ip, body AS message, id_topic AS topic_id',
			'FROM'		=> 'messages',
			'WHERE'		=> 'id_msg > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['message'] = $this->convert_message($cur_post['message']);
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		$this->redirect('messages', 'id_msg', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_group AS id, group_name AS rank, min_posts',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'min_posts <> -1',
		)) or error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_report AS id, id_msg AS post_id, id_topic AS topic_id, id_board AS forum_id, membername AS reported_by, time_started AS created, body AS message, closed AS zapped',
			'FROM'		=> 'log_reported',
		)) or error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'reports', $this->db->num_rows($result));
		while ($cur_report = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('reports', $cur_report);
		}
	}

	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS user_id, id_topic AS topic_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'id_topic > 0',
		)) or error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	function convert_forum_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS user_id, id_board AS forum_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'id_board > 0',
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
			'SELECT'	=> 't.id_topic AS id, m.poster_name AS poster, t.num_views AS num_views, t.num_replies AS num_replies, t.is_sticky AS sticky, t.locked AS closed, t.id_board AS forum_id, m.subject AS subject, m.poster_time AS posted, m.id_msg AS first_post_id, lm.poster_time AS last_post, lm.poster_name AS last_poster, lm.id_msg AS last_post_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'messages AS m',
					'ON'		=> 'm.id_msg=t.id_first_msg'
				),
				array(
					'LEFT JOIN'	=> 'messages AS lm',
					'ON'		=> 'lm.id_msg=t.id_last_msg'
				),
			),
			'FROM'		=> 'topics AS t',
			'WHERE'		=> 't.id_topic > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		$this->redirect('topics', 'id_topic', $start_at);
	}

	function convert_users($start_at)
	{
		// Add salt field to the users table to allow login
		$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true);

		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS id, id_group AS group_id, member_name AS username, passwd AS password, password_salt AS salt, website_url AS url, icq AS icq, msn AS msn, aim AS aim, yim AS yahoo, signature AS signature, time_offset AS timezone, posts AS num_posts, date_registered AS registered, last_login AS last_visit, location AS location, email_address AS email',
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

			$result_post = $this->db->query_build(array(
				'SELECT'	=> 'poster_time',
				'FROM'		=> 'messages',
				'WHERE'		=> 'id_member='.$cur_user['id'],
				'ORDER BY'	=> 'poster_time DESC',
				'LIMIT'		=> 1
			)) or error('Unable to fetch last post', __FILE__, __LINE__, $this->db->error());

			if ($this->db->num_rows($result_post))
				$cur_user['last_post'] = $this->db->result($result_post);

			$this->fluxbb->add_row('users', $cur_user);
		}

		$this->redirect('members', 'id_member', $start_at);
	}

	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(0 => 4, 3 => 2, 5 => 4, 6 => 4, 7 => 4, 8 => 4);

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
					'SELECT'	=> 'MAX(id_member)',
					'FROM'		=> 'members',
				)) or error('Unable to fetch last user id', __FILE__, __LINE__, $this->db->error());

				$last_uid = $this->db->result($result) + 1;
			}
			return $last_uid;
		}

		return $id;
	}

	// Convert posts BB-code
	function convert_message($message)
	{
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		$replace = array(
			'#\\[quote author=(.*?) link(.*?)\](.*?)\[/quote\]#is'	=>	'[quote=$1]$3[/quote]',
			'#\\[flash=(.*?)\](.*?)\[/flash\]#is'					=>	'Flash: $2',
			'#\\[ftp=(.*?)\](.*?)\[/ftp\]#is'						=>	'[url=$1]$2[/url]',
			'#\\[list=?.*?\](.*?)\[/list\]#is'						=>	'[list]$1[/list]',
			'#\\[li\](.*?)\[/li\]#is'								=>	'[*]$1[/*]',

			'#\\[table\](.*?)\[/table\]#is'							=>	'$1',
			'#\\[tr\]#is'											=>	'[list]',
			'#\\[/tr\]#is'											=>	'[/list]',
			'#\\[td\](.*?)\[/td\]#is'								=>	'[*]$1[/*]',

			'#\\[hr\]#is'											=>	'[b]$1[/b]'."\n",
		);
		$message = preg_replace(array_keys($replace), array_values($replace), $message);

		// Strip tags that are not supported by FluxBB
		$strip_tags = array('font', 'size', 'glow', 's', 'shadow', 'move', 'pre', 'left', 'right', 'center', 'sup', 'sub', 'tt');
		foreach ($strip_tags as $cur_tag)
		{
			$message = preg_replace('%\['.preg_quote($cur_tag).'(=.*?)?\]%i', '', $message);
			$message = preg_replace('%\[/'.preg_quote($cur_tag).'\]%i', '', $message);
		}

		$replace = array(
			'<br />'	=>	"\n",
			'::)'		=>	':rolleyes:',
		);
		$message = str_replace(array_keys($replace), array_values($replace), $message);

		return $message;
	}
}
