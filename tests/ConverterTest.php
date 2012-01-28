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

class ConverterTest extends PHPUnit_Framework_TestCase
{
	protected $converter;
	protected $fluxbb;
	protected $forum;

	public function setUp()
	{
		global $pun_config, $db, $db_config, $old_db_config, $forum_config;

		// Default database configuration
		$old_db_config = array(
			'type'			=> 'mysqli',
			'host'			=> 'localhost',
			'name'			=> 'phpbb__test',
			'username'		=> '',
			'password'		=> '',
			'prefix'		=> 'phpbb_',
			'charset'		=> 'UTF-8',
		);

		$forum_config = array(
			'type'		=> 'PhpBB_3_0_9',
			'path'		=> ''
		);

		// Create a wrapper for fluxbb (has easy functions for adding users etc.)
		require CONV_ROOT.'include/fluxbb.class.php';
		$this->fluxbb = new FluxBB($pun_config);
		$db = $this->fluxbb->connect_database($db_config);

		// Load the migration script
		require CONV_ROOT.'include/forum.class.php';
		$this->forum = load_forum($forum_config, $fluxbb);
		$this->forum->connect_database($old_db_config);

		// Load converter script
		require CONV_ROOT.'include/converter.class.php';
		$this->converter = new Converter($this->fluxbb, $this->forum);
	}

	public function testA()
	{
		$this->assertEquals(true, true);
	}
}
