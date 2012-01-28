<?php

/**
 * Converter tests
 *
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 * @subpackage Tests
 */

define('CONV_ROOT', realpath(dirname(__FILE__).'/..').'/');

if (is_dir(CONV_ROOT.'fluxbb/'))
	define('PUN_ROOT', CONV_ROOT.'fluxbb/');

require_once 'PHPUnit/Framework/TestCase.php';

require CONV_ROOT.'include/functions_cli.php';
require CONV_ROOT.'include/common.php';

// Output log messages to file
define('CONV_LOG', PUN_ROOT.'cache/converter_'.uniqid().'.log');

// The number of items to process per page view (very hackish :P)
define('PER_PAGE', pow(2, 32));

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
	conv_error('Not installed');

// Get database configuration from config.php
$db_config = array(
	'type'			=> $db_type,
	'host'			=> $db_host,
	'name'			=> $db_name,
	'username'		=> $db_username,
	'password'		=> $db_password,
	'prefix'		=> $db_prefix,
);

// Default database configuration
$old_db_config = array(
	'type'			=> 'mysqli',
	'host'			=> $db_config['host'],
	'name'			=> 'phpbb__test',
	'username'		=> $db_config['username'],
	'password'		=> $db_config['password'],
	'prefix'		=> 'phpbb_',
	'charset'		=> 'UTF-8',
);

$forum_config = array(
	'type'		=> 'PhpBB_3_0_9',
	'path'		=> ''
);

// Create a wrapper for fluxbb (has easy functions for adding users etc.)
require CONV_ROOT.'include/fluxbb.class.php';

// Load the migration script
require CONV_ROOT.'include/forum.class.php';

// Load converter script
require CONV_ROOT.'include/converter.class.php';

function convert($forum_type, $old_db_name, $old_db_prefix)
{
	global $old_db_config, $pun_config, $db_config, $db, $forum_config, $converter;

	$fluxbb = new FluxBB($pun_config);
	$db = $fluxbb->connect_database($db_config);

	echo "\n\n".$forum_type.': '.$old_db_name."\n\n";
	$forum_config['type'] = $forum_type;
	$old_db_config['name'] = $old_db_name;
	$old_db_config['prefix'] = $old_db_prefix;
	$forum = load_forum($forum_config, $fluxbb);
	$forum->connect_database($old_db_config);

	$converter = new Converter($fluxbb, $forum);
	$converter->convert();
	$forum->close_database();

	$fluxbb->close_database();
}

class ConverterTest extends PHPUnit_Framework_TestCase
{
	function testMyBB()
	{
		convert('MyBB_1', 'mybb__test', 'mybb_');
	}

	function testFusion()
	{
		convert('PHP_Fusion_7', 'fusion__test', 'fusion_');
	}

	function testPhpbb3()
	{
		convert('PhpBB_3_0_9', 'phpbb__test', 'phpbb_');
	}

	function testPunBB()
	{
		convert('PunBB_1.3_1.4', 'punbb__test', 'pun_');
	}

	function testSmf1()
	{
		convert('SMF_1_1_11', 'smf1__test', 'smf_');
	}

	function testSmf2()
	{
		convert('SMF_2', 'smf2__test', 'smf_');
	}
}
