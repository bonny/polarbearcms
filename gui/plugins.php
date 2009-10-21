<?php
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();

// start performing actions based on $post here
// ...
// stop performing actions based on $post

pb_must_come_through_tree();
pb_plugins_cleanup();

$action = $_REQUEST["action"];
$okmsg = null;
$errmsg = null;

if ($action == "pluginEnable" || $action == "pluginDisable") {
	$pluginFilename = $_GET["pluginFilename"];
	$plugin = new pb_plugin;
	$plugin->loadByFilename($pluginFilename);
	if ($action == "pluginEnable") {
		$plugin->enable();
		$okmsg = "Enabled plugin \"{$plugin->name}\"";
	} elseif ($action == "pluginDisable") {
		$plugin->disable();
		$okmsg = "Disabled plugin \"{$plugin->name}\"";
	}
}





?>
<div class=polarbear-content-main-inner>

	
	<?php
	polarbear_infomsg($okmsg, $errmsg);
	?>

	<h1>Plugins</h1>

	<?php

	// if plugins path is false, the directory does not exist
	if (POLARBEAR_PLUGINS_PATH == false) {
		// no plugin dir
		?>
		<p class="errmsg">Error: no plugins directory found. Please make sure directory "polarbear-plugins" exists.</p>
		<?php
	} else {
		// exists, look up files
		$dir = opendir(POLARBEAR_PLUGINS_PATH);
		$arrPlugins = array();
		while (false !== ($file = readdir($dir))) {
			if ($file != "." && $file != "..") {
				
				$plugin = new pb_plugin;
				if ($plugin->loadByFilename($file)) {
					$arrPlugins[] = $plugin;
				}
	
			}
		}

		if (empty($arrPlugins)) {
			?><p class="errmsg">No plugins found</p><?php
		} else {
			?>
				<?php
				foreach ($arrPlugins as $onePlugin) {
					$enabled = $onePlugin->is_enabled();
					if ($enabled) {
						$img = "./images/silkicons/plugin.png";
					} else {
						$img = "./images/silkicons/plugin_disabled.png";
					}
					?>
					<h2>
						<img src="<?php echo $img ?>" alt="" /> <?php echo $onePlugin->name ?>)
						
					</h2>
					<p>
						Author: <?php echo htmlspecialchars($onePlugin->author) ?>
						<br />Version <?php echo $onePlugin->version ?>
					</p>
					<p><?php echo htmlspecialchars($onePlugin->description) ?></p>
					<p>
						<?php
						if ($enabled) {
							?>Plugin is enabled. <a href="gui/plugins.php?action=pluginDisable&pluginFilename=<?php echo $onePlugin->filename ?>">Disable</a><?php
						} else {
							?>Plugin is disabled. <a href="gui/plugins.php?action=pluginEnable&pluginFilename=<?php echo $onePlugin->filename ?>">Enable.</a><?php
						}
						?>
					</p>
					<?php
					
				}
				?>
			<?php
		}
		
	}


?>

</div>