<?php
/**
 * The page that is used to add links or images/files in tinymce
 * 
 */
$skip_menu = true;
$skip_layout = true;
$page_class = "polarbear-page-fileBrowser";
require_once("../polarbear-boot.php");

if ($_GET["action"] == "getArticleURL") {
	$articleID = $_GET["articleID"];
	$articleID = str_replace("article-", "", $articleID);
	$a = new PolarBear_Article($articleID);
	echo $a->fullpath();
	exit;
	
}

// dont load tinyMCE. hopefully this speeds up things a little bit
$polarbear_menu_skip = array(
	"tinyMCE"	
);

require_once("../includes/php/admin-header.php");

$field_name = $_GET["field_name"];
$url = $_GET["url"];
$type = $_GET["type"];
?>
<div id="tabs" class="xui-layout-content">
	
	<style type="text/css">
	.tree li a,
	.tree .tree-default li a, .tree .tree-default li span
	{
		background-image:url("<?php polarbear_webpath() ?>includes/tree_component/images/file.png");
		background-image:url("<?php polarbear_webpath() ?>images/silkicons/page_white_text.png");
	}
	.polarbear-page-fileBrowser #polarbear-filebrowser-iframe-files,
	.polarbear-page-fileBrowser #articles-tree
	{
		width: 650px; height: 365px;	
	}
	</style>
	<script type="text/javascript" src="<?php polarbear_webpath(); ?>includes/tiny_mce/tiny_mce_popup.js"></script>
	<script type="text/javascript">
	
		$(function() {

			polarbear_files_onload();
			polarbear_files_create_ajaxlinks("fileBrowser");

			// init tabs
			var $tabs = $('#tabs').tabs();

			// init browser
			var treeOptions = {
				path: "<?php polarbear_webpath() ?>includes/tree_component/",
				data: {
					type	: "json",
					async 	: true,
					url   	: "<?php polarbear_webpath() ?>gui/tree.php?type=filebrowser"
				},
				cookies	: false,
				ui: {
					dots	: true,
					context	: false
				},
				rules: {
					type_attr 	: "rel",
					metadata	: "data",
					use_inline	: true,
					deletable	: "none",
					renameable	: "none",
					creatable	: "none",
					draggable	: "none",
				},
				callback: {
					onchange : polarbear_fileBrowserTreeOnChange
				}
	
			}
			$("#articles-tree").tree(treeOptions);
		});
	
		function polarbear_fileBrowserTreeOnChange(node, tree) {
			var articleName = $(node).text();
			var articleID = node.id;
		
			var url = articleName;
	
			// get article url
			$.get("<?php polarbear_webpath() ?>gui/fileBrowser.php", {action: "getArticleURL", articleID: articleID }, function(data) {
				tinyUpdate(data);
			});
			
		}


		function tinyUpdate(url) {

			var win = tinyMCEPopup.getWindowArg("window");
			var input = tinyMCEPopup.getWindowArg("input");
	
			win.document.getElementById(input).value = url;
	
			// are we an image browser
	        if (typeof(win.ImageDialog) != "undefined")
	        {
	            // we are, so update image dimensions and preview if necessary
	            if (win.ImageDialog.getImageData) win.ImageDialog.getImageData();
	            if (win.ImageDialog.showPreviewImage) win.ImageDialog.showPreviewImage(url);
	        }
	
	       tinyMCEPopup.close();
	
		}
	
	</script>

<?php 
// image
// file
// media

// om inte visa file, ta bort tabbarna
if ($type!="file") {
	$disableTabs = true;
}
?>
	<?php if ($type=="file") { ?>
		<ul>
			<li><a href="#tabs-articles">Articles</a></li>
			<li><a href="#tabs-files">Images/Files</a></li>
		</ul>
		<div id="tabs-articles">
			<div id="articles-tree">Loading articles...</div>
		</div>
	<?php } ?>

	<div id="tabs-files">
		<?php polarbear_files_get_page_content($_GET); ?>
	</div>

</div> <!-- // layout-content -->

<?php
require_once(POLARBEAR_ROOT . "/includes/php/admin-footer.php");
?>