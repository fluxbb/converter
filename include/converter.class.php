<?php

class Converter
{
	var $forum;
	var $start;

	var $tables = array(
		'bans' 					=> true,
		'categories'			=> true,
		'censoring'				=> true,
		'config'				=> true,
		'forums'				=> true,
		'forum_perms'			=> true,
		'groups'				=> true,
		'online'				=> false,
		'posts'					=> true,
		'ranks'					=> true,
		'reports'				=> true,
		'search_cache'			=> false,
		'search_matches'		=> false,
		'search_words'			=> false,
		'topic_subscriptions'	=> true,
		'forum_subscriptions'	=> true,
		'topics'				=> true,
		'users'					=> true,
	);

	function Converter($forum)
	{
		$this->forum = $forum;

		if (!isset($_SESSION['fluxbb_converter']['start']))
			$_SESSION['fluxbb_converter']['start'] = $this->start = get_microtime();
		else
			$this->start = $_SESSION['fluxbb_converter']['start'];
	}

//	function convert_all()
//	{
//		foreach ($this->tables as $name => $convert)
//		{
//			$this->convert($name);
//		}
//	}

	function convert($name, $start_at = 0)
	{
		$keys = array_keys($this->tables);

		// Start from beginning
		if (!isset($name))
			$name = $keys[0];

		$this->forum->stage = $name;

		$start = get_microtime();
		$convert = $this->tables[$name];

		message('%s %s', $convert ? 'Converting' : 'Initializing', $name);

		if ($convert && $this->forum->fluxbb->db->table_exists($name))
			$this->forum->fluxbb->db->drop_table($name);

		call_user_func(array($this->forum->fluxbb, 'init_'.$name));

		if ($convert)
			call_user_func(array($this->forum, 'convert_'.$name), $start_at);

		if (is_callable(array($this->forum, 'check_'.$name)))
			call_user_func(array($this->forum, 'check_'.$name));

		// Redirect to the next stage
		$current = array_search($name, $keys);

		// No more work to do?
		if (!isset($keys[++$current]))
		{
			$_SESSION['fluxbb_converter']['time'] = get_microtime() - $this->start;
			redirect('results');
		}

		$next_stage = $keys[$current];
		redirect($next_stage);
	}

	function get_time()
	{
		return get_microtime() - $this->start;
	}
}
