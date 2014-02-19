# FluxBB converter ![Build status](https://secure.travis-ci.org/fluxbb/converter.png?branch=master)

This is the FluxBB converter tool. Please note that this is currently a pre-beta.

## Supported forums
- IP.Board 3.2
- miniBB 3.0
- MyBB 1
- PHP Fusion 7
- phpBB 3.0
- SMF 1.1
- SMF 2
- vBulletin 4.1
- Merge FluxBB - merge two fluxbb installations into one database. Categories, forums, topics and posts are added to the current database and the users are merged from the both installations.

**Note**: Because FluxBB is a fork of PunBB you are able to convert from PunBB 1.2, 1.3 and 1.4 by following the [upgrading process](http://fluxbb.org/downloads/upgrade.html).

## Notes
- All FluxBB tables are dumped (not applicable to Merge FluxBB converter)
- MAKE BACKUP BEFORE USING THIS CONVERTER!

## Installation instructions
- Install FluxBB v1.5.6 on your server (example: http://example.com/fluxbb/).
- Create a subdirectory in the FluxBB directory (example name: "converter").
- Put all files from the converter zipfile in that directory.
- Go to the converter page (http://example.com/fluxbb/converter/).
- Choose which forum software you want to convert from.
- Enter database connection information
- Press 'Start converter'.
- When the converter is done, remove the converter files
- Done! You're now ready to use FluxBB!

## Running from command line

	php converter.php -f forum_name -t old_db_type -s old_db_host -n old_db_name -u old_db_username -p old_db_password -r old_db_prefix -c old_db_charset

Type: ``php converter.php --help`` to see available parameters
