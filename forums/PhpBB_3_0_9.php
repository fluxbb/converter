<?php

/**
 * @copyright (C) 2011 FluxBB (http://fluxbb.org)
 * @license LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @package FluxBB
 */

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.7');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class PhpBB_3_0_9 extends Forum
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
		if (!$this->db->field_exists('banlist', 'ban_id'))
			conv_error('Selected database does not contain valid phpBB installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'b.ban_id AS id, u.username, b.ban_ip AS ip, b.ban_email AS email, b.ban_reason AS message, b.ban_end AS expire',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=b.ban_userid'
				),
			),
			'FROM'		=> 'banlist AS b',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'bans', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'categories', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch words', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'censors', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing', 'config');
		while ($cur_config = $this->db->fetch_assoc($result))
			$old_config[$cur_config['config_name']] = $cur_config['config_value'];

		$new_config = array(
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> $old_config['sitename'],
			'o_board_desc'				=> $old_config['site_desc'],
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
			'o_disp_topics_default'		=> $old_config['topics_per_page'],
			'o_disp_posts_default'		=> $old_config['posts_per_page'],
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
			'o_admin_email'				=> $old_config['board_email'],
			'o_webmaster_email'			=> $old_config['board_email'],
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> $old_config['smtp_host'],
			'o_smtp_user'				=> $old_config['smtp_username'],
			'o_smtp_pass'				=> $old_config['smtp_password'],
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
			'SELECT'	=> 'forum_id AS id, forum_name, forum_desc, IF(forum_link=\'\', NULL, forum_link) AS redirect_url, forum_topics AS num_topics, forum_posts AS num_posts, left_id AS disp_position, forum_last_poster_name AS last_poster, forum_last_post_id AS last_post_id, forum_last_post_time AS last_post, forum_parents, parent_id AS cat_id',
			'FROM'		=> 'forums',
			'WHERE'		=> 'forum_type <> 0',
			'ORDER BY'	=> 'left_id ASC'
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$parents = unserialize($cur_forum['forum_parents']);
			unset($cur_forum['forum_parents']);
			if ($parents !== false)
			{
				$parents = array_keys($parents);
				$cur_forum['cat_id'] = $parents[0];
			}

			$cur_forum['forum_desc'] = $this->convert_message($cur_forum['forum_desc']);

			if ($cur_forum['num_topics'] == 0)
				$cur_forum['last_post'] = $cur_forum['last_post_id'] = $cur_forum['last_poster'] = NULL;

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
			'SELECT'	=> 'group_id AS g_id, group_name AS g_title, group_name AS g_user_title',
			'FROM'		=> 'groups',
			'WHERE'		=> 'group_id > 7'
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
			'SELECT'	=> 'p.post_id AS id, IF(p.post_username=\'\', u.username, p.post_username) AS poster, p.poster_id, p.post_time AS posted, p.poster_ip, p.post_text AS message, p.topic_id',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.user_id=p.poster_id'
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

			$this->fluxbb->add_row('posts', $cur_post);
		}

		$this->redirect('posts', 'post_id', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'rank_id AS id, rank_title AS rank, rank_min AS min_posts',
			'FROM'		=> 'ranks',
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
			'SELECT'	=> 'r.report_id AS id, r.post_id, p.topic_id, p.forum_id, r.user_notify AS reported_by, r.report_time AS created, r.report_text AS message, r.report_closed AS zapped, NULL AS zapped_by',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'posts AS p',
					'ON'		=> 'r.post_id=p.post_id'
				),
			),
			'FROM'		=> 'reports AS r',
		)) or conv_error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'reports', $this->db->num_rows($result));
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
		)) or conv_error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'topic subscriptions', $this->db->num_rows($result));
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
			'SELECT'	=> 'topic_id AS id, topic_first_poster_name AS poster, topic_title AS subject, topic_time AS posted, topic_first_post_id AS first_post_id, topic_last_post_time AS last_post, topic_last_post_id AS last_post_id, topic_last_poster_name AS last_poster, topic_views AS num_views, topic_replies AS num_replies, IF(topic_status=1, 1, 0) AS closed, IF(topic_type=1, 1, 0) AS sticky, IF(topic_moved_id=0, NULL, topic_moved_id) AS moved_to, forum_id',
			'FROM'		=> 'topics',
			'WHERE'		=> 'topic_id > '.$start_at,
			'ORDER BY'	=> 'topic_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];
			$cur_topic['subject'] = html_entity_decode($cur_topic['subject'], ENT_QUOTES, 'UTF-8');

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		$this->redirect('topics', 'topic_id', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'user_id AS id, group_id AS group_id, username AS username, user_password AS password, user_website AS url, user_icq AS icq, user_msnm AS msn, user_aim AS aim, user_yim AS yahoo, user_posts AS num_posts, user_from AS location, user_allow_viewemail AS email_setting, user_timezone AS timezone, user_regdate AS registered, user_lastvisit AS last_visit, user_sig AS signature, user_email AS email, user_avatar',
			'FROM'		=> 'users',
			'WHERE'		=> 'group_id <> 6 AND user_id <> 1 AND user_id > '.$start_at,
			'ORDER BY'	=> 'user_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];
			$cur_user['username'] = html_entity_decode($cur_user['username']);
			$cur_user['group_id'] = $this->grp2grp($cur_user['group_id']);
			$cur_user['email_setting'] = !$cur_user['email_setting'];
			$cur_user['signature'] = $this->convert_message($cur_user['signature']);

			$this->convert_avatar($cur_user);
			unset($cur_user['user_avatar']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		$this->redirect('users', 'user_id', $start_at);
	}

	/**
	 * Convert group id to the FluxBB style (use FluxBB constants, see index.php:83)
	 */
	function grp2grp($id)
	{
		static $mapping;

		if (!isset($mapping))
			$mapping = array(1 => PUN_GUEST, 2 => PUN_MEMBER, 3 => PUN_MEMBER, 4 => PUN_MOD, 5 => PUN_ADMIN);

		if (!array_key_exists($id, $mapping))
			return $id;

		return $mapping[$id];
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

		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		if (!isset($patterns))
		{
			$patterns = array(
				'%\[(/?)(b|i|u|list|\*|color|img|url|code|quote|size)(\=[^:\]]*)?:[a-z0-9]{5,8}\]%i'	=>	'[$1$2$3]', // Strip text after colon in tag name

				// Smileys
				'#<!-- s.*? --><img src=".*?" alt="(.*?)" title=".*?" \/><!-- s.*? -->#i'			=>	'$1',

				'#<!-- [mw] --><a class="postlink" href="(.*?)">(.*?)</a><!-- [mw] -->#i'			=>	'[url=$1]$2[/url]',
				'#<!-- e --><a href="mailto:(.*?)">(.*?)</a><!-- e -->#i'							=>	'[email=$1]$2[/email]',
			);
		}

		$message = preg_replace(array_keys($patterns), array_values($patterns), $message);

		if (!isset($replacements))
		{
			$replacements = array(
				':shock:'		=> ':o',
				'8-)'			=> ':cool:',
				':evil:'		=> ':/',
				':roll:'		=> ':rolleyes:',
			);
		}

		return preparse_bbcode(str_replace(array_keys($replacements), array_values($replacements), $message), $errors);
	}

	/**
	 * Copy avatar file to the FluxBB avatars dir
	 */
	function convert_avatar($cur_user)
	{
		static $avatars_config;

		if (!isset($this->path))
			return false;

		if (!isset($avatars_config))
		{
			$avatars_config = array();

			$result = $this->db->query_build(array(
				'SELECT'	=> 'config_name, config_value',
				'FROM'		=> 'config',
				'WHERE'		=> 'config_name IN (\'avatar_path\', \'avatar_salt\')'
			)) or conv_error('Unable to fetch config', __FILE__, __LINE__, $this->db->error());

			while ($cur_config = $this->db->fetch_assoc($result))
				$avatars_config[$cur_config['config_name']] = $cur_config['config_value'];
		}

		$old_avatars_dir = $this->path.rtrim($avatars_config['avatar_path'], '/').'/';

		$extensions = array('.jpg', '.gif', '.png');
		foreach ($extensions as $cur_ext)
		{
			$cur_avatar_file = $old_avatars_dir.$avatars_config['avatar_salt'].'_'.$cur_user['id'].$cur_ext;
			if (file_exists($cur_avatar_file))
			{
				copy($cur_avatar_file, $this->fluxbb->avatars_dir.$cur_user['id'].$cur_ext);
				return true;
			}
		}
	}
}
