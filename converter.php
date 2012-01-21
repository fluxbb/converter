<?php

/**
 * Command line based converter script
 *
 * @copyright (C) 2011 FluxBB (http://fluxbb.org)
 * @license LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @package FluxBB
 */

define('SCRIPT_ROOT', dirname(__FILE__).'/');
require SCRIPT_ROOT.'include/functions_cli.php';
require SCRIPT_ROOT.'include/common.php';

// Output log messages to file
define('CONV_LOG', PUN_ROOT.'cache/converter_'.substr(sha1(time()), 0, 7).'.log');

// The number of items to process per page view (very hackish :P)
define('PER_PAGE', pow(2, 32));

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
	conv_error('Not installed');


echo '=========================================='."\n";
echo '       '.sprintf($lang_convert['FluxBB converter'], CONV_VERSION).'       '."\n";
echo '=========================================='."\n\n";
$params = array('help', 'forum:', 'path:', 'type:', 'host::', 'name:', 'user:', 'pass:', 'prefix:', 'charset:');
$options = getopt('hf:d:t:s:n:u:p:r:c:', $params);

if (empty($options) || isset($options['h']) || isset($options['help']))
{
	echo $lang_convert['Usage'].': '."\n";
	echo "\t".'-f --forum'."\t".$lang_convert['Forum software']."\n";
	echo "\t\t\t".sprintf($lang_convert['Possible values'], "\n\t\t\t\t".implode("\n\t\t\t\t", array_keys($forums)))."\n";
	echo "\t".'-d --path'."\t".$lang_convert['Old forum path']."\n";
	echo "\t".'-t --type'."\t".$lang_convert['Database type'].' '.sprintf($lang_convert['Default value'], $db_config_default['type'])."\n";
	echo "\t\t\t".sprintf($lang_convert['Possible values'], "\n\t\t\t\t".implode("\n\t\t\t\t", $engines))."\n";
	echo "\t".'-s --host'."\t".$lang_convert['Database server hostname'].' '.sprintf($lang_convert['Default value'], $db_config_default['host'])."\n";
	echo "\t".'-n --name'."\t".$lang_convert['Database name']."\n";
	echo "\t".'-u --user'."\t".$lang_convert['Database username'].' '.sprintf($lang_convert['Default value'], $db_config_default['username'])."\n";
	echo "\t".'-p --pass'."\t".$lang_convert['Database password']."\n";
	echo "\t".'-r --prefix'."\t".$lang_convert['Table prefix']."\n";
	echo "\t".'-c --charset'."\t".$lang_convert['Database charset'].' '.sprintf($lang_convert['Default value'], $db_config_default['charset'])."\n";
	exit(1);
}

$forum_config = array(
	'type'		=> isset($options['f']) ? $options['f'] : (isset($options['forum']) ? $options['forum'] : null),
	'path'		=> isset($options['d']) ? $options['d'] : (isset($options['path']) ? $options['path'] : null),
);

$old_db_config = array(
	'type'		=> isset($options['t']) ? $options['t'] : (isset($options['type']) ? $options['type'] : $db_config_default['type']),
	'host'		=> isset($options['s']) ? $options['s'] : (isset($options['host']) ? $options['host'] : $db_config_default['host']),
	'name'		=> isset($options['n']) ? $options['n'] : (isset($options['name']) ? $options['name'] : $db_config_default['name']),
	'username'	=> isset($options['u']) ? $options['u'] : (isset($options['user']) ? $options['user'] : $db_config_default['username']),
	'password'	=> isset($options['p']) ? $options['p'] : (isset($options['pass']) ? $options['pass'] : $db_config_default['password']),
	'prefix'	=> isset($options['r']) ? $options['r'] : (isset($options['prefix']) ? $options['prefix'] : $db_config_default['prefix']),
	'charset'	=> isset($options['c']) ? $options['c'] : (isset($options['charset']) ? $options['charset'] : $db_config_default['charset']),
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
		conv_error($lang_convert['Invalid forum software'].' '.sprintf($lang_convert['Possible values'], "\n".implode("\n", array_keys($forums))));
}

if (!in_array($old_db_config['type'], $engines))
	conv_error($lang_convert['Invalid database type'].' '.sprintf($lang_convert['Possible values'], "\n".implode("\n", $engines)));


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
	conv_error('Same database tables');

if (defined('CONV_LOG') && file_exists(PUN_ROOT.'cache/converter.log'))
	@unlink(PUN_ROOT.'cache/converter.log');

conv_log('Running command line based converter for: '.$forum_config['type'].' ('.gmdate('Y-m-d').')');
conv_log('PHP version: '.PHP_VERSION.', OS: '.PHP_OS);

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
	conv_message($lang_convert['Notes'].':'."\n".implode("\n", $alerts));
}

conv_message();
conv_message($lang_convert['Conversion completed in'], round($_SESSION['fluxbb_converter']['time'], 4));

conv_log('Done', false, true);

exit(1);
