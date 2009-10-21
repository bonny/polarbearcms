<?php
/**
 * Little "bootstrap" function go load a plugin, since the plugins are located outside the polarbear cms-folder
 */

require_once("../../polarbear-boot.php");
$pluginFilename = $_GET["pluginFilename"];

$pluginFilenameFullpath = POLARBEAR_PLUGINS_PATH . $pluginFilename;

if (empty($pluginFilename) || !is_readable($pluginFilenameFullpath)) {
	polarbear_infomsg("", "Plugin was not found");
	exit;
}

$pb_plugin_action = "show_gui";
$pluginfile = POLARBEAR_WEBPATH . "../polarbear-plugins/" . $pluginFilename;
?>
<iframe style="width: 98%; height: 98%; padding: 1%" xclass="ui-layout-content" src="<?php echo $pluginfile ?>?pb_plugin_action=show_gui&pb_plugin_cmsroot=<?php echo POLARBEAR_ROOT ?>"></iframe>