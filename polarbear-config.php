<?php
/**
 * Denna fil är den enda man ska behöva ändra i för att Maj ska fungera på en sajt.
 */

// Database settings
define("POLARBEAR_DB_SERVER", "localhost");
define("POLARBEAR_DB_USER", "root");
define("POLARBEAR_DB_PASSWORD", "root");
define("POLARBEAR_DB_DATABASE", "maj");
define("POLARBEAR_DB_PREFIX", "polarbear");

// Misc
define("POLARBEAR_SALT", ""); // no salt since prev. mamba-installation did not have any salt in the passwords

error_reporting(E_ALL  & ~E_NOTICE);
ini_set('display_errors', '1'); // just for some debuging

// extras, only set if necessary
// define("POLARBEAR_PASSWORD_HASHTYPE", "MD5"); // default sha1

// To be able to attach events to for example file downloads include a functions-file here
// include_once($_SERVER["DOCUMENT_ROOT"] . "/2.0/inc/functions.php");

?>