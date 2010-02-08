<?php
/**
 * Little "bootstrap" function go load a plugin, since the plugins are located outside the polarbear cms-folder
 */
require_once("../../polarbear-boot.php");
$pluginFilename = $_GET["pluginFilename"];

$pb_plugin_action = "show_gui";

?>
<iframe style="width: 100%; height: 100%; xwidth: 98%; xheight: 98%; xpadding: 1%" xclass="ui-layout-content" src="<?php echo POLARBEAR_PLUGINS_WEBPATH . $pluginFilename ?>?pb_plugin_action=show_gui"></iframe>