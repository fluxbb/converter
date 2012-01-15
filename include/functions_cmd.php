<?php

/**
 * Command line based converter functions
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

	if (count($args) && $args[0] == 'Processing range')
		$args[0] = 'Processing num';

	if (count($args) && isset($lang_convert[$args[0]]))
		$args[0] = $lang_convert[$args[0]];

	$message = count($args) > 0 ? array_shift($args) : '';

	$output = vsprintf($message, $args);
	echo $output."\n";
	conv_log($output);
}

/**
 * Shows an error
 */
function conv_error($message, $file = null, $line = null, $dberror = false)
{
	global $fluxbb, $forum;

	if (isset($fluxbb))
		$fluxbb->close_database();
	if (isset($forum))
		$forum->close_database();

	echo 'ERROR: '.$message.(defined('PUN_DEBUG') && isset($file) ? ' in '.$file.', '.$line : '')."\n";
	if (defined('PUN_DEBUG') && $dberror !== false)
		echo 'Database reported: '.$dberror['error_msg']."\n";
	exit(0);
}


/**
 * Redirect to the next stage
 */
function conv_redirect($step, $start_at = 0, $time = 0)
{
	return false;
}
