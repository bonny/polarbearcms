<?php
/**
 * Plugin_name: Forms
 * Plugin_version: 0.1
 * Plugin_description: Enables the use of forms
 * Plugin_author: Pär Thernström / MarsApril AB <par@marsapril.se>
 */

// Load PolarBear
if (defined("POLARBEAR_ROOT")) {
	require_once(POLARBEAR_ROOT . "polarbear-boot.php");
} else {
	require_once(realpath(dirname($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"]) . "/../polarbear-boot.php"));
}

// Make plugin visible in tree
if (function_exists("pb_plugin_add_to_tree")) {
	pb_plugin_add_to_tree(array("name" => "Forms", "icon" => "sökväg till icon", "filename" => "forms.php"));
}	

$pb_plugin_action = $_REQUEST["pb_plugin_action"];
$pb_plugin_form_edit_id = (int) $_REQUEST["pb_plugin_form_edit_id"];
$plugin_form_file = "forms.php";


// klass som representerar en form
if (!class_exists("plugin_form")) {
	class plugin_form {

		private $id, $name, $email, $is_active, $is_deleted, $date_created, $after_submit, $after_submit_message, $after_submit_url, $fields, $submit_button_text;
		
		function __construct() {
			$this->id = null;
			$this->is_active = true;
			$this->is_deleted = false;
			$this->date_created = time();
			$this->fields = array();
			$this->after_submit = "showMessage"; // showMessage | goToURL
			$this->submit_button_text = "Ok";
		}
		
		function id() {
			return $this->id;
		}

		function submit_button_text($newValue = null) {
			if (isset($newValue)) {
				$this->submit_button_text = $newValue;
			}
			return $this->submit_button_text;
		}

		function email($newValue = null) {
			if (isset($newValue)) {
				$this->email = $newValue;
			}
			return $this->email;
		}
		
		function name($newValue = null) {
			if (isset($newValue)) {
				$this->name = $newValue;
			}
			return $this->name;
		}

		function is_active($newValue = null) {
			if (isset($newValue)) {
				$this->is_active = (bool) $newValue;
			}
			return $this->is_active;
		}

		function is_deleted($newValue = null) {
			if (isset($newValue)) {
				$this->is_deleted = (bool) $newValue;
			}
			return $this->is_deleted;
		}

		function date_created($newValue = null) {
			if (isset($newValue)) {
				if (!is_numeric($newValue)) {
					$newValue = strtotime($newValue);
				}
				$this->date_created = (int) $newValue;
			}
			return $this->date_created;
		}
		
		function fields($newValue = null) {
			if (isset($newValue)) {
				$this->fields = (array) $newValue;
			}
			return $this->fields;
		}

		function after_submit($newValue = null) {
			if (isset($newValue)) {
				$this->after_submit = (string) $newValue;
			}
			return $this->after_submit;
		}

		function after_submit_message($newValue = null) {
			if (isset($newValue)) {
				$this->after_submit_message = (string) $newValue;
			}
			return $this->after_submit_message;
		}		
		
		function after_submit_url($newValue = null) {
			if (isset($newValue)) {
				$this->after_submit_url = (string) $newValue;
			}
			return $this->after_submit_url;
		}			

		
		/**
		 * loads a form
		 */
		function load($id) {

			if (!is_numeric($id)) {
				return false;
			}

			$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_plugin_forms WHERE id = $id AND isDeleted = 0";
			global $polarbear_db;
			$r = $polarbear_db->get_results($sql);
			if (sizeof($r) != 1) {
				return false;
			}

			$row = $r[0];
			$this->id = (int) $id;
			$this->name($row->name);
			$this->email($row->email);
			$this->is_active($row->isActive);
			$this->is_deleted($row->isDeleted);
			$this->date_created($row->dateCreated);
			$this->fields(unserialize($row->fields));
			$this->after_submit($row->afterSubmit);
			$this->after_submit_message($row->afterSubmitMessage);
			$this->after_submit_url($row->afterSubmitURL);
			$this->submit_button_text($row->submitButtonText);

			return true;
		}


		/**
		 * Saves
		 * Field will get a new ID if it does not have one yet.
		 */
		function save() {
			global $polarbear_db;
			if (is_numeric($this->id) && $this->id != 0) {
				$sql = "UPDATE ";
				$sqlWhere = " WHERE id = '$this->id'";
			} else {
				$sql = "INSERT INTO ";
				$sqlWhere = "";
			}
			$sql .= POLARBEAR_DB_PREFIX . "_plugin_forms ";
			$sql .= "SET ";
			$sql .= " name='".$polarbear_db->escape($this->name)."', ";
			$sql .= " email='".$polarbear_db->escape($this->email)."', ";
			$sql .= " submitButtonText='".$polarbear_db->escape($this->submit_button_text)."', ";
			$sql .= " afterSubmit='".$polarbear_db->escape($this->after_submit)."', ";
			$sql .= " afterSubmitMessage='".$polarbear_db->escape($this->after_submit_message)."', ";
			$sql .= " afterSubmitURL='".$polarbear_db->escape($this->after_submit_url)."', ";
			$sql .= " dateCreated=FROM_UNIXTIME(".$polarbear_db->escape($this->date_created)."), ";
			$sql .= " isActive=". (int) $this->is_active.", ";
			$sql .= " isDeleted=". (int) $this->is_deleted.", ";
			// reindex fields and save
			// also remove delted
			$arrFieldsCleaned = array();
			foreach ($this->fields as $oneField) {
				if ($oneField["isDeleted"] == 1) {
					continue;
				}
				$arrFieldsCleaned[] = $oneField;
			}
			$this->fields($arrFieldsCleaned);
			$serializedFields = serialize($arrFieldsCleaned);
			$sql .= "fields = '" . $polarbear_db->escape($serializedFields) . "' ";
			$sql .= $sqlWhere;
			#echo "sql: $sql";exit;
			$polarbear_db->query($sql);

			if (is_null($this->id)) {
				$this->id = $polarbear_db->insert_id;
			}

		}
		
		
		/**
		 * Delete this form
		 */
		function delete() {
			$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_plugin_forms SET isDeleted = 1 WHERE id = '{$this->id}'";
			global $polarbear_db;
			$polarbear_db->query($sql);
		}
		
		/**
		 * returns all forms, to be used like plugin_form::get_forms
		 */
		function get_forms() {
			global $polarbear_db;
			$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_plugin_forms WHERE isDeleted = 0 ORDER BY name ASC";
			$polarbear_db->query($sql);
			$arrForms = array();
			if ($r = $polarbear_db->get_results()) {
				foreach ($r as $oneRow) {
					$F = new plugin_form();
					$F->load($oneRow->id);
					$arrForms[] = $F;
				}
				
			}
			return $arrForms;
		}
		
		
	} // end class
} // end if


// save form (new or existing)
if ($pb_plugin_action == "plugin_form_save") {

	$plugin_form = new plugin_form;

	// nytt formulär
	if ((int)$_POST["pb_plugin_forms_form_id"]) {
		$plugin_form->load($_POST["pb_plugin_forms_form_id"]);
	}

	$plugin_form->name($_POST["pb_plugin_forms_name"]);
	$plugin_form->is_active($_POST["pb_plugin_forms_active"]);
	$plugin_form->email($_POST["pb_plugin_forms_email"]);
	$plugin_form->after_submit($_POST["pb_plugin_forms_after_submit"]);
	$plugin_form->after_submit_message($_POST["pb_plugin_forms_after_submit_message"]);
	$plugin_form->after_submit_url($_POST["pb_plugin_forms_after_submit_url"]);
	$plugin_form->fields($_POST["pb_plugin_forms_fields"]);
	$plugin_form->submit_button_text($_POST["pb_plugin_forms_submit_button_text"]);
	$plugin_form->save();
	
	// sparad, gå till översikt
	header("Location: $plugin_form_file?pb_plugin_action=show_gui&pb_plugin_forms_saved=1");
	exit;
}

// get a field, new or existing
if ($pb_plugin_action == "plugin_forms_field_get_template") {
	$numAddedNewFields = (int) $_REQUEST["numAddedNewFields"];
	$options = array(
		"numAddedNewFields" => $numAddedNewFields
	);
	echo plugin_forms_add_field($options);
	exit;
}


// delete a form
if ($pb_plugin_action == "plugin_form_delete") {

	$plugin_form = new plugin_form;

	$plugin_form->load($_GET["pb_plugin_form_id"]);
	$plugin_form->delete();
	
}


// Add shortcodes and your own functions here
function plugin_form_shortcode($options) {
    
	$defaults = array(
		"id" => null
	);
	$options = polarbear_extend($defaults, $options);
	
	$out = "";
	if (!is_numeric($options["id"])) {
		$out = "";
	}
		
	$form = new plugin_form;
	if ($form->load($options["id"]) != false) {
		
		// form exists, but is it active?
		if ($form->is_active()) {
			// oh my, it is!
			$fields = $form->fields();
			$out = "</p><form method='post' class='plugin_form' action=''>";
			foreach ($fields as $key => $oneField) {
				/*
			    [0] => Array
			        (
			            [name] => Name
			            [type] => text
			            [choices] => 
			            [isDeleted] => 0
			        )
				*/
				$fieldID = "plugin-form-" . $form->id() . "-field-{$key}";
				$fieldName = $oneField["name"];
				$out .= "<p class='plugin-form-{$oneField["type"]}'>";
				$out .= "<label for='$fieldID'>" . htmlspecialchars($oneField["name"], ENT_COMPAT, "UTF-8") . "</label>";
				if ($oneField["type"] == "text") {
					$out .= "<input class='plugin-form-text' type='text' name='$fieldName' id='$fieldID' />";
				} else if ($oneField["type"] == "multiline") {
					$out .= "<textarea class='plugin-form-textarea' cols='35' rows='10' name='$fieldName' id='$fieldID'></textarea>";
				} else if ($oneField["type"] == "multichoice") {
					$out .= "<select class='plugin-form-select' name='$fieldName' id='$fieldID'>";
					
					$choices = $oneField["choices"];
					$choices = trim($choices);
					$choices = explode("\n", $choices);
					foreach ($choices as $oneChoice) {
						$oneChoice = trim($oneChoice);
						$oneChoice = htmlspecialchars ($oneChoice, ENT_COMPAT, "UTF-8");
						$out .= "<option value='$oneChoice'>$oneChoice</option>";
					}
					
					$out .= "</select>";
				}
				$out .= "</p>";
			}
			$buttonValue = htmlspecialchars ($form->submit_button_text(), ENT_COMPAT, "UTF-8");
			$out .= "
				<p class='plugin-form-submit'><input class='plugin-form-submit' type='submit' value='$buttonValue' /></p>
			";
			$out .= "</form><p>";
		
		}
		
	}
	
	//return "arge1 = {$options["arg1"]}, arg2 = $options[arg2]";
	return $out;
}
pb_add_shortcode('plugin_form', 'plugin_form_shortcode');



/**
 * show gui if we're coming through tree
 * You may want to show this in some other situations also
 */
#if ($pb_plugin_action == "show_gui") {
if ($pb_plugin_action) {
	$skip_layout = true;
	require_once(POLARBEAR_ROOT . "/includes/php/admin-header.php");
	?>
	<style>
		ul.plugin_forms li {
			padding: .5ex;
		}

		ul.plugin_forms .selected {
			background-color: #eee;
			font-weight: bold;
		}

		ul.plugin_forms_field_add_wrapper {
			list-style-type: none;
			margin: 0;
		}
		ul.plugin_forms_field_add_wrapper li {
		    margin-bottom: 1em;
		    background-color: #f5f5f5;
		    padding: .5em;
		    border-bottom: 1px solid #d5d5d5;
		}

		ul.plugin_forms_field_add_wrapper li div {
			position: relative;
			margin-bottom: .5em;
		}
		
		form.plugin_forms_form label {
			display: block;
			font-weight: bold;
		}
		form.plugin_forms_form label[class='for-radio'] {
			display: inline;
			font-weight: normal;
		}
		form.plugin_forms_form small.plugin_forms_description {
			color: #999;
			display: block;
		}
		form.plugin_forms_form input[type=text] {
			display: block;
			width: 300px;
		}
		
		.plugin_forms_form_row {
			margin-top: 1em;
			margin-bottom: 1em;
		}
		
		.pb_plubin_form_edit_inactive {
			color: #999;
		}
		
		.plugin_forms_form_row_delete {
			text-align: right;
			margin-top: -2.5em;
			margin-right: 1em;
		}
		
		.pb_plubin_form_list_edit {
			display: none;
			margin-left: .5em;
			font-size: 10px;
		}
			
	</style>

	<script>
		var numAddedNewFields = 0;
		$("a.plugin_forms_field_add").live("click", function() {
			var thisNumAddedNewFields = numAddedNewFields++;
			$.post("<?php echo $plugin_form_file ?>", {
				pb_plugin_action: "plugin_forms_field_get_template",
				numAddedNewFields: thisNumAddedNewFields
			}, function (data) {
			
				$(".plugin_forms_field_add_wrapper").prepend(data).find("li:first").effect("highlight", {}, 1000);

			});
			return false;
		});
		
		$("a.plugin_forms_field_added_link_remove").live("click", function() {
			var parentLI = $(this).parents("li");
			jConfirm("Remove field?", null, function(r) {
				if (r) {
					parentLI.find("input:last").val(1);
					parentLI.fadeOut("slow");
				}
			})
			return false;
		});
		
		$("#plugin_forms_after_submit_showMessage").live("click", function() {
			$("#plugin_forms_after_submit_showMessage_theMessage").show();
			$("#plugin_forms_after_submit_goToURL_theURL").hide();
			$("label[for='plugin_forms_after_submit_goToURL_theURL']").hide();
			$("label[for='plugin_forms_after_submit_showMessage_theMessage']").show();
		});

		$("#plugin_forms_after_submit_goToURL").live("click", function() {
			$("#plugin_forms_after_submit_goToURL_theURL").show();
			$("#plugin_forms_after_submit_showMessage_theMessage").hide();
			$("label[for='plugin_forms_after_submit_showMessage_theMessage']").hide();
			$("label[for='plugin_forms_after_submit_goToURL_theURL']").show();
		});
		
		$(function() {
			$("ul.plugin_forms_field_add_wrapper").sortable({
				axis: "y"
			});
			$("#plugin_forms_after_submit_showMessage_theMessage").overLabel();
			$("#plugin_forms_after_submit_goToURL_theURL").overLabel();
			$("label[for='plugin_forms_after_submit_goToURL_theURL']").hide();
			//$("label[for='plugin_forms_after_submit_showMessage_theMessage']").hide();
		});
		
		$(".plugin_forms_form_row_delete a").live("click", function() {
			jConfirm("Delete this form?", "", function(r) {
				if (r == true) {
					document.location = $(".plugin_forms_form_row_delete a").attr("href");
				}
			});
			return false;
		});
		
		$(".plugin_forms li").live("mouseover", function() {
			$(this).find(".pb_plubin_form_list_edit").show();
		}).live("mouseout", function() {
			$(this).find(".pb_plubin_form_list_edit").hide();
		});
		
	</script>
	
	<?php
	if ($_GET["pb_plugin_forms_saved"]) {
		polarbear_infomsg("Saved form");
	} else if ($_GET["pb_plugin_okmsg"]) {
		polarbear_infomsg($_GET["pb_plugin_okmsg"]);
	}
	
	?>
	
	
	
	<div>
		<h1>
			<a href="<?php echo $plugin_form_file ?>?pb_plugin_action=show_gui">Forms</a>
			<img src="../images/silkicons/application_form.png" alt="" />
		</h1>
	</div>
	
	<div style="float: left; width: 30%; margin-right: 2%">
		<p><a href="<?php echo $plugin_form_file ?>?pb_plugin_action=edit">+ Add</a></p>	
		
		<ul class="plugin_forms">		
		<?php

		// if editing a new
		if ($pb_plugin_action == "edit" && !$pb_plugin_form_edit_id) {
			?><li class="selected">New form</li><?php
		}

		/**
		 * fetch existing forms
		 */
		$arr_forms = plugin_form::get_forms();
		if (sizeof($arr_forms)<1) {
			?><li>There are no forms created yet. Perhaps you want to <a href="<?php echo $plugin_form_file ?>?pb_plugin_action=edit">create a new form</a>?</li><?php
		} else {
			foreach ($arr_forms as $oneForm) {	
				$class = "";
				if ($pb_plugin_form_edit_id == $oneForm->id()) {
					$class = "selected";
				}
				echo "<li class='$class'>";
				echo "<a href='{$plugin_form_file}?pb_plugin_action=view&pb_plugin_form_view_id={$oneForm->id()}'>" . htmlspecialchars ($oneForm->name(), ENT_COMPAT, "UTF-8") . "</a>";
				if ($oneForm->is_active() == false) {
					echo " <span class='pb_plubin_form_edit_inactive'>Inactive</span>";
				}
				echo "<span class='pb_plubin_form_list_edit'><a href='{$plugin_form_file}?pb_plugin_action=edit&pb_plugin_form_edit_id={$oneForm->id()}'>Edit</a></span>";
				echo "</li>";
			}
		}
		?>		
		</ul>
		
	</div>
	
	
	
	
	<?php
	/**
	 * Edit a new or an existing form
	 */
	if ($pb_plugin_action == "edit") {
		
		?>
		<div style="float: left; width: 68%;">
			<?php
			if ($pb_plugin_form_edit_id == 0) {
				// new form
				$form_title = "New form";
				$pb_plugin_form_edit_Form = new plugin_form;
			} else {
				// existing form
				$pb_plugin_form_edit_Form = new plugin_form;
				$pb_plugin_form_edit_Form->load($pb_plugin_form_edit_id);
				$form_title = htmlspecialchars($pb_plugin_form_edit_Form->name(), ENT_COMPAT, "UTF-8");
			}
			
			echo "<h2>$form_title</h2>";
			
			// Stuff that all forms have
			?>
			<form class="plugin_forms_form" method="post" action="<?php echo $plugin_form_file ?>">

				<div class="plugin_forms_form_row">
					<label for="plugin_forms_name">Name</label>
					<small class="plugin_forms_description">Give your form a name, for example "Contact form". This name is only visible for you and other admins.</small>
					<input type="text" name="pb_plugin_forms_name" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->name(), ENT_COMPAT, "UTF-8") ?>" id="plugin_forms_name" />
				</div>


				<div class="plugin_forms_form_row">
					<label for="plugin_forms_active">Active</label>
					<div><input <?php print($pb_plugin_form_edit_Form->is_active()?" checked='checked' ":"") ?> id="pb_plugin_forms_active_yes" type="radio" name="pb_plugin_forms_active" class="" value="1" /><label class="for-radio" for="pb_plugin_forms_active_yes"> Yes</label></div>
					<div><input <?php print(!$pb_plugin_form_edit_Form->is_active()?" checked='checked' ":"") ?> id="pb_plugin_forms_active_no" type="radio" name="pb_plugin_forms_active" class="" value="0" /><label class="for-radio" for="pb_plugin_forms_active_no"> No</label></div>
				</div>
						
				<div class="plugin_forms_form_row">
					<label for="plugin_forms_email">Email</label>
					<small class="plugin_forms_description">Notify this email address when someones submits this form (separate multiple email addresses with comma)</small>
					<input type="text" name="pb_plugin_forms_email" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->email(), ENT_COMPAT, "UTF-8") ?>" id="plugin_forms_email" />
				</div>
	
				<div class="plugin_forms_form_row">

					<label>After form has been submitted...</label>
					<div><input <?php print($pb_plugin_form_edit_Form->after_submit() == "showMessage") ? " checked='checked' " : "" ?> type="radio" name="pb_plugin_forms_after_submit" value="showMessage" id="plugin_forms_after_submit_showMessage" /><label class="for-radio" for="plugin_forms_after_submit_showMessage"> Show a message</label></div>
					<div><input <?php print($pb_plugin_form_edit_Form->after_submit() == "goToURL") ? " checked='checked' " : "" ?> type="radio" name="pb_plugin_forms_after_submit" value="goToURL" id="plugin_forms_after_submit_goToURL" /><label class="for-radio" for="plugin_forms_after_submit_goToURL"> Go to a URL</label></div>
					
					<?php
					if ($pb_plugin_form_edit_Form->after_submit() == "showMessage") {
						$pb_plugin_forms_after_submit_message_display = "block";
						$pb_plugin_forms_after_submit_go_to_url_display = "none";
					} else {
						$pb_plugin_forms_after_submit_message_display = "none";
						$pb_plugin_forms_after_submit_go_to_url_display = "block";
					}
					?>
					<div style="position: relative">
						<label for="plugin_forms_after_submit_showMessage_theMessage">Message to show after submit</label>
						<textarea style="display: <?php echo $pb_plugin_forms_after_submit_message_display ?>" id="plugin_forms_after_submit_showMessage_theMessage" name="pb_plugin_forms_after_submit_message" cols="50" rows="7"><?php echo htmlspecialchars($pb_plugin_form_edit_Form->after_submit_message(), ENT_COMPAT, "UTF-8") ?></textarea>
					</div>
					
					<div style="position: relative">
						<label for="plugin_forms_after_submit_goToURL_theURL">URL to go to after submit</label>
						<input id="plugin_forms_after_submit_goToURL_theURL" style="display: <?php echo $pb_plugin_forms_after_submit_go_to_url_display ?>" type="text" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->after_submit_url(), ENT_COMPAT, "UTF-8") ?>" name="pb_plugin_forms_after_submit_url" />
					</div>

				</div>

				<div class="plugin_forms_form_row">
					<label>Text on submit button</label>
					<input type="text" name="pb_plugin_forms_submit_button_text" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->submit_button_text(), ENT_COMPAT, "UTF-8") ?>" />
				</div>

				<h3>Fields</h3>
				<p><a class="plugin_forms_field_add" href="#">+ Add</a></p>

				<ul class="plugin_forms_field_add_wrapper">
					<?php
					foreach($pb_plugin_form_edit_Form->fields() as $oneField) {
						echo plugin_forms_add_field($oneField);
					}
					?>
				</ul>
			
				<div class="plugin_forms_form_row">
					<input type="submit" value="Save" /> or <a href="<?php echo $plugin_form_file ?>?pb_plugin_action=show_gui">cancel</a>
					<input type="hidden" name="pb_plugin_action" value="plugin_form_save" />
					<input type="hidden" name="pb_plugin_forms_form_id" value="<?php echo $pb_plugin_form_edit_Form->id() ?>" />
				</div>
				
				<?php
				if ($pb_plugin_form_edit_Form->id()) {
					?>
					<div class="plugin_forms_form_row plugin_forms_form_row_delete">
						<a href="<?php echo $plugin_form_file ?>?pb_plugin_action=plugin_form_delete&pb_plugin_form_id=<?php echo $pb_plugin_form_edit_Form->id() ?>&pb_plugin_okmsg=<?php echo urlencode("Form deleted") ?>">Delete form</a>
					</div>
				<?php } ?>

			</form>
			<?php
			
			?>
		</div>
		<?php
	}

	require_once(POLARBEAR_ROOT . "/includes/php/admin-header.php");

}


function plugin_forms_add_field($options) {
	$defaults = array(
		#"arg1" => "this is the default arg1",
	);
	$options = polarbear_extend($defaults, $options);

	if (isset($options["numAddedNewFields"])) {
		// är en ny
		$liID = $options["numAddedNewFields"];
		$fieldID = "field_new_$liID";
	} else {
		$liID = "field_existing_" . md5(serialize($options));
		$fieldID = $liID;
	}

	/*
	Array
	(
	    [name] => trean
	    [type] => multiline
	    [textarea] => 
	    [isDeleted] => 0
	)
	*/
	#pb_d($options);
	$multichoice_textarea_wrapper_display = "none";
	if ($options["type"] == "text") {
		$textSelected = " selected='selected' ";
	} elseif ($options["type"] == "multiline") {
		$multiLine = " selected='selected' ";
	} elseif ($options["type"] == "multichoice") {
		$multiChoice = " selected='selected' ";
		$multichoice_textarea_wrapper_display = "block";
	}
	$types = "
		<select name='pb_plugin_forms_fields[{$fieldID}][type]' id='plugin_forms_field_added_select_type_$liID'>
			<option $textSelected value='text'>Text</option>
			<option $multiLine value='multiline'>Text - multi line</option>
			<option $multiChoice value='multichoice'>Multi choice</option>
		</select>
	";

	$out = "
		<li class='plugin_forms_field_added_$liID'>
			<div style='float: right; z-index: 12;'>
				<a href='#' class='plugin_forms_field_added_link_remove'><img src='../images/silkicons/bin_closed.png' alt='Trashcan' /></a>
			</div>
			<div>
				<label for='plugin_forms_field_added_{$liID}_input'>Name</label>
				<input type='text' name='pb_plugin_forms_fields[{$fieldID}][name]' value='".htmlspecialchars($options["name"], ENT_COMPAT, "UTF-8")."' id='plugin_forms_field_added_{$liID}_input' />
			</div>
			<div>
				$types
			</div>
			<div class='plugin_forms_field_added_multichoice_textarea_wrapper' style='display: $multichoice_textarea_wrapper_display;'>
				<label for='plugin_forms_field_added_multichoice_$liID'>Choices (one per line)</label>
				<textarea cols='50' rows='7' name='pb_plugin_forms_fields[{$fieldID}][choices]' id='plugin_forms_field_added_multichoice_$liID'>".htmlspecialchars($options["choices"], ENT_COMPAT, "UTF-8")."</textarea>
			</div>
			<input type='hidden' name='pb_plugin_forms_fields[{$fieldID}][isDeleted]' value='0' />
		</li>

<script>
	// xxx denna ska funka för existerande åxå, inte bara nya
	$('#plugin_forms_field_added_{$liID}_input').overLabel();
	$('#plugin_forms_field_added_multichoice_{$liID}').overLabel();
	$('#plugin_forms_field_added_select_type_{$liID}').change(function() {
		var val = $(this).find(':selected').val();
		if (val == 'multichoice') {
			$(this).parents('li').find('.plugin_forms_field_added_multichoice_textarea_wrapper').slideDown();
		} else {
			$(this).parents('li').find('.plugin_forms_field_added_multichoice_textarea_wrapper').slideUp();
		}
	});

</script>

	";
	
	return $out;
	
}


if ($pb_plugin_cmsroot) {
	require_once($pb_plugin_cmsroot . "/includes/php/admin-footer.php");
}
?>