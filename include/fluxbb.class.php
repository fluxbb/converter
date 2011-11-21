<?php

class FluxBB
{
	var $db;
	var $db_type;

	function FluxBB($db, $db_type)
	{
		$db->set_names('utf8');

		$this->db = $db;
		$this->db_type = $db_type;
	}

	function add_row($table, $data, $error_callback = null)
	{
	//	$fields = array_keys($this->schemas[$table]['FIELDS']);
//		$keys = array_keys($data);
//		$diff = array_diff($fields, $keys);

//		if (!$ignore_column_count && (count($fields) != count($keys) || !empty($diff)))
//			error('Field list doesn\'t match for '.$table.' table.', __FILE__, __LINE__);

		$values = array();
		foreach ($data as $key => $value)
			$values[$key] = $value === null ? 'NULL' : '\''.$this->db->escape($value).'\'';

		$result = $this->db->query_build(array(
			'INSERT'	=> implode(', ', array_keys($values)),
			'INTO'		=> $table,
			'VALUES'	=> implode(', ', array_values($values)),
		)) or ($error_callback === null ? error('Unable to insert values', __FILE__, __LINE__, $this->db->error()) : call_user_func($error_callback, $data));
	}

	function error_users($cur_user)
	{
		if (!isset($_SESSION['converter']['dupe_users']))
			$_SESSION['converter']['dupe_users'] = array();

		$_SESSION['converter']['dupe_users'][$cur_user['id']] = $cur_user;
	}

	function convert_users_dupe($cur_user)
	{
		$old_username = $cur_user['username'];
		$suffix = 1;

		// Find new free username
		while (true)
		{
			$username = $old_username.$suffix;
			$result = $this->db->query('SELECT username FROM '.$this->db->prefix.'users WHERE (UPPER(username)=UPPER(\''.$this->db->escape($username).'\') OR UPPER(username)=UPPER(\''.$this->db->escape(ucp_preg_replace('%[^\p{L}\p{N}]%u', '', $username)).'\')) AND id>1') or error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());

			if (!$this->db->num_rows($result))
				break;
		}

		$_SESSION['converter']['dupe_users'][$cur_user['id']]['new_username'] = $cur_user['username'] = $username;

		$temp = array();
		foreach ($cur_user as $idx => $value)
			$temp[$idx] = $value === null ? 'NULL' : '\''.$this->db->escape($value).'\'';

		// Insert the renamed user
		$this->db->query('INSERT INTO '.$this->db->prefix.'users('.implode(',', array_keys($temp)).') VALUES ('.implode(',', array_values($temp)).')') or error('Unable to insert data to new table', __FILE__, __LINE__, $this->db->error());

		// Renaming a user also affects a bunch of other stuff, lets fix that too...
		$this->db->query('UPDATE '.$this->db->prefix.'posts SET poster=\''.$this->db->escape($username).'\' WHERE poster_id='.$cur_user['id']) or error('Unable to update posts', __FILE__, __LINE__, $this->db->error());

		// TODO: The following must compare using collation utf8_bin otherwise we will accidently update posts/topics/etc belonging to both of the duplicate users, not just the one we renamed!
		$this->db->query('UPDATE '.$this->db->prefix.'posts SET edited_by=\''.$this->db->escape($username).'\' WHERE edited_by=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or error('Unable to update posts', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'topics SET poster=\''.$this->db->escape($username).'\' WHERE poster=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or error('Unable to update topics', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'topics SET last_poster=\''.$this->db->escape($username).'\' WHERE last_poster=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or error('Unable to update topics', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'forums SET last_poster=\''.$this->db->escape($username).'\' WHERE last_poster=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or error('Unable to update forums', __FILE__, __LINE__, $this->db->error());
		$this->db->query('UPDATE '.$this->db->prefix.'online SET ident=\''.$this->db->escape($username).'\' WHERE ident=\''.$this->db->escape($old_username).'\' COLLATE utf8_bin') or error('Unable to update online list', __FILE__, __LINE__, $this->db->error());

		// If the user is a moderator or an administrator we have to update the moderator lists
		$result = $this->db->query('SELECT g_moderator FROM '.$this->db->prefix.'groups WHERE g_id='.$cur_user['group_id']) or error('Unable to fetch group', __FILE__, __LINE__, $this->db->error());
		$group_mod = $this->db->result($result);

		if ($cur_user['group_id'] == PUN_ADMIN || $group_mod == '1')
		{
			$result = $this->db->query('SELECT id, moderators FROM '.$this->db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $this->db->error());

			while ($cur_forum = $this->db->fetch_assoc($result))
			{
				$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

				if (in_array($cur_user['id'], $cur_moderators))
				{
					unset($cur_moderators[$old_username]);
					$cur_moderators[$username] = $cur_user['id'];
					uksort($cur_moderators, 'utf8_strcasecmp');

					$this->db->query('UPDATE '.$this->db->prefix.'forums SET moderators=\''.$this->db->escape(serialize($cur_moderators)).'\' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $this->db->error());
				}
			}
		}
	}

	function pass_hash($str)
	{
		if (function_exists('sha1'))	// Only in PHP 4.3.0+
			return sha1($str);
		else if (function_exists('mhash'))	// Only if Mhash library is loaded
			return bin2hex(mhash(MHASH_SHA1, $str));
		else
			return md5($str);
	}

	function random_pass($len)
	{
		static $chars;

		if (!isset($chars))
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$key = '';
		for ($i = 0;$i < $len;$i++)
			$key .= substr($chars, (mt_rand() % strlen($chars)), 1);

		return $key;
	}
}
