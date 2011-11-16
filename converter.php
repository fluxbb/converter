<?php

define('SCRIPT_ROOT', './');

// Include the common functions
require SCRIPT_ROOT.'include/functions.php';


$forums = get_forums();
$engines = get_engines();
$languages = get_languages();
$styles = get_styles();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FluxBB Forum Converter</title>

<link rel="stylesheet" type="text/css" href="style.css" />

</head>
<body>

	<div class="site">

		<h1>FluxBB Forum Converter</h1>
<?php


if (isset($_POST['submit']))
{
	$forum_config = array(
		'type'			=> isset($_POST['convert_to']) && isset($forums[$_POST['convert_to']]) ? $_POST['convert_to'] : error('You entered an invalid forum software.'.$_POST['convert_to'], __FILE__, __LINE__),
		'base_url'		=> isset($_POST['base_url']) ? trim($_POST['base_url']) : '', // TODO: utf8_trim()? is_url()?
		'default_lang'	=> isset($_POST['new_language']) && in_array($_POST['new_language'], $languages) ? $_POST['new_language'] : 'English',
		'default_style'	=> isset($_POST['new_style']) && in_array($_POST['new_style'], $styles) ? $_POST['new_style'] : 'Air'
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
	$forum = load_forum($forum_config['type'], $old_db, $fluxbb);
	$forum->init_config($forum_config);

	// Start the conversion process
	require SCRIPT_ROOT.'include/converter.class.php';
	$converter = new Converter($forum);
	$converter->convert_all();

	// Finished! :-)
	message('--------------------------------------------------------');
	message('Conversion completed in %s seconds', number_format($converter->get_time(), 2));

	// TODO: Try to create config file, set styles etc. for everybody
}
else
{


?>

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
		echo "\t\t\t\t\t\t".'<option value="'.$name.'"'.(($name == 'Air') ? ' selected="selected"' : '').'>'.$name.'</option>'."\n";

?>
					</select>
					<span>Choose the style that will be used as default on the new forum. This will also be set as the new style for every user.</span>
				</div>
			</fieldset>

			<input type="submit" name="submit" class="submit" value="Convert!" />
		</form>

<?php

}

?>
	</div>

</body>
</html>
<?php

