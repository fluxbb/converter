<?php

/**
 * Web based converter functions
 *
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

/**
 * Shows an info about current running conversion process
 */
function conv_message()
{
	global $lang_convert;

	$args = func_get_args();

	conv_log($args[0].': '.implode(', ', array_slice($args, 1)));

	// Translate message
	if (count($args) && isset($lang_convert[$args[0]]))
		$args[0] = $lang_convert[$args[0]];

	$message = count($args) > 0 ? array_shift($args) : '';

	$output = vsprintf($message, $args);
	echo $output."\n".'<br />';
}


/**
 * Shows an error
 */
function conv_error($message, $file = null, $line = null, $dberror = false)
{
	global $fluxbb, $forum, $lang_convert;

	unset($_SESSION);
	session_destroy();

	if (isset($fluxbb))
		$fluxbb->close_database();
	if (isset($forum))
		$forum->close_database();

	conv_log('Error: '.$message.' in '.$line.', '.$line.(is_array($dberror) ? ' '.implode(', ', $dberror) : ''), false, true);

	if (isset($lang_convert[$message]))
		$message = $lang_convert[$message];

	error($message, $file, $line, $dberror);
}


/**
 * Redirect to the next stage
 */
function conv_redirect($step, $start_at = 0, $time = 0)
{
	global $lang_convert, $default_style, $fluxbb, $forum;

	if (isset($fluxbb))
		$fluxbb->close_database();
	if (isset($forum))
		$forum->close_database();

	$contents = ob_get_clean();

	$url = 'index.php?step='.htmlspecialchars($step).($start_at > 0 ? '&start_at='.$start_at : '');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="<?php echo $time ?>; url=<?php echo $url ?>">
<title><?php echo sprintf($lang_convert['FluxBB converter'], CONV_VERSION) ?></title>
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

	conv_log('Redirect');
	conv_log();
	conv_log('-----------------');
	conv_log('', false, true);
	exit;
}
