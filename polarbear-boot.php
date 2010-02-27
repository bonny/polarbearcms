<?php
/**
 * Denna fil är enda filen som vi får "göra" saker, dvs. instansiera klasser etc. för att boota upp PolarBear.
 * Alla andra filer får bara innehålla funktioner och klassen, men ingen kod som körs utan att klasser 
 * etc. har instansierats eller anropats.
 */
$polarbear_render_start_ms = microtime(true);

define('POLARBEAR_VERSION', 0.2);

// setup some paths
define('POLARBEAR_DOC_ROOT', realpath(dirname( __FILE__ ).'/../')); // absolute path to the root of the webfolder
define('POLARBEAR_ROOT', (realpath(dirname( __FILE__ ).'/')) . "/"); // absolute path the directory where our CMS is
define('POLARBEAR_WEBPATH', '/' . basename(dirname(__FILE__)) . '/'); // Path to polarbear, for webbrowser
define("POLARBEAR_PLUGINS_PATH", realpath(POLARBEAR_ROOT . "/polarbear-plugins/"). "/");
define("POLARBEAR_PLUGINS_WEBPATH", POLARBEAR_WEBPATH . "polarbear-plugins/");

require_once(POLARBEAR_ROOT . '/includes/php/functions.php');

// php quick profiler, as soon as possible
require_once(POLARBEAR_ROOT . '/includes/pqp/classes/PhpQuickProfiler.php');
pb_event_attach("pb_boot_start", "pb_pqp_start");
pb_event_attach("pb_page_contents", "pb_pqp_display");

require_once(POLARBEAR_ROOT . '/polarbear-config.php');
require_once(POLARBEAR_ROOT . '/includes/php/locale.php');
require_once(POLARBEAR_ROOT . '/includes/dwoo/dwooAutoload.php');
require_once(POLARBEAR_ROOT . '/includes/utf8/utf8.php');
require_once(POLARBEAR_ROOT . '/includes/php/shortcodes.php');

spl_autoload_register('polarbear_class_autoload');

// attach stuff for the log/recent activites-functionality
pb_event_attach("pb_article_saved", "pb_log");
pb_event_attach("pb_article_deleted", "pb_log");
pb_event_attach("pb_user_saved", "pb_log");
pb_event_attach("pb_user_deleted", "pb_log");
pb_event_attach("pb_file_saved", "pb_log");
pb_event_attach("pb_file_deleted", "pb_log");
pb_add_shortcode("pb_emaillist", "pb_emaillist_shortcode");

polarbear_boot();
?>