<?php
require_once(realpath(dirname( __FILE__ ).'/../../')."/polarbear-boot.php");
polarbear_require_admin();
header('Content-type: text/html; charset="utf-8"');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>PolarBear CMS</title>
	<meta name="robots" content="noindex" />
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>gui/combine.php?type=javascript&amp;files=jquery.jeditable.mini.js,jquery.cookie.js,jquery.listen.js,jquery.metadata.js,jquery.blockUI.js,jquery.layout-1.3.rc4.2.js,jquery.timePicker.js,jquery.query-2.1.6.js,swfobject.js,jquery.alerts.js"></script>
	<!-- <script type="text/javascript" src="<?php polarbear_webpath() ?>includes/tree_component/tree_component.js"></script> -->
	<!-- <script type="text/javascript" src="<?php polarbear_webpath() ?>includes/tree_component/css.js"></script> -->
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/jstree/jquery.tree.min.js"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/jstree/plugins/jquery.tree.cookie.js"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/js/global.js.php"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/js/articles.js.php"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/js/files.js.php"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/js/pb.tags.js.php"></script>
	<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/jquery.uploadify/jquery.uploadify.js"></script>

	<style type="text/css" media="all">
		@import url(http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css);
		@import url(<?php polarbear_webpath() ?>includes/tree_component/tree_component.css);
		@import url(<?php polarbear_webpath() ?>includes/tree_component/themes/default/style.css);
	</style>

	<link rel="stylesheet" type="text/css" href="<?php polarbear_webpath() ?>gui/combine.php?type=css&amp;files=reset.css,files.css,timePicker.css,styles.css" />
	
	<?php
	if (!@in_array("tinyMCE", $polarbear_menu_skip)) {
		?>
		<script type="text/javascript" src="<?php polarbear_webpath() ?>includes/tiny_mce/tiny_mce_gzip.js"></script>
		<script type="text/javascript">
			tinyMCE_GZ.init({
				plugins : 'table,advimage,advlink,media,searchreplace,print,contextmenu,paste,visualchars,nonbreaking,xhtmlxtras,inlinepopups,tabfocus',
				themes : 'simple,advanced',
				languages : 'en',
				disk_cache : true,
				debug : false
			});
		</script>
		<?php
		}
	?>
	<script type="text/javascript">
		/* Skapa layout som är gemensam för alla sidor */
		$(function() {
			<?php if (!isset($skip_layout) || !$skip_layout) { ?>
				$("body").layout({
					applyDefaultStyles: true,
					defaults: {
						spacing_closed: 5,
						spacing_open: 5,
						togglerLength_closed: 35,
						togglerLength_open: 35,
						resizable: false,
						slidable: false
					},
					west: {
						size: 275
					}
				});
			<?php } ?>
		});
		var treepage = "";
		<?php
		if (isset($_GET["treepage"]) && $_GET["treepage"]) {
			?>treepage = "<?php echo $_GET["treepage"] ?>";<?php
		} elseif (isset($treepage)) {
			?>treepage = "<?php echo $treepage ?>";<?php
		}
		?>
		
	</script>
</head>                            

<body class="<?php print (isset($page_class)) ? $page_class : "" ?>">

<div class="ui-layout-ignore" id="pb-message">
	<p>I'm a message!</p>
</div>