<?php

/**
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

/**
 * Wrapper for FluxBB (has easy functions for adding rows to database etc.)
 */
class FluxBB
{
	public $db;
	public $db_config;
	public $pun_config;
	public $avatars_dir;

	public $tables = array(
		'bans'					=> array('id'),
		'categories'			=> array('id'),
		'censoring'				=> array('id'),
		'config'				=> -1,
		'forums'				=> array('id'),
		'forum_perms'			=> array('forum_id'),
		'groups'				=> array('g_id', 'g_id > 4'),
		'posts'					=> array('id'),
		'ranks'					=> array('id'),
		'reports'				=> array('id'),
		'topic_subscriptions'	=> array('topic_id'),
		'forum_subscriptions'	=> array('forum_id'),
		'topics'				=> array('id'),
		'users'					=> array('id', 'id > 1'),
	);

	public $avatar_exts = array('jpg', 'gif', 'png');
	public $avatar_mimes = array('image/jpg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');

	function __construct($pun_config)
	{
		$this->pun_config = $pun_config;
		$this->avatars_dir = PUN_ROOT.rtrim($this->pun_config['o_avatars_dir'], '/').'/';
	}

	/**
	 * Connect to the FluxBB database
	 *
	 * @param array $db_config
	 */
	function connect_database($db_config)
	{
		$this->db_config = $db_config;

		$this->db = connect_database($db_config);
		$this->db->set_names('utf8');

		return $this->db;
	}

	/**
	 * Close database connection
	 */
	function close_database()
	{
		$this->db->end_transaction();
		$this->db->close();
	}

	function fetch_item_count()
	{
		$tables = array();
		foreach ($this->tables as $cur_table => $table_info)
		{
			$count = 0;

			if (is_numeric($table_info))
				$count = $table_info;

			if (is_array($table_info))
			{
				$query = array(
					'SELECT'	=> 'COUNT('.$this->db->escape($table_info[0]).')',
					'FROM'		=> $this->db->escape($cur_table)
				);
				if (isset($table_info[1]))
					$query['WHERE'] = $table_info[1];

				$result = $this->db->query_build($query) or conv_error('Unable to fetch num rows for '.$cur_table, __FILE__, __LINE__, $this->db->error());
				$count = $this->db->result($result);
			}

			$tables[$cur_table] = $count;
		}
		return $tables;
	}

	function preparse_bbcode($message, &$errors)
	{
		global $re_list, $lang_common;

		$errors = array();
		require_once PUN_ROOT.'include/parser.php';

		$message = preparse_bbcode($message, $errors);
		if (!empty($errors))
			conv_log('convert_message: bbcode error: '.implode(', ', $errors));

		return $message;
	}

	/**
	 * Adds a row to the FluxBB table with specified data
	 *
	 * @param string $table
	 * @param array $data Array containig data to insert into db
	 * @param mixed $error_callback	A function that will be called when error occurs
	 */
	function add_row($table, $data, $error_callback = null)
	{
	//	$fields = array_keys($this->schemas[$table]['FIELDS']);
//		$keys = array_keys($data);
//		$diff = array_diff($fields, $keys);

//		if (!$ignore_column_count && (count($fields) != count($keys) || !empty($diff)))
//			conv_error('Field list doesn\'t match for '.$table.' table.', __FILE__, __LINE__);

		$values = array();
		foreach ($data as $key => $value)
			$values[$key] = $value === null ? 'NULL' : '\''.$this->db->escape($value).'\'';

		$result = $this->db->query_build(array(
			'INSERT'	=> implode(', ', array_keys($values)),
			'INTO'		=> $table,
			'VALUES'	=> implode(', ', array_values($values)),
		)) or ($error_callback === null ? conv_error('Unable to insert values', __FILE__, __LINE__, $this->db->error()) : call_user_func($error_callback, $data));
	}

	/**
	 * Function called when a duplicate user is found
	 *
	 * @param array $cur_user
	 */
	function error_users($cur_user)
	{
		global $session;

		if (!isset($session['dupe_users']))
			$session['dupe_users'] = array();

		$session['dupe_users'][$cur_user['id']] = $cur_user;
	}

	/**
	 * Rename duplicate users
	 *
	 * @param array $cur_user
	 */
	function convert_users_dupe($cur_user)
	{
		global $session;

		$old_username = $cur_user['username'];
		$suffix = 1;

		// Find new free username
		while (true)
		{
			$username = $old_username.$suffix;
			$result = $this->db->query('SELECT username FROM '.$this->db->prefix.'users WHERE (UPPER(username)=UPPER(\''.$this->db->escape($username).'\') OR UPPER(username)=UPPER(\''.$this->db->escape(ucp_preg_replace('%[^\p{L}\p{N}]%u', '', $username)).'\')) AND id>1') or conv_error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());

			if (!$this->db->num_rows($result))
				break;
		}

		$session['dupe_users'][$cur_user['id']]['username'] = $cur_user['username'] = $username;

		$temp = array();
		foreach ($cur_user as $idx => $value)
			$temp[$idx] = $value === null ? 'NULL' : '\''.$this->db->escape($value).'\'';

		// Insert the renamed user
		$this->db->query('INSERT INTO '.$this->db->prefix.'users('.implode(',', array_keys($temp)).') VALUES ('.implode(',', array_values($temp)).')') or conv_error('Unable to insert data to new table', __FILE__, __LINE__, $this->db->error());

		// Renaming a user also affects a bunch of other stuff, lets fix that too...
		$this->db->query('UPDATE '.$this->db->prefix.'posts SET poster=\''.$this->db->escape($username).'\' WHERE poster_id='.$cur_user['id']) or conv_error('Unable to update posts', __FILE__, __LINE__, $this->db->error());

		// The following must compare using collation utf8_bin otherwise we will accidently update posts/topics/etc belonging to both of the duplicate users, not just the one we renamed!
		$this->db->query('UPDATE '.$this->db->prefix.'posts SET edited_by=\''.$this->db->escape($username).'\' WHERE edited_by=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or conv_error('Unable to update posts', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'topics SET poster=\''.$this->db->escape($username).'\' WHERE poster=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or conv_error('Unable to update topics', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'topics SET last_poster=\''.$this->db->escape($username).'\' WHERE last_poster=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or conv_error('Unable to update topics', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'forums SET last_poster=\''.$this->db->escape($username).'\' WHERE last_poster=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or conv_error('Unable to update forums', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'online SET ident=\''.$this->db->escape($username).'\' WHERE ident=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or conv_error('Unable to update online list', __FILE__, __LINE__, $this->db->error());

		// If the user is a moderator or an administrator we have to update the moderator lists
		$result = $this->db->query('SELECT g_moderator FROM '.$this->db->prefix.'groups WHERE g_id='.$cur_user['group_id']) or conv_error('Unable to fetch group', __FILE__, __LINE__, $this->db->error());
		$group_mod = $this->db->result($result);

		if ($cur_user['group_id'] == PUN_ADMIN || $group_mod == '1')
		{
			$result = $this->db->query('SELECT id, moderators FROM '.$this->db->prefix.'forums') or conv_error('Unable to fetch forum list', __FILE__, __LINE__, $this->db->error());

			while ($cur_forum = $this->db->fetch_assoc($result))
			{
				$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

				if (in_array($cur_user['id'], $cur_moderators))
				{
					unset($cur_moderators[$old_username]);
					$cur_moderators[$username] = $cur_user['id'];
					uksort($cur_moderators, 'utf8_strcasecmp');

					$this->db->query('UPDATE '.$this->db->prefix.'forums SET moderators=\''.$this->db->escape(serialize($cur_moderators)).'\' WHERE id='.$cur_forum['id']) or conv_error('Unable to update forum', __FILE__, __LINE__, $this->db->error());
				}
			}
		}

		$session['dupe_users'][$cur_user['id']]['old_username'] = $old_username;
	}

	/**
	 * Save user avatar to the FluxBB avatar directory
	 *
	 * @param string $file
	 *  	A path to the file or image url
	 *
	 * @param integer $user_id
	 * 		Id of the user
	 *
	 * @return bool
	 */
	function save_avatar($file, $user_id)
	{
		// Download remote file
		if (strpos($file, '://') !== false)
		{
			conv_log('Download avatar: '.$file.' for user '.$user_id);
			$extension = strtolower(substr($file, strrpos($file, '.') + 1));

			// Download avatar to temporary location (FluxBB cache directory)
			$tmp_file = FORUM_CACHE_DIR.uniqid();
			if (!file_put_contents($tmp_file, file_get_contents($file)))
			{
				conv_log('Failed to download avatar file "'.$file.'"" for user '.$user_id);
				return false;
			}

			// Check image mime type
			$info = @getimagesize($tmp_file);
			if (!isset($info['mime']))
			{
				conv_log('Failed to get miemtype for file '.$file);
				unlink($tmp_file);
				return false;
			}
			else if (!array_key_exists($info['mime'], $this->avatar_mimes))
			{
				conv_log('Invalid avatar mimetype for file '.$file.' ('.$info['mime'].')');
				unlink($tmp_file);
				return false;
			}

			$extension = $this->avatar_mimes[$info['mime']];
			if (!rename($tmp_file, $this->avatars_dir.$user_id.'.'.$extension))
			{
				conv_log('Failed to move avatar file to '.$tmp_file.' to '.$this->avatars_dir.$user_id.'.'.$extension);
				return false;
			}

			return true;
		}

		// Copy local file
		else if (file_exists($file))
		{
			$extension = strtolower(substr($file, strrpos($file, '.') + 1));
			if (!in_array($extension, $this->avatar_exts))
			{
				conv_log('Invalid avatar extension for file '.$file);
				return false;
			}

			if (!copy($file, $this->avatars_dir.$user_id.'.'.$extension))
			{
				conv_log('Failed to save avatar file "'.$file.'"" for user '.$user_id);
				return false;
			}
			return true;
		}

		conv_log('Avatar file '.$file.' for user '.$user_id.' does not exist');
		return false;
	}

	/**
	 * Recount all posts, topics after conversion
	 *
	 * @return type
	 */
	function sync_db()
	{
		conv_log('Updating post count for each user');
		$start = get_microtime();

		// Update user post count
		$result = $this->db->query_build(array(
			'SELECT'	=> 'p.poster_id, COUNT(p.id) AS post_count, u.num_posts',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id = p.poster_id'
				)
			),
			'FROM'		=> 'posts AS p',
			'GROUP BY'	=> 'p.poster_id',
			'WHERE'		=> 'p.poster_id > 0'
		)) or conv_error('Unable to fetch user posts', __FILE__, __LINE__, $this->db->error());

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			if ($cur_user['num_posts'] == $cur_user['post_count'])
				continue;

			$this->db->query_build(array(
				'UPDATE'	=> 'users',
				'SET'		=> 'num_posts = '.$cur_user['post_count'],
				'WHERE'		=> 'id = '.$cur_user['poster_id'],
			)) or conv_error('Unable to update user post count', __FILE__, __LINE__, $this->db->error());
		}

		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
		conv_log('Updating post count for each topic');
		$start = get_microtime();

		// Update post count for each topic
		$result = $this->db->query_build(array(
			'SELECT'	=> 'p.topic_id, COUNT(p.id) AS post_count, t.num_replies',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'topics AS t',
					'ON'			=> 't.id = p.topic_id'
				)
			),
			'FROM'		=> 'posts AS p',
			'GROUP BY'	=> 'p.topic_id',
		)) or conv_error('Unable to fetch topic posts', __FILE__, __LINE__, $this->db->error());

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			if ($cur_topic['num_replies'] == ($cur_topic['post_count'] - 1))
				continue;

			$this->db->query_build(array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'num_replies = '.($cur_topic['post_count'] - 1),
				'WHERE'		=> 'id = '.$cur_topic['topic_id'],
			)) or conv_error('Unable to update topic post count', __FILE__, __LINE__, $this->db->error());
		}

		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
		conv_log('Updating last post for each topic');
		$start = get_microtime();

		// Update last post for each topic
		$subquery = array(
			'SELECT'	=> 'topic_id, MAX(posted) AS last_post',
			'FROM'		=> 'posts',
			'GROUP BY'	=> 'topic_id',
		);

		$result = $this->db->query_build(array(
			'SELECT'	=> 'p.topic_id, p.id, p.posted, p.poster, t.last_post, t.last_post_id, t.last_poster',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> $this->db->prefix.'posts AS p',
					'ON'			=> 'p.topic_id = s.topic_id AND p.posted = s.last_post',
				),
				array(
					'INNER JOIN'	=> $this->db->prefix.'topics AS t',
					'ON'			=> 'p.topic_id = t.id',
				)
			),
			'FROM'		=> '('.$this->db->query_build($subquery, true).') AS s',
			'PARAMS'	=> array(
				'NO_PREFIX'		=> true,
			)
		)) or conv_error('Unable to fetch topic last post', __FILE__, __LINE__, $this->db->error());

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$values = array();
			if ($cur_topic['posted'] != $cur_topic['last_post'])
				$values[] = 'last_post = '.$cur_topic['posted'];
			if ($cur_topic['poster'] != $cur_topic['last_poster'])
				$values[] = 'last_poster = \''.$this->db->escape($cur_topic['poster']).'\'';
			if ($cur_topic['id'] != $cur_topic['last_post_id'])
				$values[] = 'last_post_id = '.$cur_topic['id'];

			if (!empty($values))
				$this->db->query_build(array(
					'UPDATE'	=> 'topics',
					'SET'		=> implode(', ', $values),
					'WHERE'		=> 'id = '.$cur_topic['topic_id'],
				)) or conv_error('Unable to update last post for topic', __FILE__, __LINE__, $this->db->error());
		}
		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
		conv_log('Updating num topics and num posts for each forum');
		$start = get_microtime();

		// Update num_topics and num_posts for each forum
		$result = $this->db->query_build(array(
			'SELECT'	=> 't.forum_id, COUNT(t.id) AS topic_count, SUM(t.num_replies) + COUNT(t.id) AS post_count, f.num_posts, f.num_topics',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 'f.id = t.forum_id'
				)
			),
			'FROM'		=> 'topics AS t',
			'GROUP BY'	=> 't.forum_id',
		)) or conv_error('Unable to fetch topics for forum', __FILE__, __LINE__, $this->db->error());

		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$values = array();
			if ($cur_forum['topic_count'] != $cur_forum['num_topics'])
				$values[] = 'num_topics = '.$cur_forum['topic_count'];
			if ($cur_forum['post_count'] != $cur_forum['num_posts'])
				$values[] = 'num_posts = '.$cur_forum['post_count'];

			if (!empty($values))
				$this->db->query_build(array(
					'UPDATE'	=> 'forums',
					'SET'		=> implode(', ', $values),
					'WHERE'		=> 'id = '.$cur_forum['forum_id'],
				)) or conv_error('Unable to update topic count for forum', __FILE__, __LINE__, $this->db->error());
		}

		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
		conv_log('Updating last post for each forum');
		$start = get_microtime();

		// Update last post for each forum
		$subquery = array(
			'SELECT'	=> 'forum_id, MAX(last_post) AS last_post',
			'FROM'		=> 'topics',
			'GROUP BY'	=> 'forum_id',
		);

		$result = $this->db->query_build(array(
			'SELECT'	=> 't.forum_id, t.last_post_id AS new_last_post_id, t.last_post AS new_last_post, t.last_poster AS new_last_poster, f.last_post_id, f.last_poster, f.last_post',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> $this->db->prefix.'topics AS t',
					'ON'			=> 't.forum_id = s.forum_id AND s.last_post = t.last_post',
				),
				array(
					'INNER JOIN'	=> $this->db->prefix.'forums AS f',
					'ON'			=> 't.forum_id = f.id',
				)
			),
			'FROM'		=> '('.$this->db->query_build($subquery, true).') AS s',
			'PARAMS'	=> array(
				'NO_PREFIX'		=> true,
			)
		)) or conv_error('Unable to fetch forum last post', __FILE__, __LINE__, $this->db->error());

		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$values = array();
			if ($cur_forum['new_last_post'] != $cur_forum['last_post'])
				$values[] = 'last_post = '.$cur_forum['new_last_post'];
			if ($cur_forum['new_last_poster'] != $cur_forum['last_poster'])
				$values[] = 'last_poster = \''.$this->db->escape($cur_forum['new_last_poster']).'\'';
			if ($cur_forum['new_last_post_id'] != $cur_forum['last_post_id'])
				$values[] = 'last_post_id = '.$cur_forum['new_last_post_id'];

			if (!empty($values))
				$this->db->query_build(array(
					'UPDATE'	=> 'forums',
					'SET'		=> implode(', ', $values),
					'WHERE'		=> 'id = '.$cur_forum['forum_id'],
				)) or conv_error('Unable to update last post for forum', __FILE__, __LINE__, $this->db->error());
		}
		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
	}
}
