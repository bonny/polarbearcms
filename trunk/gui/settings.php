<?php
/**
 * hanterar artiklar
 */
$page_class = "polarbear-page-settings";
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();

$skip_layout = true;
// require_once("includes/admin-header.php");

// save = unset "action" and then just add everything
if ($_POST["action"] == "settingsSave") {
	//http://se.php.net/manual/en/function.serialize.php#76056
	unset($_POST["action"]);
	$values = $_POST;
	$values = $polarbear_db->escape(addslashes(serialize($values))); // feels veeery wierd with that addslashes there... doesn't work without it however
	$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_settings SET settings = '$values', date = now()";
	$polarbear_db->query($sql);
	$pageToLoad = urlencode(POLARBEAR_WEBPATH . "gui/settings.php?settingsSaved=1");

	pb_event_fire("pb_settings_general_saved");
	
	header("Location: " . POLARBEAR_WEBPATH . "?treepage=$pageToLoad");
	exit;
}

$settings = polarbear_getGlobalSettings();

// Required settings; stuff that needs to be set
$arrDefaultSettings = array(
	"storagepath" => array("fieldType"=>"singleline", "description" => ""),
	"imagemagickpath" => array("fieldType"=>"singleline", "description" => ""),
	"usemodrewrite" => array("fieldType"=>"bool", "description" => ""),
	"templates" => array("fieldType"=>"multiline", "description" => ""),
	"article404" => array("fieldType"=>"singleline", "description" => "ID of article to use as 404"),
	"GoogleAnalyticsEmail" => array("fieldType"=>"singleline", "description" => ""),
	"GoogleAnalyticsPassword" => array("fieldType"=>"password", "description" => ""),
	"GoogleAnalyticsReportID" => array("fieldType"=>"singleline", "description" => "")
);

?>

<div class="polarbear-page-settings">
	
	<script type="text/javascript">
		/**
		 * page "Settings"
	 	 */
		$(function() {
			$(".polarbear-page-settings .settings-link-add").click(function() {
				var keyName = prompt("Name of new setting", "");
				if (keyName) {
					$(".polarbear-page-settings .settings-list").append("<li style='display: none;'>" + keyName + " <a href='#'>Remove</a><br /><textarea name='" + keyName + "' value=''></textarea></li>");
					$(".polarbear-page-settings .settings-list li:last").show("slow");
				}
			});
			$(".polarbear-page-settings .settings-list").sortable({});
			$(".polarbear-page-settings .settings-list").click(function(e, t) {
				if (e.target.tagName == "A") {
					if (confirm("Delete?")) {
						$(e.target).closest("li").hide("slow", function() {
							$(this).remove();
						});
						
					}
				}
			});
		});
	</script>
	
	<form method="post" action="gui/settings.php" class="polarbear-page-settings-frm">
		<h1>Settings</h1>

		<?php
		if ($_GET["settingsSaved"]) {
			?>
			<div class="ui-widget">
				<div style="padding: 0pt 0.7em; margin-top: 20px;" class="ui-state-highlight ui-corner-all">
					<p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>
					Settings saved
					</p>
				</div>
			</div>
		<?php
		}
		?>

		<ul class="settings-list-required">
		<?php
			foreach ($arrDefaultSettings as $key => $val) {
				?>
				<li class="<?php echo $key ?>">
					<?php echo htmlspecialchars ($key, ENT_COMPAT, "UTF-8") ?>
					<?php
					if (!empty($val["description"])) {
						echo " - " . $val["description"];
					}
					?>
					<br />
					<?php
					$fieldType = $val["fieldType"];
					if ($fieldType == "singleline") {
						?><input type="text" class="ui-widget-content ui-corner-all text" name="<?php echo $key ?>" value="<?php echo htmlspecialchars ($settings[$key], ENT_COMPAT, "UTF-8") ?>" /><?php
					} elseif ($fieldType == "password") {
						?><input type="password" class="ui-widget-content ui-corner-all text" name="<?php echo $key ?>" value="<?php echo htmlspecialchars ($settings[$key], ENT_COMPAT, "UTF-8") ?>" /><?php
					} elseif ($fieldType == "bool") {
						$boolval = strtolower($settings[$key]);
						if ($boolval == "true" || $boolval == "1" || $boolval == "yes") {
							$boolval = true;
						} else {
							$boolval = false;
						}
						?>
						<input name="<?php echo $key ?>" type="radio" value="true" <?php if($boolval == true) { echo 'checked="checked"'; } ?> /> Yes
						<br />
						<input name="<?php echo $key ?>" type="radio" value="false" <?php if($boolval == false) { echo 'checked="checked"'; } ?> /> No
						<?php
					} else {
						?><textarea class="ui-widget-content ui-corner-all" name="<?php echo $key ?>"><?php echo htmlspecialchars ($settings[$key], ENT_COMPAT, "UTF-8") ?></textarea><?php
					}
					?>
				</li>
				<?php
			}
		?>
		</ul>
		<ul class="settings-list">
			<?php
			foreach ($settings as $key => $val) {
				// if a value that is not among the required
				if (!array_key_exists($key, $arrDefaultSettings)) {
					?>
					<li>
						<?php echo htmlspecialchars ($key, ENT_COMPAT, "UTF-8") ?>
						<a href="#">Remove</a>
						<br />
						<textarea name="<?php echo $key ?>"><?php echo htmlspecialchars ($settings[$key], ENT_COMPAT, "UTF-8") ?></textarea>
					</li>
					<?php
				}
			}
			?>
		</ul>
		
		<p>
			<a href="#" class="settings-link-add">Add</a>
		</p>
	
		<p>
			<input type="submit" value="Save" />
			<input type="hidden" name="action" value="settingsSave" />
		</p>

	</form>
	
</div>

<?php
// require ("includes/admin-footer.php");
?>