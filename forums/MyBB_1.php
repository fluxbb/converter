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

class MyBB_1 extends Forum
{
	// Will the passwords be converted?
	var $converts_password = false;

	var $steps = array(
		'bans'					=> array('banned', 'uid'),
		'categories'			=> array('forums', 'fid', 'type = \'c\''),
		'censoring'				=> 0,//array('words', 'word_id'),
		'config'				=> -1,
		'forums'				=> array('forums', 'fid', 'type = \'f\''),
//		'forum_perms'			=> 0,
		'groups'				=> array('usergroups', 'gid', 'gid > 7'),
		'posts'					=> array('posts', 'pid'),
		'ranks'					=> array('usertitles', 'utid'),
		'reports'				=> array('reportedposts', 'rid'),
		'topic_subscriptions'	=> array('threadsubscriptions', 'tid'),
		'forum_subscriptions'	=> array('forumsubscriptions', 'fid'),
		'topics'				=> array('threads', 'tid'),
		'users'					=> array('users', 'uid'),
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
		if (!$this->db->field_exists('banned', 'uid'))
			conv_error('Selected database does not contain valid MyBB installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'u.username AS username, './*b.ban_ip AS ip, b.ban_email AS email, */'b.reason AS message',//, b.ban_end AS expire',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.uid=b.uid'
				),
			),
			'FROM'		=> 'banned AS b',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		// FIXME: Subforums might cause difficulties
		$result = $this->db->query_build(array(
			'SELECT'	=> 'fid AS id, name AS cat_name, disporder AS disp_position',
			'FROM'		=> 'forums',
			'WHERE'		=> 'type = \'c\'',
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
//			'SELECT'	=> 'word_id AS id, word AS search_for, replacement AS replace_with',
//			'FROM'		=> 'words',
//		)) or conv_error('Unable to fetch words', __FILE__, __LINE__, $this->db->error());

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
			'SELECT'	=> 'name, value',
			'FROM'		=> 'settings',
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['name']] = $cur_config['value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> $old_config['bbname'],
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
			'o_show_dot'				=> $old_config['dotfolders'],
			'o_topic_views'				=> 1,
			'o_quickjump'				=> 1,
			'o_gzip'					=> 0,
			'o_additional_navlinks'		=> '',
			'o_report_method'			=> ($old_config['reportmethod'] == 'email') ? 1 : 0,
			'o_regs_report'				=> 0,
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> $old_config['adminemail'],
			'o_avatars'					=> 1,
//			'o_avatars_dir'				=> 'img/avatars', // No need to change this value
			'o_avatars_width'			=> 60,
			'o_avatars_height'			=> 60,
			'o_avatars_size'			=> 10240,
			'o_search_all_forums'		=> 1,
//			'o_base_url'				=> '', // No need to change this value
			'o_admin_email'				=> $old_config['adminemail'],
			'o_webmaster_email'			=> $old_config['adminemail'],
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> $old_config['smtp_host'].(!empty($old_config['smtp_port']) ? ':'.$old_config['smtp_port'] : ''),
			'o_smtp_user'				=> $old_config['smtp_user'],
			'o_smtp_pass'				=> $old_config['smtp_pass'],
			'o_smtp_ssl'				=> $old_config['secure_smtp'] == 1,
			'o_regs_allow'				=> $old_config['disableregs'] == 0,
			'o_regs_verify'				=> $old_config['regtype'] == 'verify',
			'o_announcement'			=> 0,
			'o_announcement_message'	=> 'Enter your announcement here.',
			'o_rules'					=> 0,
			'o_rules_message'			=> 'Enter your rules here',
			'o_maintenance'				=> 0,
			'o_maintenance_message'		=> 'The forums are temporarily down for maintenance. Please try again in a few minutes.',
			'o_default_dst'				=> $old_config['dstcorrection'],
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
			'SELECT'	=> 'f.fid AS id, f.name AS forum_name, f.description AS forum_desc, IF(f.linkto=\'\', NULL, f.linkto) AS redirect_url, f.threads AS num_topics, f.posts AS num_posts, f.disporder AS disp_position, f.lastposter AS last_poster, f.lastpost AS last_post, f.parentlist AS cat_id, f.lastposttid',
			'FROM'		=> 'forums AS f',
			'WHERE'		=> 'type = \'f\'',
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			if ($cur_forum['num_topics'] == 0)
				$cur_forum['last_post_id'] = $cur_forum['last_poster'] = $cur_forum['last_post'] = NULL;
			else
			{
				$result_last_post_id = $this->db->query_build(array(
					'SELECT'	=> 'MAX(pid)',
					'FROM'		=> 'posts',
					'WHERE'		=> 'tid = '.$cur_forum['lastposttid']
				)) or conv_error('Unable to fetch forum last post', __FILE__, __LINE__, $this->db->error());

				$cur_forum['last_post_id'] = $this->db->result($result_last_post_id);
			}
			unset($cur_forum['lastposttid']);

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	function convert_forum_perms()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'gid AS group_id, fid AS forum_id, canview AS read_forum, canpostreplys AS post_replies, canpostreplys AS post_topics',
			'FROM'		=> 'forumpermissions',
		)) or conv_error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forum_perms', $this->db->num_rows($result));
		while ($cur_perm = $this->db->fetch_assoc($result))
		{
			$cur_perm['group_id'] = $this->grp2grp($cur_perm['group_id']);

			$this->fluxbb->add_row('forum_perms', $cur_perm);
		}
	}

	function convert_groups()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'gid AS g_id, title AS g_title, usertitle AS g_user_title, canmodcp AS g_moderator',
			'FROM'		=> 'usergroups',
			'WHERE'		=> 'gid > 7'
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
			'SELECT'	=> 'pid AS id, username AS poster, uid AS poster_id, dateline AS posted, ipaddress AS poster_ip, message AS message, tid AS topic_id, smilieoff AS hide_smilies',
			'FROM'		=> 'posts',
			'WHERE'		=> 'pid > '.$start_at,
			'ORDER BY'	=> 'pid ASC',
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

		return $this->redirect('posts', 'pid', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'utid AS id, title AS rank, posts AS min_posts',
			'FROM'		=> 'usertitles',
		)) or conv_error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'rid AS id, pid AS post_id, tid AS topic_id, fid AS forum_id, dateline AS created, reason AS message, reportstatus AS zapped',
			'FROM'		=> 'reportedposts',
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
			'SELECT'	=> 'uid AS user_id, tid AS topic_id',
			'FROM'		=> 'threadsubscriptions',
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
			'SELECT'	=> 'uid AS user_id, fid AS forum_id',
			'FROM'		=> 'forumsubscriptions',
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
			'SELECT'	=> 't.tid AS id, t.username AS poster, t.subject, t.dateline AS posted, t.views AS num_views, t.replies AS num_replies, t.firstpost AS first_post_id, t.lastpost AS last_post, t.lastposter AS last_poster, t.sticky, t.closed, t.fid AS forum_id',
			'FROM'		=> 'threads AS t',
			'WHERE'		=> 't.tid > '.$start_at,
			'ORDER BY'	=> 't.tid ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('topics', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];

			$result_last_post = $this->db->query_build(array(
				'SELECT'	=> 'p.pid',
				'FROM'		=> 'posts AS p',
				'WHERE'		=> 'p.tid = '.$cur_topic['id'],
				'ORDER BY'	=> 'p.pid DESC',
				'LIMIT'		=> '1',
			)) or conv_error('Unable to fetch last post for topic', __FILE__, __LINE__, $this->db->error());
			if ($this->db->num_rows($result_last_post))
				$cur_topic['last_post_id'] = $this->db->result($result_last_post);

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		return $this->redirect('threads', 'tid', $start_at);
	}

	function convert_users($start_at)
	{
		// Add salt field to the users table to allow login
		if ($start_at == 0)
			$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true, '', 'password') or conv_error('Unable to add field', __FILE__, __LINE__, $this->db->error());

		$result = $this->db->query_build(array(
			'SELECT'	=> 'uid AS id, username AS username, password AS password, email, avatar, salt AS salt, website AS url, icq AS icq, msn AS msn, aim AS aim, yahoo AS yahoo, postnum AS num_posts, IF(hideemail=1, 1, 0) AS email_setting, timezone, lastvisit AS last_visit, signature, regdate AS registered, usergroup AS group_id',
			'FROM'		=> 'users',
			'WHERE'		=> 'uid > '.$start_at,
			'ORDER BY'	=> 'uid ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('users', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);
			$cur_user['id'] = $this->uid2uid($cur_user['id']);

			$this->convert_avatar($cur_user);
			unset($cur_user['avatar']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		return $this->redirect('users', 'uid', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(1 => PUN_GUEST, 2 => PUN_MEMBER, 3 => PUN_MOD, 4 => PUN_ADMIN, 5 => PUN_UNVERIFIED, 6 => PUN_MOD, 7 => PUN_UNVERIFIED);

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

		if ($id == 0)
			return 1;

		// id=1 is reserved for the guest user
		else if ($id == 1)
		{
			if (!isset($last_uid))
			{
				$result = $this->db->query_build(array(
					'SELECT'	=> 'MAX(uid)',
					'FROM'		=> 'users',
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
		global $re_list;

		$errors = array();
		require_once PUN_ROOT.'include/parser.php';

		if (!isset($patterns))
		{
			$patterns = array(
				'%\[quote=\'(.*?)\'.*?\]\s*%si'								=>	'[quote=$1]',
				'%\[/?(font|size|align)(?:\=[^\]]*)?\]%i'					=>	'', // Strip tags not supported by FluxBB
			);
		}

		$message = preg_replace(array_keys($patterns), array_values($patterns), $message);

		if (!isset($replacements))
		{
			$replacements = array(
				'[php]'		=>	'[code]',
				'[/php]'	=>	'[/code]',
			);
		}
		return preparse_bbcode(str_replace(array_keys($replacements), array_values($replacements), $message), $errors);
	}


	/**
	 * Copy avatar file to the FluxBB avatars dir
	 */
	function convert_avatar($cur_user)
	{
		static $config;

		if (empty($cur_user['avatar']))
			return false;

		if (($pos = strpos($cur_user['avatar'], '?dateline=')) !== false)
			$cur_user['avatar'] = substr($cur_user['avatar'], 0, $pos);

		// Fetch avatar from remote url
		if (strpos($cur_user['avatar'], '://') !== false)
			return $this->fluxbb->save_avatar($cur_user['avatar'], $cur_user['id']);

		// Copy local avatar file
		else if (isset($this->path))
		{
			$cur_avatar_file = $this->path.$cur_user['avatar'];
			if (file_exists($cur_avatar_file))
				return $this->fluxbb->save_avatar($cur_avatar_file, $cur_user['id']);
		}

		return false;
	}
}
