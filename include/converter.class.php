<?php

/**
* Copyright (C) 2011 FluxBB (http://fluxbb.org)
* License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
*/

class Converter
{
	protected $forum;
	protected $fluxbb;
	public $start;

	function __construct($fluxbb, $forum)
	{
		$this->forum = $forum;
		$this->fluxbb = $fluxbb;
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
	function convert($name = null, $start_at = 0, $redirect = false)
	{
		// Start from beginning
		if (!isset($name))
		{
			$_SESSION['fluxbb_converter']['start_time'] = get_microtime();
			$this->initialize();
			$name = $this->forum->steps[0];
		}

		$this->forum->stage = $name;

		$start = get_microtime();

		conv_message('Converting', $name);
		if (is_callable(array($this->forum, 'convert_'.$name)))
			call_user_func(array($this->forum, 'convert_'.$name), $start_at);

		conv_message('Done in', round(get_microtime() - $start, 4));

		// Redirect to the next stage
		$current = array_search($name, $this->forum->steps);

		// No more work to do?
		if (!isset($this->forum->steps[++$current]))
		{
			$this->finnish();
			$_SESSION['fluxbb_converter']['time'] = get_microtime() - $_SESSION['fluxbb_converter']['start_time'];
			if ($redirect)
				conv_redirect('results');
			else
				return true;
		}

		$next_stage = $this->forum->steps[$current];
		if ($redirect)
			conv_redirect($next_stage);
		else
		{
			conv_message();
			$this->convert($next_stage);
		}
	}

	/**
	 * Do some initial cleanup of database
	 */
	function initialize()
	{
		$this->fluxbb->db->truncate_table('bans');
		$this->fluxbb->db->truncate_table('categories');
		$this->fluxbb->db->truncate_table('censoring');
		$this->fluxbb->db->truncate_table('forums');
		$this->fluxbb->db->truncate_table('forum_perms');
		$this->fluxbb->db->truncate_table('online');
		$this->fluxbb->db->truncate_table('posts');
		$this->fluxbb->db->truncate_table('ranks');
		$this->fluxbb->db->truncate_table('reports');
		$this->fluxbb->db->truncate_table('search_cache');
		$this->fluxbb->db->truncate_table('search_matches');
		$this->fluxbb->db->truncate_table('search_words');
		$this->fluxbb->db->truncate_table('topic_subscriptions');
		$this->fluxbb->db->truncate_table('forum_subscriptions');
		$this->fluxbb->db->truncate_table('topics');
//		$this->fluxbb->db->truncate_table('users');
		$this->fluxbb->db->query('DELETE FROM '.$this->fluxbb->db->prefix.'users WHERE id > 1');
		$this->fluxbb->db->query('DELETE FROM '.$this->fluxbb->db->prefix.'groups WHERE g_id > 4');
	}

	function finnish()
	{
		// Handle users dupe
		if (!empty($_SESSION['converter']['dupe_users']))
		{
			foreach ($_SESSION['converter']['dupe_users'] as $cur_user)
				$this->fluxbb->convert_users_dupe($cur_user);
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
