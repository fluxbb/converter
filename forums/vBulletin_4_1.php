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

class VBulletin_4_1 extends Forum
{
	// Will the passwords be converted?
	const CONVERTS_PASSWORD = false;

	public $steps = array(
		'bans'					=> array('userban', 'userid'),
		'categories'			=> array('forum', 'forumid', 'parentid = -1'),
		'censoring'				=> 0,//array('words', 'word_id'),
		'config'				=> -1,
		'forums'				=> array('forum', 'forumid', 'parentid <> -1'),
//		'forum_perms'			=> 0,
		'groups'				=> array('usergroup', 'usergroup', 'usergroupid > 8'),
		'posts'					=> array('post', 'postid'),
		'ranks'					=> array('usertitle', 'usertitleid'),
		// 'reports'				=> array('reports', 'report_id'),
		'topic_subscriptions'	=> array('subscribethread', 'threadid'),
		'forum_subscriptions'	=> array('subscribeforum', 'forumid'),
		'topics'				=> array('thread', 'threadid'),
		'users'					=> array('user', 'userid'),
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
		if (!$this->db->field_exists('forum', 'forumid'))
			conv_error('Selected database does not contain valid vBulletin installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.userid AS id, u.username, b.reason AS message, IF(b.liftdate=0,NULL,b.liftdate) AS expire, b.adminid AS ban_creator',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'user AS u',
					'ON'		=> 'u.userid = b.userid'
				),
			),
			'FROM'		=> 'userban AS b',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forumid AS id, title AS cat_name, displayorder AS disp_position',
			'FROM'		=> 'forum',
			'WHERE'		=> 'parentid = -1'
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

//	function convert_censoring()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'id, search_for, replace_with',
//			'FROM'		=> 'censoring',
//		)) or conv_error('Unable to fetch censoring', __FILE__, __LINE__, $this->db->error());
//
//		conv_processing_message('censoring', $this->db->num_rows($result));
//		while ($cur_censor = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('censoring', $cur_censor);
//		}
//	}

	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'varname, value',
			'FROM'		=> 'setting',
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['varname']] = $cur_config['value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> $old_config['bbtitle'],
			'o_board_desc'				=> '<p><span>Unfortunately no one can be told what FluxBB is - you have to see it for yourself.</span></p>',
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
			'o_quickpost'				=> $old_config['quickedit'],
			'o_users_online'			=> 1,
			'o_censoring'				=> $old_config['enablecensor'],
			'o_ranks'					=> 1,
			'o_show_dot'				=> $old_config['showdots'],
			'o_topic_views'				=> $old_config['threadpreview'],
			'o_quickjump'				=> 1,
			'o_gzip'					=> 0,
			'o_additional_navlinks'		=> '',
			'o_report_method'			=> 0,
			'o_regs_report'				=> 0,
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> $old_config['webmasteremail'],
			'o_avatars'					=> $old_config['avatarenabled'],
//			'o_avatars_dir'				=> 'img/avatars', // No need to change this value
			'o_avatars_width'			=> 60,
			'o_avatars_height'			=> 60,
			'o_avatars_size'			=> 10240,
			'o_search_all_forums'		=> 1,
//			'o_base_url'				=> '', // No need to change this value
			'o_admin_email'				=> $old_config['webmasteremail'],
			'o_webmaster_email'			=> $old_config['webmasteremail'],
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> $old_config['smtp_host'].(empty($old_config['smtp_port']) ? '' : ':'.$old_config['smtp_port']),
			'o_smtp_user'				=> $old_config['smtp_user'],
			'o_smtp_pass'				=> $old_config['smtp_pass'],
			'o_smtp_ssl'				=> $old_config['smtp_tls'] == 'ssl',
			'o_regs_allow'				=> $old_config['allowregistration'],
			'o_regs_verify'				=> $old_config['verifyemail'],
			'o_announcement'			=> 0,
			'o_announcement_message'	=> 'Enter your announcement here.',
			'o_rules'					=> 0,
			'o_rules_message'			=> 'Enter your rules here',
			'o_maintenance'				=> $old_config['bbactive'] == 1,
			'o_maintenance_message'		=> $old_config['bbclosedreason'],
			'o_default_dst'				=> $old_config['dstonoff'],
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

		foreach ($old_config as $key => $value)
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
			'SELECT'	=> 'forumid AS id, title AS forum_name, description AS forum_desc, threadcount AS num_topics, replycount AS num_posts, displayorder AS disp_position, lastposter AS last_poster, lastpostid AS last_post_id, lastpost AS last_post, parentlist AS cat_id',
			'FROM'		=> 'forum',
			'WHERE'		=> 'parentid <> -1'
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$parent_list = explode(',', $cur_forum['cat_id']);
			$cur_forum['cat_id'] = $parent_list[count($parent_list) - 2];

			if ($cur_forum['last_post'] == 0)
				$cur_forum['last_post'] = $cur_forum['last_post_id'] = NULL;

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

//	function convert_forum_perms()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
//			'FROM'		=> 'forum_perms',
//		)) or conv_error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());
//
//		conv_processing_message('forum_perms', $this->db->num_rows($result));
//		while ($cur_perm = $this->db->fetch_assoc($result))
//		{
//			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);
//
//			$this->fluxbb->add_row('forum_perms', $cur_perm);
//		}
//	}

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'usergroupid AS g_id, title AS g_title, usertitle AS g_user_title',//, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'usergroup',
			'WHERE'		=> 'usergroupid > 8',
		)) or conv_error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'postid AS id, username AS poster, userid AS poster_id, dateline AS posted, ipaddress AS poster_ip, pagetext AS message, IF(allowsmilie=1, 0, 1) AS hide_smilies, threadid as topic_id',
			'FROM'		=> 'post',
			'WHERE'		=> 'postid > '.$start_at,
			'ORDER BY'	=> 'postid ASC',
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

			$result_edit = $this->db->query_build(array(
				'SELECT'	=> 'dateline AS edited, username AS edited_by',
				'FROM'		=> 'postedithistory',
				'WHERE'		=> 'original = 0 AND postid = '.$cur_post['id'],
				'ORDER BY'	=> 'dateline DESC',
				'LIMIT'		=> 1
			)) or conv_error('Unable to fetch last post', __FILE__, __LINE__, $this->db->error());

			if ($this->db->num_rows($result_edit))
				$cur_post = array_merge($cur_post, $this->db->fetch_assoc($result_edit));

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('post', 'postid', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'usertitleid AS id, title AS rank, minposts AS min_posts',
			'FROM'		=> 'usertitle',
		)) or conv_error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

//	function convert_reports()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'id, post_id, topic_id, forum_id, reported_by, created, message, zapped, zapped_by',
//			'FROM'		=> 'reports',
//		)) or conv_error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());
//
//		conv_processing_message('reports', $this->db->num_rows($result));
//		while ($cur_report = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('reports', $cur_report);
//		}
//	}

	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'userid AS user_id, threadid AS topic_id',
			'FROM'		=> 'subscribethread',
		)) or conv_error('Unable to fetch subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	function convert_forum_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'userid AS user_id, forumid AS forum_id',
			'FROM'		=> 'subscribeforum',
		)) or conv_error('Unable to fetch subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forum subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('forum_subscriptions', $cur_sub);
		}
	}

	function convert_topics($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'threadid AS id, postusername AS poster, title AS subject, dateline AS posted, views AS num_views, replycount AS num_replies, lastpost AS last_post, lastpostid AS last_post_id, lastposter AS last_poster, sticky AS sticky, IF(open=0, 1, 0) AS closed, forumid AS forum_id',
			'FROM'		=> 'thread',
			'WHERE'		=> 'threadid > '.$start_at,
			'ORDER BY'	=> 'threadid ASC',
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

		return $this->redirect('thread', 'threadid', $start_at);
	}

	function convert_users($start_at)
	{
		// Add salt field to the users table to allow login
		if ($start_at == 0)
			$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true, '', 'password') or conv_error('Unable to add field', __FILE__, __LINE__, $this->db->error());

		$result = $this->db->query_build(array(
			'SELECT'	=> 'u.userid AS id, u.username, u.password, u.salt, u.timezoneoffset AS timezone, u.posts AS num_posts, u.joindate AS registered, u.lastvisit AS last_visit, u.email, u.usergroupid AS group_id, u.lastpost AS last_post, t.signature, t.rank AS title',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'usertextfield AS t',
					'ON'		=> 'u.userid = t.userid'
				),
			),
			'FROM'		=> 'user AS u',
			'WHERE'		=> 'u.userid > '.$start_at,
			'ORDER BY'	=> 'u.userid ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('users', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['id'] = $this->uid2uid($cur_user['id']);
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		return $this->redirect('user', 'userid', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(1 => PUN_GUEST, 2 => PUN_MEMBER, 3 => PUN_UNVERIFIED, 4 => PUN_UNVERIFIED, 5 => PUN_MOD, 6 => PUN_ADMIN, 7 => PUN_MOD, 8 => PUN_UNVERIFIED);

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

		// id=1 is reserved for the guest user
		if ($id == 1)
		{
			if (!isset($last_uid))
			{
				$result = $this->db->query_build(array(
					'SELECT'	=> 'MAX(userid)',
					'FROM'		=> 'user',
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
				'%\[QUOTE=(.*?);\d+\]%si'								=>	'[quote=$1]',
				'%\[(/?)(HTML|PHP)\]%i'									=>	'[$1code]',
				'%\[/?(FONT|SIZE|INDENT|LEFT|RIGHT|CENTER|SUP|SUB|TABLE)(?:\=[^\]]*)?\]%i'	=> '',	// Strip tags not supported by FluxBB
				'%\[([A-Z]+\=)"([^\]]*)"\]%i'							=>	'[$1$2]',	// Strip double quotes from param
				'%\[(URL|IMG)(=[^\]]*)?\]\s*(.*?)\s*\[/\1\]%si'			=>	'[$1$2]$3[/$1]',	// Strip multiline characters from img and url tags
				'%\[(/?)([A-Z]+)(=[^\]]*)?\]%sie'						=>	'\'[$1\'.strtolower(\'$2\').\'$3]\'',	// Convert tag name to lower case
			);
		}

		$message = preg_replace(array_keys($patterns), array_values($patterns), $message);

		if (!isset($replacements))
		{
			$replacements = array(
				'[video'	=>	'[url',		// Allow [video=something]
				'[/video]'	=>	'[/url]',
				'[tr]'		=>	'[list]',
				'[/tr]'		=>	'[/list]',
				'[td]'		=>	'[*]',
				'[/td]'		=>	'[/*]',
				'[hr]'		=>	"\n",
				'[/hr]'		=>	'',
				':confused:'=>	':rolleyes:',
				':eek:'		=>	':o',
			);
		}

		return $this->fluxbb->preparse_bbcode(str_replace(array_keys($replacements), array_values($replacements), $message), $errors);
	}
}
