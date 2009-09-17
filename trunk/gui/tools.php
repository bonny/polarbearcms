<?php
/**
 * hanterar artiklar
 */
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();

$skip_layout = true;

if ($_GET["action"] == "clearCache") {
	pb_clear_cache_all();
	$okmsg = "The cache has been cleared";
}


pb_must_come_through_tree();

?>
<div class="polarbear-page-tools polarbear-content-main-inner">
	

	<h1>Tools</h1>
	
	<?php
	polarbear_infomsg($okmsg, $errmsg);
	?>
	
	<h2>Cache</h2>
	<?php
	// count number of files/items in the cache
	$d = dir(POLARBEAR_CACHEPATH);
	$itemCount = 0;
	while (false !== ($entry = $d->read())) {
		if ($entry != "." && $entry != "..") {
			$itemCount++;
		}
	}
	$d->close();
	if ($itemCount==0) {
		$strItemCount = "No items";
	} elseif($itemCount == 1) {
		$strItemCount = "One item";
	} else {
		$strItemCount = "$itemCount items";
	}
	?>
	<p><?php echo $strItemCount ?> are stored in the cache.</p>
	<p><a href="<?php echo polarbear_treepage("gui/tools.php?action=clearCache") ?>">Clear cache</a> - don't worry, this is a non destructive action</p>

</div>
