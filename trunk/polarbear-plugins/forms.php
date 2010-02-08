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
$pb_plugin_form_view_id = (int) $_REQUEST["pb_plugin_form_view_id"];
$plugin_form_file = "forms.php";

// klass som representerar en form
if (!class_exists("plugin_form")) {
	class plugin_form {

		private 
			$id, $name, $email, $is_active, $is_deleted, 
			$date_created, $after_submit, $after_submit_message, 
			$after_submit_url, $fields, $submit_button_text,
			$after_submit_message_error;
			
		
		function __construct() {
			$this->id = null;
			$this->is_active = true;
			$this->is_deleted = false;
			$this->date_created = time();
			$this->fields = array();
			$this->after_submit = "showMessage"; // showMessage | goToURL
			$this->submit_button_text = "Ok";
			$this->type = "form";
			$this->subscribeButtonText = "Subscribe";
			$this->unsubscribeButtonText = "Unsubscribe";
			$this->afterSubscribeMessage = "Your are now susbcribed to our newsletter.";
			$this->afterUnsubscribeMessage = "You have not unsubscribed from our newsletter.";
			$this->subscribe_keyField = "";
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

		function after_submit_message_error($newValue = null) {
			if (isset($newValue)) {
				$this->after_submit_message_error = (string) $newValue;
			}
			return $this->after_submit_message_error;
		}		

		function after_submit_url($newValue = null) {
			if (isset($newValue)) {
				$this->after_submit_url = (string) $newValue;
			}
			return $this->after_submit_url;
		}

		
		function submitted_values($args = null) {

			$defaults = array(
				"orderby" => "date_posted DESC"
			);
			$args = polarbear_extend($defaults, $args);

			$sql = "SELECT date_posted, UNIX_TIMESTAMP(date_posted) AS date_posted_unix, ip, submitted_values FROM " . POLARBEAR_DB_PREFIX . "_plugin_forms_submits WHERE formID = '{$this->id()}' ORDER BY {$args['orderby']}";
			$arrValues = array();
			global $polarbear_db;
			if ($r = $polarbear_db->get_results($sql)) {
				foreach ($r as $rowNum => $row) {
					$r[$rowNum]->values = unserialize($row->submitted_values);
				}
			} else {
				$r = array();
			}
			return $r;
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
			$this->after_submit_message_error($row->afterSubmitMessageError);
			$this->after_submit_url($row->afterSubmitURL);
			$this->submit_button_text($row->submitButtonText);

			$this->type = $row->type;

			$this->subscribeButtonText = $row->subscribeButtonText;
			$this->unsubscribeButtonText = $row->unsubscribeButtonText;
			$this->afterSubscribeMessage = $row->afterSubscribeMessage;
			$this->afterUnsubscribeMessage = $row->afterUnsubscribeMessage;
			$this->subscribe_keyField = $row->subscribeKeyField;

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
			$sql .= " afterSubmitMessageError='".$polarbear_db->escape($this->after_submit_message_error)."', ";
			$sql .= " afterSubmitURL='".$polarbear_db->escape($this->after_submit_url)."', ";

			$sql .= " type = '" . $polarbear_db->escape($this->type)."', ";
			$sql .= " subscribeButtonText = '" . $polarbear_db->escape($this->subscribeButtonText)."', ";
			$sql .= " unsubscribeButtonText = '" . $polarbear_db->escape($this->unsubscribeButtonText)."', ";
			$sql .= " afterSubscribeMessage = '" . $polarbear_db->escape($this->afterSubscribeMessage)."', ";
			$sql .= " afterUnsubscribeMessage = '" . $polarbear_db->escape($this->afterUnsubscribeMessage)."', ";
			$sql .= " subscribeKeyField = '" . $polarbear_db->escape($this->subscribe_keyField)."', ";

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
	$plugin_form->after_submit_message_error($_POST["pb_plugin_forms_after_submit_message_error"]);
	$plugin_form->after_submit_url($_POST["pb_plugin_forms_after_submit_url"]);
	$plugin_form->fields($_POST["pb_plugin_forms_fields"]);

	$plugin_form->type = ($_POST["pb_plugin_forms_type"]);

	$plugin_form->submit_button_text($_POST["pb_plugin_forms_submit_button_text"]);
	
	$plugin_form->subscribeButtonText = $_POST["pb_plugin_forms_subscribe_button_text"];
	$plugin_form->unsubscribeButtonText = $_POST["pb_plugin_forms_unsubscribe_button_text"];
	$plugin_form->afterSubscribeMessage= $_POST["pb_plugin_forms_after_subscribe_message"];
	$plugin_form->afterUnsubscribeMessage = $_POST["pb_plugin_forms_after_unsubscribe_message"];
	$plugin_form->subscribe_keyField = $_POST["pb_plugin_forms_unsubscribe_keyField_theValue"];
	
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

// download as csv
if ($pb_plugin_action == "download_csv") {

	$form = new plugin_form;
	$pb_plugin_form_id = (int) $_GET["pb_plugin_form_id"];
	if (!$pb_plugin_form_id) {
		echo "Sorry, could not find the requested form.";
		exit;
	}
	$form->load($pb_plugin_form_id);
	$values = $form->submitted_values();
	$headers = array();
	
	// find all kind of existing headers
	foreach ($values as $oneVal) {
		foreach ($oneVal->values as $key => $val) {
			$headers[$key] = 1;
		}
	}
	
	// It will be called downloaded.pdf
	#header("Content-Type: text/csv; charset=iso-8859-1");
	#header("Content-Type: text/html; charset=iso-8859-1");
	header('Content-Disposition: attachment; filename="'.$form->name(). ' (' . strftime("%e-%b-%Y %H:%M", time()) . ').tsv"');
	
	$out = "";
	
	$out .= "Date";
	foreach($headers as $headerName => $blah) {
		$out .= "\t$headerName";
	}
	
	foreach ($values as $oneVal) {
		$date = strftime("%d-%b-%Y %H:%M", $oneVal->date_posted_unix);
		$out .= "\n";
		$out .= "$date";
		foreach($headers as $headerName => $blah) {
			#$out .= "\"";
			#$out .= nl2br(htmlspecialchars($oneVal->values[$headerName], ENT_COMPAT, "UTF-8"));
			$thisVal = $oneVal->values[$headerName];
			$thisVal = str_replace("\t", "", $thisVal);
			$thisVal = str_replace("\n\r", "\n", $thisVal);
			$thisVal = str_replace("\r\n", "\n", $thisVal);
			$thisVal = str_replace("\r", "\n", $thisVal);
			$thisVal = str_replace("\n", " ", $thisVal);
			$out .= "\t";
			#$out .= '"'.$thisVal.'"';
			$out .= $thisVal;
			#$out .= $oneVal->values[$headerName];
			#$out .= "\",";
		}
	}

	$out = utf8_decode($out);
	echo $out;

	exit;
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
			
			// check if form is posted
			$isSubmitted = ($_POST["plugin-form-submitted"] && $_POST["plugin-form-submitted-id"] == $form->id());
			// if form was submitted, check if all required fields where submitted
			$allValid = true;
			$isAddedToDatabase = false;
			if ($isSubmitted) {

				// check for spam
				$fakeFieldRequired = $_POST["pb-plugin-forms-captcha"]; // must be 1
				$fakeFieldNotRequired = $_POST["pb-plugin-forms-captcha-confirm"]; // must be empty ("")
				if ($fakeFieldRequired == "1" && $fakeFieldNotRequired == "") {
					// ok, hopefully not spam
				} else {
					$allValid = false;
				}

				foreach ($fields as $key => $oneField) {
					if ($oneField["is_required"]) {

						$fieldName = "plugin_forms_" . htmlspecialchars($oneField["name"], ENT_COMPAT, "UTF-8");
						$fieldValue = trim($_POST[$fieldName]);
						$fieldType = $oneField["type"];
						$isValid = true;
						if ($fieldType == "text" || $fieldType == "multiline") {
							if (empty($fieldValue)) {
								$isValid = false;
								$allValid = false;
							}
						} else if ($fieldType = "multichoice") {
							// multichoice = not first val selected
							$choices = htmlspecialchars($oneField["choices"], ENT_COMPAT, "UTF-8");
							$choices = explode("\n", $choices);
							if ($fieldValue == trim($choices[0])) {
								$isValid = false;
								$allValid = false;
							}
						}
						
						$fields[$key]["is_required_is_valid"] = $isValid;
					
					}
				}
				
				if ($allValid) {
					// we have checked all fields and they all looks ok
					// let's store the values
					$ip = $_SERVER["REMOTE_ADDR"];
					// get all key that begin with plugin_forms_
					$values = array();
					$emailMsg = "";
					foreach ($_POST as $key => $val) {
						if (preg_match('/^plugin_forms_/', $key)) {
							$keyFixed = preg_replace('/^plugin_forms_/', "", $key);
							$val = trim($val);
							$values[$keyFixed] = $val;
							$msg .= "\n$keyFixed: $val";
							unset($_POST[$key]);
						}
					}
					$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_plugin_forms_submits SET date_posted = now(), ip = '$ip', formID = '" . $form->id() . "', submitted_values = '".mysql_real_escape_string(serialize($values))."'";
					global $polarbear_db;
					$polarbear_db->query($sql);
					$isAddedToDatabase =  true;
					
					// ok it's added to the database. should we also send a mail?
					$emails = trim($form->email());
					if (!empty($emails)) {
						$subject = "A form was submitted on " . POLARBEAR_DOMAIN;
						$message = "";
						$mailR = mail($emails, $subject, $msg);
					}
					
				}
				
			}
			
			// print out form
			$out = "</p>";

			if (!$allValid) {
				$out .= "
				<p class='plugin-form-errormsg'><strong>" . htmlspecialchars($form->after_submit_message_error(), ENT_COMPAT, "UTF-8") . "</strong></p>
				";
			}
			if ($isAddedToDatabase) {
				$out .= "<p class='plugin-form-okmsg'><strong>" . htmlspecialchars($form->after_submit_message(), ENT_COMPAT, "UTF-8") . "</strong></p>";
			}
			
			$out .= "<form method='post' class='plugin_form' action=''>";
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
				$fieldName = htmlspecialchars($oneField["name"], ENT_COMPAT, "UTF-8");
				$fieldNamePrefixed = "plugin_forms_" . htmlspecialchars($oneField["name"], ENT_COMPAT, "UTF-8");
				// name måste ha underscore istället för mellanslag. verkar som att webbläsaren ändrar
				$fieldNamePrefixedNoSpaces = str_replace(" ", "_", $fieldNamePrefixed);

				$fieldValue = htmlspecialchars($_POST[$fieldNamePrefixedNoSpaces], ENT_COMPAT, "UTF-8");
				$is_required = $oneField["is_required"];
				$is_required_is_valid = $oneField["is_required_is_valid"];
				$out .= "<p class='plugin-form-{$oneField["type"]}'>";
				$out .= "<label for='$fieldID'>$fieldName</label>";

				if ($is_required) {
					$out .= "<span class='plugin-form-required'>" . htmlspecialchars ($oneField["is_required_text"], ENT_COMPAT, "UTF-8") . "</span>";
				}

				if ($oneField["type"] == "text") {
					$out .= "<input class='plugin-form-text' type='text' name='$fieldNamePrefixed' id='$fieldID' value='$fieldValue' />";
				} else if ($oneField["type"] == "multiline") {
					$out .= "<textarea class='plugin-form-textarea' cols='35' rows='10' name='$fieldNamePrefixed' id='$fieldID'>$fieldValue</textarea>";
				} else if ($oneField["type"] == "multichoice") {
					$out .= "<select class='plugin-form-select' name='$fieldNamePrefixed' id='$fieldID'>";
					
					$choices = $oneField["choices"];
					$choices = trim($choices);
					$choices = explode("\n", $choices);
					foreach ($choices as $oneChoice) {
						$oneChoice = trim($oneChoice);
						$oneChoice = htmlspecialchars ($oneChoice, ENT_COMPAT, "UTF-8");
						$optionSelected = "";
						if ($fieldValue == $oneChoice) {
							$optionSelected = " selected='selected' ";
						}
						$out .= "<option $optionSelected value='$oneChoice'>$oneChoice</option>";
					}
					
					$out .= "</select>";
				}
				if ($is_required_is_valid == false && isset($is_required_is_valid)) {
					$out .= " <span class='plugin-form-required-missing'>". htmlspecialchars ($oneField["is_required_text_reminder"], ENT_COMPAT, "UTF-8") . "</span>";
				}

				$out .= "</p>";
			}
			$buttonValue = htmlspecialchars ($form->submit_button_text(), ENT_COMPAT, "UTF-8");
			$out .= "
				<p class='plugin-form-submit'><input class='plugin-form-submit' type='submit' value='$buttonValue' /></p>
			";
			$out .= "<input type='hidden' name='plugin-form-submitted' value='1' />";
			$out .= "<input type='hidden' name='plugin-form-submitted-id' value='" . $form->id() . "' />";
			// must be filled in
			$out .= '<input type="hidden" name="pb-plugin-forms-captcha" value="1">';
			// must not be filled in
			$out .= '<input type="hidden" name="pb-plugin-forms-captcha-confirm" value="">';

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
	<style type="text/css">
	
		.ui-layout-resizer {
			display: none;
			background-color: white;
		}
		.ui-layout-toggler {
			display: none !important;
		}
	
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
		
		.pb_plugin_form_list_edit {
			display: none;
			margin-left: .5em;
			font-size: 10px;
		}

		.plugin-form-submitted-values th,
		.plugin-form-submitted-values td {
			padding: 7px;
			vertical-align: top;
			text-align: left;
			font-size: 12px;
			border-bottom: 1px solid #ddd;
		}
		.plugin-form-submitted-values-date {
			white-space: nowrap;
		}

	</style>

	<script type="text/javascript" src="../includes/js/jgrid/jquery.jqGrid.min.js"></script>
	<script type="text/javascript" src="../includes/js/jgrid/grid.locale-en.js"></script>
	<link rel="stylesheet" type="text/css" href="../includes/js/jgrid/ui.jqgrid.css" />
	<script type="text/javascript">

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
			$("#plugin_forms_after_submit_showMessage_theMessage").show("fast");
			$("#plugin_forms_after_submit_goToURL_theURL").hide("fast");
			$("label[for='plugin_forms_after_submit_goToURL_theURL']").hide("fast");
			$("label[for='plugin_forms_after_submit_showMessage_theMessage']").show("fast");
		});

		$("#plugin_forms_after_submit_goToURL").live("click", function() {
			$("#plugin_forms_after_submit_goToURL_theURL").show("fast");
			$("#plugin_forms_after_submit_showMessage_theMessage").hide("fast");
			$("label[for='plugin_forms_after_submit_showMessage_theMessage']").hide("fast");
			$("label[for='plugin_forms_after_submit_goToURL_theURL']").show("fast");
		});
		
		
		/*
		pb_plugin_forms_type_form
		pb_plugin_forms_type_subscribe
		*/
		$("#pb_plugin_forms_type_form").live("click", function() {
			$("#plugin_forms_form_row_type_form_options").fadeIn("slow");
			$("#plugin_forms_form_row_type_subscribe_options").fadeOut("slow");
		});
		$("#pb_plugin_forms_type_subscribe").live("click", function() {
			$("#plugin_forms_form_row_type_form_options").fadeOut("slow");
			$("#plugin_forms_form_row_type_subscribe_options").fadeIn("slow");
		});
		
		
		$(function() {
			$("ul.plugin_forms_field_add_wrapper").sortable({
				axis: "y"
			});
			$("#plugin_forms_after_submit_showMessage_theMessage").overLabel();
			$("#plugin_forms_after_submit_goToURL_theURL").overLabel();
			$("label[for='plugin_forms_after_submit_goToURL_theURL']").hide();
			//$("label[for='plugin_forms_after_submit_showMessage_theMessage']").hide();
			
			$("form.plugin_forms_form").submit(function() {
				return pb_plugin_forms_submit_validate();
			});
			
			$('body').layout({
				applyDefaultStyles: false,
				west: {
					size: 300
				}
			});
			
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
			$(this).find(".pb_plugin_form_list_edit").show();
		}).live("mouseout", function() {
			$(this).find(".pb_plugin_form_list_edit").hide();
		});
		
		function pb_plugin_forms_submit_validate() {
			var formType = $("input[name=pb_plugin_forms_type]:checked").val();
			if (formType == "subscribe") {
				var enteredField = $("#pb_plugin_forms_unsubscribe_keyField_theValue").val();
				var foundEnteredField = false;
				$(".plugin_forms_field_add_wrapper li").each(function(i, o) {
					// hämta första input i varje
					inputVal = $(o).find("input[type=text]:first").val();
					if (inputVal == enteredField) {
						foundEnteredField = true;
					}
				});
				
				if (!foundEnteredField) {
					alert("Please make sure 'Field to use as key' has an existing name entered");
				}
				
				return foundEnteredField;
				
			} else {
				return true;
			}
		}


	</script>
	
	<?php
	if ($_GET["pb_plugin_forms_saved"]) {
		polarbear_infomsg("Saved form");
	} else if ($_GET["pb_plugin_okmsg"]) {
		polarbear_infomsg($_GET["pb_plugin_okmsg"]);
	}
	
	?>

	<div class="ui-layout-west">
		<div>
			<h1>
				<a href="<?php echo $plugin_form_file ?>?pb_plugin_action=show_gui">Forms</a>
				<img src="../images/silkicons/application_form.png" alt="" />
			</h1>
		</div>
		
		<div>
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
					if ($pb_plugin_form_edit_id == $oneForm->id() || $pb_plugin_form_view_id == $oneForm->id()) {
						$class = "selected";
					}
					echo "<li class='$class'>";
					echo "<a href='{$plugin_form_file}?pb_plugin_action=view&amp;pb_plugin_form_view_id={$oneForm->id()}'>" . htmlspecialchars ($oneForm->name(), ENT_COMPAT, "UTF-8") . "</a>";
					if ($oneForm->is_active() == false) {
						echo " <span class='pb_plubin_form_edit_inactive'>Inactive</span>";
					}
					echo "<span class='pb_plugin_form_list_edit'><a href='{$plugin_form_file}?pb_plugin_action=edit&amp;pb_plugin_form_edit_id={$oneForm->id()}'>Edit</a></span>";
					echo "</li>";
				}
			}
			?>		
			</ul>
			
		</div>
	</div>
	
	
	
	<?php
	
	/**
	 * Show submitted values for a form
	 */
	if ($pb_plugin_action == "view") {


		$form = new plugin_form;
		$form->load($pb_plugin_form_view_id);
		$values = $form->submitted_values();
		$headers = array();
		?>

		<div class="ui-layout-center">
				
			<div>
				<?php
				if ($values) {
					?>
					<a href="<?php echo $plugin_form_file ?>?pb_plugin_action=download_csv&amp;pb_plugin_form_id=<?php echo $pb_plugin_form_view_id ?>">Download as CSV-file</a>
					<?php
				}
				?>
			</div>
			
			<div class="ui-layout-content">
			
				<table class="plugin-form-submitted-values">
					<?php
					
					if ($values) {
						// find all kind of existing headers
						foreach ($values as $oneVal) {
							foreach ($oneVal->values as $key => $val) {
								$headers[$key] = 1;
							}
						}
						echo "<tr>";
						echo "<th class='plugin-form-submitted-values-date'>Date</th>";
						foreach($headers as $headerName => $blah) {
							echo "<th>$headerName</th>";
						}
						echo "</tr>";
						
						foreach ($values as $oneVal) {
							$date = strftime("%e-%b-%Y %H:%M", $oneVal->date_posted_unix);
							echo "<tr>";
							echo "<td class='plugin-form-submitted-values-date'>$date</td>";
							foreach($headers as $headerName => $blah) {
								echo "<td>";
								echo nl2br(htmlspecialchars($oneVal->values[$headerName], ENT_COMPAT, "UTF-8"));
								echo "</td>";
							}
							echo "</tr>";
						}
					} else {
						echo "<tr><td>No values yet</td></tr>";
					}
					?>
				</table>

			</div>	

		</div>

		<?php
	}	

	
	/**
	 * Edit a new or an existing form
	 */
	if ($pb_plugin_action == "edit") {
		
		?>
		<div class="ui-layout-center">
			<div class="ui-layout-content">
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
				
					<div class="" style="float: right; margin: 1em; width: 250px; padding: .5em; background-color: #eee;">
						<p>
							Use this code to show this form on your webpage:<br />
							<?php
							if (!$pb_plugin_form_edit_id) {
								?>Please save your form to see the code.<?php
							} else {
								?>
								<code>[plugin_form id="<?php echo $pb_plugin_form_edit_id ?>"]</code>
								<?php
							}
							?>
						</p>
					</div>
	
					<div class="plugin_forms_form_row">
						<label for="plugin_forms_name">Name</label>
						<small class="plugin_forms_description">Give your form a name, for example "Contact form". This name is only visible for you and other admins.</small>
						<input type="text" name="pb_plugin_forms_name" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->name(), ENT_COMPAT, "UTF-8") ?>" id="plugin_forms_name" />
					</div>
	
	
					<div class="plugin_forms_form_row">
						<label>Active</label>
						<div><input <?php print($pb_plugin_form_edit_Form->is_active()?" checked='checked' ":"") ?> id="pb_plugin_forms_active_yes" type="radio" name="pb_plugin_forms_active" class="" value="1" /><label class="for-radio" for="pb_plugin_forms_active_yes"> Yes</label></div>
						<div><input <?php print(!$pb_plugin_form_edit_Form->is_active()?" checked='checked' ":"") ?> id="pb_plugin_forms_active_no" type="radio" name="pb_plugin_forms_active" class="" value="0" /><label class="for-radio" for="pb_plugin_forms_active_no"> No</label></div>
					</div>
							
					<div class="plugin_forms_form_row">
						<label for="plugin_forms_email">Email</label>
						<small class="plugin_forms_description">Notify this email address when someones submits this form (separate multiple email addresses with comma)</small>
						<input type="text" name="pb_plugin_forms_email" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->email(), ENT_COMPAT, "UTF-8") ?>" id="plugin_forms_email" />
					</div>
		
					<?php
					// $pb_plugin_form_edit_Form->type;
					?>
					<!--
					<div class="plugin_forms_form_row">
						<label>Type of form</label>
						<div><input <?php echo ($pb_plugin_form_edit_Form->type=="form") ? " checked='checked' " : "" ?> name="pb_plugin_forms_type" type="radio" value="form" id="pb_plugin_forms_type_form" /><label for="pb_plugin_forms_type_form" class="for-radio"> Form</label></div>
						<div><input <?php echo ($pb_plugin_form_edit_Form->type=="subscribe") ? " checked='checked' " : "" ?> name="pb_plugin_forms_type" type="radio" value="subscribe" id="pb_plugin_forms_type_subscribe" /><label for="pb_plugin_forms_type_subscribe" class="for-radio"> Subscribe</label></div>
					</div>
					-->

					<div id="plugin_forms_form_row_type_subscribe_options" style="<?php echo ($pb_plugin_form_edit_Form->type=="subscribe") ? " ": " display: none; " ?>">

						<div class="plugin_forms_form_row">
							<label for="pb_plugin_forms_subscribe_button_text">Text on subscribe button</label>
							<input type="text" id="pb_plugin_forms_subscribe_button_text" name="pb_plugin_forms_subscribe_button_text" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->subscribeButtonText, ENT_COMPAT, "UTF-8") ?>" />
						</div>

						<div class="plugin_forms_form_row">
							<!-- <a href="#" onclick="pb_plugin_forms_submit_validate();">test</a> -->
							<div id="pb_plugin_forms_unsubscribe_keyField">
								<label for="pb_plugin_forms_unsubscribe_keyField_theValue">Field to use as key</label>
								<input id="pb_plugin_forms_unsubscribe_keyField_theValue" type="text" name="pb_plugin_forms_unsubscribe_keyField_theValue" value="<?php echo $pb_plugin_form_edit_Form->subscribe_keyField ?>" />
							</div>
						</div>

						<div class="plugin_forms_form_row">
							<label for="pb_plugin_forms_unsubscribe_button_text">Text on unsubscribe button</label>
							<input id="pb_plugin_forms_unsubscribe_button_text" type="text" name="pb_plugin_forms_unsubscribe_button_text" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->unsubscribeButtonText, ENT_COMPAT, "UTF-8") ?>" />
						</div>

						<div class="plugin_forms_form_row">
							<label for="plugin_forms_after_subscribe_message">Message to show on subscribe sucess</label>
							<textarea cols="50" rows="4" id="plugin_forms_after_subscribe_message" name="pb_plugin_forms_after_subscribe_message"><?php echo htmlspecialchars($pb_plugin_form_edit_Form->afterSubscribeMessage, ENT_COMPAT, "UTF-8") ?></textarea>
						</div>

						<div class="plugin_forms_form_row">
							<label for="plugin_forms_after_unsubscribe_message">Message to show on unsubscribe sucess</label>
							<textarea cols="50" rows="4" id="plugin_forms_after_unsubscribe_message" name="pb_plugin_forms_after_unsubscribe_message"><?php echo htmlspecialchars($pb_plugin_form_edit_Form->afterUnsubscribeMessage, ENT_COMPAT, "UTF-8") ?></textarea>
						</div>

					</div>
		

					<div id="plugin_forms_form_row_type_form_options" style="<?php echo ($pb_plugin_form_edit_Form->type=="form") ? " ": " display: none; " ?>">
						<div class="plugin_forms_form_row">
		
							<label>After form has been successfully submitted:</label>
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
								<textarea cols="50" rows="4" style="display: <?php echo $pb_plugin_forms_after_submit_message_display ?>" id="plugin_forms_after_submit_showMessage_theMessage" name="pb_plugin_forms_after_submit_message"><?php echo htmlspecialchars($pb_plugin_form_edit_Form->after_submit_message(), ENT_COMPAT, "UTF-8") ?></textarea>
							</div>
							
							<div style="position: relative">
								<label for="plugin_forms_after_submit_goToURL_theURL">URL to go to after submit</label>
								<input id="plugin_forms_after_submit_goToURL_theURL" style="display: <?php echo $pb_plugin_forms_after_submit_go_to_url_display ?>" type="text" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->after_submit_url(), ENT_COMPAT, "UTF-8") ?>" name="pb_plugin_forms_after_submit_url" />
							</div>
		
						</div>
		
						<div class="plugin_forms_form_row">
							<label for="plugin_forms_after_submit_message_error">Message to show if a form is submitted but not all required fields are filled in</label>
							<textarea cols="50" rows="4" id="plugin_forms_after_submit_message_error" name="pb_plugin_forms_after_submit_message_error"><?php echo htmlspecialchars($pb_plugin_form_edit_Form->after_submit_message_error(), ENT_COMPAT, "UTF-8") ?></textarea>
						</div>
		
						<div class="plugin_forms_form_row">
							<label>Text on submit button</label>
							<input type="text" name="pb_plugin_forms_submit_button_text" value="<?php echo htmlspecialchars($pb_plugin_form_edit_Form->submit_button_text(), ENT_COMPAT, "UTF-8") ?>" />
						</div>
					
					</div>
	
					<h2>Fields</h2>
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
							<a href="<?php echo $plugin_form_file ?>?pb_plugin_action=plugin_form_delete&amp;pb_plugin_form_id=<?php echo $pb_plugin_form_edit_Form->id() ?>&amp;pb_plugin_okmsg=<?php echo urlencode("Form deleted") ?>">Delete form</a>
						</div>
					<?php } ?>
	
				</form>

			</div>
		</div>
		<?php
	}

	require_once(POLARBEAR_ROOT . "/includes/php/admin-footer.php");

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

	$required_checked = "";
	$required_options_display = " none ";
	if ($options["is_required"]) {
		$required_checked = " checked='checked' ";
		$required_options_display = " block ";
	}
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
			<div class=''>
				<input value='1' $required_checked type='checkbox' id='plugin_forms_field_added_required_$liID' name='pb_plugin_forms_fields[{$fieldID}][is_required]' />
				<label for='plugin_forms_field_added_required_$liID' class='for-radio'> User must fill in this field</label>
			</div>

			<div>
				<label style='display: $required_options_display' for='plugin_forms_field_added_required_text_$liID'>Text to show next to field (e.g. 'Required')</label>
				<input style='display: $required_options_display' type='text' value='".htmlspecialchars($options["is_required_text"], ENT_COMPAT, "UTF-8")."' name='pb_plugin_forms_fields[{$fieldID}][is_required_text]' id='plugin_forms_field_added_required_text_$liID' />
			</div>

			<div>
				<label style='display: $required_options_display' for='plugin_forms_field_added_required_text_reminder_$liID'>Text to show if required value is missing (e.g. 'Please enter a value')</label>
				<input style='display: $required_options_display' type='text' value='".htmlspecialchars($options["is_required_text_reminder"], ENT_COMPAT, "UTF-8")."' name='pb_plugin_forms_fields[{$fieldID}][is_required_text_reminder]' id='plugin_forms_field_added_required_text_reminder_$liID' />
			</div>
			
			<input type='hidden' name='pb_plugin_forms_fields[{$fieldID}][isDeleted]' value='0' />
		</li>

		<script type='text/javascript'>
			//$('#plugin_forms_field_added_{$liID}_input').overLabel();
			$('#plugin_forms_field_added_multichoice_{$liID}').overLabel();
			$('#plugin_forms_field_added_select_type_{$liID}').change(function() {
				var val = $(this).find(':selected').val();
				if (val == 'multichoice') {
					$(this).parents('li').find('.plugin_forms_field_added_multichoice_textarea_wrapper').slideDown();
				} else {
					$(this).parents('li').find('.plugin_forms_field_added_multichoice_textarea_wrapper').slideUp();
				}
			});
			//$('#plugin_forms_field_added_required_text_$liID').overLabel();
			//$('#plugin_forms_field_added_required_text_reminder_$liID').overLabel();
			$('#plugin_forms_field_added_required_$liID').change(function() {
				if ($(this).attr('checked')) {
					$('#plugin_forms_field_added_required_text_$liID').show('fast');
					$('#plugin_forms_field_added_required_text_reminder_$liID').show('fast');
					$('label[for=plugin_forms_field_added_required_text_$liID]').show('fast');
					$('label[for=plugin_forms_field_added_required_text_reminder_$liID]').show('fast');
				} else {
					$('#plugin_forms_field_added_required_text_$liID').hide('fast');
					$('#plugin_forms_field_added_required_text_reminder_$liID').hide('fast');
					$('label[for=plugin_forms_field_added_required_text_$liID]').hide('fast');
					$('label[for=plugin_forms_field_added_required_text_reminder_$liID]').hide('fast');
				}
				
			});		
		</script>

	";
	
	return $out;
	
}


?>