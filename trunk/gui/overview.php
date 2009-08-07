<?php
/**
 * någon form av översiktssida
 */
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();
$page_class = "polarbear-page-overview";
?>

<h1>Overview</h1>

<?php
polarbear_infomsg($_GET["okmsg"], $_GET["errmsg"]);
?>

<?php
// require("includes/admin-footer.php");
?>