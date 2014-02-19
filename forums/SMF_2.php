<?php

/**
 * @copyright (C) 2012-2014 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.5.6');

define('FORUM_DB_REVISION', 20);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class SMF_2 extends Forum
{
	// Will the passwords be converted?
	var $converts_password = false;

	var $steps = array(
		'bans'					=> array('ban_items', 'id_ban'),
		'categories'			=> array('categories', 'id_cat'),
		'censoring'				=> -1,
		'config'				=> -1,
		'forums'				=> array('boards', 'id_board'),
//		'forum_perms'			=> 0,
		'groups'				=> array('membergroups', 'id_group', 'min_posts = -1 AND id_group > 3'),
		'posts'					=> array('messages', 'id_msg'),
		// 'reports'				=> array('reports', 'report_id'),
		'topic_subscriptions'	=> array('log_notify', 'id_topic', 'id_topic > 0'),
		'forum_subscriptions'	=> array('log_notify', 'id_board', 'id_board > 0'),
		'topics'				=> array('topics', 'id_topic'),
		'users'					=> array('members', 'id_member'),
	);

	function initialize()
	{
		$this->db->set_names('utf8');
	}

	/**
	 * Check whether specified database has valid current forum software structure
	 */
	function validate()
	{
		if (!$this->db->field_exists('ban_items', 'id_ban'))
			conv_error('Selected database does not contain valid SMF installation');
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
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('bans', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('categories', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['variable']] = $cur_config['value'];

		$censor_words = array_combine(explode("\n", $old_config['censor_vulgar']), explode("\n", $old_config['censor_proper']));

		conv_processing_message('censoring', count($censor_words));

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
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['variable']] = $cur_config['value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> 'My FluxBB Forum',
			'o_board_desc'				=> '<p><span>Unfortunately no one can be told what FluxBB is - you have to see it for yourself.</span></p>',
			'o_default_timezone'		=> 0,
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
			'o_censoring'				=> 0,
			'o_show_dot'				=> 0,
			'o_topic_views'				=> 1,
			'o_quickjump'				=> 1,
			'o_gzip'					=> 0,
			'o_additional_navlinks'		=> '',
			'o_report_method'			=> 0,
			'o_regs_report'				=> 0,
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> 'user@example.com',
			'o_avatars'					=> 1,
//			'o_avatars_dir'				=> 'img/avatars', // No need to change this value
			'o_avatars_width'			=> 60,
			'o_avatars_height'			=> 60,
			'o_avatars_size'			=> 10240,
			'o_search_all_forums'		=> 1,
//			'o_base_url'				=> '', // No need to change this value
			'o_admin_email'				=> 'user@example.com',
			'o_webmaster_email'			=> 'user@example.com',
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host' 				=> $old_config['smtp_host'].(!empty($old_config['smtp_host']) && !empty($old_config['smtp_port'])) ? ':'.$old_config['smtp_port'] : '',
			'o_smtp_user' 				=> $old_config['smtp_username'],
			'o_smtp_pass' 				=> $old_config['smtp_password'],
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
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forums', $this->db->num_rows($result));
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
//		)) or conv_error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

//		conv_processing_message('forum_perms', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('groups', $this->db->num_rows($result));
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
			'ORDER BY'	=> 'id_msg ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('posts', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['message'] = $this->convert_message($cur_post['message']);
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('messages', 'id_msg', $start_at);
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_report AS id, id_msg AS post_id, id_topic AS topic_id, id_board AS forum_id, membername AS reported_by, time_started AS created, body AS message, closed AS zapped',
			'FROM'		=> 'log_reported',
		)) or conv_error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('reports', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('topic subscriptions', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forum subscriptions', $this->db->num_rows($result));
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
			'ORDER BY'	=> 't.id_topic ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('topics', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		return $this->redirect('topics', 'id_topic', $start_at);
	}

	function convert_users($start_at)
	{
		// Add salt field to the users table to allow login
		if ($start_at == 0)
			$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true, '', 'password') or conv_error('Unable to add field', __FILE__, __LINE__, $this->db->error());

		$result = $this->db->query_build(array(
			'SELECT'	=> 'id_member AS id, id_group AS group_id, member_name AS username, passwd AS password, password_salt AS salt, website_url AS url, icq AS icq, msn AS msn, aim AS aim, yim AS yahoo, signature AS signature, time_offset AS timezone, posts AS num_posts, date_registered AS registered, last_login AS last_visit, location AS location, email_address AS email',
			'FROM'		=> 'members',
			'WHERE'		=> 'id_member > '.$start_at,
			'ORDER BY'	=> 'id_member ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('users', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['id'] = $this->uid2uid($cur_user['id']);

			$result_post = $this->db->query_build(array(
				'SELECT'	=> 'poster_time',
				'FROM'		=> 'messages',
				'WHERE'		=> 'id_member='.$cur_user['id'],
				'ORDER BY'	=> 'poster_time DESC',
				'LIMIT'		=> 1
			)) or conv_error('Unable to fetch last post', __FILE__, __LINE__, $this->db->error());

			if ($this->db->num_rows($result_post))
				$cur_user['last_post'] = $this->db->result($result_post);

			$this->fluxbb->add_row('users', $cur_user);
		}

		return $this->redirect('members', 'id_member', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(0 => PUN_MEMBER, 3 => PUN_MOD, 5 => PUN_MEMBER, 6 => PUN_MEMBER, 7 => PUN_MEMBER, 8 => PUN_MEMBER);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
	}

	/**
 	* Convert user id to FluxBB style
	 */
	function uid2uid($id)
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
				)) or conv_error('Unable to fetch last user id', __FILE__, __LINE__, $this->db->error());

				$last_uid = $this->db->result($result) + 1;
			}
			return $last_uid;
		}

		return $id;
	}

	/**
	 * Convert BBcode
	 */
	function convert_message($message)
	{
		static $patterns, $replacements;

		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		if (!isset($patterns))
		{
			$patterns = array(
				'%\[quote author=(.*?) link.*?\](.*?)\[/quote\]%si'		=>	'[quote=$1]$2[/quote]',
				'%\[flash=.*?\](.*?)\[/flash\]%si'						=>	'Flash: [url]$1[/url]',
				'%\[ftp=(.*?)\](.*?)\[/ftp\]%si'						=>	'[url=$1]$2[/url]',
				'%\[/?(font|size|glow|s|shadow|move|pre|left|right|center|sup|sub|tt|table)(?:\=[^\]]*)?\]%i'	=> '',	// Strip tags not supported by FluxBB
			);
		}

		$message = preg_replace(array_keys($patterns), array_values($patterns), $message);

		if (!isset($replacements))
		{
			$replacements = array(
				'[li]'		=>	'[*]',
				'[/li]'		=>	'[/*]',
				'[tr]'		=>	'[list]',
				'[/tr]'		=>	'[/list]',
				'[td]'		=>	'[*]',
				'[/td]'		=>	'[/*]',
				'<br />'	=>	"\n",
				'[hr]'		=>	"\n",
				'::)'		=>	':rolleyes:',
			);
		}

		return $this->fluxbb->preparse_bbcode(str_replace(array_keys($replacements), array_values($replacements), $message), $errors);
	}
}
