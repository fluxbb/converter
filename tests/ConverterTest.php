<?php

/**
 * Converter tests
 *
 * @copyright (C) 2012-2014 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 * @subpackage Tests
 */

define('CONV_ROOT', realpath(dirname(__FILE__).'/..').'/');

if (is_dir(CONV_ROOT.'fluxbb/'))
	define('PUN_ROOT', CONV_ROOT.'fluxbb/');

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

class ConverterTest extends PHPUnit_Framework_TestCase
{
	protected function convert($forum_type, $old_db_name, $old_db_prefix)
	{
		global $old_db_config, $pun_config, $db_config, $db, $forum_config;

		$fluxbb = new FluxBB($pun_config);
		$db = $fluxbb->connect_database($db_config);

		echo "\n\n".$forum_type.': '.$old_db_name."\n\n";
		$forum_config['type'] = $forum_type;
		$old_db_config['name'] = $old_db_name;
		$old_db_config['prefix'] = $old_db_prefix;
		$forum = load_forum($forum_config, $fluxbb);
		$forum->connect_database($old_db_config);

		$converter = new Converter($fluxbb, $forum);

		// Start the converter
		$next_step = array(null);
		while ($next_step !== false)
		{
			conv_message();
			conv_log('-----------------'."\n");
			$next_step = $converter->convert($next_step[0], isset($next_step[1]) ? $next_step[1] : 0);
		}

		$fluxbb_item_count = $converter->get_fluxbb_item_count();
		$forum_item_count = $converter->get_forum_item_count();

		foreach ($fluxbb_item_count as $table => $fluxbb_count)
		{
			if (!isset($forum_item_count[$table]))
				continue;

			$forum_count = $forum_item_count[$table];
			if ($fluxbb_count != -1 && $forum_count != -1)
				$this->assertEquals($fluxbb_count, $forum_count, 'Different item count for '.$table);
		}

		$forum->close_database();
		$fluxbb->close_database();
	}

	function testIPBoard()
	{
		$this->convert('IP_Board_3_2', 'ipb__test', 'ipb_');
	}

	function testMergeFluxBB()
	{
		$this->convert('Merge_FluxBB', 'merge__fluxbb', 'flux_');
	}

	function testminiBB()
	{
		$this->convert('miniBB_3_0', 'minibb__test', 'minibbtable_');
	}

	function testMyBB()
	{
		$this->convert('MyBB_1', 'mybb__test', 'mybb_');
	}

	function testPhpFusion()
	{
		$this->convert('PHP_Fusion_7', 'fusion__test', 'fusion_');
	}

	function testPhpbb3()
	{
		$this->convert('PhpBB_3_0', 'phpbb__test', 'phpbb_');
	}

	function testSmf1()
	{
		$this->convert('SMF_1_1', 'smf1__test', 'smf_');
	}

	function testSmf2()
	{
		$this->convert('SMF_2', 'smf2__test', 'smf_');
	}

	function testvB()
	{
		$this->convert('vBulletin_4_1', 'vb__test', 'vb_');
	}
}
