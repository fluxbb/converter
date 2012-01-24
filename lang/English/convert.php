<?php

// Language definitions used in install.php

return array(
'Bad request'					=>	'Bad request. The link you followed is incorrect or outdated.',
'You are running error'			=>	'You are running %1$s version %2$s. FluxBB converter %3$s requires at least %1$s %4$s to run properly. You must upgrade your %1$s installation before you can continue.',
'Bad request'					=>	'Bad request. The link you followed is incorrect or outdated.',
'Not installed'					=>	'FluxBB was not installed.',

'Choose convert language'		=>	'Choose the convert script language',
'Choose convert language info'	=>	'The language used for this convert script.',
'Convert language'				=>	'Convert language',
'Change language'				=>	'Change language',

'FluxBB converter'				=>	'FluxBB converter %s',
'Convert message'				=>	'Welcome to the FluxBB Forum Converter! You can use this simple script to convert your forum to a FluxBB forum with just one click - and take all the data with you!',
'Note'							=>	'Note',
'Note info'			=>	'The converter is not used for upgrading forums. In addition, please make sure all modifications or plugins that may interefere with the conversion process are deactivated on your old forum, before you run the converter. It is also strongly recommended to make a backup of both forums before you continue.',
'Convert'						=>	'Convert',
'Convert info 1'				=>	'Convert from other forum software',
'Select software'				=>	'Select forum software',
'Convert info 2'				=>	'Choose the forum software you want to convert from. If you cannot find the exact version, the conversion might still be possible with just a minor version number change.',
'Forum software'				=>	'Forum software',
'Enter old forum path'			=>	'Enter old forum path',
'Old forum path info'			=>	'Type the absolute (or relative to the FluxBB directory) path to the old forum. When you uploaded FluxBB to the subdirectory of the old forum folder, enter ".." (without quotes). It is needed for the avatar conversion. Leave empty if you do not want to convert avatars.',
'Old forum path'				=>	'Old forum path',
'Select old database'			=>	'Select old database',
'Convert info 3'				=>	'Enter your old database parameters',
'Database type'					=>	'Database type',
'Required'						=>	'(Required)',
'Required field'				=>	'is a required field in this form.',
'Database server hostname'		=>	'Database server hostname',
'Database name'					=>	'Database name',
'Database username'				=>	'Database username',
'Database password'				=>	'Database password',
'Database charset'				=>	'Database charset',
'Database charset info'			=>	'(no need to change default value for most forums)',
'Table prefix'					=>	'Table prefix',

'Start converter'				=>	'Start converter',
'Converting header'				=>	'Converting',
'Conversion completed in'		=>	'Conversion completed in %s',

'Invalid forum software'		=>	'You entered an invalid forum software',
'Invalid database type'			=>	'Database type for old forum is invalid.',
'Same database tables'			=>	'Old and new tables must be different!',

// Processing message (with number of arguments)
'Processing'					=>	'Processing %s',
'Processing num'				=>	'Processing %2$d %1$s',
'Processing range'				=>	'Processing %2$d %1$s (%3$d - %4$d)',

'Not implemented'				=>	'%s: Not implemented',
'Converting'					=>	'Converting %s',
'Done in'						=>	'Done in %s',

// Username dupes
'Error info 1'					=>	'There was an error when converting some users. This can occur when converting from PunBB v1.3 if multiple users have registered with very similar usernames, for example "bob" and "böb".',
'Error info 2'					=>	'Below is a list of users who failed to convert.',
'Click alert button'			=>	'Clicking Alert users button will send an email alerting them of the change.',
'Convert username dupes question'=>	'Alert users by email about the username change? (yes/no).',
'Username dupes head'			=>	'Username dupes',
'Username dupes'				=>	'Username dupes',
'was renamed to'				=>	'%s was renamed to %s',
'Alert users'					=>	'Alert users',

'Final instructions'			=>	'Final instructions',
'Rebuild search index note'		=>	'Don\'t forget to rebuild the search index!',
'Password converter mod'		=>	'This converter does not support converting passwords for specified forum. You have to install Password Converter Mod (file password_converter_mod.txt in converter directory) or you will not be able to login!',
'Database converted'			=>	'Your forum has been successfully converted! You may now %s.',
'go to forum index'				=>	'go to the forum index',

// Command line based
'Possible values'				=>	'Possible values are: %s',
'Default value'					=>	'(default: %s)',
'Usage'							=>	'Usage',
'Notes'							=>	'NOTES',
'Error'							=>	'ERROR: %s',
'Error file line'				=>	'in %s, line %s',
'Database reported'				=>	'Database reported: %s',
);
