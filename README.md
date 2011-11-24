FluxBB converter
=====================

FluxBB converter tool

Supported forums
----------------
 - PHP Fusion 7
 - PhpBB 3
 - PunBB 1.3
 - SMF 1
 - SMF 2

Notes
-------------------
- All FluxBB tables are dumped.
- MAKE BACKUP BEFORE USING THIS CONVERTER!
- The passwords in some forums are NOT converted due to differences in password storing between the forums.

Installation instructions
---------
 - Install FluxBB on the server (example: "www.example.com/fluxbb/").
 - Create a subdirectory in the FluxBB directory (example name: "converter").
 - Put all files from the converter zipfile in that directory.
 - Go to the converter page ("www.example.com/fluxbb/converter/").
 - Choose which forum software you want to convert from.
 - Enter database connection information
 - Press 'Start conversion'.
 - When the converter is done, remove the converter files.
 - Done! You're now ready yo use FluxBB!