<?php

//function error($message, $file = __FILE__, $line = __LINE__, $db_error = false)
//{
//	exit($message."\n".'<br />'.($db_error ? $db_error['error_msg'] : ''));
//}

function conv_message()
{
	global $lang_convert;

	$args = func_get_args();

	if (substr($args[0], 0, 10) == 'Processing')
		$args[0] = 'Processing '.(count($args) - 1);

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
<title><?php echo sprintf($lang_convert['FluxBB converter'], CONVERTER_VERSION) ?></title>
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
		$mail_message = str_replace('<old_username>', $cur_user['old_username'], $mail_message);
		$mail_message = str_replace('<new_username>', $cur_user['username'], $mail_message);
		$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'], $mail_message);

		pun_mail($cur_user['email'], $mail_subject, $mail_message);
	}
}


//
// Determines whether $str is UTF-8 encoded or not
//
function seems_utf8($str)
{
	$str_len = strlen($str);
	for ($i = 0; $i < $str_len; ++$i)
	{
		if (ord($str[$i]) < 0x80) continue; # 0bbbbbbb
		else if ((ord($str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
		else if ((ord($str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
		else if ((ord($str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
		else if ((ord($str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
		else if ((ord($str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
		else return false; # Does not match any model

		for ($j = 0; $j < $n; ++$j) # n bytes matching 10bbbbbb follow ?
		{
			if ((++$i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80))
				return false;
		}
	}

	return true;
}


//
// Translates the number from a HTML numeric entity into an UTF-8 character
//
function dcr2utf8($src)
{
	$dest = '';
	if ($src < 0)
		return false;
	else if ($src <= 0x007f)
		$dest .= chr($src);
	else if ($src <= 0x07ff)
	{
		$dest .= chr(0xc0 | ($src >> 6));
		$dest .= chr(0x80 | ($src & 0x003f));
	}
	else if ($src == 0xFEFF)
	{
		// nop -- zap the BOM
	}
	else if ($src >= 0xD800 && $src <= 0xDFFF)
	{
		// found a surrogate
		return false;
	}
	else if ($src <= 0xffff)
	{
		$dest .= chr(0xe0 | ($src >> 12));
		$dest .= chr(0x80 | (($src >> 6) & 0x003f));
		$dest .= chr(0x80 | ($src & 0x003f));
	}
	else if ($src <= 0x10ffff)
	{
		$dest .= chr(0xf0 | ($src >> 18));
		$dest .= chr(0x80 | (($src >> 12) & 0x3f));
		$dest .= chr(0x80 | (($src >> 6) & 0x3f));
		$dest .= chr(0x80 | ($src & 0x3f));
	}
	else
	{
		// out of range
		return false;
	}

	return $dest;
}


//
// Attempts to convert $str from $old_charset to UTF-8. Also converts HTML entities (including numeric entities) to UTF-8 characters
//
function convert_to_utf8(&$str, $old_charset)
{
	if ($str === null || $str == '')
		return false;

	$save = $str;

	// Replace literal entities (for non-UTF-8 compliant html_entity_encode)
	if (version_compare(PHP_VERSION, '5.0.0', '<') && $old_charset == 'ISO-8859-1' || $old_charset == 'ISO-8859-15')
		$str = html_entity_decode($str, ENT_QUOTES, $old_charset);

	if ($old_charset != 'UTF-8' && !seems_utf8($str))
	{
		if (function_exists('iconv'))
			$str = iconv($old_charset == 'ISO-8859-1' ? 'WINDOWS-1252' : 'ISO-8859-1', 'UTF-8', $str);
		else if (function_exists('mb_convert_encoding'))
			$str = mb_convert_encoding($str, 'UTF-8', $old_charset == 'ISO-8859-1' ? 'WINDOWS-1252' : 'ISO-8859-1');
		else if ($old_charset == 'ISO-8859-1')
			$str = utf8_encode($str);
	}

	// Replace literal entities (for UTF-8 compliant html_entity_encode)
	if (version_compare(PHP_VERSION, '5.0.0', '>='))
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

	// Replace numeric entities
	$str = preg_replace_callback('%&#([0-9]+);%', 'utf8_callback_1', $str);
	$str = preg_replace_callback('%&#x([a-f0-9]+);%i', 'utf8_callback_2', $str);

	// Remove "bad" characters
	$str = remove_bad_characters($str);

	return ($save != $str);
}


function utf8_callback_1($matches)
{
	return dcr2utf8($matches[1]);
}


function utf8_callback_2($matches)
{
	return dcr2utf8(hexdec($matches[1]));
}
