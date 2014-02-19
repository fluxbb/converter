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

class PunBB_1_3_1_4 extends Forum
{
	// Will the passwords be converted?
	const CONVERTS_PASSWORD = true;

	public $steps = array(
		'bans'					=> array('bans', 'id'),
		'categories'			=> array('categories', 'id'),
		'censoring'				=> array('censoring', 'id'),
		'config'				=> array('config', 'conf_name'),
		'forums'				=> array('forums', 'id'),
		'forum_perms'			=> array('forum_perms', 'forum_id'),
		'groups'				=> array('groups', 'g_id', 'g_id > 4'),
		'posts'					=> array('posts', 'id'),
		'reports'				=> array('reports', 'id'),
		'topic_subscriptions'	=> array('subscriptions', 'topic_id'),
		'forum_subscriptions'	=> 0,
		'topics'				=> array('topics', 'id'),
		'users'					=> array('users', 'id', 'id > 1'),
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
		if (!$this->db->field_exists('bans', 'id'))
			conv_error('Selected database does not contain valid PunBB installation');

		$result = $this->db->query_build(array(
			'SELECT'	=> 'conf_value',
			'FROM'		=> 'config',
			'WHERE'		=> 'conf_name = \'o_avatars_dir\''
		)) or conv_error('Unable to fetch avatars_dir', __FILE__, __LINE__, $this->db->error());

		$avatars_dir = $this->db->result($result);

		if (isset($this->path) && !is_dir($this->path.$avatars_dir))
			conv_error('Avatars directory does not exist');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, username, ip, email, message, expire, ban_creator',
			'FROM'		=> 'bans',
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
			'SELECT'	=> 'id, cat_name, disp_position',
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
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, search_for, replace_with',
			'FROM'		=> 'censoring',
		)) or conv_error('Unable to fetch censoring', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('censoring', $this->db->num_rows($result));
		while ($cur_censor = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('censoring', $cur_censor);
		}
	}

	function convert_config()
	{
		$old_config = array();

		$result = $this->db->query_build(array(
			'SELECT'	=> 'conf_name, conf_value',
			'FROM'		=> 'config',
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('config');

		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['conf_name']] = $cur_config['conf_value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> $old_config['o_board_title'],
			'o_board_desc'				=> $old_config['o_board_desc'],
			'o_default_timezone'		=> $old_config['o_default_timezone'],
			'o_time_format'				=> $old_config['o_time_format'],
			'o_date_format'				=> $old_config['o_date_format'],
			'o_timeout_visit'			=> $old_config['o_timeout_visit'],
			'o_timeout_online'			=> $old_config['o_timeout_online'],
			'o_redirect_delay'			=> $old_config['o_redirect_delay'],
			'o_show_version'			=> $old_config['o_show_version'],
			'o_show_user_info'			=> $old_config['o_show_user_info'],
			'o_show_post_count'			=> $old_config['o_show_post_count'],
			'o_signatures'				=> $old_config['o_signatures'],
			'o_smilies'					=> $old_config['o_smilies'],
			'o_smilies_sig'				=> $old_config['o_smilies_sig'],
			'o_make_links'				=> $old_config['o_make_links'],
//			'o_default_lang'			=> $old_config['o_default_lang'], // No need to change this value
//			'o_default_style'			=> $old_config['o_default_style'], // No need to change this value
			'o_default_user_group'		=> $old_config['o_default_user_group'],
			'o_topic_review'			=> $old_config['o_topic_review'],
			'o_disp_topics_default'		=> $old_config['o_disp_topics_default'],
			'o_disp_posts_default'		=> $old_config['o_disp_posts_default'],
			'o_indent_num_spaces'		=> $old_config['o_indent_num_spaces'],
			'o_quote_depth'				=> $old_config['o_quote_depth'],
			'o_quickpost'				=> $old_config['o_quickpost'],
			'o_users_online'			=> $old_config['o_users_online'],
			'o_censoring'				=> $old_config['o_censoring'],
			'o_show_dot'				=> $old_config['o_show_dot'],
			'o_topic_views'				=> $old_config['o_topic_views'],
			'o_quickjump'				=> $old_config['o_quickjump'],
			'o_gzip'					=> $old_config['o_gzip'],
			'o_additional_navlinks'		=> $old_config['o_additional_navlinks'],
			'o_report_method'			=> $old_config['o_report_method'],
			'o_regs_report'				=> $old_config['o_regs_report'],
			'o_default_email_setting'	=> $old_config['o_default_email_setting'],
			'o_mailing_list'			=> $old_config['o_mailing_list'],
			'o_avatars'					=> $old_config['o_avatars'],
//			'o_avatars_dir'				=> $old_config['o_avatars_dir'], // No need to change this value
			'o_avatars_width'			=> $old_config['o_avatars_width'],
			'o_avatars_height'			=> $old_config['o_avatars_height'],
			'o_avatars_size'			=> $old_config['o_avatars_size'],
			'o_search_all_forums'		=> $old_config['o_search_all_forums'],
//			'o_base_url'				=> $old_config['o_base_url'], // No need to change this value
			'o_admin_email'				=> $old_config['o_admin_email'],
			'o_webmaster_email'			=> $old_config['o_webmaster_email'],
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> $old_config['o_subscriptions'],
			'o_smtp_host'				=> $old_config['o_smtp_host'],
			'o_smtp_user'				=> $old_config['o_smtp_user'],
			'o_smtp_pass'				=> $old_config['o_smtp_pass'],
			'o_smtp_ssl'				=> $old_config['o_smtp_ssl'],
			'o_regs_allow'				=> $old_config['o_regs_allow'],
			'o_regs_verify'				=> $old_config['o_regs_verify'],
			'o_announcement'			=> $old_config['o_announcement'],
			'o_announcement_message'	=> $old_config['o_announcement_message'],
			'o_rules'					=> $old_config['o_rules'],
			'o_rules_message'			=> $old_config['o_rules_message'],
			'o_maintenance'				=> $old_config['o_maintenance'],
			'o_maintenance_message'		=> $old_config['o_maintenance_message'],
			'o_default_dst'				=> $old_config['o_default_dst'],
			'o_feed_type'				=> 2,
			'o_feed_ttl'				=> 0,
			'p_message_bbcode'			=> $old_config['p_message_bbcode'],
			'p_message_img_tag'			=> $old_config['p_message_img_tag'],
			'p_message_all_caps'		=> $old_config['p_message_all_caps'],
			'p_subject_all_caps'		=> $old_config['p_subject_all_caps'],
			'p_sig_all_caps'			=> $old_config['p_sig_all_caps'],
			'p_sig_bbcode'				=> $old_config['p_sig_bbcode'],
			'p_sig_img_tag'				=> $old_config['p_sig_img_tag'],
			'p_sig_length'				=> $old_config['p_sig_length'],
			'p_sig_lines'				=> $old_config['p_sig_lines'],
			'p_allow_banned_email'		=> $old_config['p_allow_banned_email'],
			'p_allow_dupe_email'		=> $old_config['p_allow_dupe_email'],
			'p_force_guest_email'		=> $old_config['p_force_guest_email'],
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
			'SELECT'	=> 'id, forum_name, forum_desc, redirect_url, moderators, num_topics, num_posts, last_post, last_post_id, last_poster, sort_by, disp_position, cat_id',
			'FROM'		=> 'forums',
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	function convert_forum_perms()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
			'FROM'		=> 'forum_perms',
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
			'SELECT'	=> 'g_id, g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
			'FROM'		=> 'groups',
			'WHERE'		=> 'g_id > 4',
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
		// Fetch o_parser_revision from database
		$pun_config = array();
		$result = $this->db->query_build(array(
			'SELECT'	=> 'conf_name, conf_value',
			'FROM'		=> 'config',
			'WHERE'		=> 'conf_name = \'o_parser_revision\'',
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		while ($cur_config = $this->db->fetch_assoc($result))
			$pun_config[$cur_config['conf_name']] = $cur_config['conf_value'];

		// Do we need to preparse post messages?
		$preparse_bbcode = (!isset($pun_config['o_parser_revision']) || $pun_config['o_parser_revision'] != FORUM_PARSER_REVISION);

		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, poster, poster_id, poster_ip, poster_email, message, hide_smilies, posted, edited, edited_by, topic_id',
			'FROM'		=> 'posts',
			'WHERE'		=> 'id > '.$start_at,
			'ORDER BY'	=> 'id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('posts', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];

			if ($preparse_bbcode)
				$cur_post['message'] = $this->fluxbb->preparse_bbcode($cur_post['message']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('posts', 'id', $start_at);
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, post_id, topic_id, forum_id, reported_by, created, message, zapped, zapped_by',
			'FROM'		=> 'reports',
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
			'SELECT'	=> 'user_id, topic_id',
			'FROM'		=> 'subscriptions',
		)) or conv_error('Unable to fetch subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	function convert_forum_subscriptions()
	{
		conv_message('No forum subscriptions');
	}

	function convert_topics($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to, forum_id',
			'FROM'		=> 'topics',
			'WHERE'		=> 'id > '.$start_at,
			'ORDER BY'	=> 'id ASC',
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

		return $this->redirect('topics', 'id', $start_at);
	}

	function convert_users($start_at)
	{
		// Add salt field to the users table to allow login
		if ($start_at == 0)
			$this->fluxbb->db->add_field('users', 'salt', 'VARCHAR(255)', true, '', 'password') or error('Unable to add field', __FILE__, __LINE__, $this->db->error());

		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, group_id, username, password, salt, email, title, realname, url, jabber, icq, msn, aim, yahoo, location, signature, disp_topics, disp_posts, email_setting, notify_with_post, auto_notify, show_smilies, show_img, show_img_sig, show_avatars, show_sig, timezone, dst, time_format, date_format, num_posts, last_post, last_search, last_email_sent, registered, registration_ip, last_visit, admin_note, activate_string, activate_key',
			'FROM'		=> 'users',
			'WHERE'		=> 'id <> 1 AND id > '.$start_at,
			'ORDER BY'	=> 'id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('users', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);

			$this->convert_avatar($cur_user['id']);

			$this->fluxbb->add_row('users', $cur_user, array($this->fluxbb, 'error_users'));
		}

		return $this->redirect('users', 'id', $start_at);
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
	 * Copy avatar file to the FluxBB avatars dir
	 */
	function convert_avatar($user_id)
	{
		static $config;

		if (!isset($config))
		{
			$config = array();

			$result = $this->db->query_build(array(
				'SELECT'	=> 'conf_name, conf_value',
				'FROM'		=> 'config',
				'WHERE'		=> 'conf_name IN (\'o_avatars\', \'o_avatars_dir\')'
			)) or conv_error('Unable to fetch avatars_dir', __FILE__, __LINE__, $this->db->error());

			while ($cur_config = $this->db->fetch_assoc($result))
				$config[$cur_config['conf_name']] = $cur_config['conf_value'];
		}

		if ($config['o_avatars'] == '0' || !isset($this->path))
			return false;

		$old_avatars_dir = $this->path.rtrim($config['o_avatars_dir'], '/').'/';

		foreach ($this->fluxbb->avatar_exts as $cur_ext)
		{
			$cur_avatar_file = $old_avatars_dir.$user_id.$cur_ext;
			if (file_exists($cur_avatar_file))
				return $this->fluxbb->save_avatar($cur_avatar_file, $user_id);
		}
	}
}
