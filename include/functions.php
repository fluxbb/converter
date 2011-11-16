<?php

function error($message, $file = __FILE__, $line = __LINE__, $db_error = false)
{
	exit($message."\n".'<br />'.($db_error ? $db_error['error_msg'] : ''));
}

function message()
{
	$args = func_get_args();
	$message = count($args) > 0 ? array_shift($args) : '';

	echo vsprintf($message, $args)."\n".'<br />';
}

function redirect($stage, $start_from = 0, $time = 0)
{
	echo '<meta http-equiv="refresh" content="'.$time.'; url=converter.php?stage='.htmlspecialchars($stage).($start_from > 0 ? '&start_from='.$start_from : '').'">';
	exit;
}

function get_microtime()
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}

function connect_database($db_config)
{
	$class = $db_config['type'].'_wrapper';

	if (!class_exists($class))
	{
		if (!file_exists(SCRIPT_ROOT.'include/dblayer/'.$db_config['type'].'.php'))
			error('Unsupported database type: '.$db_config['type'], __FILE__, __LINE__);

		require SCRIPT_ROOT.'include/dblayer/'.$db_config['type'].'.php';
	}

	return new $class($db_config['host'], $db_config['username'], $db_config['password'], $db_config['name'], $db_config['prefix'], false);
}

function load_forum($forum_type, $db, $fluxbb)
{
	if (!class_exists($forum_type))
	{
		if (!file_exists(SCRIPT_ROOT.'forums/'.$forum_type.'.php'))
			error('Unsupported forum type: '.$forum_type, __FILE__, __LINE__);

		require SCRIPT_ROOT.'forums/'.$forum_type.'.php';
	}

	return new $forum_type($db, $fluxbb);
}

// Get all forum softwares
function get_forums()
{
	$forums = array();

	$d = dir(SCRIPT_ROOT.'forums');
	while ($entry = $d->read())
	{
		if (substr($entry, -4) == '.php')
		{
			$entry = substr($entry, 0, -4);
			// To have a nice name to display, we replace the first underscore with a space and the rest with dots (version number)
			$name = preg_replace('/_/', ' ', $entry, 1);
			$name = str_replace('_', '.', $name);

			$forums[$entry] = $name;
		}
	}
	asort($forums);

	return $forums;
}

// Get all database engines
function get_engines()
{
	$engines = array();

	$d = dir(SCRIPT_ROOT.'include/dblayer');
	while ($entry = $d->read())
	{
		if (substr($entry, -4) == '.php')
			$engines[] = substr($entry, 0, -4);
	}
	asort($engines);

	return $engines;
}

// Get all installed language packs
function get_languages()
{
	// TODO: Determine them from the forum package (where is it?)
	return array('Deutsch', 'English', 'Espanol');
}

// Get all installed styles
function get_styles()
{
	// TODO: Determine them from the forum package (where is it?)
	return array('Air', 'Oxygen', 'Cobalt');
}