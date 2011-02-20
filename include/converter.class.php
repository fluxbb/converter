<?php

class Converter
{
	var $old_db;
	var $forum;
	var $fluxbb;
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

	function Converter($old_db, $forum, $fluxbb)
	{
		$this->old_db = $old_db;
		$this->forum = $forum;
		$this->fluxbb = $fluxbb;
		
		$this->start = get_microtime();
	}

	function convert_all()
	{
		foreach ($this->tables as $name => $convert)
		{
			$start = get_microtime();

			message('%s %s', $convert ? 'Converting' : 'Initializing', $name);
			
			call_user_func(array($this->fluxbb, 'init_'.$name));

			if ($convert)
				call_user_func(array($this->forum, 'convert_'.$name), $this->old_db, $this->fluxbb);
			
			message('Completed in %s seconds', number_format(get_microtime() - $start, 2));
			message();
		}
	}
	
	function get_time()
	{
		return get_microtime() - $this->start;
	}
}
