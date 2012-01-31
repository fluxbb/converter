<?php

/**
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.8');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class SMF_1_1 extends Forum
{
	// Will the passwords be converted?
	const CONVERTS_PASSWORD = true;

	public $steps = array(
		'bans'					=> array('ban_items', 'ID_BAN'),
		'categories'			=> array('categories', 'ID_CAT'),
		'censoring'				=> -1,
		'config'				=> -1,
		'forums'				=> array('boards', 'ID_BOARD'),
//		'forum_perms'			=> 0,
		'groups'				=> array('membergroups', 'ID_GROUP', 'minPosts = -1 AND ID_GROUP > 3'),
		'posts'					=> array('messages', 'ID_MSG'),
		'ranks'					=> array('membergroups', 'ID_GROUP', 'minPosts <> -1'),
		// 'reports'				=> array('reports', 'report_id'),
		'topic_subscriptions'	=> array('log_notify', 'ID_TOPIC', 'ID_TOPIC > 0'),
		'forum_subscriptions'	=> array('log_notify', 'ID_BOARD', 'ID_BOARD > 0'),
		'topics'				=> array('topics', 'ID_TOPIC'),
		'users'					=> array('members', 'ID_MEMBER'),
	);

	function initialize()
	{
//		$this->db->set_names('utf8');
	}

	/**
	 * Check whether specified database has valid current forum software strucutre
	 */
	function validate()
	{
		if (!$this->db->field_exists('categories', 'catOrder'))
			conv_error('Selected database does not contain valid SMF installation');
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
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'bans', $this->db->num_rows($result));
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
			'SELECT'	=> 'ID_CAT AS id, name AS cat_name, catOrder AS disp_position',
			'FROM'		=> 'categories',
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'categories', $this->db->num_rows($result));
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

	// TODO
	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'conf_name, conf_value',
			'FROM'		=> 'config',
		));

		conv_message('Processing', 'config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['conf_name']] = $cur_config['conf_value'];

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
			'o_ranks'					=> 1,
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
			'o_smtp_host'				=> '',
			'o_smtp_user'				=> '',
			'o_smtp_pass'				=> '',
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
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.ID_BOARD AS id, b.name AS forum_name, b.description AS forum_desc, b.numTopics AS num_topics, b.numPosts AS num_posts, m.posterTime AS last_post, b.ID_LAST_MSG AS last_post_id, m.posterName AS last_poster, b.boardOrder AS disp_position, b.ID_CAT AS cat_id',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'	=> 'messages AS m',
					'ON'		=> 'm.ID_MSG = b.ID_LAST_MSG'
				)
			),
			'FROM'		=> 'boards AS b',
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forums', $this->db->num_rows($result));
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
			'SELECT'	=> 'ID_GROUP AS g_id, groupName AS g_title',//, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'minPosts = -1 AND ID_GROUP > 3'
		)) or conv_error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'groups', $this->db->num_rows($result));
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
			'ORDER BY'	=> 'ID_MSG ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch messages', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['edited'] = NULL;
			$cur_post['edited_by'] = NULL;
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);
			$cur_post['message'] = $this->convert_message($cur_post['message']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('messages', 'ID_MSG', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_GROUP AS id, groupName AS rank, minPosts AS min_posts',
			'FROM'		=> 'membergroups',
			'WHERE'		=> 'minPosts <> -1',
		)) or conv_error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'ranks', $this->db->num_rows($result));
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
			'SELECT'	=> 'ID_MEMBER AS user_id, ID_TOPIC AS topic_id',
			'FROM'		=> 'log_notify',
			'WHERE'		=> 'ID_TOPIC > 0',
		)) or conv_error('Unable to fetch log notify', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'topic subscriptions', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch forum subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forum subscriptions', $this->db->num_rows($result));
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
			'ORDER BY'	=> 't.ID_TOPIC ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];
			$cur_topic['moved_to'] = NULL;

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		return $this->redirect('topics', 'ID_TOPIC', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ID_MEMBER AS id, ID_GROUP AS group_id, memberName AS username, passwd AS password, passwordSalt AS salt, websiteUrl AS url, ICQ AS icq, MSN AS msn, AIM AS aim, YIM AS yahoo, signature AS signature, timeOffset AS timezone, posts AS num_posts, dateRegistered AS registered, lastLogin AS last_visit, location AS location, emailAddress AS email',
			'FROM'		=> 'members',
			'WHERE'		=> 'ID_MEMBER > '.$start_at,
			'ORDER BY'	=> 'ID_MEMBER ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['id'] = $this->uid2uid($cur_user['id']);
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		return $this->redirect('members', 'ID_MEMBER', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(0 => PUN_UNVERIFIED, 1 => PUN_ADMIN, 2 => PUN_GUEST, 3 => PUN_MEMBER, 4 => PUN_MOD);

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
					'SELECT'	=> 'MAX(ID_MEMBER)',
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
		static $replacements;
		global $re_list;

		$errors = array();
		require_once PUN_ROOT.'include/parser.php';

		$message = $this->convert_to_utf8($message);

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

		return preparse_bbcode(str_replace(array_keys($replacements), array_values($replacements), $message), $errors);
	}
}
