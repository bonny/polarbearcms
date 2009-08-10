<?php
require_once("../polarbear-boot.php");
$page_class = "polarbear-page-fields";
$action = $_REQUEST["action"];

/**
 * laddar in ett fält
 */
function polarbear_settings_fields_fieldsLoadOne($fieldID) {
	// $fieldID
	if (is_numeric($fieldID)){
		global $polarbear_db;
		$sql = "SELECT * from " . POLARBEAR_DB_PREFIX . "_fields WHERE id = $fieldID";
		$row = $polarbear_db->get_row($sql);
		$content = unserialize($row->content);
	} else {
		$content = array();
	}
	?>
	<li>
		<input type="hidden" name="fields[<?php echo $fieldID ?>][fieldID]" value="<?php echo $fieldID ?>" />
		<div class="delete">
			<a href="#">Delete</a>
			<input type="hidden" name="fields[<?php echo $fieldID ?>][deleted]" value="0" />
		</div>
		<div>
			<label>Name</label>
			<input name="fields[<?php echo $fieldID ?>][fieldName]" class="text" type="text" value="<?php echo htmlentities($row->name, ENT_COMPAT, "UTF-8") ?>" />
		</div>
		<div>
			<label>Type</label>
			<select name="fields[<?php echo $fieldID ?>][fieldType]">
				<option <?php print ($row->type == "text") ? "selected='selected'" : "" ?> value="text">Text</option>
				<option <?php print ($row->type == "textarea") ? "selected='selected'" : "" ?> value="textarea">Textarea</option>
				<option <?php print ($row->type == "html") ? "selected='selected'" : "" ?> value="html">HTML</option>
				<option <?php print ($row->type == "multichoice") ? "selected='selected'" : "" ?> value="multichoice">Multi choice</option>
				<option <?php print ($row->type == "file") ? "selected='selected'" : "" ?> value="file">File</option>
				<option <?php print ($row->type == "image") ? "selected='selected'" : "" ?> value="image">Image</option>
			</select>
		</div>
		<div class="fieldMultichoiceChoices" style="<?php print ($row->type == "multichoice") ? "" : "display: none" ?>">
			<label>Choices for multi choice</label>
			<textarea name="fields[<?php echo $fieldID ?>][fieldMultichoiceChoices]" cols="25" rows="10"><?php echo htmlentities($content["multichoiceChoices"], ENT_COMPAT, "UTF-8"); ?></textarea>
		</div>
	</li>
	<?php
}


function polarbear_settings_connectors_connectorLoadOne($collectionID) {
	global $polarbear_db;
	$collectionName = $polarbear_db->get_var("SELECT name FROM " . POLARBEAR_DB_PREFIX . "_fields_collections WHERE id = $collectionID");
	?>
		<li>
			<?php echo $collectionName ?>
			<a class='polarbear_fields_connectoredit_trash' href='#'><img src='images/silkicons/bin_closed.png' alt='Trashcan' /></a>
			<input type='hidden' name='collections[]' value='<?php echo $collectionID ?>' />
		</li>
	<?php
}

if ($action == "connectorLoad") {
	$collectionID = $_GET["collectionID"];
	polarbear_settings_connectors_connectorLoadOne($collectionID);
	exit;
}


// save field connector
if ($action == "fieldConnectorSave") {

	$fieldConnectorID = (int) $_POST["fieldConnectorID"];
	$name = mysql_real_escape_string($_POST["name"]);
	$delete = (int) $_POST["delete"];
	
	if ($fieldConnectorID) {
		$sql = "UPDATE polarbear_fields_connectors SET ";
		$sqlWhere = " WHERE id = $fieldConnectorID";
	} else {
		$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_fields_connectors SET ";
		$sqlWhere = "";
	}
	$sql .= " name = '$name', ";
	$sql .= " deleted = $delete ";
	$sql .= $sqlWhere;
	$polarbear_db->query($sql);
	
	if (!$fieldConnectorID) {
		$fieldConnectorID = (int) $polarbear_db->insert_id;
	}
	
	// attach...
	$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_fields_link_connectors_collections WHERE fieldConnectorID = $fieldConnectorID";
	$polarbear_db->query($sql);
	
	$collections = $_POST["collections"];
	if (is_array($collections)) {
		$prio = 0;
		foreach ($collections as $collectionID) {
			$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_fields_link_connectors_collections set prio = $prio, fieldConnectorID = $fieldConnectorID, fieldCollectionID = " . (int) $collectionID;
			$polarbear_db->query($sql);
			$prio++;
		}
	}

	$action = "";
	$okmsg = urlencode("Saved Field connector");
	header("Location: " . polarbear_treepage("gui/fields.php?okmsg=$okmsg"));
	exit;
}

// save fieldCollection
if ($action == "fieldCollectionEditSave") {
	// polarbear_d($_POST);
	/*
	spara ny eller befintlig fältsamling
	dvs. spara fältsamling + alla fält
	*/
	$fieldCollectionID = (int) $_POST["fieldCollectionID"];
	$repeatable = (int) $_POST["repeatable"];
	$deleted = (int) $_POST["deleted"];
	$name = mysql_real_escape_string($_POST["name"]);
	
	if ($fieldCollectionID) {
		// existing
		$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_fields_collections SET ";
		$sqlWhere = " WHERE id = $fieldCollectionID";
	} else {
		// new
		$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_fields_collections SET ";
		$sqlWhere = "";
	}
	$sql .= " repeatable = $repeatable, ";
	$sql .= " deleted = $deleted, ";
	$sql .= " name = '$name'";
	$sql .= $sqlWhere;
	$polarbear_db->query($sql);
	// echo $sql;
	if (!$fieldCollectionID) {
		$fieldCollectionID = $polarbear_db->insert_id;
	}

	// add/update the fields belonging to this fieldcollection
	$fieldPrio = 0;
	if (!empty($_POST["fields"])) {
		foreach ($_POST["fields"] as $oneField) {
			$fieldID = $oneField["fieldID"];
			$deleted = (int) $oneField["deleted"];
			$fieldName = mysql_real_escape_string($oneField["fieldName"]);
			$fieldType = $oneField["fieldType"];
			$fieldMultichoiceChoices = $oneField["fieldMultichoiceChoices"];
			if (is_numeric($fieldID)) {
				$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_fields SET ";
				$sqlWhere = " WHERE id = $fieldID";
			} else {
				$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_fields SET ";
				$sqlWhere = "";
			}
			$sql .= " name = '$fieldName', ";
			$sql .= " type = '$fieldType', ";
			$sql .= " fieldcollectionID = $fieldCollectionID, ";
			$sql .= " deleted = '$deleted', ";
			$sql .= " prio = '$fieldPrio' ";
			$sql .= $sqlWhere;
			// echo $sql;exit;
			$polarbear_db->query($sql);
		
			if (!is_numeric($fieldID)) {
				$fieldID = $polarbear_db->insert_id;
				$arrContent = array();
			} else {
				$content = $polarbear_db->get_var("select content from " . POLARBEAR_DB_PREFIX . "_fields where id = $fieldID");
				$arrContent = unserialize($content);
				if (!$arrContent) {
					$arrContent = array();
				}
			}
			$arrContent["multichoiceChoices"] = $fieldMultichoiceChoices;
			// spara mer info/extrainfo om ett fält. t.ex. multichoice-valen sparas här
			$contentForSQL = mysql_real_escape_string(serialize($arrContent));
			$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_fields set content = '$contentForSQL' WHERE id = $fieldID";
			$polarbear_db->query($sql);
				
			$fieldPrio++;
		}
	}
	
	$okmsg = urlencode("Saved");
	header("Location: " . polarbear_treepage("gui/fields.php?okmsg=$okmsg"));
	exit;
}

// visa redigeringsinputs för ny el. befintligt fält
if ($action == "fieldLoad"){
	$numNewFields = $_GET["numNewFields"];
	$fieldID = "fieldNew-" . $numNewFields;
	polarbear_settings_fields_fieldsLoadOne($fieldID);
	exit;
} // fieldload

// $skip_layout = true;
// require_once("includes/admin-header.php");

?>
<style type="text/css">
	#fieldEditFields li {
		border-top: 1px solid #aaa;
		border-bottom: 1px solid #aaa;
		margin-bottom: -1px;
		padding: 1em;
		background: white;
	}
	#fieldEditFields li:hover {
		background: lightyellow;
		cursor: move;
	}
	#fieldEditFields .delete {
		float: right;
	}
</style>

<script type="text/javascript">
	var numNewFields = 0;
	$(function(){
		$("#fieldEditFields ul").sortable();

		// add field link
		$("#fieldEditAdd").click(function(){
			numNewFields++;
			$.get("gui/fields.php", { action: "fieldLoad", numNewFields: numNewFields }, function(data){
				$("#fieldEditFields ul").append(data);
				$("#fieldEditFields li:last input[type=text]:first").focus();
			});
		});
		
		// option drop down
		$("#fieldEditFields ul select").live("change", function() {
			if ($(this).val() == "multichoice") {
				$(this).parents("li").find(".fieldMultichoiceChoices").fadeIn("slow");
			} else {
				$(this).parents("li").find(".fieldMultichoiceChoices").fadeOut("slow");
			}
		});

		// delete field
		$("#fieldEditFields .delete a").live("click", function() {
			if (confirm("Delete?")) {
				$(this).next("input").val(1);
				$(this).closest("li").fadeOut("slow");
			}
		});
		
		// add collection to connector
		$("#polarbear_fields_connectoredit_select_collection").change(function() {
			if (this.value == "") {
				return false;
			}
			var t = $(this);
			$.get("gui/fields.php", { action: "connectorLoad", collectionID: $("#polarbear_fields_connectoredit_select_collection option:selected").val() }, function(data) {
				$("#polarbear_fields_connectoredit_selected_collections").append(data).find("li:last").effect("highlight", {}, 3000);
			});
		});
		$("#polarbear_fields_connectoredit_selected_collections").sortable();
		$(".polarbear_fields_connectoredit_trash").live("click", function() {
			if (confirm("Remove?")) {
				$(this).closest("li").hide("slow", function() {
					$(this).remove();
				});
			}
		});

	});
</script>
<?php

if ($action == "fieldCollectionEdit"){
	
	$fieldCollectionID = (int) $_GET["fieldCollectionID"];
	
	if ($fieldCollectionID){
		$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_collections WHERE id = $fieldCollectionID and deleted = 0";
		if ($row = $polarbear_db->get_row($sql)) {
			// polarbear_d($row);
		} else {
			die("Error: collection not found");
		}
	} else {
	}
	
	?>
	<form method="post" action="gui/fields.php">
		<h2>Field group collection</h2>
		<p>
			<label>Name</label>
			<input type="text" name="name" value="<?php echo htmlentities($row->name, ENT_COMPAT, "UTF-8") ?>" />
		</p>
		
		<h3>Fields</h3>
		<p><a href="#" id="fieldEditAdd">Add</a></p>
		<div id="fieldEditFields"><ul><?php
			// om fält finns, lägg till
			$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields WHERE fieldcollectionID = $fieldCollectionID AND deleted = 0 ORDER BY prio ASC";
			if ($r = $polarbear_db->get_results($sql)) {
				foreach ($r as $rowField){
					polarbear_settings_fields_fieldsLoadOne($rowField->id);
				}
			}
		?></ul></div>

		<p>
			<input <?php print ($row->repeatable) ? "checked='checked'" : "" ?> type="checkbox" value="1" name="repeatable" id="cbRepeatable" /><label for="cbRepeatable"> Repeatable</label>
		</p>
		<p>
			<input <?php print ($row->deleted) ? "checked='checked'" : "" ?> type="checkbox" value="1" name="deleted" id="cbDelete" /><label for="cbDelete"> Delete</label>
		</p>
		<p>
			<input type="submit" value="Save" />
			or <a href="<?php echo polarbear_treepage("gui/fields.php") ?>">Cancel</a>
			<input type="hidden" name="action" value="fieldCollectionEditSave" />
			<input type="hidden" name="fieldCollectionID" value="<?php echo $fieldCollectionID ?>" />
		</p>
	</form>
	
	<?php
	

} else if ($action == "fieldConnectorEdit") {

	$fieldConnectorID = (int) $_GET["fieldConnectorID"];
	if ($fieldConnectorID) {
		$row = $polarbear_db->get_row("SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_connectors WHERE id = $fieldConnectorID");
	}
	?>
	<h2>Edit field connector</h2>
	<form method="post" action="gui/fields.php" id="polarbear_fields_connectoredit_form">
	<p>
		<label>Name</label>
		<input type="text" value="<?php echo htmlentities($row->name, ENT_COMPAT, "UTF-8"); ?>" name="name" />
	</p>
	<?php
	/*
	Lägga till, ta bort, sortera field collections
	*/
	// get existing field collections
	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_collections WHERE deleted = 0 ORDER BY name ASC";
	if ($r = $polarbear_db->get_results($sql)) {
		echo "<p><label>Add collection </label><select id='polarbear_fields_connectoredit_select_collection'><option value=''>Select...</option>";
		foreach ($r as $row2) {
			echo "<option value='$row2->id'>$row2->name</option>";
		}
		echo "</select></p>";
	} else {
	
	}
	?>
	<ul id="polarbear_fields_connectoredit_selected_collections">
		<?php
		$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_link_connectors_collections WHERE fieldConnectorID = $fieldConnectorID ORDER BY prio ASC";
		if ($r = $polarbear_db->get_results($sql)) {
			foreach ($r as $row2) {
				polarbear_settings_connectors_connectorLoadOne($row2->fieldCollectionID);
			}
		}
		?>
	</ul>
	<p>
		<input <?php print ($row->delete) ? "checked='checked'" : "" ?> type="checkbox" value="1" name="delete" id="cbRepeatable" /><label for="cbRepeatable"> Delete</label>
	</p>
	<p>
		<input type="submit" value="Save" /> or <a href="<?php echo polarbear_treepage("gui/fields.php") ?>">Cancel</a>
		<input type="hidden" name="action" value="fieldConnectorSave">
		<input type="hidden" name="fieldConnectorID" value="<?php echo $fieldConnectorID ?>">
	</p>
	</form>
	<?php
}

if (!$action){
	?>

	<h1>Fields</h1>

	<?php
	polarbear_infomsg($_GET["okmsg"], $errmsg);
	?>
	<h2>Article Field Connectors</h2>
	<p>+ <a href="<?php echo polarbear_treepage("gui/fields.php?action=fieldConnectorEdit&fieldConnectorID=0") ?>">New</a></p>
	<?php
	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_connectors WHERE deleted = 0 ORDER BY name ASC";
	if ($r = $polarbear_db->get_results($sql)) {
		echo "<ul>";
		foreach ($r as $row) {
			echo "<li><a href='" . polarbear_treepage("gui/fields.php?action=fieldConnectorEdit&fieldConnectorID=$row->id") . "'>$row->name</a></li>";
		}
		echo "</ul>";
	}
	?>

	<h2>Field collections</h2>
	<p>+ <a href="<?php echo polarbear_treepage("gui/fields.php?action=fieldCollectionEdit&fieldCollectionID=0")?>">New</a></p>
	<?php
	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_collections WHERE deleted = 0 ORDER BY name ASC";
	if ($r = $polarbear_db->get_results($sql)) {
		echo "<ul>";
		foreach ($r as $row) {
			echo "<li><a href='" . polarbear_treepage("gui/fields.php?action=fieldCollectionEdit&fieldCollectionID=$row->id") . "'>$row->name</a></li>";
		}
		echo "</ul>";
	}

}


#require ("includes/admin-footer.php");
?>