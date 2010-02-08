<?php
/**
 * Plugin_name: Emaillist
 * Plugin_version: 0.1
 * Plugin_description: Maintain a list of email addresses
 * Plugin_author: Pär Thernström / MarsApril AB <par@marsapril.se>
 */


/*
http://localhost/polarbearcms/polarbear-plugins/forms.php?pb_plugin_action=show_gui
*/


// Load PolarBear
if (defined("POLARBEAR_ROOT")) {
	require_once(POLARBEAR_ROOT . "polarbear-boot.php");
} else {
	require_once(realpath(dirname($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"]) . "/../polarbear-boot.php"));
}

// Make plugin visible in tree
if (function_exists("pb_plugin_add_to_tree")) {
	pb_plugin_add_to_tree(array("name" => "Email lists", "icon" => "sökväg till icon", "filename" => "emaillist.php"));
}	

$pb_plugin_action = $_REQUEST["pb_plugin_action"];

// xxx
if ($pb_plugin_action) {
	var_dump($pb_plugin_action);
}

$pb_plugin_form_edit_id = (int) $_REQUEST["pb_plugin_form_edit_id"];
$pb_plugin_form_view_id = (int) $_REQUEST["pb_plugin_form_view_id"];
$plugin_form_file = "emaillist.php";

// Add shortcodes and your own functions here
function plugin_emaillist_shortcode($options) {
    
	$defaults = array(
		"id" => null
	);
	$options = polarbear_extend($defaults, $options);
	//return "arge1 = {$options["arg1"]}, arg2 = $options[arg2]";
	return $out;
}
pb_add_shortcode('plugin_emaillist', 'plugin_emaillist_shortcode');



/**
 * show gui if we're coming through tree
 * You may want to show this in some other situations also
 */
if ($pb_plugin_action) {
	$skip_layout = true;
	require_once(POLARBEAR_ROOT . "/includes/php/admin-header.php");
	?>
	<style type="text/css">
	

	</style>

	<div class="ui-layout-west">
		<div>
			<h1>
				Email lists
			</h1>
		</div>
		
		<div>
			<p><a href="<?php echo $plugin_form_file ?>?pb_plugin_action=edit">+ Add</a></p>	
			
			<ul>
				<li>Meep</li>
			</ul>
			
			
		</div>
	</div>
	
	
		<div class="ui-layout-center">
					
			<div class="ui-layout-content">
				abc
			</div>	

		</div>

	<?php
	require_once(POLARBEAR_ROOT . "/includes/php/admin-footer.php");

}



?>