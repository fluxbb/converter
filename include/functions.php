<?php

//function error($message, $file = __FILE__, $line = __LINE__, $db_error = false)
//{
//	exit($message."\n".'<br />'.($db_error ? $db_error['error_msg'] : ''));
//}

function conv_message()
{
	$args = func_get_args();
	$message = count($args) > 0 ? array_shift($args) : '';

	echo vsprintf($message, $args)."\n".'<br />';
}

function conv_redirect($stage, $start_at = 0, $time = 0)
{
	global $lang_install, $lang_convert, $default_style;

	$contents = ob_get_clean();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="<?php echo $time ?>; url=index.php?stage=<?php echo htmlspecialchars($stage).($start_at > 0 ? '&start_at='.$start_at : '') ?>">
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div class="blockform">
	<h2><span><?php echo $lang_convert['Converting'] ?></span></h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
				<div class="forminfo">
					<?php echo $contents ?>
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
	exit;

}

//function get_microtime()
//{
//	list($usec, $sec) = explode(' ', microtime());
//	return ((float)$usec + (float)$sec);
//}

function connect_database($db_config)
{
	$class = $db_config['type'].'_wrapper';

	if (!class_exists($class))
	{
		if (!file_exists(SCRIPT_ROOT.'include/dblayer/'.$db_config['type'].'.php'))
			error('Unsupported database type: '.$db_config['type'], __FILE__, __LINE__);

		require SCRIPT_ROOT.'include/dblayer/'.$db_config['type'].'.php';
	}

	return new $class($db_config['host'], $db_config['username'], $db_config['password'], $db_config['name'], $db_config['prefix'], false);
}

function load_forum($forum_type, $db, $fluxbb)
{
	if (!class_exists($forum_type))
	{
		if (!file_exists(SCRIPT_ROOT.'forums/'.$forum_type.'.php'))
			error('Unsupported forum type: '.$forum_type, __FILE__, __LINE__);

		require SCRIPT_ROOT.'forums/'.$forum_type.'.php';
	}

	return new $forum_type($db, $fluxbb);
}

// Get all forum softwares
function forum_list_forums()
{
	$forums = array();

	$d = dir(SCRIPT_ROOT.'forums');
	while ($entry = $d->read())
	{
		if (substr($entry, -4) == '.php')
		{
			$entry = substr($entry, 0, -4);
			// To have a nice name to display, we replace the first underscore with a space and the rest with dots (version number)
			$name = preg_replace('/_/', ' ', $entry, 1);
			$name = str_replace('_', '.', $name);

			$forums[$entry] = $name;
		}
	}
	asort($forums);

	return $forums;
}

// Get all database engines
function forum_list_engines()
{
	$engines = array();

	$d = dir(SCRIPT_ROOT.'include/dblayer');
	while ($entry = $d->read())
	{
		if (substr($entry, -4) == '.php')
			$engines[] = substr($entry, 0, -4);
	}
	asort($engines);

	return $engines;
}
