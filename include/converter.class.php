<?php

/**
 * @copyright (C) 2012-2014 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

class Converter
{
	var $forum;
	var $fluxbb;
	var $start;

	function Converter($fluxbb, $forum)
	{
		global $session;

		$this->forum = $forum;
		$this->fluxbb = $fluxbb;

		if (!isset($session))
			$session = array();
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
			$start = get_microtime();

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

			conv_log($num_deleted.' avatars deleted in '.round(get_microtime() - $start, 6)."\n");
		}
	}

	/**
	 * Runs conversion process
	 *
	 * @param mixed $step Table name
	 * @param integer $start_at A row number from which we start processing table
	 */
	function convert($step = null, $start_at = 0)
	{
		global $session;

		$steps = array_keys($this->forum->steps);

		// Start from beginning
		if (!isset($step))
		{
			conv_log();
			conv_message('Converting', 'start');
			$session['start_time'] = get_microtime();

			// Validate only first time we run converter (check whether database configuration is valid)
			$this->validate();

			$session['count'] = $this->forum->fetch_item_count();

			// Drop the FluxBB database tables (when there is no NO_DB_CLEANUP constant defined for forum)
			if (!defined(get_class($this->forum).'::NO_DB_CLEANUP'))
				$this->cleanup_database();

			conv_message('Done in', round(get_microtime() - $session['start_time'], 6));
			$step = $steps[0];

			return array($step);
		}

		$start = get_microtime();
		$redirect_to = null;

		conv_message('Converting', $step);
		if (is_callable(array($this->forum, 'convert_'.$step)))
			$redirect_to = call_user_func(array($this->forum, 'convert_'.$step), $start_at);
		else if (is_callable(array($this, $step)))
			$redirect_to = call_user_func(array($this, $step));
		else
			conv_message('Not implemented', $step);

		conv_message('Done in', round(get_microtime() - $start, 6));

		// Process same step starting from $start_at
		if ($redirect_to != null)
			return array($step, $redirect_to);

		// Are we done?
		if ($step == 'finish')
			return false;

		$current_step = array_search($step, $steps);

		// Basically should never happen
		if ($current_step === false)
			return false;

		// No more tables to process?
		if (!isset($steps[++$current_step]))
			return array('finish');

		// Redirect to the next step
		return array($steps[$current_step]);
	}

	/**
	 * Do some initial cleanup of database
	 */
	function cleanup_database()
	{
		conv_log('Cleaning database');
		$start = get_microtime();

		$this->fluxbb->cleanup_database();

		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
	}

	function finish()
	{
		global $session;

		// Handle users dupe
		if (!empty($session['dupe_users']))
		{
			conv_log('Converting dupe users');
			$start = get_microtime();

			foreach ($session['dupe_users'] as $cur_user)
				$this->fluxbb->convert_users_dupe($cur_user);

			conv_log('Done in '.round(get_microtime() - $start, 6)."\n");
		}

		$this->fluxbb->sync_db();

		conv_log('Generate cache');
		$start = get_microtime();
		$this->generate_cache();
		conv_log('Done in '.round(get_microtime() - $start, 6)."\n");

		$session['fluxbb_count'] = $this->fluxbb->fetch_item_count();
		$session['time'] = get_microtime() - $session['start_time'];
	}

	function get_forum_item_count($table = null)
	{
		return $this->forum->fetch_item_count($table);
	}

	function get_fluxbb_item_count($table = null)
	{
		return $this->fluxbb->fetch_item_count($table);
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
		generate_quickjump_cache();
		generate_censoring_cache();
		generate_users_info_cache();
		clear_feed_cache();
	}

	// TODO:
	function get_time()
	{
		return get_microtime() - $this->start;
	}

}
