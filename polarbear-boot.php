<?php
/**
 * Denna fil är enda filen som vi får "göra" saker, dvs. instansiera klasser etc. för att boota upp PolarBear.
 * Alla andra filer får bara innehålla funktioner och klassen, men ingen kod som körs utan att klasser 
 * etc. har instansierats eller anropats.
 */
$polarbear_render_start_ms = microtime(true);

define('POLARBEAR_VERSION', 0.1);

// setup some paths
define('POLARBEAR_DOC_ROOT', realpath(dirname( __FILE__ ).'/../')); // absolute path to the root of the webfolder
define('POLARBEAR_ROOT', realpath(dirname( __FILE__ ).'/')); // absolute path the directory where our CMS is
define('POLARBEAR_WEBPATH', '/' . basename(dirname(__FILE__)) . '/'); // Path to polarbear, for webbrowser

require_once(POLARBEAR_ROOT . '/includes/php/functions.php');
require_once(POLARBEAR_ROOT . '/polarbear-config.php');
require_once(POLARBEAR_ROOT . '/includes/php/locale.php');
require_once(POLARBEAR_ROOT . '/includes/dwoo/dwooAutoload.php');
require_once(POLARBEAR_ROOT . '/includes/utf8/utf8.php');

polarbear_boot();
?>