<?php

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.7');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class PhpBB_3_0_8 extends Forum
{
	function initialize()
	{
		$this->db->set_names('utf8');
	}

	function validate()
	{
		if (!$this->db->field_exists('banlist', 'ban_id'))
			error('Selected database does not contain valid phpBB installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.ban_id AS id, u.username AS username, b.ban_ip AS ip, b.ban_email AS email, b.ban_reason AS message, b.ban_end AS expire',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=b.ban_userid'
				),
			),
			'FROM'		=> 'banlist AS b',
		)) or error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		// FIXME: Subforums might cause difficulties
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forum_id AS id, forum_name AS cat_name',
			'FROM'		=> 'forums',
			'WHERE'		=> 'forum_type = 0',
			'ORDER BY'	=> 'left_id ASC'
		)) or error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'categories', $this->db->num_rows($result));
		$i = 1;
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$cur_cat['disp_position'] = $i;
			$this->fluxbb->add_row('categories', $cur_cat);
			$i++;
		}
	}

	function convert_censoring()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'word_id AS id, word AS search_for, replacement AS replace_with',
			'FROM'		=> 'words',
		)) or error('Unable to fetch words', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'censors', $this->db->num_rows($result));
		while ($cur_censor = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('censoring', $cur_censor);
		}
	}

	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'config_name, config_value',
			'FROM'		=> 'config',
		)) or error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['config_name']] = $cur_config['config_value'];

		$new_config = array(
			'o_board_title'			=> $old_config['sitename'],
			'o_board_desc'			=> $old_config['site_desc'],
			'o_admin_email'			=> $old_config['board_email'],
			'o_server_timezone'		=> $old_config['board_timezone'],
			'o_disp_topics_default'	=> $old_config['topics_per_page'],
			'o_disp_posts_default'	=> $old_config['posts_per_page'],
			'o_webmaster_email'		=> $old_config['board_email'],
			'o_smtp_host'			=> $old_config['smtp_host'],
			'o_smtp_user'			=> $old_config['smtp_username'],
			'o_smtp_pass'			=> $old_config['smtp_password']
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
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forum_id AS id, forum_name AS forum_name, forum_desc AS forum_desc, forum_link AS redirect_url, forum_topics AS num_topics, forum_posts AS num_posts, left_id AS disp_position, forum_last_poster_name AS last_poster, forum_last_post_id AS last_post_id, forum_last_post_time AS last_post, parent_id AS cat_id',
			'FROM'		=> 'forums',
			'WHERE'		=> 'forum_type <> 0',
			'ORDER BY'	=> 'left_id ASC'
		)) or error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
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

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'group_id AS g_id, group_name AS g_title, group_name AS g_user_title',
			'FROM'		=> 'groups',
			'WHERE'		=> 'group_id > 7'
		)) or error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
//			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'p.post_id AS id, u.username AS poster, p.poster_id AS poster_id, p.post_time AS posted, p.poster_ip AS poster_ip, p.post_text AS message, p.topic_id AS topic_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=p.poster_id'
				),
			),
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.post_id > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['message'] = $this->convert_message($cur_post['message']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		$this->redirect('posts', 'post_id', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'rank_id AS id, rank_title AS rank, rank_min AS min_posts',
			'FROM'		=> 'ranks',
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
			'SELECT'	=> 'r.report_id AS id, r.post_id, p.topic_id, p.forum_id, r.user_notify AS reported_by, r.report_time AS created, r.report_text AS message, r.report_closed AS zapped, NULL AS zapped_by',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'posts AS p',
					'ON'		=> 'r.post_id=p.post_id'
				),
			),
			'FROM'		=> 'reports AS r',
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
			'SELECT'	=> 'user_id, topic_id',
			'FROM'		=> 'topics_watch',
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
			'SELECT'	=> 'user_id, forum_id',
			'FROM'		=> 'forums_watch',
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
			'SELECT'	=> 'topic_id AS id, topic_first_poster_name AS poster, topic_title AS subject, topic_time AS posted, topic_first_post_id AS first_post_id, topic_last_post_time AS last_post, topic_last_post_id AS last_post_id, topic_last_poster_name AS last_poster, topic_views AS num_views, topic_replies AS num_replies, IF(topic_status=1, 1, 0) AS closed, IF(topic_type=1, 1, 0) AS sticky, IF(topic_moved_id=0, NULL, topic_moved_id) AS moved_to, forum_id',
			'FROM'		=> 'topics',
			'WHERE'		=> 'topic_id > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];
			$cur_topic['subject'] = html_entity_decode($cur_topic['subject']);

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		$this->redirect('topics', 'topic_id', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'user_id AS id, group_id AS group_id, username AS username, user_password AS password, user_website AS url, user_icq AS icq, user_msnm AS msn, user_aim AS aim, user_yim AS yahoo, user_posts AS num_posts, user_from AS location, user_allow_viewemail AS email_setting, user_timezone AS timezone, user_regdate AS registered, user_lastvisit AS last_visit, user_sig AS signature, user_email AS email',
			'FROM'		=> 'users',
			'WHERE'		=> 'group_id <> 6 AND user_id <> 1 AND user_id > '.$start_at,
			'LIMIT'		=> PER_PAGE,
		)) or error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['username'] = html_entity_decode($cur_user['username']);
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
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


	// Convert posts BB-code
	function convert_message($message)
	{
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		// Strip text after colon in tag name
		$tags = array('b', 'i', 'u', 'list', '*', 'color', 'img', 'url', 'code', 'quote', 'size');
		foreach ($tags as $cur_tag)
			$message = preg_replace('%\[(/?'.preg_quote($cur_tag).')(=.*?)?(:[a-z0-9])?:[a-z0-9]{8}\]%i', '[$1$2]', $message);

		$replace = array(
			// Smileys
			'#<!-- s.*? --><img src=".*?" alt="(.*?)" title=".*?" \/><!-- s.*? -->#i'			=>	'$1',

			'#<!-- [mw] --><a class="postlink" href="(.*?)">(.*?)</a><!-- [mw] -->#i'			=>	'[url=$1]$2[/url]',
			'#<!-- e --><a href="mailto:(.*?)">(.*?)</a><!-- e -->#i'							=>	'[email=$1]$2[/email]',
		);

		$message = preg_replace(array_keys($replace), array_values($replace), $message);

		$smilies = array(
			':shock:'		=> ':o',
			'8-)'			=> ':cool:',
			':evil:'		=> ':/',
			':roll:'		=> ':rolleyes:',
		);

		return str_replace(array_keys($smilies), array_values($smilies), $message);
	}
}
