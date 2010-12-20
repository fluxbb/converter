<?php

define('SCRIPT_ROOT', './');

// Include the common functions
require SCRIPT_ROOT.'include/functions.php';


$forums = get_forums();
$engines = get_engines();
$languages = get_languages();
$styles = get_styles();


if (isset($_POST['submit']))
{
	$forum_config = array(
		'type'			=> isset($_POST['convert_to']) && isset($forums[$_POST['convert_to']]) ? $_POST['convert_to'] : error('You entered an invalid forum software.'.$_POST['convert_to'], __FILE__, __LINE__),
		'base_url'		=> isset($_POST['base_url']) ? trim($_POST['base_url']) : '', // TODO: utf8_trim()? is_url()?
		'default_lang'	=> isset($_POST['new_language']) && in_array($_POST['new_language'], $languages) ? $_POST['new_language'] : 'English',
		'default_style'	=> isset($_POST['new_style']) && in_array($_POST['new_style'], $styles) ? $_POST['new_style'] : 'Oxygen'
	);

	$old_db_config = array(
		'type'		=> isset($_POST['old_type']) && in_array($_POST['old_type'], $engines) ? $_POST['old_type'] : error('Database type for old forum is invalid.', __FILE__, __LINE__),
		'host'		=> isset($_POST['old_host']) ? trim($_POST['old_host']) : error('You have to enter a database host for the old forum.', __FILE__, __LINE__),
		'username'	=> isset($_POST['old_user']) ? trim($_POST['old_user']) : error('You have to enter a database username for the old forum.', __FILE__, __LINE__),
		'password'	=> isset($_POST['old_pass']) ? $_POST['old_pass'] : '',
		'name'		=> isset($_POST['old_name']) ? trim($_POST['old_name']) : error('You have to enter a database name for the old forum.', __FILE__, __LINE__),
		'prefix'	=> isset($_POST['old_prefix']) ? trim($_POST['old_prefix']) : ''
	);

	$new_db_config = array(
		'type'		=> isset($_POST['new_type']) && in_array($_POST['new_type'], $engines) ? $_POST['new_type'] : error('Database type for old forum is invalid.', __FILE__, __LINE__),
		'host'		=> isset($_POST['new_host']) ? trim($_POST['new_host']) : error('You have to enter a database host for the old forum.', __FILE__, __LINE__),
		'username'	=> isset($_POST['new_user']) ? trim($_POST['new_user']) : error('You have to enter a database username for the old forum.', __FILE__, __LINE__),
		'password'	=> isset($_POST['new_pass']) ? $_POST['new_pass'] : '',
		'name'		=> isset($_POST['new_name']) ? trim($_POST['new_name']) : error('You have to enter a database name for the old forum.', __FILE__, __LINE__),
		'prefix'	=> isset($_POST['new_prefix']) ? trim($_POST['new_prefix']) : ''
	);

	// Check we aren't trying to convert to the same database
	//if ($old_db_config['name'] == $new_db_config['name'])
	//	error('Old and new tables must be different!', __FILE__, __LINE__);

	// Check the new database doesn't have any tables in it
	// TODO
	// Why?

	// The forum scripts must specify the charset manually!
	define('FORUM_NO_SET_NAMES', 1);

	// Connect to both databases
	$old_db = connect_database($old_db_config);
	$new_db = connect_database($new_db_config);

	// Create a wrapper for fluxbb (has easy functions for adding users etc.)
	require SCRIPT_ROOT.'include/fluxbb.class.php';
	$fluxbb = new FluxBB($new_db, $new_db_config['type']);

	// Load the migration script
	require SCRIPT_ROOT.'include/forum.class.php';
	$forum = load_forum($forum_config['type']);
	$forum->init_config($old_db, $forum_config);

	// Start the conversion process
	require SCRIPT_ROOT.'include/converter.class.php';
	$converter = new Converter($old_db, $forum, $fluxbb);
	$converter->convert_all();

	// Finished! :-)
	message('--------------------------------------------------------');
	message('Conversion completed in %s seconds', number_format($converter->get_time(), 2));

	// TODO: Try to create config file, set styles etc. for everybody
}
else
{

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FluxBB Forum Converter</title>

<style type="text/css">
<!--

.site {
	font: 62.5%/100% "Helvetica Neue", arial, helvetica, sans-serif;
	color: #566579;
}

fieldset {
	border: 2px solid #566579;
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	margin-bottom: 10px;
}

h1 {
	font: 2.8em/2.25em "Trebuchet MS", helvetica, arial, sans-serif;
	background: url('logo.png') left center no-repeat;
	padding-left: 60px;
	padding-bottom: 7px;
	border-top: 3px solid #566579;
	border-bottom: 3px solid #566579;
}

legend {
	font: 2em/1.333em "Trebuchet MS", helvetica, arial, sans-serif;
	padding: 6px;
}

.fset {
	padding: 5px 0;
	border-bottom: 1px solid #C0C0C0;
}

.fset:last-child {
	border-bottom-width: 0;
}

.fset:hover {
	background-color: #F0F0F0;
}

.fset label {
	display: block;
	width: 200px;
	text-align: right;
	margin-top: 7px;
	float: left;
	font-size: 1.2em;
	font-weight: bold;
}

.fset input, .fset select {
	margin-left: 10px;
}

.fset input {
	width: 20em;
}

.fset span {
	display: block;
	margin-top: 5px;
	margin-left: 210px;
	font-size: 1.1em;
}

input.submit {
	padding: 7px;
	background-color: #F0F5FA;
	font-size: 1.7em;
	font-weight: bold;
	margin-top: 15px;
	margin-left: 220px;
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
}

.message {
	border: 1px solid #566579;
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	background-color: #FFFFC6;
}

.message p {
	font-size: 1.3em;
	margin: 10px;
}

-->
</style>

</head>
<body>

	<div class="site">

		<h1>FluxBB Forum Converter</h1>

		<div class="message">
			<p>Welcome to the FluxBB Forum Converter! You can use this simple script to convert your forum to a FluxBB forum with just one click - and take all the data with you!</p>
		</div>

		<form action="converter.php" method="post">
			<fieldset>
				<legend>General options</legend>

				<div class="fset">
					<label>Base URL</label>
					<input type="text" name="base_url" />
					<span>The URL (Uniform Resource Locator) where your forum can be found.</span>
				</div>
			</fieldset>

			<fieldset>
				<legend>Old forum</legend>

				<div class="fset">
					<label>Forum software</label>
					<select name="convert_to">
<?php

	foreach ($forums as $key => $name)
		echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$name.'</option>'."\n";

?>
					</select>
					<span>Choose the forum software you want to convert from. If you cannot find the exact version, the conversion might still be possible with just a minor version number change.</span>
				</div>

				<div class="fset">
					<label>Database type</label>
					<select name="old_type">
<?php

	foreach ($engines as $name)
		echo "\t\t\t\t\t\t".'<option value="'.$name.'">'.$name.'</option>'."\n";

?>
					</select>
				</div>

				<div class="fset">
					<label>Database host</label>
					<input type="text" name="old_host" />
				</div>

				<div class="fset">
					<label>Database name</label>
					<input type="text" name="old_name" />
				</div>

				<div class="fset">
					<label>Database user</label>
					<input type="text" name="old_user" />
				</div>

				<div class="fset">
					<label>Database password</label>
					<input type="text" name="old_pass" />
				</div>

				<div class="fset">
					<label>Database table prefix</label>
					<input type="text" name="old_prefix" />
				</div>
			</fieldset>

			<fieldset>
				<legend>New forum</legend>

				<div class="fset">
					<label>Database type</label>
					<select name="new_type">
<?php

	foreach ($engines as $name)
		echo "\t\t\t\t\t\t".'<option value="'.$name.'">'.$name.'</option>'."\n";

?>
					</select>
				</div>

				<div class="fset">
					<label>Database host</label>
					<input type="text" name="new_host" />
				</div>

				<div class="fset">
					<label>Database name</label>
					<input type="text" name="new_name" />
				</div>

				<div class="fset">
					<label>Database user</label>
					<input type="text" name="new_user" />
				</div>

				<div class="fset">
					<label>Database password</label>
					<input type="text" name="new_pass" />
				</div>

				<div class="fset">
					<label>Database table prefix</label>
					<input type="text" name="new_prefix" />
				</div>

				<div class="fset">
					<label>Forum language</label>
					<select name="new_language">
<?php

	foreach ($languages as $name)
		echo "\t\t\t\t\t\t".'<option value="'.$name.'"'.(($name == 'English') ? ' selected="selected"' : '').'>'.$name.'</option>'."\n";

?>
					</select>
					<span>Choose the language that will be used as default on the new forum. This will also be set as the new language for every user.</span>
				</div>

				<div class="fset">
					<label>Forum style</label>
					<select name="new_style">
<?php

	foreach ($styles as $name)
		echo "\t\t\t\t\t\t".'<option value="'.$name.'"'.(($name == 'Oxygen') ? ' selected="selected"' : '').'>'.$name.'</option>'."\n";

?>
					</select>
					<span>Choose the style that will be used as default on the new forum. This will also be set as the new style for every user.</span>
				</div>
			</fieldset>

			<input type="submit" name="submit" class="submit" value="Convert!" />
		</form>
	</div>

</body>
</html>
<?php

}
