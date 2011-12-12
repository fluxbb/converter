<?php

/**
 * Command line based converter functions
 *
 * Copyright (C) 2011 FluxBB (http://fluxbb.org)
 * License: LGPL - GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 */

/**
 * Shows an info about current running conversion process
 */
function conv_message()
{
	global $lang_convert;

	$args = func_get_args();

	// TODO: what when we have 3 arguments?
	if (substr($args[0], 0, 10) == 'Processing')
		$args[0] = 'Processing '.(count($args) - 1);

	if (isset($lang_convert[$args[0]]))
		$args[0] = $lang_convert[$args[0]];

	$message = count($args) > 0 ? array_shift($args) : '';

	echo vsprintf($message, $args)."\n";
}

/**
 * Shows an error
 */
function conv_error($message, $file = null, $line = null, $dberror = false)
{
	global $fluxbb;

	if (isset($fluxbb))
		$fluxbb->close_database();

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
	global $lang_convert, $default_style, $fluxbb;

	if (isset($fluxbb))
		$fluxbb->close_database();

	echo $contents."\n";
}