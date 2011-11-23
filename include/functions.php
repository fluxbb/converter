<?php

//function error($message, $file = __FILE__, $line = __LINE__, $db_error = false)
//{
//	exit($message."\n".'<br />'.($db_error ? $db_error['error_msg'] : ''));
//}

function conv_message()
{
	global $lang_convert;

	$args = func_get_args();

	if (isset($lang_convert[$args[0]]))
		$args[0] = $lang_convert[$args[0]];

	$message = count($args) > 0 ? array_shift($args) : '';

	echo vsprintf($message, $args)."\n".'<br />';
}

function conv_redirect($stage, $start_at = 0, $time = 0)
{
	global $lang_convert, $default_style;

	$contents = ob_get_clean();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="<?php echo $time ?>; url=index.php?stage=<?php echo htmlspecialchars($stage).($start_at > 0 ? '&start_at='.$start_at : '') ?>">
<title><?php echo $lang_convert['FluxBB converter'] ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div class="blockform">
	<h2><span><?php echo $lang_convert['Converting header'] ?></span></h2>
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

			// To have a nice name to display, we replace the underscores with a space
			$name = str_replace('_', ' ', $entry);
			// and spaces in version number with dots
			if (preg_match('%\s([0-9\s]+)$%', $name, $matches))
				$name = substr($name, 0, strpos($name, $matches[0])).' '.str_replace(' ', '.', $matches[1]);

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

function converter_list_langs()
{
	$langs = array();

	$d = dir(SCRIPT_ROOT.'lang');
	while ($entry = $d->read())
	{
		if ($entry != '.' && $entry != '..' && is_dir(SCRIPT_ROOT.'lang/'.$entry) && file_exists(SCRIPT_ROOT.'lang/'.$entry.'/convert.php'))
			$langs[] = $entry;
	}
	asort($langs);

	return $langs;
}


function alert_dupe_users()
{
	global $pun_config;

	require PUN_ROOT.'include/email.php';

	foreach ($_SESSION['converter']['dupe_users'] as $cur_user)
	{
		// Email the user alerting them of the change
		if (file_exists(PUN_ROOT.'lang/'.$cur_user['language'].'/mail_templates/rename.tpl'))
			$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$cur_user['language'].'/mail_templates/rename.tpl'));
		else if (file_exists(PUN_ROOT.'lang/'.$pun_config['o_default_lang'].'/mail_templates/rename.tpl'))
			$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_config['o_default_lang'].'/mail_templates/rename.tpl'));
		else
			$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/English/mail_templates/rename.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<board_title>', $pun_config['o_board_title'], $mail_subject);
		$mail_message = str_replace('<base_url>', get_base_url().'/', $mail_message);
		$mail_message = str_replace('<old_username>', $cur_user['username'], $mail_message);
		$mail_message = str_replace('<new_username>', $cur_user['new_username'], $mail_message);
		$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

		pun_mail($cur_user['email'], $mail_subject, $mail_message);
	}
}