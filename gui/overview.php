<?php
/**
 * Overview
 */
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
require realpath(dirname(__FILE__)."/../") . "/includes/php/gapi/gapi.class.php";
polarbear_require_admin();

if ($_GET["action"] == "loadMoreActivites") {
	$recentActivitiesPage = $_GET["recentActivitiesPage"];
	$page = $recentActivitiesPage+1;
	pb_get_recent_activites(array("limitStartPage" => $page));
	echo "
		<script type='text/javascript'>
			recentActivitiesPage = $page;
		</script>
	";
	exit;
}

if ($_POST["action"] == "loadGaAnalytics") {
	pb_get_ga_statistics();
	exit;
}

$page_class = "polarbear-page-overview";
pb_must_come_through_tree();
?>

<div class="polarbear-content-main-inner">

<h1>Overview</h1>

<script type="text/javascript">
	var recentActivitiesPage = 0;
	$("a.overview-recent-activities-load-more").live("click", function() {
		$.get("<?php polarbear_webpath() ?>gui/overview.php", { "action": "loadMoreActivites", recentActivitiesPage: recentActivitiesPage }, function(data) {
			$(".overview-recent-activities-ajax-container").append("<div style='display: none;' class='overview-recent-activities-load-more-added'>" + data + "</div>");
			$(".overview-recent-activities-load-more-added:last").slideDown("slow");
		});
	});
	
	/*
		on load = load statistics via ajax
		(because it can take a while to fetch them from GA)
	*/
	$(function() {
		$(".overview-statistics-content").hide().load("<?php polarbear_webpath() ?>gui/overview.php", { "action": "loadGaAnalytics" }, function() {
			$(".overview-statistics-loading").hide();
			$("#overview-statistics-tabs").tabs();
			$(".overview-statistics-content").fadeIn("slow");
		});
	});
	
	
</script>

<?php

polarbear_infomsg($_GET["okmsg"], $_GET["errmsg"]);

/*
	Log/Recent activites - part of core or plugin?
*/
?>

<h2>Recent activities</h2>
<div class="overview-recent-activities">
	<?php echo pb_get_recent_activites(); ?>
</div>

<?php
$gaID = polarbear_setting("GoogleAnalyticsReportID");
if (!empty($gaID)) {
	?>
	<h2>Statistics</h2>
	<div class="overview-statistics">
		<div class="overview-statistics-loading">
			<img src="<?php polarbear_webpath() ?>images/loading.gif" alt="" />
			Fetching data from Google Analytics...
		</div>
		<div class="overview-statistics-content"></div>
	</div>
	<?php
}


?>
</div>
<?php

/**
 * statistics from Google Analytics
 * For available dimensions and metrics: 
 * http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
 */

function pb_get_ga_statistics() {

	$gaID = polarbear_setting("GoogleAnalyticsReportID");
	$gaEmail = polarbear_setting("GoogleAnalyticsEmail");
	$gaPassword = polarbear_setting("GoogleAnalyticsPassword");
	$maxAge = 3600; // seconds to store data in cache. 3600 = one hour
	
	if (empty($gaID) || empty($gaEmail) || empty($gaPassword)) {
		echo "<p>Can not load statistics: no settings found.</p>";
		return false;
	}

	$ga = polarbear_storage_get("pb_google_analytics_GAPI_1", $expired); // get
	if ($expired) {
		$ga = polarbear_storage("pb_google_analytics_GAPI_1", new gapi($gaEmail, $gaPassword), $maxAge); // store
	}

	$gaTmp = polarbear_storage_get("pb_google_analytics_GAPI_date", $expired);
	if ($expired) {
		$ga->requestReportData($gaID,array('date'),array('pageviews','visits','uniquePageviews',"timeOnPage","timeOnSite","bounces"), array("date"));
		$ga = polarbear_storage("pb_google_analytics_GAPI_date", $ga, $maxAge);
	} else {
		$ga = $gaTmp;
	}

	$maxVisitsPerDay = 0;
	$chartData = "";
	$arrDays = array();
	$loopNum = 0;
	foreach($ga->getResults() as $result):
		if ($result->getVisits() > $maxVisitsPerDay) {
			$maxVisitsPerDay = $result->getVisits();
		}
		$chartData .= $result->getVisits() . ",";
		// don't add all days, just every..eh.. fifth?
		if ($loopNum % 5 == 0) {
			$day = strftime("%b %e", strtotime($result));
			$arrDays[] = $day;
		}
		$loopNum++;
	endforeach;
	#$arrDays = array_reverse($arrDays);
	// also add the last day fetched
	$day = strftime("%b %e", strtotime($result));
	$arrDays[] = $day;
	$labelDay = "|" . implode($arrDays, "|");

	// Generate chart with visits per day
	$chartData = preg_replace("/,$/", "", $chartData);
	$chartImg = "http://chart.apis.google.com/chart?";
	$chartImg .= "chs=600x125&cht=lc"; // chart type and size
	$chartImg .= "&chm=B,e6f2fa,0,0.0,0.0"; // blue solid fill
	//$chartImg .= "|N,666666,0,-1,10,0"; // label on each day
	$chartImg .= "&chco=0077cc"; // colors
	$chartImg .= "&chd=t:$chartData"; // data
	$chartImg .= "&chds=0,$maxVisitsPerDay"; // min and max
	$chartImg .= "&chg=25,50"; // grid lines
	$chartImg .= "&chxt=x,y,r"; // labels
	$labelVisits = "||" . ceil($maxVisitsPerDay/2) . "|" . $maxVisitsPerDay;
	$chartImg .= "&chxl=0:{$labelDay}|1:{$labelVisits}|2:{$labelVisits}";

	if ($ga->getVisits()>0) {
		$bounceRate = round(($ga->getBounces() / $ga->getVisits())*100,2);		
		$pagesPerVisit = $ga->getPageviews() / $ga->getVisits();
		$avgTimeOnSite = ceil($ga->getTimeOnSite() / $ga->getVisits());
	} else {
		$bounceRate = 0;
		$pagesPerVisit = 0;
		$avgTimeOnSite = 0;
	}
	
	$visits = $ga->getVisits();
	$pageviews = $ga->getPageviews();
	
	?>
		<p class="stats">
			The last 30 days <?php polarbear_domain() ?> had 
			<em><?php echo $visits ?> visits</em>
			and <em><?php echo $pageviews ?> pageviews</em>.
			That's about <em><?php echo round($pagesPerVisit, 2) ?> pages per visit</em>.
		</p>
		<p class="stats">
			<em>Average time on site was <?php echo $avgTimeOnSite ?> seconds</em> 
			and the <em>bounce rate was <?php echo $bounceRate ?>%</em>.
		</p>
	
		<h3>Visits per day</h3>	
		<p><img src="<?php echo $chartImg ?>" alt="Chart showing visitor count for the last 30 days" /></p>
	
	
		<div id="overview-statistics-tabs">
			<ul>
				<li><a href="#overview-statistics-top-content"><span>Top Content</span></a></li>
				<li><a href="#overview-statistics-keywords"><span>Top Keywords</span></a></li>
				<li><a href="#overview-statistics-sources"><span>Top Sources</span></a></li>
				<li><a href="#overview-statistics-medium"><span>Medium</span></a></li>
			</ul>
			<div id="overview-statistics-top-content">
				<table>
					<tr>
						<th>Page</th>
						<th>Pageviews</th>
					</tr>
					<?php
					// info about most visited pages

					$gaTmp = polarbear_storage_get("pb_google_analytics_GAPI_pagePath", $expired);
					if ($expired) {
						$ga->requestReportData($gaID,array('pagePath'),array('pageviews'), "-pageviews");
						$ga = polarbear_storage("pb_google_analytics_GAPI_pagePath", $ga, $maxAge);
					} else {
						$ga = $gaTmp;
					}

					$loopNum = 0;
					foreach($ga->getResults() as $result):
						if ($loopNum>=10) { break; }
						echo "<tr>";
						echo "<td>$result</td>";
						echo "<td>" . $result->getPageviews() . "</td>";
						echo "</tr>";
						$loopNum++;
					endforeach;
					?>
				</table>
			</div>
			<div id="overview-statistics-keywords">
				<table>
					<tr>
						<th>Keyword</th>
						<th>Pageviews</th>
					</tr>
					<?php
					// search keywords
					$gaTmp = polarbear_storage_get("pb_google_analytics_GAPI_keyword", $expired);
					if ($expired) {
						$ga->requestReportData($gaID,array('keyword'),array('pageviews'), array("-pageviews"));
						$ga = polarbear_storage("pb_google_analytics_GAPI_keyword", $ga, $maxAge);
					} else {
						$ga = $gaTmp;
					}

					$loopNum = 0;
					foreach($ga->getResults() as $result) {
						if ($result == "(not set)") { continue; }
						if ($loopNum > 10) { break; }
						echo "<tr>";
						echo "<td>$result</td>";
						echo "<td>" . $result->getPageviews() . "</tr>";
						echo "</tr>";
						$loopNum++;
					}
					?>
				</table>
			
			</div>
			<div id="overview-statistics-sources">
				<table>
					<tr>
						<th>Source</th>
						<th>Pageviews</th>
					</tr>
					<?php
					// referers
					$gaTmp = polarbear_storage_get("pb_google_analytics_GAPI_source", $expired);
					if ($expired) {
						$ga->requestReportData($gaID,array('source'),array('pageviews'), array("-pageviews"));
						$ga = polarbear_storage("pb_google_analytics_GAPI_source", $ga, $maxAge);
					} else {
						$ga = $gaTmp;
					}

					$loopNum = 0;
					foreach($ga->getResults() as $result) {
						if ($result == "(direct)") { continue; }
						if ($loopNum > 10) { break; }
						echo "<tr>";
						echo "<td>$result</td>";
						echo "<td>" . $result->getPageviews() . "</tr>";
						echo "</tr>";
						$loopNum++;
					}
					?>
				</table>
			</div>
			<div id="overview-statistics-medium">
				<table>
					<tr>
						<th>Source</th>
						<th>Pageviews</th>
					</tr>
					<?php
					// medium
					$gaTmp = polarbear_storage_get("pb_google_analytics_GAPI_medium", $expired);
					if ($expired) {
						$ga->requestReportData($gaID,array('medium'),array('pageviews'), array("-pageviews"));
						$ga = polarbear_storage("pb_google_analytics_GAPI_medium", $ga, $maxAge);
					} else {
						$ga = $gaTmp;
					}

					$loopNum = 0;
					foreach($ga->getResults() as $result) {
						#if ($result == "(direct)") { continue; }
						if ($loopNum > 10) { break; }
						echo "<td>$result</td>";
						echo "<td>" . $result->getPageviews() . "</tr>";
						$loopNum++;
					}
					?>
				</table>
			</div>
		
		</div>
		
		<p>
			<a href="http://www.google.com/analytics/">Visit Google Analytics</a> for more detailed statistics.
			Statistics updated on <?php echo strftime("%b %e, %H:%M", strtotime($ga->getUpdated())) ?>
		</p>

	<?php
} // get stats

/**
 * get recent activites
 */
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

?>