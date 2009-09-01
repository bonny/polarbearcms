<?php
/**
 * Owerview
 */
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();

if ($_GET["action"] == "loadMoreActivites") {
	$recentActivitiesPage = $_GET["recentActivitiesPage"];
	#echo $recentActivitiesPage;
	$page = $recentActivitiesPage+1;
	pb_get_recent_activites(array("limitStartPage" => $page));
	echo "
		<script type='text/javascript'>
			recentActivitiesPage = $page;
		</script>
	";
	exit;
}


$page_class = "polarbear-page-overview";
?>

<h1>Overview</h1>

<style>
</style>
<script type="text/javascript">
	var recentActivitiesPage = 0;
	$("a.overview-recent-activities-load-more").live("click", function() {
		$.get("<? polarbear_webpath() ?>gui/overview.php", { "action": "loadMoreActivites", recentActivitiesPage: recentActivitiesPage }, function(data) {
			$(".overview-recent-activities-ajax-container").append("<div style='display: none;' class='overview-recent-activities-load-more-added'>" + data + "</div>");
			$(".overview-recent-activities-load-more-added:last").slideDown("slow");
		});
	});
</script>

<?php

polarbear_infomsg($_GET["okmsg"], $_GET["errmsg"]);

/*
	Log/Recent activites - part of core or plugin?
*/
?><h2>Recent activities</h2><?
echo "<div class='overview-recent-activities'>";

echo pb_get_recent_activites();

function pb_get_recent_activites($options = null) {
	global $polarbear_db;
	$defaults = array(
		"limitStartPage" => 0,
		"limitCount" => 10
	);
	$options = polarbear_extend($defaults, $options);
	$limitCount = 10;
	$limitStart = $options["limitStartPage"] * $options["limitCount"];
	$sql = "SELECT date, UNIX_TIMESTAMP(date) as dateUnix, user, type, objectType, objectID, objectName FROM " . POLARBEAR_DB_PREFIX . "_log ORDER BY date DESC LIMIT $limitStart, $limitCount";
	$r = $polarbear_db->get_results($sql);
	if ($r) {
		$prevDay = null;
		echo "<ul>";
		foreach ($r as $row) {

			if ($prevDay != date("Y-m-d", $row->dateUnix)) {
				$prevDay = date("Y-m-d", $row->dateUnix);
				if (date("Y-m-d", $row->dateUnix) == date("Y-m-d", strtotime("today"))) {
					$prettyWhen = polarbear_msg("Today");
				} elseif (date("Y-m-d", $row->dateUnix) == date("Y-m-d", strtotime("yesterday"))) {
					$prettyWhen = polarbear_msg("Yesterday");
				} else {
					$prettyWhen = polarbear_time2str(date("Y-m-d", $row->dateUnix));
				}
				echo "<li><h3>" . strftime("%A, %B %e", $row->dateUnix) . " ($prettyWhen)</h3></li>";
			}
			$time = date("H:i", $row->dateUnix);
			$details = "";
			$icon = "";
			$link = "";
			if ($row->user) {
				$user = polarbear_user::getInstance($row->user);
			} else {
				$user = "Unknown user";
			}
			$actionType = $row->type;
			if ($row->type == "create") {
				#$icon = "page_white_add.png";
				$icon = "add.png";
				$actionType = "created";
				if ($row->objectType == "file") { $actionType = "uploaded";	}
			} elseif ($row->type == "update") {
				#$icon = "page_white_edit.png";
				$icon = "pencil.png";
				$actionType = "modified";
			} elseif ($row->type == "delete") {
				#$icon = "page_white_delete.png";
				#$icon = "bullet_delete.png";
				$icon = "	delete.png";
				$actionType = "deleted";
			}
			$objectName = "Unknown object";
			$actionObjectType = "";
			switch ($row->objectType) {
				case "article":
					$a = polarbear_article::getInstance($row->objectID);
					$a->load($a->getId(), true);
					#$objectName = $a->getTitleArticle();
					$objectName = $f->name;
					#$icon = "page_white_text_edit.png";
					$link = $a->fullpath();
					$actionObjectType = "article";
					break;
				case "file":
					$f = polarbear_file::getInstance($row->objectID);
					$objectName = $f->name;
					$link = $f->getSrc();
					#$icon = "page_white_edit.png";
					#$icon = "folder_edit.png";
					$actionObjectType = "file";
					break;
				case "user":
					$u = polarbear_user::getInstance($row->objectID);
					$objectName = $u;
					$actionObjectType = "user";
					#$icon = "user_green.png";
					break;
			}
			if (!empty($row->objectName)) {
				$objectName = $row->objectName;
			}
			
			// lägg till details, t.ex. om bild så visa bilden
			if ($row->objectType == "file" && ($row->type == "create" || $row->type == "update") && $f->isImage()) {
				$details = "<span><img src='" . $f->getSrc("w=75&h=75") . "' alt='' /></span>";
			}
			
			if ($icon) {
				$icon = "<img src='" . POLARBEAR_WEBPATH . "images/silkicons/$icon' alt='' />";
			}
			if ($link) {
				$objectName = "<a target='_blank' href='$link' title='Open this object in a new window/tab'>$objectName</a>";
			}
	
			echo "
				<li>
					$icon 
					$time $user $actionType $actionObjectType $objectName
				</li>
			";
		}
		echo "</ul>";
			
		if ($options["limitStartPage"] == 0) {
			echo "<div class='overview-recent-activities-ajax-container'></div>";
			echo "<p><a href='#' class='overview-recent-activities-load-more'>More recent activities</a></p>";
		}
		
	} else {
		
		// no activity
		if ($options["limitStartPage"] == 0) {
			echo "<p>Welcome to PolarBear! Start using the system and your recent activities will be shown here.</p>";
		} elseif ($options["limitStartPage"] > 0) {
			echo "<p>No more activites</p>";
		}
		
	}

}

echo "<div>";

?>