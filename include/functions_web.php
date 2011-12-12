<?php

/**
 * Web based converter functions
 *
 * @copyright (C) 2011 FluxBB (http://fluxbb.org)
 * @license LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @package FluxBB
 */

/**
 * Shows an info about current running conversion process
 */
function conv_message()
{
	global $lang_convert;

	$args = func_get_args();

	if (count($args) && isset($lang_convert[$args[0]]))
		$args[0] = $lang_convert[$args[0]];

	$message = count($args) > 0 ? array_shift($args) : '';

	echo vsprintf($message, $args)."\n".'<br />';
}


/**
 * Shows an error
 */
function conv_error($message, $file = null, $line = null, $dberror = false)
{
	global $fluxbb;

	if (isset($fluxbb))
		$fluxbb->close_database();

	error($message, $file, $line, $dberror);
}


/**
 * Redirect to the next stage
 */
function conv_redirect($step, $start_at = 0, $time = 0)
{
	global $lang_convert, $default_style, $fluxbb;

	if (isset($fluxbb))
		$fluxbb->close_database();

	$contents = ob_get_clean();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="<?php echo $time ?>; url=index.php?step=<?php echo htmlspecialchars($step).($start_at > 0 ? '&start_at='.$start_at : '') ?>">
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