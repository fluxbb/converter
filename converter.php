<?php

/**
 * Command line based converter script
 *
 * @copyright (C) 2011 FluxBB (http://fluxbb.org)
 * @license LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @package FluxBB
 */

define('SCRIPT_ROOT', dirname(__FILE__).'/');
require SCRIPT_ROOT.'include/functions_cmd.php';
require SCRIPT_ROOT.'include/common.php';

// The number of items to process per page view (very hackish :P)
define('PER_PAGE', pow(2, 32));

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
	conv_error('Not installed');


echo '=========================================='."\n";
echo '       FluxBB converter v'.CONVERTER_VERSION.'       '."\n";
echo '=========================================='."\n\n";
$params = array('help', 'forum:', 'path:', 'type:', 'host::', 'name:', 'user:', 'pass:', 'prefix:', 'charset:');
$options = getopt('hf:d:t:s:n:u:p:r:c:', $params);

if (empty($options) || isset($options['h']) || isset($options['help']))
{
	echo 'Usage: '."\n";
	echo "\t".'-f --forum'."\t".'Forum name.'."\n";
	echo "\t\t\t".'Possible values are: '.implode(", ", array_keys($forums))."\n";
	echo "\t".'-d --path'."\t".'Old forum directory.'."\n";
	echo "\t".'-t --type'."\t".'Old database type.'."\n";
	echo "\t\t\t".'Possible values are: '.implode(", ", $engines)."\n";
	echo "\t".'-s --host'."\t".'Old database host.'."\n";
	echo "\t".'-n --name'."\t".'Old database name.'."\n";
	echo "\t".'-u --user'."\t".'Old database username.'."\n";
	echo "\t".'-p --pass'."\t".'Old database password.'."\n";
	echo "\t".'-r --prefix'."\t".'Old database table prefix.'."\n";
	echo "\t".'-c --charset'."\t".'Old database charset (default UTF-8).'."\n";
	exit(1);
}

$forum_config = array(
	'type'		=> isset($options['f']) ? $options['f'] : (isset($options['forum']) ? $options['forum'] : null),
	'path'		=> isset($options['d']) ? $options['d'] : (isset($options['path']) ? $options['path'] : null),
);

$old_db_config = array(
	'type'		=> isset($options['t']) ? $options['t'] : (isset($options['type']) ? $options['type'] : null),
	'host'		=> isset($options['s']) ? $options['s'] : (isset($options['host']) ? $options['host'] : null),
	'name'		=> isset($options['n']) ? $options['n'] : (isset($options['name']) ? $options['name'] : null),
	'username'	=> isset($options['u']) ? $options['u'] : (isset($options['user']) ? $options['user'] : null),
	'password'	=> isset($options['p']) ? $options['p'] : (isset($options['pass']) ? $options['pass'] : ''),
	'prefix'	=> isset($options['r']) ? $options['r'] : (isset($options['prefix']) ? $options['prefix'] : ''),
	'charset'	=> isset($options['c']) ? $options['c'] : (isset($options['charset']) ? $options['charset'] : 'UTF-8'),
);

$forum_config = array_map('trim', $forum_config);
$old_db_config = array_map('trim', $old_db_config);

// Check whether we have all needed data valid
validate_params($forum_config, $old_db_config);

if (!array_key_exists($forum_config['type'], $forums))
{
	// Try to correct forum name (ignore case)
	$keys = array_keys($forums);
	$values = array();
	foreach ($keys as $cur_key)
		if (strpos(strtolower($cur_key), strtolower($forum_config['type'])) === 0)
			$values[] = $cur_key;

	if (count($values) == 1)
		$forum_config['type'] = $values[0];
	else if (($key = array_search(strtolower($forum_config['type']), array_map('strtolower', $keys))) !== false)
		$forum_config['type'] = $keys[$key];
	else
		conv_error('You entered an invalid forum software. Possible values are:'."\n".implode("\n", array_keys($forums)));
}

if (!in_array($old_db_config['type'], $engines))
	conv_error('Database type for old forum is invalid.'.(defined('CMDLINE') ? ' Possible values are:'."\n".implode("\n", $engines) : ''));


// Get database configuration from config.php
$db_config = array(
	'type'			=> $db_type,
	'host'			=> $db_host,
	'name'			=> $db_name,
	'username'		=> $db_username,
	'password'		=> $db_password,
	'prefix'		=> $db_prefix,
);

// Check we aren't trying to convert to the same database
if ($old_db_config == $db_config)
	conv_error('Old and new tables must be different!');

// Create a wrapper for fluxbb (has easy functions for adding users etc.)
require SCRIPT_ROOT.'include/fluxbb.class.php';
$fluxbb = new FluxBB($pun_config);
$db = $fluxbb->connect_database($db_config);

// Load the migration script
require SCRIPT_ROOT.'include/forum.class.php';
$forum = load_forum($forum_config, $fluxbb);
$forum->connect_database($old_db_config);

// Load converter script
require SCRIPT_ROOT.'include/converter.class.php';
$converter = new Converter($fluxbb, $forum);

// Start the converter
$converter->convert();

// We're done
$alerts = array($lang_convert['Rebuild search index note']);

if (!$forum->converts_password())
	$alerts[] = $lang_convert['Password converter mod'];

$fluxbb->close_database();

if (!empty($_SESSION['converter']['dupe_users']))
{
	conv_message("\n".'---------------------------'."\n");
	conv_message($lang_convert['Username dupes head']);
	conv_message($lang_convert['Error info 1']);
	conv_message($lang_convert['Error info 2']);
	foreach ($_SESSION['converter']['dupe_users'] as $id => $cur_user)
		conv_message("\t".$lang_convert['was renamed to'], $cur_user['old_username'], $cur_user['username']);

	conv_message();
	conv_message($lang_convert['Convert username dupes question']);

	$handle = fopen('php://stdin', 'r');
	$line = trim(fgets($handle));
	if ($line == 'yes')
	{
		alert_dupe_users();
		unset($_SESSION['converter']['dupe_users']);
	}
}

if (!empty($alerts))
{
	conv_message("\n".'---------------------------'."\n");
	conv_message('NOTE: '.implode("\n\n".'NOTE: ', $alerts));
}

conv_message();
conv_message($lang_convert['Conversion completed in'], round($_SESSION['fluxbb_converter']['time'], 4));

exit(1);
