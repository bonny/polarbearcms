<?php
/**
 * hanterar filer och bilder
 * låter användaren se bilder, sortera, ändra namn och lägga till/ta bort taggar
 * todo: lite cleanup
 */
 
$page_class = "polarbear-page-files";
require_once("../polarbear-boot.php");

/**
 * little helper for our ajax call to get the inner table
 */
if ($_POST["action"] == 'getFilesTable') {
	header('Content-Type: text/html; charset=utf-8'); 
	polarbear_files_get_page_content($_GET);
	exit;
}

/**
 * little helper for our ajax call to get the tags in the nav
 */
if ($_POST["action"] == 'getNavTags') {
	header('Content-Type: text/html; charset=utf-8'); 
	$selectedTag = $_POST["selectedTag"];
	$selectedSort = $_POST["selectedSort"];
	polarbear_files_get_tags_content($selectedTag, $selectedSort);
	exit;
}


if ($_POST['action'] == 'getTagActionsDiv') {
	header('Content-Type: text/html; charset=utf-8'); 
	$fileID = $_POST["fileID"];
	$tagName = trim($_POST["tagName"]);
	$f = new PolarBear_File($fileID);
	$arrTags = $f->arrTags;
	$out = "<ul>";
	$out .= "<li><span class='add'>Add tag</span><ul>";
	$out .= "<li class='the-tags-new-tag'><a href='#'>New tag...</a></li>";
	
	// hämta alla existerande taggar, men ta bort dom som finns till filen redan
	$sql = "SELECT DISTINCT tagName FROM " . POLARBEAR_DB_PREFIX . "_files_tags ORDER BY tagName ASC";
	if ($r = $polarbear_db->get_results($sql)) {
		foreach ($r as $row) {
			if (!in_array($row->tagName, $arrTags)) {
				$tagEscaped = rawurlencode(utf8_decode($row->tagName));
				$out .= "<li><a href='#'>" . htmlspecialchars($row->tagName) . "</a></li>";
			}
		}
	}
	$out .= "</ul></li>";
	
	
	if (!empty($arrTags)) {
		$out .= "<li><span class='remove'>Remove tag</span><ul>";
		foreach ($arrTags as $oneTag) {
			$tagEscaped = rawurlencode(utf8_decode($oneTag));
			$out .= "<li><a href='#'>" . htmlspecialchars($oneTag) . "</a></li>";
		}
		$out .= "</ul>";
		$out .= "</li>";
	}
	
	$out .= "</ul>";

	echo $out;
	exit;
}


if ($_POST['action'] == 'getDropDownForTags') {
	header('Content-Type: text/html; charset=utf-8'); 
	$fileID = $_POST["fileID"];
	$tagName = trim($_POST["tagName"]);
	$f = new PolarBear_File($fileID);
	$arrTags = $f->arrTags;
	$out = "<option value=''>Tags</option>";
	$out .= "<option value=''>Add tag</option>";
	$out .= "<option value='new'>&nbsp;&nbsp;&nbsp;&nbsp;New tag...</option>";
	
	// hämta alla existerande taggar, men ta bort dom som finns till filen redan
	$sql = "SELECT DISTINCT tagName FROM " . POLARBEAR_DB_PREFIX . "_files_tags ORDER BY tagName ASC";
	if ($r = $polarbear_db->get_results($sql)) {
		foreach ($r as $row) {
			if (!in_array($row->tagName, $arrTags)) {
				$tagEscaped = rawurlencode(utf8_decode($row->tagName));
				$out .= "<option value='$tagEscaped'>&nbsp;&nbsp;&nbsp;&nbsp;" . htmlspecialchars($row->tagName) . "</option>";
			}
		}
	}
	
	
	if (!empty($arrTags)) {
		$out .= "<option value=''>Remove tag</option>";
		foreach ($arrTags as $oneTag) {
			$tagEscaped = rawurlencode(utf8_decode($oneTag));
			$out .= "<option value='$tagEscaped'>&nbsp;&nbsp;&nbsp;&nbsp;" . htmlspecialchars($oneTag) . "</option>";
		}
	}
	echo $out;
	exit;
}

if ($_POST['action'] == 'getTagsLinks') {
	$fileID = $_POST['fileID'];
	echo polarbear_files_printTagsLinks($fileID);
	exit;
}

if ($_POST['action'] == 'addTagToFile') {
	// tagName
	// fileID
	$fileID = $_POST["fileID"];
	$tagName = trim($_POST["tagName"]);
	$f = new PolarBear_File($fileID);
	$f->toggleTag($tagName);
	echo "ok";
	exit;
}

/**
 * hämtar texten att redigera när man redigerar ett filnamn
 * måste hämtas såhär för att inte & etc. ska blir &amp; i textrutan
 */
if ($_GET['action'] == 'editNameGetSource') {
	$fileID = $_GET['id'];
	$fileID = str_replace('name-', '', $fileID);
	$f = new PolarBear_File($fileID);
	echo $f->name;
	exit;
}
 
/**
 * spara ett redigera filnamn
 */
if ($_GET['action'] == 'editName') {
	/*
	Array
	(
		[value] => folder.jpg
		[id] => name-162
	)
	*/
	$fileID = $_POST['id'];
	$fileID = str_replace('name-', '', $fileID);
	$f = new PolarBear_File($fileID);
	$f->name = $_POST['value'];
	$f->save();
	echo htmlspecialchars($f->name);
	exit;
}

/**
 * radera fil. körs via ajax från delete-länken
 */
if ($_POST['action'] == 'deleteFile') {
	$fileID = (int) $_POST['fileID'];
	$f = new PolarBear_File($fileID);
	$f->delete();
	exit;
}

/**
 * redigera fil, formulär som hämtas via ajax
 */
if ($_POST['action'] == 'loadFilesEditDiv') {
	$fileID = (int) $_POST['fileID'];
	$f = new PolarBear_File($fileID);
	$nameExtension = $f->getNameExtension();
	$nameWithoutExtension = $f->getNameWithoutExtension();
	?>
	<div class="file-edit">
		<div class="fields">
			<div class="group">
				<label>File name</label>
				<div class="col">
					<label>Name</label>
					<input size="25" type="text" class="text" value="<?php echo $nameWithoutExtension ?>" />
				</div>
				<div class="col">
					<label>Extension</label>
					<input size="3" type="text" class="text" value="<?php echo $nameExtension ?>" />
				</div>				
			</div>
			<div class="group">
				<label>Tags</label>
			</div>
		</div>
	</div>
	<?php
	exit;
}

$skip_menu = true;
$filebrowser_type = $_GET["type"];

?>
	<div class="ui-layout-content polarbear-content-main-inner" id="polarbear-page-files-content">
		<?php
			polarbear_files_get_page_content();
		?>
	</div>

<script type="text/javascript">

	$(function() {
		polarbear_files_onload();
		polarbear_files_create_ajaxlinks("files");
	});

</script>
