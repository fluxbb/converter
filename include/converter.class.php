<?php

/**
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @package FluxBB
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

		// Delete old avatars
		if (is_writable($this->fluxbb->avatars_dir))
		{
			conv_log('Cleaning FluxBB avatars directory');

			$d = dir($this->fluxbb->avatars_dir);
			$num_deleted = 0;
			while ($f = $d->read())
			{
				if ($f != '.' && $f != '..' && in_array(substr($f, -4), array('.jpg', '.gif', '.png')))
				{
					$num_deleted++;
					if (!unlink($this->fluxbb->avatars_dir.$f))
						conv_log('Failed to delete avatar: '.$f);
				}
			}

			conv_log($num_deleted.' avatars deleted');
		}
	}

	/**
	 * Runs conversion process
	 *
	 * @param mixed $step Table name
	 * @param integer $start_at A row number from which we start processing table
	 */
	function convert($step = null, $start_at = 0, $redirect = false)
	{
		if (!$redirect && isset($step))
			conv_message();

		// Start from beginning
		if (!isset($step))
		{
			conv_message('Converting', 'start');
			$start = get_microtime();
			$_SESSION['fluxbb_converter']['start_time'] = get_microtime();

			// Validate only first time we run converter (check whether database configuration is valid)
			$this->validate();

			// Drop the FluxBB database tables
			$this->cleanup_database();

			$step = $this->forum->steps[0];

			conv_message('Done in', round(get_microtime() - $start, 4));
			return $this->redirect($step, 0, $redirect);
		}

		$start = get_microtime();
		$redirect_to = false;

		conv_message('Converting', $step);
		if (is_callable(array($this->forum, 'convert_'.$step)))
			$redirect_to = call_user_func(array($this->forum, 'convert_'.$step), $start_at);

		conv_message('Done in', round(get_microtime() - $start, 4));

		// Process same step starting from the $start_at row
		if ($redirect_to != false)
			$this->redirect($step, $redirect_to, $redirect);

		$current_step = array_search($step, $this->forum->steps);

		// Bassically should never happen
		if ($current_step === false)
			return false;

		// No more work to do?
		if (!isset($this->forum->steps[++$current_step]))
		{
			$this->finnish();
			$_SESSION['fluxbb_converter']['time'] = get_microtime() - $_SESSION['fluxbb_converter']['start_time'];
			$this->redirect('results', 0, $redirect);
		}
		else
		{
			// Redirect to the next step
			$next_step = $this->forum->steps[$current_step];
			$this->redirect($next_step, 0, $redirect);
		}
	}

	/**
	 * Redirect to the next step (or when running from command line - do next step without redirecting)
	 */
	function redirect($step, $start_at, $redirect)
	{
		if ($redirect)
			conv_redirect($step, $start_at);
		else if ($step != 'results')
	 		return $this->convert($step, $start_at, $redirect);
	}

	/**
	 * Do some initial cleanup of database
	 */
	function cleanup_database()
	{
		conv_log('Cleaning database');
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
		conv_log('Cleaning database: done');
	}

	function finnish()
	{
		// Handle users dupe
		if (!empty($_SESSION['converter']['dupe_users']))
		{
			conv_log('Converting dupe users');

			foreach ($_SESSION['converter']['dupe_users'] as $cur_user)
				$this->fluxbb->convert_users_dupe($cur_user);

			conv_log('Converting dupe users: done');
		}

		conv_log('Generate cache');
		$this->generate_cache();
		conv_log('Generate cache: done');
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
