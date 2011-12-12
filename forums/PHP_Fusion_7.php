<?php

/**
* Copyright (C) 2011 FluxBB (http://fluxbb.org)
* License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
*/

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.7');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class PHP_Fusion_7 extends Forum
{
	// Will the passwords be converted?
	const CONVERTS_PASSWORD = false;

	public $steps = array(
		'bans',
		'categories',
		'censoring',
		'config',
		'forums',
//		'forum_perms',
		'groups',
		'posts',
		'ranks',
		'reports',
		'topic_subscriptions',
		'forum_subscriptions',
		'topics',
		'users',
	);

	function initialize()
	{
		$this->db->set_names('utf8');
	}

	/**
	 * Check whether specified database has valid current forum software strucutre
	 */
	function validate()
	{
		if (!$this->db->field_exists('forums', 'forum_cat'))
			conv_error('Selected database does not contain valid PHP Fusion installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.blacklist_id AS id, u.user_name AS username, b.blacklist_ip AS ip, b.blacklist_email AS email, b.blacklist_reason AS message, NULL AS expire',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=b.blacklist_user_id'
				),
			),
			'FROM'		=> 'blacklist AS b',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forum_id AS id, forum_name AS cat_name, forum_order AS disp_position',
			'FROM'		=> 'forums',
			'WHERE'		=> 'forum_cat = 0',
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_censoring()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'settings_value',
			'FROM'		=> 'settings',
			'WHERE'		=> 'settings_name = \'bad_words\''
		)) or conv_error('Unable to fetch words', __FILE__, __LINE__, $this->db->error());

		$censor_words = explode("\n", trim($this->db->result($result), "\n"));

		conv_message('Processing num', 'censors', count($censor_words));
		foreach ($censor_words as $cur_word)
		{
			$this->fluxbb->add_row('censoring', array('search_for' => $cur_word, 'replace_With' => '****'));
		}
	}

	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'settings_name, settings_value',
			'FROM'		=> 'settings',
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['settings_name']] = $cur_config['settings_value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> $old_config['sitename'],
			'o_board_desc'				=> $old_config['description'],
			'o_default_timezone'		=> $old_config['timeoffset'],
			'o_time_format'				=> 'H:i:s',
			'o_date_format'				=> 'Y-m-d',
			'o_timeout_visit'			=> 30000,
			'o_timeout_online'			=> 3000,
			'o_redirect_delay'			=> 1,
			'o_show_version'			=> 0,
			'o_show_user_info'			=> 1,
			'o_show_post_count'			=> 1,
			'o_signatures'				=> 1,
			'o_smilies'					=> 1,
			'o_smilies_sig'				=> 1,
			'o_make_links'				=> 1,
//			'o_default_lang'			=> 'English', // No need to change this value
//			'o_default_style'			=> 'Air', // No need to change this value
			'o_default_user_group'		=> 4,
			'o_topic_review'			=> 15,
			'o_disp_topics_default'		=> 30,
			'o_disp_posts_default'		=> 25,
			'o_indent_num_spaces'		=> 4,
			'o_quote_depth'				=> 3,
			'o_quickpost'				=> 1,
			'o_users_online'			=> 1,
			'o_censoring'				=> $old_config['bad_words_enabled'],
			'o_ranks'					=> 1,
			'o_show_dot'				=> 0,
			'o_topic_views'				=> 1,
			'o_quickjump'				=> 1,
			'o_gzip'					=> 0,
			'o_additional_navlinks'		=> '',
			'o_report_method'			=> 0,
			'o_regs_report'				=> 0,
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> $old_config['siteemail'], // TODO: admin email?
			'o_avatars'					=> 1,
//			'o_avatars_dir'				=> 'img/avatars', // No need to change this value
			'o_avatars_width'			=> $old_config['avatar_width'],
			'o_avatars_height'			=> $old_config['avatar_height'],
			'o_avatars_size'			=> $old_config['avatar_filesize'],
			'o_search_all_forums'		=> 1,
//			'o_base_url'				=> '', // No need to change this value
			'o_admin_email'				=> $old_config['siteemail'], // TODO: admin email?
			'o_webmaster_email'			=> $old_config['siteemail'], // TODO: admin email?
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> $old_config['smtp_host'].(isset($old_config['smtp_port']) ? ':'.$old_config['smtp_port'] : ''),
			'o_smtp_user'				=> $old_config['smtp_username'],
			'o_smtp_pass'				=> $old_config['smtp_password'],
			'o_smtp_ssl'				=> 0,
			'o_regs_allow'				=> $old_config['enable_registration'],
			'o_regs_verify'				=> $old_config['email_verification'],
			'o_announcement'			=> 1,
			'o_announcement_message'	=> $old_config['siteintro'],
			'o_rules'					=> $old_config['enable_terms'],
			'o_rules_message'			=> 'Enter your rules here',
			'o_maintenance'				=> $old_config['maintenance'],
			'o_maintenance_message'		=> $old_config['maintenance_message'],
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
			'p_force_guest_email'		=> 1,

		);

		foreach ($new_config as $key => $value)
		{
			$this->fluxbb->db->query_build(array(
				'UPDATE'	=> 'config',
				'SET' 		=> 'conf_value = \''.$this->db->escape($value).'\'',
				'WHERE'		=> 'conf_name = \''.$this->db->escape($key).'\'',
			)) or conv_error('Unable to update config', __FILE__, __LINE__, $this->fluxbb->db->error());
		}
	}

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
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			if ($cur_forum['num_topics'] == 0)
				$cur_forum['last_post_id'] = $cur_forum['last_poster'] = $cur_forum['last_post'] = NULL;
			else
			{
				$result_last_post_id = $this->db->query_build(array(
					'SELECT'	=> 'thread_lastpostid',
					'FROM'		=> 'threads',
					'WHERE'		=> 'forum_id = '.$cur_forum['id']
				)) or conv_error('Unable to fetch forum last post', __FILE__, __LINE__, $this->db->error());

				$cur_forum['last_post_id'] = $this->db->result($result_last_post_id);
			}

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	// TODO!!!
//	function convert_forum_perms()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
//			'FROM'		=> 'forum_perms',
//		)) or conv_error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing num', 'forum_perms', $this->db->num_rows($result));
//		while ($cur_perm = $this->db->fetch_assoc($result))
//		{
//			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

//			$this->fluxbb->add_row('forum_perms', $cur_perm);
//		}
//	}

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'group_id AS g_id, group_name AS g_title, group_description AS g_user_title',
			'FROM'		=> 'user_groups',
		)) or conv_error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
//			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

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
			'ORDER BY'	=> 'p.post_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

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
//		)) or conv_error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing num', 'ranks', $this->db->num_rows($result));
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
//		)) or conv_error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing num', 'reports', $this->db->num_rows($result));
//		while ($cur_report = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('reports', $cur_report);
//		}
//	}

	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'notify_user AS user_id, thread_id AS topic_id',
			'FROM'		=> 'thread_notify',
		)) or conv_error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

//	function convert_forum_subscriptions()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'user_id, forum_id',
//			'FROM'		=> 'forums_watch',
//		)) or conv_error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing num', 'forum subscriptions', $this->db->num_rows($result));
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
			'ORDER BY'	=> 'thread_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];

			$result_posted = $this->db->query_build(array(
				'SELECT'	=> 'post_id AS first_post_id, post_datestamp AS posted',
				'FROM'		=> 'posts',
				'WHERE'		=> 'thread_id = '.$cur_topic['id'],
			)) or conv_error('Unable to fetch topic posted', __FILE__, __LINE__, $this->db->error());

			$cur_topic = array_merge($cur_topic, $this->db->fetch_assoc($result_posted));

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		$this->redirect('threads', 'thread_id', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'user_id AS id, user_name AS username, user_password AS password, user_email AS email, user_web AS url, user_icq AS icq, user_msn AS msn, user_yahoo AS yahoo, user_sig AS signature, user_offset AS timezone, user_posts AS num_posts, user_joined AS registered, user_lastvisit AS last_visit, user_location AS location, IF(user_hide_email=0, 1, 0) AS email_setting, user_groups AS group_id',
			'FROM'		=> 'users',
			'WHERE'		=> 'user_id > '.$start_at,
			'ORDER BY'	=> 'user_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];

			$result_last_post = $this->db->query_build(array(
				'SELECT'	=> 'MAX(post_id)',
				'FROM'		=> 'posts',
				'WHERE'		=> 'post_author = '.$cur_user['id'],
			)) or conv_error('Unable to fetch user last post', __FILE__, __LINE__, $this->db->error());
			$cur_user['last_post'] = $this->db->result($result_last_post);

//			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$groups = explode(',', $cur_user['group_id']);
			$cur_user['group_id'] = $cur_user['id'] == 1 ? 1 : (count($groups) ? $groups[0] : 4);
			$cur_user['id'] = $this->uid2uid($cur_user['id']);
			$cur_user['email_setting'] = !$cur_user['email_setting'];
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		$this->redirect('users', 'user_id', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
//	function grp2grp($id)
//	{
//		static $mapping;

//		if (!isset($mapping))
//			$mapping = array(1 => 3, 2 => 4, 3 => 4, 4 => 2, 5 => 1);

//		if (!array_key_exists($id, $mapping))
//			return $id;

//		return $mapping[$id];
//	}

	/**
 	* Convert user id to FluxBB style
	 */
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
				)) or conv_error('Unable to fetch last user', __FILE__, __LINE__, $this->db->error());
				$last_uid = $this->db->result($result) + 1;
			}
			return $last_uid;
		}
		return $id;
	}


	function convert_lists($matches)
	{
		return '[list]'."\n".'[*]'.str_replace("\n", '[/*]'."\n".'[*]', trim($matches[2], "\n")).'[/*][/list]';
	}

	/**
	 * Convert BBcode
	 */
	function convert_message($message)
	{
		static $replacements;

		// Convert lists
		$message = preg_replace_callback('%\[ulist(=.*?)?\](.*?)\[/ulist\]%s', 'PHP_Fusion_7::convert_lists', $message);

		if (!isset($replacements))
		{
			$replacements = array(
				'%\[mail(=.*?)\](.*?)\[/mail\]%si'							=>	'[email$1]$2[/email]',
				'%\[center\](.*?)\[/center\]%si'							=>	'$1',
				'%\[small\](.*?)\[/small\]%si'								=>	'$1',
				'%\[quote\]\[url.*?\[b\](.*?) wrote:\[/b\]\[/url\]\s*%si'	=>	'[quote=$1]',
			);
		}
		return preg_replace(array_keys($replacements), array_values($replacements), $message);
	}
}
