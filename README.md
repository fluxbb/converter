FluxBB converter
=====================

FluxBB converter tool

Supported forums
----------------
 - Invision Power Board 3.2
 - MyBB 1
 - PHP Fusion 7
 - PhpBB 3.0
 - PunBB 1.3 and 1.4
 - SMF 1.1
 - SMF 2
 - vBulletin 4.1
 - Merge FluxBB - merge two fluxbb installations into one database. Categories, forums, topics and posts are added to the current database and the users are merged from the both installations.

Notes
-------------------
- All FluxBB tables are dumped.
- MAKE BACKUP BEFORE USING THIS CONVERTER!
- The passwords in some forums are NOT converted due to differences in password storing between the forums.

Installation instructions
-------------------
 - Install FluxBB on the server (example: "www.example.com/fluxbb/").
 - Create a subdirectory in the FluxBB directory (example name: "converter").
 - Put all files from the converter zipfile in that directory.
 - Go to the converter page ("www.example.com/fluxbb/converter/").
 - Choose which forum software you want to convert from.
 - Enter database connection information
 - Press 'Start converter'.
 - When the converter is done, remove the converter files.
 - Done! You're now ready to use FluxBB!

 Running from command line
-------------------

	php converter.php -f forum_name -t old_db_type -s old_db_host -n old_db_name -u old_db_username -p old_db_password -r old_db_prefix -c old_db_charset

Type: ``php converter.php --help`` to see available parameters
