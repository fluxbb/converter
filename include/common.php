<?php

/**
* Copyright (C) 2011 FluxBB (http://fluxbb.org)
* License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
*/

define('MIN_PHP_VERSION', '4.4.0');
define('MIN_MYSQL_VERSION', '4.1.2');
define('MIN_PGSQL_VERSION', '7.0.0');
define('PUN_SEARCH_MIN_WORD', 3);
define('PUN_SEARCH_MAX_WORD', 20);

define('PUN_ROOT', SCRIPT_ROOT.'/../');
define('CONVERTER_VERSION', '1.0-dev');

// Include the common functions
require SCRIPT_ROOT.'include/functions.php';

// Attempt to load the configuration file config.php
if (file_exists(PUN_ROOT.'config.php'))
	require PUN_ROOT.'config.php';

// If we have the 1.3-legacy constant defined, define the proper 1.4 constant so we don't get an incorrect "need to install" message
if (defined('FORUM'))
	define('PUN', FORUM);

// Load the functions script
require PUN_ROOT.'include/functions.php';

// Load UTF-8 functions
require PUN_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// Reverse the effect of register_globals
forum_unregister_globals();

// Record the start time (will be used to calculate the generation time for the page)
$pun_start = get_microtime();

// Make sure PHP reports all errors except E_NOTICE. FluxBB supports E_ALL, but a lot of scripts it may interact with, do not
error_reporting(E_ALL/* ^ E_NOTICE*/);

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime())
	set_magic_quotes_runtime(0);

// Strip slashes from GET/POST/COOKIE/REQUEST/FILES (if magic_quotes_gpc is enabled)
if (get_magic_quotes_gpc())
{
	function stripslashes_array($array)
	{
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}

	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
	$_REQUEST = stripslashes_array($_REQUEST);
	$_FILES = stripslashes_array($_FILES);
}

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

// Define a few commonly used constants
define('PUN_UNVERIFIED', 0);
define('PUN_ADMIN', 1);
define('PUN_MOD', 2);
define('PUN_GUEST', 3);
define('PUN_MEMBER', 4);

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

$languages = converter_list_langs();

// If we've been passed a default language, use it
$convert_lang = isset($_REQUEST['convert_lang']) ? trim($_REQUEST['convert_lang']) : (isset($_SESSION['converter']['lang']) ? $_SESSION['converter']['lang'] : 'English');

// If such a language pack doesn't exist, default to English
if (!in_array($convert_lang, $languages))
	$convert_lang = 'English';

// Load converter language file
require SCRIPT_ROOT.'lang/'.$convert_lang.'/convert.php';

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit(sprintf($lang_convert['You are running error'], 'PHP', PHP_VERSION, FORUM_VERSION, MIN_PHP_VERSION));

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
	include FORUM_CACHE_DIR.'cache_config.php';

$engines = forum_list_engines();
$forums = forum_list_forums();

// Default database configuration
$db_config_default = array(
	'type'			=> 'mysqli',
	'host'			=> 'localhost',
	'name'			=> '',
	'username'		=> '',
	'password'		=> '',
	'prefix'		=> '',
	'charset'		=> 'UTF-8',
);
