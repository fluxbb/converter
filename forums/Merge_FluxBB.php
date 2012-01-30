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

class Merge_FluxBB extends Forum
{
	// Will the passwords be converted?
	const CONVERTS_PASSWORD = true;

	// Do not truncate FluxBB database tables
	const NO_DB_CLEANUP = true;

	public $steps = array(
		'groups',
		'users',
		'bans',
		'categories',
		'censoring',
		'config',
		'forums',
		'forum_perms',
		'topics',
		'posts',
		'ranks',
		'reports',
		'topic_subscriptions',
		'forum_subscriptions',
	);

	private $last_id = array();

	function initialize()
	{
		$this->db->set_names('utf8');

		if (!session_id())
			session_start();

		if (isset($_SESSION['converter']['last_id']))
			$this->last_id = $_SESSION['converter']['last_id'];
		else
		{
			$check_id = array('groups' => 'g_id', 'users', 'bans', 'categories', 'censoring', 'forums', 'topics', 'posts', 'ranks', 'reports');

			foreach ($check_id as $key => $cur_table)
			{
				$field = 'id';
				if (!is_numeric($key))
				{
					$field = $cur_table;
					$cur_table = $key;
				}

				$result = $this->fluxbb->db->query_build(array(
					'SELECT'	=> $field,
					'FROM'		=> $cur_table,
					'ORDER BY'	=> $field.' DESC',
					'LIMIT'		=> 1,
				)) or conv_error('Unable to fetch table id', __FILE__, __LINE__, $this->fluxbb->db->error());

				$this->last_id[$cur_table] = $this->fluxbb->db->num_rows($result) ? $this->db->result($result) : 0;
			}
			$_SESSION['converter']['last_id'] = $this->last_id;
		}
	}

	/**
	 * Check whether specified database has valid current forum software strucutre
	 */
	function validate()
	{
		if (!$this->db->field_exists('bans', 'id'))
			conv_error('Selected database does not contain valid FluxBB installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, username, ip, email, message, expire, ban_creator',
			'FROM'		=> 'bans',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$cur_ban['id'] += $this->last_id['bans'];
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		$result = $this->fluxbb->db->query_build(array(
			'SELECT'	=> 'disp_position',
			'FROM'		=> 'categories',
			'ORDER BY'	=> 'disp_position DESC',
			'LIMIT'		=> '1',
		)) or conv_error('Unable to fetch last disp position', __FILE__, __LINE__, $this->fluxbb->db->error());

		$last_disp_postion = $this->fluxbb->db->num_rows($result) ? $this->fluxbb->db->result($result) : 0;

		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, cat_name, disp_position',
			'FROM'		=> 'categories',
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'categories', $this->db->num_rows($result));
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$cur_cat['id'] += $this->last_id['categories'];
			$cur_cat['disp_position'] += $last_disp_postion;
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_censoring()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, search_for, replace_with',
			'FROM'		=> 'censoring',
		)) or conv_error('Unable to fetch censoring', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'censors', $this->db->num_rows($result));
		while ($cur_censor = $this->db->fetch_assoc($result))
		{
			$cur_censor['id'] += $this->last_id['censoring'];
			$this->fluxbb->add_row('censoring', $cur_censor);
		}
	}

	function convert_config()
	{
		return false;
	}

	function convert_forums()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, forum_name, forum_desc, redirect_url, moderators, num_topics, num_posts, last_post, last_post_id, last_poster, sort_by, disp_position, cat_id',
			'FROM'		=> 'forums',
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$cur_forum['id'] += $this->last_id['forums'];
			$cur_forum['last_post_id'] += $this->last_id['posts'];
			$cur_forum['cat_id'] += $this->last_id['categories'];

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	function convert_forum_perms()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
			'FROM'		=> 'forum_perms',
		)) or conv_error('Unable to fetch forum perms', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forum_perms', $this->db->num_rows($result));
		while ($cur_perm = $this->db->fetch_assoc($result))
		{
			$cur_perm['group_id'] += $this->last_id['groups'];
			$cur_perm['forum_id'] += $this->last_id['forums'];

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

		conv_message('Processing num', 'groups', $this->db->num_rows($result));
		while ($cur_group = $this->db->fetch_assoc($result))
		{
			$cur_group['g_id'] += $this->last_id['groups'] - 4;

			$this->fluxbb->add_row('groups', $cur_group);
		}
	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, poster, poster_id, poster_ip, poster_email, message, hide_smilies, posted, edited, edited_by, topic_id',
			'FROM'		=> 'posts',
			'WHERE'		=> 'id > '.$start_at,
			'ORDER BY'	=> 'id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'posts', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['id'] += $this->last_id['posts'];
			$cur_post['poster_id'] = $this->fetchUid($cur_post['poster']);
			$cur_post['topic_id'] += $this->last_id['topics'];

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('posts', 'id', $start_at);
	}

	function convert_ranks()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, rank, min_posts',
			'FROM'		=> 'ranks',
		)) or conv_error('Unable to fetch ranks', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'ranks', $this->db->num_rows($result));
		while ($cur_rank = $this->db->fetch_assoc($result))
		{
			$cur_rank['id'] += $this->last_id['ranks'];

			$this->fluxbb->add_row('ranks', $cur_rank);
		}
	}

	function convert_reports()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, post_id, topic_id, forum_id, reported_by, created, message, zapped, zapped_by',
			'FROM'		=> 'reports',
		)) or conv_error('Unable to fetch reports', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'reports', $this->db->num_rows($result));
		while ($cur_report = $this->db->fetch_assoc($result))
		{
			$cur_report['id'] += $this->last_id['reports'];
			$cur_report['post_id'] += $this->last_id['posts'];
			$cur_report['topic_id'] += $this->last_id['topics'];
			$cur_report['forum_id'] += $this->last_id['forums'];
			$this->fluxbb->add_row('reports', $cur_report);
		}
	}

	function convert_topic_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 's.user_id, s.topic_id, u.username',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.id = s.user_id'
				),
			),
			'FROM'		=> 'topic_subscriptions AS s',
		)) or conv_error('Unable to fetch subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'topic subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$cur_sub['user_id'] = $this->fetchUid($cur_sub['username']);
			$cur_sub['topic_id'] += $this->last_id['topics'];

			unset($cur_sub['username']);

			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
	}

	function convert_forum_subscriptions()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 's.user_id, s.forum_id, u.username',
			'JOINS'        => array(
				array(
					'LEFT JOIN'	=> 'users AS u',
					'ON'		=> 'u.id = s.user_id'
				),
			),
			'FROM'		=> 'forum_subscriptions AS s',
		)) or conv_error('Unable to fetch subscriptions', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing num', 'forum subscriptions', $this->db->num_rows($result));
		while ($cur_sub = $this->db->fetch_assoc($result))
		{
			$cur_sub['user_id'] = $this->fetchUid($cur_sub['username']);
			$cur_sub['forum_id'] += $this->last_id['forums'];

			unset($cur_sub['username']);

			$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
		}
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

		conv_message('Processing range', 'topics', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];
			$cur_topic['id'] += $this->last_id['topics'];
			$cur_topic['first_post_id'] += $this->last_id['posts'];
			$cur_topic['last_post_id'] += $this->last_id['posts'];
			$cur_topic['forum_id'] += $this->last_id['forums'];

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
			'SELECT'	=> 'id, group_id, username, password, email, title, realname, url, jabber, icq, msn, aim, yahoo, location, signature, disp_topics, disp_posts, email_setting, notify_with_post, auto_notify, show_smilies, show_img, show_img_sig, show_avatars, show_sig, timezone, dst, time_format, date_format, num_posts, last_post, last_search, last_email_sent, registered, registration_ip, last_visit, admin_note, activate_string, activate_key',
			'FROM'		=> 'users',
			'WHERE'		=> 'id <> 1 AND id > '.$start_at,
			'ORDER BY'	=> 'id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_message('Processing range', 'users', $this->db->num_rows($result), $start_at, $start_at + PER_PAGE);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];

			$uid = $this->fetchUid($cur_user['username']);
			if ($uid > 0)
				$cur_user['id'] = $uid;
			else
				$cur_user['id'] += $this->last_id['users'];

			if ($cur_user['group_id'] > 4)
				$cur_user['group_id'] += $this->last_id['groups'];

			$this->fluxbb->add_row('users', $cur_user, array($this, 'error_users'));
		}

		return $this->redirect('users', 'id', $start_at);
	}

	function error_users()
	{
		// Do nothing
	}

	function fetchUid($username)
	{
		$result = $this->fluxbb->db->query_build(array(
			'SELECT'	=> 'id',
			'FROM'		=> 'users',
			'WHERE'		=> 'username = \''.$this->fluxbb->db->escape($username).'\'',
		)) or conv_error('Unable to fetch user id', __FILE__, __LINE__, $this->fluxbb->db->error());

		if ($this->fluxbb->db->num_rows($result))
			return $this->fluxbb->db->result($result);

		return 0;
	}

}
