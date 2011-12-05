<?php

/**
* Copyright (C) 2011 FluxBB (http://fluxbb.org)
* License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
*/

// The number of items to process per page view (lower this if the script times out)
define('PER_PAGE', 1000);

define('MIN_PHP_VERSION', '4.4.0');
define('MIN_MYSQL_VERSION', '4.1.2');
define('MIN_PGSQL_VERSION', '7.0.0');
define('PUN_SEARCH_MIN_WORD', 3);
define('PUN_SEARCH_MAX_WORD', 20);

define('PUN_ROOT', dirname(__FILE__).'/../');
define('SCRIPT_ROOT', './');
define('CONVERTER_VERSION', '1.0-dev');
define('PUN_DEBUG', 1);
define('PUN_SHOW_QUERIES', 1);

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

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
{
	header('Location: ../install.php');
	exit;
}

// Record the start time (will be used to calculate the generation time for the page)
$pun_start = get_microtime();

// Make sure PHP reports all errors except E_NOTICE. FluxBB supports E_ALL, but a lot of scripts it may interact with, do not
error_reporting(E_ALL ^ E_NOTICE);

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

// Include the common functions
require SCRIPT_ROOT.'include/functions.php';

session_start();

$languages = converter_list_langs();
$engines = forum_list_engines();
$forums = forum_list_forums();

// If we've been passed a default language, use it
$convert_lang = isset($_REQUEST['convert_lang']) ? trim($_REQUEST['convert_lang']) : (isset($_SESSION['converter']['lang']) ? $_SESSION['converter']['lang'] : 'English');

// If such a language pack doesn't exist, default to English
if (!in_array($convert_lang, $languages))
	$convert_lang = 'English';

// Load converter language file
require SCRIPT_ROOT.'lang/'.$convert_lang.'/convert.php';

$default_style = 'Air';

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit(sprintf($lang_convert['You are running error'], 'PHP', PHP_VERSION, FORUM_VERSION, MIN_PHP_VERSION));

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
	include FORUM_CACHE_DIR.'cache_config.php';

if (isset($_GET['alert_dupe_users']))
{
	if (empty($_SESSION['converter']['dupe_users']))
		error($lang_convert['Bad request']);

	alert_dupe_users();
	unset($_SESSION['converter']['dupe_users']);
}

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


$old_db_config = $db_config_default;

// We submited the form, store data in session as we'll redirect to the next page
if (isset($_POST['form_sent']))
{
	$forum_config = array(
		'type'			=> isset($_POST['req_forum']) && isset($forums[$_POST['req_forum']]) ? $_POST['req_forum'] : error('You entered an invalid forum software.'.$_POST['convert_to'], __FILE__, __LINE__),
	);

	$old_db_config = array(
		'type'		=> isset($_POST['req_old_db_type']) && in_array($_POST['req_old_db_type'], $engines) ? $_POST['req_old_db_type'] : error('Database type for old forum is invalid.', __FILE__, __LINE__),
		'host'		=> isset($_POST['req_old_db_host']) ? trim($_POST['req_old_db_host']) : error('You have to enter a database host for the old forum.', __FILE__, __LINE__),
		'name'		=> isset($_POST['req_old_db_name']) ? trim($_POST['req_old_db_name']) : error('You have to enter a database name for the old forum.', __FILE__, __LINE__),
		'username'	=> isset($_POST['old_db_username']) ? trim($_POST['old_db_username']) : error('You have to enter a database username for the old forum.', __FILE__, __LINE__),
		'password'	=> isset($_POST['old_db_pass']) ? $_POST['old_db_pass'] : '',
		'prefix'	=> isset($_POST['old_db_prefix']) ? trim($_POST['old_db_prefix']) : '',
		'charset'	=> isset($_POST['old_db_charset']) ? trim($_POST['old_db_charset']) : 'UTF-8'
	);

	$_SESSION['converter'] = array('forum_config' => $forum_config, 'old_db_config' => $old_db_config, 'lang' => $convert_lang);
}

// Fetch data from session
else if (isset($_SESSION['converter']))
{
	$forum_config = $_SESSION['converter']['forum_config'];
	$old_db_config = $_SESSION['converter']['old_db_config'];
	$convert_lang = $_SESSION['converter']['lang'];
}


if (isset($_POST['form_sent']) || isset($_GET['stage']))
{
	if (!isset($forum_config))
		error($lang_convert['Bad request']);

	$stage = isset($_GET['stage']) ? $_GET['stage'] : null;
	$start_at = isset($_GET['start_at']) ? $_GET['start_at'] : 0;

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
		error('Old and new tables must be different!', __FILE__, __LINE__);

	// The forum scripts must specify the charset manually!
	define('FORUM_NO_SET_NAMES', 1);

	// Connect to both databases
	$db = connect_database($db_config);
	$old_db = connect_database($old_db_config);

	// Load configuration cache (or recreate when it does not exist)
	// We need it for fetching default language for mail templates when alerting dupe users
	if (!defined('PUN_CONFIG_LOADED'))
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require PUN_ROOT.'include/cache.php';

		generate_config_cache();
		require FORUM_CACHE_DIR.'cache_config.php';
	}

	// Create a wrapper for fluxbb (has easy functions for adding users etc.)
	require SCRIPT_ROOT.'include/fluxbb.class.php';
	$fluxbb = new FluxBB($db, $db_config['type']);

	// Load the migration script
	require SCRIPT_ROOT.'include/forum.class.php';
	$forum = load_forum($forum_config['type'], $old_db, $fluxbb);
	$forum->init_config($old_db_config);

	// Start the conversion process
	require SCRIPT_ROOT.'include/converter.class.php';
	$converter = new Converter($forum);

	// Validate only first time we run converter (it checks whether database configuration is corrent)
	if (!isset($stage))
		$converter->validate();

	if (!isset($stage) || $stage != 'results')
	{
		// We are ready to run converter. When it do its work, it redirects to the next page
		$converter->convert($stage, $start_at);
	}

	if (empty($_SESSION['converter']['dupe_users']))
		unset($_SESSION['converter']);

	// We're done
	$alerts = array();

	if (!$forum->CONVERTS_PASSWORD)
		$alerts[] = $lang_convert['Password converter mod'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo sprintf($lang_convert['FluxBB converter'], CONVERTER_VERSION) ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo sprintf($lang_convert['FluxBB converter'], CONVERTER_VERSION) ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_convert['Conversion completed'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">

<div class="blockform">

<?php if (!empty($_SESSION['converter']['dupe_users'])) : ?>
	<h2><span><?php echo $lang_convert['Username dupes head'] ?></span></h2>
	<div class="box">
		<form method="post" action="index.php?stage=results&alert_dupe_users">
			<div class="inform">
				<div class="forminfo">
					<p style="font-size: 1.1em"><?php echo $lang_convert['Error info 1'] ?></p>
					<p style="font-size: 1.1em"><?php echo $lang_convert['Error info 2'] ?></p>
				</div>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_convert['Username dupes'] ?></legend>
					<div class="infldset">
						<p>
<?php
			foreach ($_SESSION['converter']['dupe_users'] as $id => $cur_user)
				echo sprintf($lang_convert['was renamed to'], $cur_user['username'], $cur_user['new_username']).'<br />'."\n";

?>
						</p>
					</div>
				</fieldset>
			</div>

			<p class="buttons"><input type="submit" name="rename" value="<?php echo $lang_convert['Alert users'] ?>" /></p>
		</form>
	</div>
<?php endif; ?>

	<h2><span><?php echo $lang_convert['Final instructions'] ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
<?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
					<ul class="error-list">
<?php

foreach ($alerts as $cur_alert)
	echo "\t\t\t\t\t".'<li>'.$cur_alert.'</li>'."\n";
?>
					</ul>
				</div>
<?php endif; ?>
				<div class="forminfo">
					<p><?php printf($lang_convert['Database converted'], '../index.php') ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php


}
else
{

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo sprintf($lang_convert['FluxBB converter'], CONVERTER_VERSION) ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var element_names = {
		"req_forum": "<?php echo $lang_convert['Forum software'] ?>",
		"req_old_db_type": "<?php echo $lang_convert['Database type'] ?>",
		"req_old_db_host": "<?php echo $lang_convert['Database server hostname'] ?>",
		"req_old_db_name": "<?php echo $lang_convert['Database name'] ?>",
		"old_db_prefix": "<?php echo $lang_convert['Table prefix'] ?>",
	};
	if (document.all || document.getElementById)
	{
		for (var i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i];
			if (elem.name && (/^req_/.test(elem.name)))
			{
				if (!elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
				{
					alert('"' + element_names[elem.name] + '" <?php echo $lang_convert['Required field'] ?>');
					elem.focus();
					return false;
				}
			}
		}
	}
	return true;
}
/* ]]> */
</script>
</head>
<body onload="document.getElementById('install').req_forum.focus();document.getElementById('install').start.disabled=false;" onunload="">

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo sprintf($lang_convert['FluxBB converter'], CONVERTER_VERSION) ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_convert['Convert message'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">
<?php if (count($languages) > 1): ?><div class="blockform">
	<h2><span><?php echo $lang_convert['Choose convert language'] ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="index.php">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_convert['Convert language'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_convert['Choose convert language info'] ?></p>
						<label><strong><?php echo $lang_convert['Convert language'] ?></strong>
						<br /><select name="convert_lang">
<?php

		foreach ($languages as $temp)
		{
			if ($temp == $convert_lang)
				echo "\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
			else
				echo "\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
		}

?>
						</select>
						<br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_convert['Change language'] ?>" /></p>
		</form>
	</div>
</div>
<?php endif; ?>

<div class="blockform">
	<h2><span><?php echo $lang_convert['Convert'] ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="index.php" onsubmit="this.start.disabled=true;if(process_form(this)){return true;}else{this.start.disabled=false;return false;}">
		<div><input type="hidden" name="module" value="Convert" /><input type="hidden" name="form_sent" value="1" /><input type="hidden" name="convert_lang" value="<?php echo pun_htmlspecialchars($convert_lang) ?>" /></div>
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_convert['Convert from'] ?></h3>
					<p><?php echo $lang_convert['Convert info 1'] ?></p>
				</div>
				<fieldset>
				<legend><?php echo $lang_convert['Select software'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_convert['Convert info 2'] ?></p>
						<label class="required"><strong><?php echo $lang_convert['Forum software'] ?> <span><?php echo $lang_convert['Required'] ?></span></strong>
						<br /><select name="req_forum">
<?php

	foreach ($forums as $value => $name)
	{
		if ($value == $forum_config['type'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$value.'" selected="selected">'.$name.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$value.'">'.$name.'</option>'."\n";
	}

?>
						</select>
						<br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
				<legend><?php echo $lang_convert['Select old database'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_convert['Convert info 3'] ?></p>
						<label class="required"><strong><?php echo $lang_convert['Database type'] ?> <span><?php echo $lang_convert['Required'] ?></span></strong>
						<br /><select name="req_old_db_type">
<?php

	foreach ($engines as $temp)
	{
		if ($temp == $old_db_config['type'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
	}

?>
						</select>
						<br /></label>
						<label class="required"><strong><?php echo $lang_convert['Database server hostname'] ?> <span><?php echo $lang_convert['Required'] ?></span></strong><br /><input type="text" name="req_old_db_host" value="<?php echo pun_htmlspecialchars($old_db_config['host']) ?>" size="50" /><br /></label>
						<label class="required"><strong><?php echo $lang_convert['Database name'] ?> <span><?php echo $lang_convert['Required'] ?></span></strong><br /><input type="text" name="req_old_db_name" value="<?php echo pun_htmlspecialchars($old_db_config['name']) ?>" size="30" /><br /></label>
						<label class="conl"><?php echo $lang_convert['Database username'] ?><br /><input type="text" name="old_db_username" value="<?php echo pun_htmlspecialchars($old_db_config['username']) ?>" size="30" /><br /></label>
						<label class="conl"><?php echo $lang_convert['Database password'] ?><br /><input type="password" name="old_db_password" size="30" /><br /></label>
						<label><?php echo $lang_convert['Database charset'] ?> <?php echo $lang_convert['Database charset info'] ?><br /><input type="text" name="old_db_charset" value="<?php echo pun_htmlspecialchars($old_db_config['charset']) ?>" size="20" maxlength="30" /><br /></label>
						<label><?php echo $lang_convert['Table prefix'] ?><br /><input id="db_prefix" type="text" name="old_db_prefix" value="<?php echo pun_htmlspecialchars($old_db_config['prefix']) ?>" size="20" maxlength="30" /><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_convert['Start converter'] ?>" /></p>
		</form>
	</div>
</div>
</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

}

