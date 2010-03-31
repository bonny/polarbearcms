<?php
require_once("polarbear-boot.php");
polarbear_require_admin();
require_once("includes/php/admin-header.php");
?>

	<div class="ui-layout-west">
		<?php require("includes/php/admin-menu.php"); ?>
	</div>

	<div class="ui-layout-center">
		<div id="polarbear-content-main" class="ui-layout-content"></div>
	</div>

</body>
</html>