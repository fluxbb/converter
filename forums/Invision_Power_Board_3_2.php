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

class Invision_Power_Board_3_2 extends Forum
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
		if (!$this->db->field_exists('banfilters', 'ban_id'))
			conv_error('Selected database does not contain valid Invision Power Board installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'ban_id AS id, ban_type, ban_content, ban_reason AS message, 0 AS expire',
			'FROM'		=> 'banfilters',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			switch ($cur_ban['ban_type'])
			{
				// TODO: check ban types
				case 'ip': $cur_ban['ip'] = $cur_ban['ban_content']; break;
				case 'email': $cur_ban['email'] = $cur_ban['ban_content']; break;
				case 'user': $cur_ban['username'] = $cur_ban['ban_content']; break;
			}
			unset($cur_ban['content'], $cur_ban['ban_type']);

			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, name AS cat_name, position AS disp_position',
			'FROM'		=> 'forums',
			'WHERE'		=> 'parent_id = -1',
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
			'SELECT'	=> 'wid AS id, type AS search_for, swop AS replace_with',
			'FROM'		=> 'badwords',
		)) or conv_error('Unable to fetch censoring', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'censors', $this->db->num_rows($result));
		while ($cur_censor = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('censoring', $cur_censor);
		}
	}

	function convert_config()
	{
		return false;
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'conf_key, conf_value',
			'FROM'		=> 'core_sys_conf_settings',
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['conf_key']] = $cur_config['conf_value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> $old_config['board_name'],
			'o_board_desc'				=> '<p><span>Unfortunately no one can be told what FluxBB is - you have to see it for yourself.</span></p>',
			'o_default_timezone'		=> $old_config['time_offset'],
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
			'o_disp_posts_default'		=> $old_config['display_max_posts'],
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
			'o_regs_report'				=> $old_config['new_reg_notify'],
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> $old_config['email_out'],
			'o_avatars'					=> 1,
//			'o_avatars_dir'				=> 'img/avatars', // No need to change this value
			'o_avatars_width'			=> 60,
			'o_avatars_height'			=> 60,
			'o_avatars_size'			=> 10240,
			'o_search_all_forums'		=> 1,
//			'o_base_url'				=> '', // No need to change this value
			'o_admin_email'				=> $old_config['email_out'],
			'o_webmaster_email'			=> $old_config['email_in'],
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> $old_config['smtp_host'].(isset($old_config['smtp_port']) ? ':'.$old_config['smtp_port'] : ''),
			'o_smtp_user'				=> $old_config['smtp_user'],
			'o_smtp_pass'				=> $old_config['smtp_pass'],
			'o_smtp_ssl'				=> 0,
			'o_regs_allow'				=> $old_config['no_reg'] == 0,
			'o_regs_verify'				=> 0,
			'o_announcement'			=> 0,
			'o_announcement_message'	=> 'Enter your announcement here.',
			'o_rules'					=> $old_config['gl_guidelines'],
			'o_rules_message'			=> $old_config['reg_rules'],
			'o_maintenance'				=> $old_config['board_offline'],
			'o_maintenance_message'		=> $old_config['offline_msg'],
			'o_default_dst'				=> $old_config['time_dst_auto_correction'],
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
			'SELECT'	=> 'id, name AS forum_name, description AS forum_desc, topics AS num_topics, posts AS num_posts, position AS disp_position, last_poster_name AS last_poster, last_post, parent_id AS cat_id',
			'FROM'		=> 'forums',
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			if ($cur_forum['num_topics'] == 0)
				$cur_forum['last_post_id'] = $cur_forum['last_poster'] = $cur_forum['last_post'] = NULL;
			else
			{
				$result_last_post_id = $this->db->query_build(array(
					'SELECT'	=> 'MAX(p.pid)',
					'JOINS'        => array(
						array(
							'INNER JOIN'=> 'topics AS t',
							'ON'		=> 'p.topic_id=t.tid'
						),
					),
					'FROM'		=> 'posts AS p',
					'WHERE'		=> 't.forum_id = '.$cur_forum['id']
				)) or conv_error('Unable to fetch forum last post', __FILE__, __LINE__, $this->db->error());

				$cur_forum['last_post_id'] = $this->db->result($result_last_post_id);
			}

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
			'SELECT'	=> 'g_id, g_title',
			'FROM'		=> 'groups',
			'WHERE'		=> 'g_id > 6',
		)) or conv_error('Unable to fetch groups', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
			$cur_group['g_id'] = $this->grp2grp($cur_group['g_id']);

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'pid AS id, author_name AS poster, author_id AS poster_id, ip_address AS poster_ip, post AS message, IF(use_emo=1, 0, 1) AS hide_smilies, post_date AS posted, edit_time AS edited, edit_name AS edited_by, topic_id',
			'FROM'		=> 'posts',
			'WHERE'		=> 'pid > '.$start_at,
			'ORDER BY'	=> 'pid ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];

			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);
			$cur_post['message'] = $this->convert_message($cur_post['message']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('posts', 'pid', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, title AS rank, posts AS min_posts',
			'FROM'		=> 'titles',
		)) or conv_error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'rid AS id, id AS post_id, report_by AS reported_by, date_reported AS created, report AS message',
			'FROM'		=> 'rc_reports',
		)) or conv_error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'reports', $this->db->num_rows($result));
		while ($cur_report = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('reports', $cur_report);
		}
	}

	//function convert_topic_subscriptions()
//	{
//		$result = $this->db->query_build(array(
//			'SELECT'	=> 'user_id, topic_id',
//			'FROM'		=> 'subscriptions',
//		)) or conv_error('Unable to fetch subscriptions', __FILE__, __LINE__, $this->db->error());

//		conv_message('Processing num', 'subscriptions', $this->db->num_rows($result));
//		while ($cur_sub = $this->db->fetch_assoc($result))
//		{
//			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
//		}
//	}

//	function convert_forum_subscriptions()
//	{
//		conv_message('No forum subscriptions', $this->db->num_rows($result));
//	}

	function convert_topics($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'tid AS id, starter_name AS poster, title AS subject, start_date AS posted, views AS num_views, posts AS num_replies, last_post, last_poster_name AS last_poster, pinned AS sticky, moved_to, forum_id',
			'FROM'		=> 'topics',
			'WHERE'		=> 'tid > '.$start_at,
			'ORDER BY'	=> 'tid ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];

			$result_last_post_id = $this->db->query_build(array(
				'SELECT'	=> 'MAX(pid)',
				'FROM'		=> 'posts',
				'WHERE'		=> 'topic_id = '.$cur_topic['id']
			)) or conv_error('Unable to fetch topic last post', __FILE__, __LINE__, $this->db->error());

			$cur_topic['last_post_id'] = $this->db->result($result_last_post_id);
			$cur_topic['subject'] = html_entity_decode($cur_topic['subject'], ENT_QUOTES, 'UTF-8');

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		return $this->redirect('topics', 'tid', $start_at);
	}

	function convert_users($start_at)
	{
		// Add salt field to the users table to allow login
		if ($start_at == 0)
			$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true, '', 'password') or error('Unable to add field', __FILE__, __LINE__, $this->db->error());

		$result = $this->db->query_build(array(
			'SELECT'	=> 'u.member_id AS id, u.member_group_id AS group_id, u.name AS username, u.members_pass_hash AS password, u.members_pass_salt AS salt, u.title, f.field_3 AS url, f.field_4 AS icq, f.field_2 AS msn, f.field_1 AS aim, f.field_8 AS yahoo, f.field_6 AS location, IF(u.time_offset IS NULL, 0, u.time_offset) AS timezone, u.posts AS num_posts, u.last_post, u.view_img AS show_img, 1 AS show_avatars, u.view_sigs AS show_sig, u.joined AS registered, u.ip_address AS registration_ip, u.last_visit AS last_visit, 1 AS email_setting, u.dst_in_use AS dst, p.signature, p.pp_main_photo',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'pfields_content AS f',
					'ON'		=> 'f.member_id = u.member_id'
				),
				array(
					'LEFT JOIN'	=> 'profile_portal AS p',
					'ON'		=> 'p.pp_member_id = u.member_id'
				),
			),
			'FROM'		=> 'members AS u',
			'WHERE'		=> 'u.member_id > '.$start_at,
			'ORDER BY'	=> 'u.member_id ASC',
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

			$this->convert_avatar($cur_user);
			unset($cur_user['pp_main_photo']);

			$this->fluxbb->add_row('users', $cur_user, array($this->fluxbb, 'error_users'));
		}

		return $this->redirect('members', 'member_id', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(1 => PUN_UNVERIFIED, 2 => PUN_GUEST, 3 => PUN_MEMBER, 4 => PUN_ADMIN, 5 => PUN_UNVERIFIED, 6 => PUN_MOD);

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
					'SELECT'	=> 'MAX(member_id)',
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
		global $re_list;

		$errors = array();
		require_once PUN_ROOT.'include/parser.php';

		$message = html_entity_decode($message);

		if (!isset($patterns))
		{
			$patterns = array(
				'%\[quote name=\'(.*?)\'.*?\]%i'										=>	'[quote=$1]',
				'%<img src=\'.*?\' class=\'bbc_emoticon\' alt=\'(.*?)\' />%'			=> '$1',
				'%\[/?(sup|sub|indent|left|center|right|font|size)(?:\=[^\]]*)?\]%i'	=> '',	// Strip tags not supported by FluxBB
			);
		}

		$message = preg_replace(array_keys($patterns), array_values($patterns), $message);

		if (!isset($replacements))
		{
			$replacements = array(
				'[CODE]'		=> '[code]',
				'[/CODE]'		=> '[/code]',
				'[html]'		=> '[code]',
				'[/html]'		=> '[/code]',
				"\n"			=> '',
				'<br />'		=> "\n",
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

		if (empty($cur_user['pp_main_photo']))
			return false;

		// Fetch avatar from remote url
		if (strpos($cur_user['pp_main_photo'], '://') !== false)
			return $this->fluxbb->save_avatar($cur_user['pp_main_photo'], $cur_user['id']);

		else if (isset($this->path))
		{
			if (!isset($config))
			{
				$config = array();

				$result = $this->db->query_build(array(
					'SELECT'	=> 'conf_key, conf_value',
					'FROM'		=> 'core_sys_conf_settings',
					'WHERE'		=> 'conf_key = \'upload_dir\''
				)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

				while ($cur_config = $this->db->fetch_assoc($result))
					$config[$cur_config['conf_key']] = $cur_config['conf_value'];
			}

			// Fetch avatar from local file
			$cur_avatar_file = $this->path.rtrim($config['upload_dir'], '/').'/'.$cur_user['pp_main_photo'];
			if (file_exists($cur_avatar_file))
				return $this->fluxbb->save_avatar($cur_avatar_file, $cur_user['id']);
		}

		return false;
	}
}
