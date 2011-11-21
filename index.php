<?php

// The number of items to process per page view (lower this if the script times out)
define('PER_PAGE', 1000);

define('MIN_PHP_VERSION', '4.4.0');
define('MIN_MYSQL_VERSION', '4.1.2');
define('MIN_PGSQL_VERSION', '7.0.0');
define('PUN_SEARCH_MIN_WORD', 3);
define('PUN_SEARCH_MAX_WORD', 20);

define('PUN_ROOT', dirname(__FILE__).'/../');
define('SCRIPT_ROOT', './');
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

// If a cookie name is not specified in config.php, we use the default (pun_cookie)
if (empty($cookie_name))
	$cookie_name = 'pun_cookie';

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

// If we've been passed a default language, use it
$install_lang = isset($_REQUEST['install_lang']) ? trim($_REQUEST['install_lang']) : (isset($_SESSION['install_lang']) ? $_SESSION['install_lang'] : 'English');
$_SESSION['install_lang'] = $install_lang;

// If such a language pack doesn't exist, or isn't up-to-date enough to translate this page, default to English
if (!file_exists(PUN_ROOT.'lang/'.$install_lang.'/install.php'))
	$install_lang = 'English';

require PUN_ROOT.'lang/'.$install_lang.'/install.php';

if (file_exists(SCRIPT_ROOT.'lang/'.$install_lang.'/convert.php'))
	require SCRIPT_ROOT.'lang/'.$install_lang.'/convert.php';
else
	require SCRIPT_ROOT.'lang/English/convert.php';

$default_style = 'Air';

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit(sprintf($lang_install['You are running error'], 'PHP', PHP_VERSION, FORUM_VERSION, MIN_PHP_VERSION));

// Default database configuration
$db_config = array(
	'type'			=> $db_type,
	'host'			=> $db_host,
	'name'			=> $db_name,
	'username'		=> $db_username,
	'password'		=> $db_password,
	'prefix'		=> $db_prefix,
);

$styles = forum_list_styles();
$languages = forum_list_langs();
$engines = forum_list_engines();
$forums = forum_list_forums();

$old_db_config = $db_config;

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
		'prefix'	=> isset($_POST['old_db_prefix']) ? trim($_POST['old_db_prefix']) : ''
	);

	// Make sure base_url doesn't end with a slash
	if (substr($forum_config['base_url'], -1) == '/')
		$forum_config['base_url'] = substr($forum_config['base_url'], 0, -1);

	$_SESSION['fluxbb_converter'] = array('forum_config' => $forum_config, 'old_db_config' => $old_db_config);
}
else if (isset($_SESSION['fluxbb_converter']))
{
	$forum_config = $_SESSION['fluxbb_converter']['forum_config'];
	$old_db_config = $_SESSION['fluxbb_converter']['old_db_config'];
}


if (isset($_POST['form_sent']) || isset($_GET['stage']))
{
	$stage = isset($_GET['stage']) ? $_GET['stage'] : null;
	$start_at = isset($_GET['start_at']) ? $_GET['start_at'] : 0;

	// Check we aren't trying to convert to the same database
	//if ($old_db_config['name'] == $new_db_config['name'])
	//	error('Old and new tables must be different!', __FILE__, __LINE__);

	// Check the new database doesn't have any tables in it
	// TODO
	// Why?

	// The forum scripts must specify the charset manually!
	define('FORUM_NO_SET_NAMES', 1);

	// Connect to both databases
	$db = connect_database($db_config);
	$old_db = connect_database($old_db_config);

	// Load fluxbb wrapper
	require SCRIPT_ROOT.'include/fluxbb.class.php';
	$fluxbb = new FluxBB($db, $db_config['type']);

	// Load the migration script
	require SCRIPT_ROOT.'include/forum.class.php';
	$forum = load_forum($forum_config['type'], $old_db, $fluxbb);
	$forum->init_config();

	// Start the conversion process
	require SCRIPT_ROOT.'include/converter.class.php';
	$converter = new Converter($forum);

	if (!isset($stage))
		$converter->validate();

	if ($stage != 'results')
		$converter->convert($stage, $start_at);
//	else
//		unset($_SESSION['fluxbb_converter']);

	// Show the results page

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo $lang_install['FluxBB Installation'] ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_install['FluxBB has been installed'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">

<div class="blockform">
	<h2><span><?php echo $lang_install['Final instructions'] ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_install['FluxBB fully installed'] ?></p>
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
	// Determine available database extensions
	$dual_mysql = false;
	$db_extensions = array();
	$mysql_innodb = false;
	if (function_exists('mysqli_connect'))
	{
		$db_extensions[] = array('mysqli', 'MySQL Improved');
		$db_extensions[] = array('mysqli_innodb', 'MySQL Improved (InnoDB)');
		$mysql_innodb = true;
	}
	if (function_exists('mysql_connect'))
	{
		$db_extensions[] = array('mysql', 'MySQL Standard');
		$db_extensions[] = array('mysql_innodb', 'MySQL Standard (InnoDB)');
		$mysql_innodb = true;

		if (count($db_extensions) > 2)
			$dual_mysql = true;
	}
	if (function_exists('sqlite_open'))
		$db_extensions[] = array('sqlite', 'SQLite');
	if (function_exists('pg_connect'))
		$db_extensions[] = array('pgsql', 'PostgreSQL');

	if (empty($db_extensions))
		error($lang_install['No DB extensions']);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var element_names = {
		"req_forum": "<?php echo $lang_install['Forum software'] ?>",
		"req_db_type": "<?php echo $lang_install['Database type'] ?>",
		"req_db_host": "<?php echo $lang_install['Database server hostname'] ?>",
		"req_db_name": "<?php echo $lang_install['Database name'] ?>",
		"db_prefix": "<?php echo $lang_install['Table prefix'] ?>",
		"req_old_db_type": "<?php echo $lang_install['Database type'] ?>",
		"req_old_db_host": "<?php echo $lang_install['Database server hostname'] ?>",
		"req_old_db_name": "<?php echo $lang_install['Database name'] ?>",
		"old_db_prefix": "<?php echo $lang_install['Table prefix'] ?>",
		"req_base_url": "<?php echo $lang_install['Base URL'] ?>"
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
					alert('"' + element_names[elem.name] + '" <?php echo $lang_install['Required field'] ?>');
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
			<h1><span><?php echo $lang_install['FluxBB Installation'] ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_install['Install message'] ?></p><p><?php echo $lang_install['Welcome'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">
<?php if (count($languages) > 1): ?><div class="blockform">
	<h2><span><?php echo $lang_install['Choose install language'] ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="index.php">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Install language'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Choose install language info'] ?></p>
						<label><strong><?php echo $lang_install['Install language'] ?></strong>
						<br /><select name="install_lang">
<?php

		foreach ($languages as $temp)
		{
			if ($temp == $install_lang)
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
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_install['Change language'] ?>" /></p>
		</form>
	</div>
</div>
<?php endif; ?>

<div class="blockform">
	<h2><span><?php echo $lang_convert['Convert'] ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="index.php" onsubmit="this.start.disabled=true;if(process_form(this)){return true;}else{this.start.disabled=false;return false;}">
		<div><input type="hidden" name="module" value="Convert" /><input type="hidden" name="form_sent" value="1" /><input type="hidden" name="install_lang" value="<?php echo pun_htmlspecialchars($install_lang) ?>" /></div>
			<div class="inform">
<?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
					<h3><?php echo $lang_install['Errors'] ?></h3>
					<ul class="error-list">
<?php

foreach ($alerts as $cur_alert)
	echo "\t\t\t\t\t\t".'<li><strong>'.$cur_alert.'</strong></li>'."\n";
?>
					</ul>
				</div>
<?php endif; ?>			</div>

			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_convert['Convert from'] ?></h3>
					<p><?php echo $lang_convert['Convert info 1'] ?></p>
				</div>
				<fieldset>
				<legend><?php echo $lang_convert['Select software'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_convert['Convert info 2'] ?></p>
						<label class="required"><strong><?php echo $lang_convert['Forum software'] ?> <span><?php echo $lang_install['Required'] ?></span></strong>
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
						<label class="required"><strong><?php echo $lang_install['Database type'] ?> <span><?php echo $lang_install['Required'] ?></span></strong>
						<br /><select name="req_old_db_type">
<?php

	foreach ($db_extensions as $temp)
	{
		if ($temp[0] == $old_db_config['type'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp[0].'" selected="selected">'.$temp[1].'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp[0].'">'.$temp[1].'</option>'."\n";
	}

?>
						</select>
						<br /></label>
						<label class="required"><strong><?php echo $lang_install['Database server hostname'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="text" name="req_old_db_host" value="<?php echo pun_htmlspecialchars($old_db_config['host']) ?>" size="50" /><br /></label>
						<label class="required"><strong><?php echo $lang_install['Database name'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="text" name="req_old_db_name" value="<?php echo pun_htmlspecialchars($old_db_config['name']) ?>" size="30" /><br /></label>
						<label class="conl"><?php echo $lang_install['Database username'] ?><br /><input type="text" name="old_db_username" value="<?php echo pun_htmlspecialchars($old_db_config['username']) ?>" size="30" /><br /></label>
						<label class="conl"><?php echo $lang_install['Database password'] ?><br /><input type="password" name="old_db_password" size="30" /><br /></label>
						<label><?php echo $lang_install['Table prefix'] ?><br /><input id="db_prefix" type="text" name="old_db_prefix" value="<?php echo pun_htmlspecialchars($old_db_config['prefix']) ?>" size="20" maxlength="30" /><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_convert['Start conversion'] ?>" /></p>
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

