<?php

/**
* Copyright (C) 2011 FluxBB (http://fluxbb.org)
* License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
*/

class Converter
{
	public $forum;
	public $start;

	public $tables = array(
		'bans' 					=> true,
		'categories'			=> true,
		'censoring'				=> true,
		'config'				=> true,
		'forums'				=> true,
		'forum_perms'			=> true,
		'groups'				=> true,
//		'online'				=> false,
		'posts'					=> true,
		'ranks'					=> true,
		'reports'				=> true,
//		'search_cache'			=> false,
//		'search_matches'		=> false,
//		'search_words'			=> false,
		'topic_subscriptions'	=> true,
		'forum_subscriptions'	=> true,
		'topics'				=> true,
		'users'					=> true,
	);

	function __construct($forum)
	{
		$this->forum = $forum;
	}

	/**
	 * Checks whether database has valid specified forum software schema
	 */
	function validate()
	{
		if (is_callable(array($this->forum, 'validate')))
			$this->forum->validate();
	}

	/**
	 * Runs conversion process
	 *
	 * @param mixed $name Table name
	 * @param integer $start_at A row number from which we start processing table
	 */
	function convert($name, $start_at = 0)
	{
		$keys = array_keys($this->tables);

		// Start from beginning
		if (!isset($name))
		{
			$_SESSION['fluxbb_converter']['start_time'] = get_microtime();
			$this->initialize();
			$name = $keys[0];
		}

		$this->forum->stage = $name;

		$start = get_microtime();
		$convert = $this->tables[$name];

		conv_message('Converting', $name);
		if ($convert && is_callable(array($this->forum, 'convert_'.$name)))
			call_user_func(array($this->forum, 'convert_'.$name), $start_at);

		conv_message('Done in', round(get_microtime() - $start, 4));

		// Redirect to the next stage
		$current = array_search($name, $keys);

		// No more work to do?
		if (!isset($keys[++$current]))
		{
			$this->finnish();
			$_SESSION['fluxbb_converter']['time'] = get_microtime() - $_SESSION['fluxbb_converter']['start_time'];
			if (defined('CMDLINE'))
				return true;
			else
				conv_redirect('results');
		}

		$next_stage = $keys[$current];
		if (defined('CMDLINE'))
		{
			conv_message();
			$this->convert($next_stage);
		}
		else
			conv_redirect($next_stage);
	}

	/**
	 * Do some initial cleanup of database
	 */
	function initialize()
	{
		$this->forum->fluxbb->db->truncate_table('bans');
		$this->forum->fluxbb->db->truncate_table('categories');
		$this->forum->fluxbb->db->truncate_table('censoring');
		$this->forum->fluxbb->db->truncate_table('forums');
		$this->forum->fluxbb->db->truncate_table('forum_perms');
		$this->forum->fluxbb->db->truncate_table('online');
		$this->forum->fluxbb->db->truncate_table('posts');
		$this->forum->fluxbb->db->truncate_table('ranks');
		$this->forum->fluxbb->db->truncate_table('reports');
		$this->forum->fluxbb->db->truncate_table('search_cache');
		$this->forum->fluxbb->db->truncate_table('search_matches');
		$this->forum->fluxbb->db->truncate_table('search_words');
		$this->forum->fluxbb->db->truncate_table('topic_subscriptions');
		$this->forum->fluxbb->db->truncate_table('forum_subscriptions');
		$this->forum->fluxbb->db->truncate_table('topics');
//		$this->forum->fluxbb->db->truncate_table('users');
		$this->forum->fluxbb->db->query('DELETE FROM '.$this->forum->fluxbb->db->prefix.'users WHERE id > 1');
		$this->forum->fluxbb->db->query('DELETE FROM '.$this->forum->fluxbb->db->prefix.'groups WHERE g_id > 4');
	}

	function finnish()
	{
		// Handle users dupe
		if (!empty($_SESSION['converter']['dupe_users']))
		{
			foreach ($_SESSION['converter']['dupe_users'] as $cur_user)
				$this->forum->fluxbb->convert_users_dupe($cur_user);
		}

		$this->generate_cache();
	}

	/**
	 * Regenerate FluxBB cache after conversion
	 */
	function generate_cache()
	{
		// Load the cache script
		require_once PUN_ROOT.'include/cache.php';

		// Generate cache
		generate_config_cache();
		generate_bans_cache();
		generate_ranks_cache();
		generate_quickjump_cache();
		generate_censoring_cache();
		generate_users_info_cache();
		clear_feed_cache();
	}

	function get_time()
	{
		return get_microtime() - $this->start;
	}

}
