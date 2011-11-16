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

		$this->start = get_microtime();
	}

	function convert_all()
	{
		foreach ($this->tables as $name => $convert)
		{
			$start = get_microtime();

			message('%s %s', $convert ? 'Converting' : 'Initializing', $name);

			if ($convert && $this->forum->fluxbb->db->table_exists($name))
				$this->forum->fluxbb->db->drop_table($name);

			call_user_func(array($this->forum->fluxbb, 'init_'.$name));

			if ($convert)
				call_user_func(array($this->forum, 'convert_'.$name));

			if (is_callable(array($this->forum, 'check_'.$name)))
				call_user_func(array($this->forum, 'check_'.$name));

			message('Completed in %s seconds', number_format(get_microtime() - $start, 2));
			message();
		}
	}

	function get_time()
	{
		return get_microtime() - $this->start;
	}
}
