<?php

/**
 * Ladda automatiskt in varje class vid behov
 * @param object $class_name namn på klassen
 */
function polarbear_class_autoload($class_name) {
	$file = POLARBEAR_ROOT.'/includes/php/class.'.strtolower($class_name).'.php';
	if (is_file($file)) {
		require POLARBEAR_ROOT.'/includes/php/class.'.strtolower($class_name).'.php';
	}
}


/**
 * Skriver ut lite debuginfo
 * @param object $var vad som ska debugas
 */
function polarbear_d($var) {
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

/**
 * skriver ut debug i header
 * bra för att debuga utan att störa användaren eller för att debuga filer/bilder
 */
function polarbear_hd($var) {
	static $count;
	$count++;
	header("x-polarbear-debug-$count: $var");
}


/**
 * Skriver ut sökvägen till polarbear-mappen
 * Används för att slippa skriva echo POLARBEAR_WEBPATH hela tiden
 */
function polarbear_webpath() {
	echo POLARBEAR_WEBPATH;
}

/**
 * Skriver ut sökvägen till... vad?
 */
function polarbear_docroot() {
	echo POLARBEAR_DOC_ROOT;
}

function polarbear_domain() {
	echo POLARBEAR_DOMAIN;
}


/**
 * en smart funktion som.. ja... vet inte hur jag ska förklara
 * Man kan ha en array i en funktion med alla standardvärden. 
 * Sen skickas man in en array till funktionen med värden som ska overrid'a defaultvärdena
 * lite som i javascript där man kör typ: x = options.x || defaults.x;
 * går även att skicka in värdena i querystring-format. Very nice indeed. I like it!
 */
function polarbear_fix_function_defaults($defaults, $options) {

	if (!is_array($options) && !empty($options)) {
		// assume "GET-format"
		parse_str($options, $options);
	}
	if (is_array($options)) {
		foreach ($options as $key => $val) {
			$defaults[$key] = $val;
		}
	}

	return $defaults;

}
/**
 * same function. better name. more jquery-like.
 */
function polarbear_extend($defaults, $options) {
	return polarbear_fix_function_defaults($defaults, $options);
}

function polarbear_fix_nasty_chars($str) {
	return preg_replace("/[\"'<>]/", "", $str);
}

/**
 * @return array med objekt
 */
function polarbear_getUserGroups() {
	global $polarbear_db;
	$arr = array();
	$r = $polarbear_db->get_results("SELECT id, name FROM " . POLARBEAR_DB_PREFIX . "_usergroups WHERE isDeleted = 0 ORDER BY name ASC");
	foreach ($r as $row) {
		$arr[$row->id] = $row;
	}
	return $arr;
}

/**
 * Skriver ut ok/fel-meddelande
 * @param string $okmsg
 * @param string $errmsg
 */
function polarbear_infomsg($okmsg = "", $errmsg = "") {
	if (!empty($okmsg)) {
		$okmsg = htmlspecialchars($okmsg, ENT_COMPAT, 'UTF-8');
		echo "
			<div class='ui-widget polarbear-okmsg'>
				<div style='padding: 0pt 0.7em;' class='ui-state-highlight ui-corner-all'> 
					<p>
						<span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-info'></span>
						<strong>$okmsg</strong>
					</p>
				</div>
			</div>
		";
	}
	if (!empty($errmsg)) {
		$ermsg = htmlspecialchars($errmsg, ENT_COMPAT, 'UTF-8');
		echo "
			<div class='ui-widget polarbear-errmsg'>
				<div style='padding: 0pt 0.7em;' class='ui-state-error ui-corner-all'> 
					<p>
						<span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-alert'></span>
						<strong>$errmsg</strong>
					</p>
				</div>
			</div>
		";
	}

}

/**
 * Ger info om en URL
 * Kod från
 * http://ca3.php.net/manual/en/function.parse-url.php#85547
 */
function polarbear_parseUrl($url) {
	$r  = "^(?:(?P<scheme>\w+)://)?";
	//$r .= "(?:(?P<login>\w+):(?P<pass>\w+)@)?";
	$r .= "(?:(?P<login>\w+):?(?P<pass>\w+)?@)?";
	
	$ip="(?:[0-9]{1,3}+\.){3}+[0-9]{1,3}";//ip check
	$s="(?P<subdomain>[-\w\.]+)\.)?";//subdomain
	$d="(?P<domain>[-\w]+\.)";//domain
	$e="(?P<extension>\w+)";//extension
	
	$r.="(?P<host>(?(?=".$ip.")(?P<ip>".$ip.")|(?:".$s.$d.$e."))";
	
	$r .= "(?::(?P<port>\d+))?";
	$r .= "(?P<path>[\w/]*/(?P<file>\w+(?:\.\w+)?)?)?";
	$r .= "(?:\?(?P<arg>[\w=&]+))?";
	$r .= "(?:#(?P<anchor>\w+))?";
	$r = "!$r!";   // Delimiters
	// echo "url: $url"; // url: localhost:8888
	preg_match($r, $url, $out);
	return $out;
}


/**
 * Logga in/återställ användare baserat på cookie
 * @return mixed användarobjekt vid success, false vid fail
 */ 
function polarbear_user_login_from_cookie() {
	global $polarbear_db;
	if (isset($_COOKIE['polarbear_user']) && isset($_COOKIE['polarbear_token'])) {
		$userID = $polarbear_db->escape($_COOKIE['polarbear_user']);
		$userToken = $polarbear_db->escape($_COOKIE['polarbear_token']);
		$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_users WHERE id = '$userID' AND loginToken = '$userToken' AND isDeleted = 0 AND loginToken <> ''";
		if ($r = $polarbear_db->get_var($sql)) {
			return new PolarBear_User($r);
		} else {
			return false;
		}	
	} else {
		return false;
	}
}


/**
 * kräver att personen är admin
 * om inte kommer man till inloggningssidan
 * används för admin-sidorna
 */
function polarbear_require_admin() {
	global $polarbear_u;
	if (is_object($polarbear_u) && $polarbear_u->isAdmin()) {
		return true;
	} else {
		header("Location: " . POLARBEAR_WEBPATH . "login.php");
		exit;
	}
	
}

/**
 * Ref: http://se2.php.net/manual/en/function.mime-content-type.php#87856
 * Extended av Pär/MarsApril
 */
function pb_mime_content_type_by_name($filename) {
	$mime_types = polarbear_getMimeTypes();
    $ext = strtolower(array_pop(explode('.',$filename)));
    if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
    } elseif (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;
    }
    else {
        return 'application/octet-stream';
    }
}

function polarbear_getMimeTypes() {
    $mime_types = array(

		// generell
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
		"3g2" => "audio/3gpp2",
		'mp4' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'flv' => 'video/x-flv',
		'wmv' => 'video/x-ms-wmv',
		"m4v" => 'video/x-m4v',
		"m2v" => 'video/x-m2v',
		"3gpp" => "audio/3gpp",

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
	return $mime_types;

}
        
/**
 * lite bra kontroller för att se till att systemet funkar som det ska
 */
function polarbear_check_things() {
	// kontrollerar sökväg till bilagor 
	if (is_dir(POLARBEAR_ATTACHPATH)) {
		// bra, attachpath är en katalog
		echo "<br>OK: Attachpath är en katalog";
		if (is_writable(POLARBEAR_ATTACHPATH)) {
			// bra, går att skriva till också
			echo "<br>OK: Attachpath går att skriva till";
		} else {
			echo "<br>FEL: Attachpath går inte att skriva till";
		}
	} else {
		echo "<br>FEL: Attachpath är inte en katalog";
	}
}

/**
 * Outputs a filesize in human readable format.
 * From Simple PHP Framework
 */
function polarbear_bytes2str($val, $round = 0)
{
	$unit = array('','K','M','G','T','P','E','Z','Y');
	while($val >= 1000)
	{
		$val /= 1024;
		array_shift($unit);
	}
	return round($val, $round) . ' ' . array_shift($unit) . 'B';
}


/**
 * Returns an English representation of a past date within the last month
 * Graciously stolen from http://ejohn.org/files/pretty.js
 * ...and Bonny stole it from Simple PHP Framework
 */
function polarbear_time2str($ts)
{
	if(!ctype_digit($ts))
		$ts = strtotime($ts);

	$diff = time() - $ts;
	if($diff == 0)
		return 'now';
	elseif($diff > 0)
	{
		$day_diff = floor($diff / 86400);
		if($day_diff == 0)
		{
			if($diff < 60) return polarbear_msg('just now');
			if($diff < 120) return polarbear_msg('1 minute ago');
			if($diff < 3600) return floor($diff / 60) . ' ' . polarbear_msg('minutes ago');
			if($diff < 7200) return '1 hour ago';
			if($diff < 86400) return polarbear_msg('X hours ago', floor($diff / 3600));
		}
		if($day_diff == 1) return polarbear_msg('Yesterday');
		if($day_diff < 7) return $day_diff . ' ' . polarbear_msg('days ago');
		if($day_diff < 31) return ceil($day_diff / 7) . ' ' . polarbear_msg('weeks ago');
		if($day_diff < 60) return polarbear_msg('last month');
		#return date('F Y', $ts);
		return strftime('%B %Y', $ts);
	}
	else
	{
		$diff = abs($diff);
		$day_diff = floor($diff / 86400);
		if($day_diff == 0)
		{
			if($diff < 120) return polarbear_msg('in a minute');
			#if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
			if($diff < 3600) return polarbear_msg('in X minutes', floor($diff / 60));
			if($diff < 7200) return polarbear_msg('in an hour');
			#if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
			if($diff < 86400) return polarbear_msg('in X hours', floor($diff / 3600));
		}
		if($day_diff == 1) return polarbear_msg('tomorrow');
		if($day_diff < 4) return date('l', $ts);
		if($day_diff < 7 + (7 - date('w'))) return 'next week';
		#if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
		if(ceil($day_diff / 7) < 4) return polarbear_msg('in X weeks', ceil($day_diff / 7));
		if(date('n', $ts) == date('n') + 1) return polarbear_msg('next month');
		#return date('F Y', $ts);
		return strftime('%B %Y', $ts);
	}
}


/**
 * Skriver ut en lista på de användargrupper som finns.
 */
function admin_get_user_group_list() {
	global $polarbear_db;
	$numUsers = $polarbear_db->get_var("SELECT COUNT(id) FROM " . POLARBEAR_DB_PREFIX . "_users WHERE isDeleted = 0");
	$numAdminUsers = $polarbear_db->get_var("SELECT COUNT(userID) FROM " . POLARBEAR_DB_PREFIX . "_users_groups_relation INNER JOIN " . POLARBEAR_DB_PREFIX . "_users as a on a.id = userID WHERE a.isDeleted = 0 AND groupID = 1");
	?>
	<ul id="users-groups">
		<!-- fasta / virtuella grupper -->
		<li class="groupID-all">
			<a href="#" class="groupID-all">All users</a>
			<span class='group-count'><?php echo $numUsers ?></span>
		</li>
		<li class="groupID-latest">
			<a href="#" class="groupID-latest">Last added</a>
			<span class='group-count'><?php // echo $numUsers ?></span>
		</li>
		<li class="groupID-latestChanged">
			<a href="#" class="groupID-latestChanged">Last changed</a>
			<span class='group-count'><?php // echo $numUsers ?></span>
		</li>
		<li class="groupID-admins">
			<a href="#" class="groupID-admins">Administrators</a>
			<span class='group-count'><?php echo $numAdminUsers ?></span>
		</li>
		<!-- vanliga/skapade grupper -->
		<?php
		// lista alla grupper utom admingruppen
		if ($r = $polarbear_db->get_results("SELECT id, name FROM " . POLARBEAR_DB_PREFIX . "_usergroups WHERE id <> 1 AND isDeleted = 0 ORDER BY name ASC"))
		{
			foreach ($r as $oneGroup)
			{
				$numGroupUsers = $polarbear_db->get_var("SELECT COUNT(userID) FROM " . POLARBEAR_DB_PREFIX . "_users_groups_relation INNER JOIN " . POLARBEAR_DB_PREFIX . "_users as a on a.id = userID WHERE a.isDeleted = 0 AND groupID = $oneGroup->id");
				echo "<li><a class='groupID-{$oneGroup->id}' href='#'>$oneGroup->name</a>\n<span class='group-count'>$numGroupUsers</span></li>\n";
			}
		}
		?>
	</ul>
	<?php
}

/**
 * fetch latest saved version of the settings
 * @return array
 */
function polarbear_getGlobalSettings() {
	global $polarbear_db, $polarbear_settings;

	if (isset($polarbear_settings)) {
		return $polarbear_settings;
	}

	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_settings ORDER BY date DESC LIMIT 1";
	$r = $polarbear_db->get_results($sql);
	if ($r) {
		$settings = $r[0]->settings;
		$settings = unserialize($settings);
	} else {
		$settings = array();
	}
	return $polarbear_settings = $settings;
}

/**
 * fetch one settings
 */
function polarbear_setting($key) {
	global $polarbear_settings;
	if (!is_array($polarbear_settings)) {
		$polarbear_settings = polarbear_getGlobalSettings();
	}
	if (isset($polarbear_settings[$key])) {
		$setting = trim($polarbear_settings[$key]);
		if (strtolower($setting) === 'true') {
			$setting = true;
		} else if (strtolower($setting) === 'false') {
			$setting = false;
		}
		return $setting;
	} else {
		return null;
	}
}

/**
 * Hämtar de sidmallar som finns
 * @return string
 */
function polarbear_getTemplates() {
	$settings = polarbear_getGlobalSettings();
	$arrTemplates = array();
	if (isset($settings['templates'])) {
		$tmp = explode("\n", $settings['templates']);
		for ($i=0; $i<sizeof($tmp); $i=$i+2) {
			// [0] namnet
			// [1] fil
			$arrTemplates[trim($tmp[$i])] = array(
				"name" => trim($tmp[$i]),
				"file" => trim($tmp[$i+1])
			);
		}
		return $arrTemplates;
	} else {
		return array();
	}
	// todo: fixa klart denna
}

/**
 * Fixes MAGIC_QUOTES
 * From simple php framework
 * Modified by Pär / MarsApril
 */
function polarbear_fix_slashes($arr = '')
{
	if(is_null($arr) || $arr == '') return $arr;
	if(!get_magic_quotes_gpc()) return $arr;
	return is_array($arr) ? array_map('polarbear_fix_slashes', $arr) : stripslashes($arr);
}

/**
 * generate url for using in links for pages that are used with the tree
 */
function polarbear_treepage($pageToLoad, $javascript = false) {
	$pageToLoad = urlencode($pageToLoad);
	if ($javascript) {
		return "http://" . POLARBEAR_DOMAIN . POLARBEAR_WEBPATH . "?treepage=$pageToLoad";
	} else {
		return POLARBEAR_WEBPATH . "?treepage=$pageToLoad";
	}
}


/**
 * Outputs the nav for the tags
 */
function polarbear_files_get_tags_content($selectedTag = "", $selectedSort = "", $type = "") {
	global $polarbear_db;
	// lista alla taggar
	$sql = "SELECT DISTINCT tagName FROM " . POLARBEAR_DB_PREFIX . "_files_tags ORDER BY tagName ASC";
	if ($r = $polarbear_db->get_results($sql)) {
		?>
		<ul class="display-by-tag">
			<?php
			foreach ($r as $row) {
				$escapedName = rawurlencode($row->tagName);
				$class = ($selectedTag==$row->tagName) ? "class='selected' " : "";
				echo "<li $class><a class='polarbear-files-ajaxload' href='" . POLARBEAR_WEBPATH . "gui/files.php?file_tag=$escapedName&file_sort=$selectedSort&type=$type'>" . htmlspecialchars($row->tagName) . "</a></li>";
			}
			?>
		</ul>
		<?php
	} else {
		?><ul><li>No files tagged yet</li></ul><?
	}
}

/**
 * Outputs the inner table of the files page
 * 
 * args we need:
 * file_type: all | images | documents | movies | audio
 * file_tag: string
 * search: string
 * page: integer
 * type: image | file | fieldImage
 * 
 * @param array $argv
 */ 
function polarbear_files_get_page_content($argv = null) {
	
	global $polarbear_db;
	
	$defaults = array(
		"file_type" => "all",
		"file_sort" => "date",
		"file_tag" => "",
		"search" => "",
		"filebrowser_type" => "",
		"page" => 0
	);
	$options = polarbear_fix_function_defaults($defaults, $argv);
	
	$options["search"] = trim($options["search"]);
	if ($options["search"]) {
		// om sök, nollställ typ av visa, kategori samt sortering
		unset($options['file_type']);
		$file_type_to_show = '';
		unset($options['file_sort']);
		unset($options['file_tag']);
		$file_tag = '';

	}

	// om visar tag: ta bort markeringen av filtyp (all, images, documents)
	if ($options["file_tag"]) {
		$options["file_type"] = '';
	}

	// vi vill alltid börja på sida 1 om vi ändrar sorteringen
	// söken ska också bort
	$qs = new Query_String($options);
	unset($qs->search);	// söken får läggas på först i sökrutan
	unset($qs->page);	// byter man villkor för visning ska man börja om på sida 1
	?>

	<!-- update this with ajax -->
	<table>
	<tr>
	<td>
		<div id="files-nav">

			<div id="uploadify">
				<input type="file" id="uploadify1" name="uploadify1" />
			</div>

			<?
			unset($qs->file_tag);
			?>
			<h3>Display</h3>
			<p class="discrete display-by-type">Type</p>
			<ul>
				<li <?php print ($options["file_type"]=="all") ? "class='selected' " : "" ?>><a class="polarbear-files-ajaxload" href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_type="all"; echo $qs; ?>">All</a></li>
				<li <?php print ($options["file_type"]=="images") ? "class='selected' " : "" ?>><a class="polarbear-files-ajaxload" href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_type="images"; echo $qs; ?>">Images</a></li>
				<li <?php print ($options["file_type"]=="documents") ? "class='selected' " : "" ?>><a class="polarbear-files-ajaxload" href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_type="documents"; echo $qs; ?>">Documents</a></li>
				<li <?php print ($options["file_type"]=="movies") ? "class='selected' " : "" ?>><a class="polarbear-files-ajaxload" href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_type="movies"; echo $qs; ?>">Movies</a></li>
				<li <?php print ($options["file_type"]=="audio") ? "class='selected' " : "" ?>><a class="polarbear-files-ajaxload" href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_type="audio"; echo $qs; ?>">Audio</a></li>
			</ul>

			<div class="polarbear-files-nav-tags">
				<!-- Update this with ajax -->
				
				<p class="display-by-tag discrete">Tag</p>
				<div id="polarbear-page-files-nav-tags">
					<?php
					polarbear_files_get_tags_content($options["file_tag"], $options["file_sort"], $options["type"]);
					?>
				</div>

			</div>
		
			<h3>Sort</h3>
			<div id="polarbear-page-files-nav-sort">
				<?php
				// återställ tag
				$qs->file_tag = $options["file_tag"];
				// återställ filetype
				$qs->file_type = $options["file_type"];
				?>
				<ul>
					<li <?php print ($options["file_sort"]=="date") ? "class='selected' " : "" ?>><a class='polarbear-files-ajaxload' href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_sort="date"; echo $qs; ?>">Date</a></li>
					<li <?php print ($options["file_sort"]=="type") ? "class='selected' " : "" ?>><a class='polarbear-files-ajaxload' href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_sort="type"; echo $qs; ?>">Type</a></li>
					<li <?php print ($options["file_sort"]=="name") ? "class='selected' " : "" ?>><a class='polarbear-files-ajaxload' href="<?php polarbear_webpath() ?>gui/files.php<?php $qs->file_sort="name"; echo $qs; ?>">Name</a></li>
				</ul>
				<?php
				$qs->file_sort = $options["file_sort"];
				?>
			</div>

			<h3>Search file</h3>
			<?php
				// qs for search
				// include 
			?>
			<form method="get" class="files-search" action="<?php polarbear_webpath() ?>gui/files.php">
				<input class="text ui-widget-content ui-corner-all polarbear-files-search-text" type="text" name="search" value="<?php echo $options["search"] ?>" />
				<input class="submit polarbear-files-search-submit fg-button ui-state-default ui-widget-content ui-corner-all" type="submit" value="Go" />
				<input type="hidden" name="polarbear-files-search-qs" id="polarbear-files-search-qs" value="<?php echo $qs ?>" />
			</form>
		
		</div>

	</td>
	<td>

		<div id="files-content" class="">
			<?php

			if ($options["uploaded"]) {
				polarbear_infomsg("Files uploaded");
			}

			// fixa fram where-villkor
			$where = '';
			if ($options["file_type"] == 'images') {
				$where = ' AND mime LIKE ("image/%") ';
			} elseif ($options["file_type"] == 'documents') {
				$where = ' AND mime NOT LIKE ("image/%") AND mime NOT LIKE ("video/%") ';
			} elseif ($options["file_type"] == 'movies') {
				$where = ' AND mime LIKE ("video/%") ';
			} elseif ($options["file_type"] == 'audio') {
				$where = ' AND mime LIKE ("audio/%") ';
			}
		
			if ($options["file_sort"] == 'date') {
				$orderBy = " ORDER BY dateUploaded DESC, name ASC";
			} else if ($options["file_sort"] == 'type') {
				$orderBy = " ORDER BY mime ASC, name ASC";
			} else if ($options["file_sort"] == 'name') {
				$orderBy = " ORDER BY name ASC, mime ASC";
			}

			if ($options["search"]) {
				$searchForSQL = $polarbear_db->escape($options["search"]);
				$where = " AND (
								name LIKE '%$searchForSQL%' 
								OR mime LIKE '%$searchForSQL%' 
								OR dateUploaded LIKE '%$searchForSQL%'
								OR dateModified LIKE '%$searchForSQL%'
								OR width LIKE '%$searchForSQL%'
								OR height LIKE '%$searchForSQL%'
							)
				";
				$orderBy = " ORDER BY dateUploaded DESC, name ASC";
			}

			// om vald tag så hämta in id på alla bilder som har vald tag
			if ($options["file_tag"]) {
				$sql = "SELECT fileID FROM " . POLARBEAR_DB_PREFIX . "_files_tags WHERE tagName = '" . $polarbear_db->escape($options["file_tag"]) . "'";
				if ($r = $polarbear_db->get_results($sql)) {
					$arrIDs = '';
					foreach ($r as $row) {
						$arrIDs[] = $row->fileID;
					}
					$where .= ' AND id IN (' . join($arrIDs, ',') . ')';
				} else {
					$arrIDs = array();
					$where .= " AND 1 = 2"; // vill inte ha några träffar
				}

			}

			if (!empty($where)) {
				$where = " WHERE 1=1 $where ";
			}
		
			// totalt antal filer
			$sql = "SELECT count(id) FROM " . POLARBEAR_DB_PREFIX . "_files $where";
			$total_num_files = $polarbear_db->get_var($sql);

			$pages= new Paginator();
			$pages->items_total = $total_num_files;
			$pages->paginate();
		
			if ($total_num_files > 25) {
				echo "<div class='pagination'>";
				echo $pages->display_pages();
				echo "</div>";
			}
			
			// hämta in och visa filerna
			$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_files $where $orderBy $pages->limit";
			if ($total_num_files && $rows = $polarbear_db->get_results($sql)) {
				?><ul class="files-fileslist"><?php
				foreach ($rows as $oneRow) {
					$file = PolarBear_File::getInstance($oneRow->id);
					$nameExtension = $file->getNameExtension();
					$nameWithoutExtension = $file->getNameWithoutExtension();

					// tumnagel eller ikon? eller både?
					if ($file->isImage()) {
						$filePreview = "<span class='image'><img src='" . $file->getImageSrc(array("w"=>75,"h"=>75)) . "' alt='' />
						</span>";
					} else {
						$filePreview = '<span class="icon"><img src="' . $file->getIcon(). '" alt="" /></span>';
					}
					?>
					<li class="fileID-<?php echo $file->id ?>">
						<?php echo $filePreview ?>
						<span class="name" id="name-<?php echo $file->id ?>"><?php echo htmlspecialchars($file->name) ?></span>
						<?php 
						// är vi i en browser, dvs. ska välja en fil för en länk eller bilaga så lägger vi in en tydlig knapp
						if (!empty($options["type"])) {
							if ($file->isImage()) {
								$browser_button_title = "Use this image";
							} else {
								$browser_button_title = "Use this file";
							}
							?>
							<span class="button-insert">
								<?php if ($options["type"] == "file" || $options["type"] == "image") { ?>
									<input type="button" class="fromTiny" value="<?php echo $browser_button_title ?>" />
								<?php } elseif ($options["type"]=="fieldImage") { ?>
									<input type="button" class="fromField" value="<?php echo $browser_button_title ?>" />
									<input type="hidden" name="file-sourceType" value="image" />
									<input type="hidden" name="file-src-thumb" value="<?php echo $file->getSrc("w=75&h=75") ?>" />
								<?php } elseif ($options["type"]=="fieldFile") { ?>
									<input type="button" class="fromField" value="<?php echo $browser_button_title ?>" />
									<input type="hidden" name="file-sourceType" value="file" />
								<?php }?>
								<input type="hidden" name="file-src" value="<?php echo $file->getSrc() ?>" />
								<input type="hidden" name="file-id" value="<?php echo $file->id ?>" />
								<input type="hidden" name="file-name" value="<?php echo $file->name ?>" />
								<input type="hidden" name="file-icon" value="<?php echo $file->getIcon() ?>" />
							</span>
							<?php 

						}
						?>
						<span class="size"><?php echo polarbear_bytes2str($file->size) ?>
							<?php
							if ($file->isImage()) { 
								?> - <?php echo $file->width ?> x <?php echo $file->height ?> px<?php
							}
							?>
							<!-- - <?php echo $file->mime ?> -->
							<br />
							Uploaded <?php echo polarbear_time2str($file->dateUploaded) ?>
							<?php
							// todo: this does not work?
							if ($file->user_creator->id) {
								echo "by $file->user_creator";
							}
							?>
						</span>
					
						<!-- visa tags, om det finns -->
						<span class="tags" style="<?php echo ($file->hasTags()) ? '' : 'display: none' ?>">
							<?php 
							// todo: denna skickar inte med type
							echo polarbear_files_printTagsLinks($file->id) 
							?>
						</span>

						<div class="actions">
							<a class="view" target="_blank" href="<?php echo $file->getLink() ?>">View</a>
							<a class="download" href="<?php echo $file->getDownloadLink() ?>">Download</a>
							<span class="actions-tags-wrapper">
								<a href="#" class="the-tags-link">Tags</a>
								<span class="the-tags" style="display: none;"></span>
							</span>
							<a class="ui-icon ui-icon-trash delete" href="#"></a>
						</div>
						<div class="file-edit-wrapper" style="display: none;"></div>
						<div class="clearer"></div>
					</li>
					<?php
				}
				?></ul><?php
			} else {
				if (empty($where)) {
					?><p>No files uploaded yet.</p><?php
				} else {
					?><p>No files matched your search.</p><?php
				}
			
			}
		
			if ($total_num_files > 25) {
				echo "<div class='pagination'>";
				echo $pages->display_pages();
				echo "</div>";
			}
		
			?>

		</div>

	</td>
	</tr>
	</table>

	<!-- end update through ajax -->
	<?php
} // end function polarbear_files_get_page_content


function polarbear_files_printTagsLinks($fileID) {
	$f = PolarBear_File::getInstance($fileID);
	$arrTags = $f->arrTags;
	$out = 'Tags: ';
	foreach ($arrTags as $oneTag) {
		$tagEscaped = rawurlencode($oneTag);
		$out .= "<span class='tags-one'><a class='polarbear-files-ajaxload' href='" . POLARBEAR_WEBPATH . "gui/files.php?file_tag=$tagEscaped'>" . htmlspecialchars($oneTag) . "</a></span>, ";
	}
	$out = preg_replace("/, $/", "", $out);
	return $out;
}


/**
 * returns all field connectors, except deleted
 * @return object
 */
function polarbear_getFieldConnectors() {
	global $polarbear_db;
	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_connectors WHERE deleted = 0 ORDER BY name ASC";
	$rows = $polarbear_db->get_results($sql);
	return $rows;
}

/**
 * hämta en array för en fältkopplare
 * hämtar allt som krävs för att skriva ut fälten som är kopplade till en fältsamlare
 * @return array
 */			
function polarbear_getFieldStructureForFieldConnector($fieldConnectorID) {

	if (!is_numeric($fieldConnectorID)) {
		return array();
	}

	global $polarbear_db, $polarbear_getFieldStructureForFieldConnector;
	if (!isset($polarbear_getFieldStructureForFieldConnector)) {
		$polarbear_getFieldStructureForFieldConnector = array();
	}
	if (isset($polarbear_getFieldStructureForFieldConnector[$fieldConnectorID])) {
		return $polarbear_getFieldStructureForFieldConnector[$fieldConnectorID];
	}
	$arr = array();
	#echo "<br>polarbear_getFieldStructureForFieldConnector $fieldConnectorID";
	// Get info about the field connector

	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_fields_connectors WHERE id = $fieldConnectorID AND deleted = 0";

	$rowFieldConnector = $polarbear_db->get_row($sql);

	$arr[$rowFieldConnector->id] = array(
		"id" => $rowFieldConnector->id,
		"name" => $rowFieldConnector->name,
		"deleted" => $rowFieldConnector->deleted
	);

	// Get info about the fieldCollections that are connected to the connector (not at all confusing!)
	$sql = "SELECT fieldConnectorID, fieldCollectionID, prio FROM " . POLARBEAR_DB_PREFIX . "_fields_link_connectors_collections WHERE fieldConnectorID = $fieldConnectorID ORDER BY prio ASC";
	$rows = $polarbear_db->get_results($sql);
	$arr[$rowFieldConnector->id]["fieldCollections"] = array();
	if ($rows) {
		foreach ($rows as $row) {
			$sql = "SELECT id, name, repeatable, deleted FROM " . POLARBEAR_DB_PREFIX . "_fields_collections WHERE id = $row->fieldCollectionID AND deleted = 0";
			$row2 = $polarbear_db->get_row($sql);
			
			$arr[$rowFieldConnector->id]["fieldCollections"][$row2->id] = array(
				"id" => $row2->id,
				"name" => $row2->name,
				"repeatable" => $row2->repeatable,
				"deleted" => $row2->deleted,
				"fields" => array()
			);

			// hämta fälten som finns i denna fältsamling
			$sql = "SELECT id, name, type, fieldCollectionID, deleted, prio, content FROM " . POLARBEAR_DB_PREFIX . "_fields WHERE fieldCollectionID = $row2->id AND deleted = 0 ORDER BY prio ASC";
			$rows2 = $polarbear_db->get_results($sql);
			$arrFields = array();
			foreach ($rows2 as $row3) {
				$arrFields = array(
					"id" => $row3->id,
					"name" => $row3->name,
					"type" => $row3->type,
					"fieldCollectionID" => $row3->fieldCollectionID,
					"deleted" => $row3->deleted,
					"prio" => $row3->prio,
					"content" => unserialize($row3->content)
				);
				$arr[$rowFieldConnector->id]["fieldCollections"][$row2->id]["fields"][$arrFields['id']] = $arrFields;
				$hasValues = true;
			}

		}
	}
	$polarbear_getFieldStructureForFieldConnector[$fieldConnectorID] = $arr;
	return $arr;
}				


function polarbear_getFieldForArticleEdit($articleID, $fieldID, $numInSet = 0) {
	$out = "";
	global $polarbear_db;
	// get info about this field. returns one row
	$sql = "SELECT id, name, type, fieldcollectionID, deleted, prio, content FROM " . POLARBEAR_DB_PREFIX . "_fields WHERE id = $fieldID";
	$field = $polarbear_db->get_row($sql);
	
	$fieldType = $field->type;
	$fieldID = $field->id;
	$fieldCollectionID = $field->fieldcollectionID;
	$fieldContent = $field->content;
	$fieldContentUnserialized = unserialize($fieldContent);
	$fieldName = $field->name;
	$fieldInputName = "fields[{$fieldCollectionID}][{$numInSet}][{$fieldID}]";
	$fieldInputID = "polarbear-fields-$fieldCollectionID-$numInSet-$fieldID";
	echo "<label title='$fieldID'>{$fieldName}</label>";

	
	// hämta in lagrade värden för detta fält för denna artikel
	$fieldValue = "";
	if (is_numeric($numInSet)) {
		$sql = "SELECT value FROM " . POLARBEAR_DB_PREFIX . "_fields_values WHERE articleID = '$articleID' AND fieldID = $fieldID AND numInSet = $numInSet";
		$fieldValue = $polarbear_db->get_var($sql);
		$fieldValue = htmlspecialchars ($fieldValue, ENT_QUOTES, "UTF-8");
	}
	if ($fieldType == "multichoice") {
		$arrChoices = explode("\n", $fieldContentUnserialized["multichoiceChoices"]);
		$out .= "<select name='$fieldInputName' id='$fieldID'>";
		foreach ($arrChoices as $oneChoice) {
			$selected = "";
			if (trim($oneChoice) == trim($fieldValue)) {
				$selected = " selected='selected' ";
			}
			$out .= "<option $selected>$oneChoice</option>";
		}
		$out .= "</select>";
	} elseif ($fieldType == "text") {
		$out .= "<input name='$fieldInputName' type='text' value='$fieldValue' class='text ui-widget-content ui-corner-all' id='$fieldInputID'/>" ;
	} elseif ($fieldType == "textarea") {
		$out .= "<textarea name='$fieldInputName' cols='40' rows='10' class='ui-widget-content ui-corner-all' id='$fieldInputID'>$fieldValue</textarea>";
	} elseif ($fieldType == "html") {
		$out .= "<textarea style='width:100%' name='$fieldInputName' class='fieldsTextHTML' cols='40' rows='10' id='$fieldInputID'>$fieldValue</textarea>";
#	} elseif ($fieldType == "file") {
#		$out .= "<input name='$fieldInputName' type='text' value='$fieldValue' class='text ui-widget-content ui-corner-all' id='$fieldInputID' />" ;
	} elseif ($fieldType == "image" || $fieldType == "file") {
		$fieldImageName = "";
		$fieldImageSrc = "";
		$fieldImageImg = "";
		if ($fieldValue && $fieldType == "image") {
			$fieldImage = new PolarBear_File($fieldValue);
			$fieldImageName = $fieldImage->name;
			$fieldImageSrc = $fieldImage->getImageSrc(array("w"=>75,"h"=>75));
			$fieldImageImg = "<img src='$fieldImageSrc' alt='' />";
		} elseif ($fieldValue && $fieldType == "file") {
			$fieldImage = new PolarBear_File($fieldValue);
			$fieldImageName = $fieldImage->name;
			$fieldImageImg = "<img src='" . $fieldImage->getIcon() . "' alt='' />";
		}
		if ($fieldType == "image") {
			$out .= "
				<div class='polarbear-article-edit-fields-fieldImage'>
					<div style='float:left;'>
						<div style='width:75px;height:75px;background-color:#eee;display:block;margin:0 .5em .5em 0;' class='polarbear-article-edit-fields-fieldImage-image'>$fieldImageImg</div>
					</div>	
					<div>
						<div class='polarbear-article-edit-fields-fieldImage-imageName'>$fieldImageName</div>
						<div><a class='polarbear-article-edit-fields-image-choose' href='#'>Choose</a> | <a href='#' class='polarbear-article-edit-fields-fieldImage-clear'>Clear</a></div>
					</div>
					<div class='clearer'></div>				
					<input name='$fieldInputName' type='hidden' value='$fieldValue' class='text ui-widget-content ui-corner-all polarbear-article-edit-fields-fieldImage-value' id='$fieldInputID' />
				</div>
			" ;
		} elseif ($fieldType == "file") {
			$out .= "
				<div class='polarbear-article-edit-fields-fieldImage'>
					<div style='float:left;'>
						<div style='width:16px;height:36px;background-color:#eee;display:block;margin:0 .5em .5em 0;' class='polarbear-article-edit-fields-fieldImage-image'>$fieldImageImg</div>
					</div>	
					<div>
						<div class='polarbear-article-edit-fields-fieldImage-imageName'>$fieldImageName</div>
						<div><a class='polarbear-article-edit-fields-file-choose' href='#'>Choose</a> | <a href='#' class='polarbear-article-edit-fields-fieldImage-clear'>Clear</a></div>
					</div>
					<div class='clearer'></div>				
					<input name='$fieldInputName' type='hidden' value='$fieldValue' class='text ui-widget-content ui-corner-all polarbear-article-edit-fields-fieldImage-value' id='$fieldInputID' />
				</div>
			" ;
		}

	}
	
	return $out;
	
} // end getFieldForArticleEdit


/**
 * laddar in artikeln för sidan man besöker
 * körs automatiskt varje gång polarbear-boot.php inkluderas. bra då man oftast vill hantera artikeln i "page"
 * sidans id eller shortname finns i $_GET[polarbear-page], t.ex. polarbear-page=305, eller polarbear-page=/hem/om-oss/medarbetare/
 * todo: hantera shortname
 * todo: hantera 404
 */
function polarbear_article_bootload() {

	pb_event_fire("pb_article_bootload_start");

	global $polarbear_rewrite_shortnames,$polarbear_a,$polarbear_u,$polarbear_article_is_autoloaded,$polarbear_db;
	$doLoadArticle = false;
	$doLoadTemplate = false;
	$show404 = false;
	$bodyTemplateNotFound = 
		"
		<h1>Oopsie daisy!</h1>
		<p>We're sorry, but the page \"{$_SERVER["REQUEST_URI"]}\" seem to suffer from an \"Internal Server Error\".</p>
		<p>It's not your fault, so please check back again later and hopefully you will find the problem solved.</p>
		<p><small>Technical details: template not found.</small></p>
	";
	$bodyPageNotFound = 
		"
		<h1>Could not find page</h1>
		<p>We're sorry, but the page \"{$_SERVER["REQUEST_URI"]}\" does not exist.</p>
		<p>Suggestions:</p>
		<ul>
			<li>Check your spelling and try again</li>
			<li>Try to access the main site at <a href='http://".POLARBEAR_DOMAIN."'>" . POLARBEAR_DOMAIN . "</a></li>
		</ul>
	";

	// if we come from rewrite.php and got some shortnames
	// find the article with that shortname
	if (!empty($polarbear_rewrite_shortnames) && is_array($polarbear_rewrite_shortnames)) {

		$shortname = $polarbear_rewrite_shortnames[sizeof($polarbear_rewrite_shortnames)-1];
		$polarbear_a = polarbear_article::getArticleByShortname($shortname);
		$doLoadArticle = true;
		$doLoadTemplate = true; // it's template-time!

		if ($polarbear_a == false) {
			$show404 = true;
		} else {
			$_GET['polarbear-page'] = $polarbear_a->getId();
			// ok, the last shortname was an existing article
			// check if the full url is ok, so no one tries to modify the url
			// querystring is however ok to modify
			$uri = $_SERVER['REQUEST_URI'];
			$uriToCheck = $uri;
			$qs = $_SERVER['QUERY_STRING'];
			if (!empty($qs)) {
				$uriToCheck = str_replace("?{$qs}", "", $uriToCheck);
			}
			#echo "<br>uri: $uri";echo "<br>uriToCheck: $uriToCheck";echo "<br>qs: $qs";exit;
			if ($uriToCheck != $polarbear_a->fullPath()) {
				// issue a 301 and go to the full path of the real articles
				header("Location: " . $polarbear_a->fullPath(), true, 301);
				exit;
			}
			
			// if we have preview=1 then find the current article's most recent preview version
			if ($_GET["preview"]) {
				$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE isRevisionTo = " . $polarbear_a->getId() . " AND status = 'preview' ORDER BY dateChanged DESC LIMIT 1";
				$previewArticleID = (int) $polarbear_db->get_var($sql);
				if ($previewArticleID) {
					$polarbear_a = polarbear_article::getInstance($previewArticleID);
				}
				
			}

		}

	} elseif (isset($_GET['polarbear-page']) && is_numeric($page = $_GET['polarbear-page'])) {
		$articleID = (int) $page;
		$polarbear_a = PolarBear_Article::getInstance($articleID);
		$doLoadArticle = true;
		$polarbear_article_is_autoloaded = true;

	} else {

		// neither page or shortname
		// just continue
	}

	// we now have an articleID of the article to show
	// check that its valid, i.e. is published and stuff like that
	if ($doLoadArticle) {

		if (
				// the article must be published
				($polarbear_a != false && $polarbear_a->isPublished())
				||
				// or it may have status=preview
				(($polarbear_u!=false) && $polarbear_u->isAdmin() && $_GET["preview"]==1 && $polarbear_a->getStatus() == "preview")
			) {
			// check for template
			// @todo: assumes a file to load. what if a domain or such?
			if ($doLoadTemplate) {
				$templateToUse = $polarbear_a->templateToUse();
				$templateToUse = POLARBEAR_DOC_ROOT . $templateToUse;
				if (is_file($templateToUse) && is_readable($templateToUse)) {
					$polarbear_article_is_autoloaded = true;
					include($templateToUse);
					#exit;

				} else {
					// template not found or not readable
					header("HTTP/1.1 500 Internal Server Error");
					echo polarbear_xhtml_page("Internal Server Error (error 500)", $bodyTemplateNotFound);
					exit;
				}
			}
			
		} else {
			// not ok. page not found so 404 here we come
			$show404 = true;
		}

		if ($show404) {
		
			header("HTTP/1.0 404 Not Found");
			
			// use a specific article
			// or just a standard message
			$article404 = polarbear_setting("article404");
			if (is_numeric($article404)) {
				$a404 = PolarBear_Article::getInstance($article404);
				$templateToUse = $a404->templateToUse();
				$templateToUse = POLARBEAR_DOC_ROOT . $templateToUse;
				if (is_file($templateToUse) && is_readable($templateToUse)) {
					$_GET['polarbear-page'] = $article404;
					$polarbear_a = PolarBear_Article::getInstance($article404);
					// todo: well, if the template does not exist? combine with oopsie-daisy-function above
					if (is_file($templateToUse) && is_readable($templateToUse)) {
						include($templateToUse);
						exit;
					} else {
						header("HTTP/1.1 500 Internal Server Error");
						echo polarbear_xhtml_page("Internal Server Error (error 500)", $bodyTemplateNotFound);
						exit;
					}
				}
			}

			echo polarbear_xhtml_page("Page not found (error 404)", $bodyPageNotFound);
			exit;
		}
	}
}


/*
	Writes a ul-li list of an article and all its sub articlces
	It's great for menus!

	Klura lite på
	- Styra antal
	- Styra sortering
	- mer info? typ antal artiklar i kategorin etc?
*/		
function polarbear_menu($rootPageID = null, $options = null) {
	if ($rootPageID === null) {
		return false;
	}
	$defaults = array(
		'includeRoot' => false,
		'rootPageID' => $rootPageID,
		'openOnlyActive' => true,
		'selectedPage' => $_GET['polarbear-page'],
		'defaultFormat' => "<a href='{\$href}'>{\$titleNav}</a>",
		'defaultSort' => 'prio',
		'defaultSortDirection' => 'desc',
		'defaultLimitStart' => 0,
		'defaultLimitCount' => null, // empty val interpreted as "all"
		'maxDepth' => null, // how deep can the menu be. default is infinitive. a maxDepth of "1" would only show the root
		'rootULClass' => "polarbear-nav" // class that the root ul should get. great for styling menues different (navigation vs. sitemap for example)
	);
	$options = polarbear_extend($defaults, $options);
	
	$options["currentDepth"] = 0;
	$options["numInLoop"] = 0;
	
	$out = '';
	
	$articleRoot = PolarBear_Article::getInstance($rootPageID);
	$out .= polarbear_menu_create_li($articleRoot, $options);

	// if items found, add ul
	if ($out) {
		$out = "<ul class='{$options["rootULClass"]}'>$out</ul>";
	}
	
	return $out;
	
}

/**
 * generate one LI for the polarbear_menu
 */		
function polarbear_menu_create_li($article, $options) {

	$out = '';
	$skipLI = false;
	$isRoot = false;
	$includeChildren = true;
	$isSelectedPage = false;
	$hasSelectedChild = false;
	$thisFormat = $options['defaultFormat'];
	$thisID = $article->getId();
	$thisChildrenSort = $options['defaultSort'];
	$thisChildrenSortDirection = $options['defaultSortDirection'];
	$thisChildrenLimitStart = $options['defaultLimitStart'];
	$thisChildrenLimitCount = $options['defaultLimitCount'];
	
	// $options['openOnlyActive'] = only show sub articles if the page in $_GET["page"] is one of the childs
	
	// article must be published to be visible
	if (!$article->isPublished()) {
		return null;
	}

	if ($options['rootPageID'] == $thisID) {
		$isRoot = true;
	}
	
	if ($options['includeRoot'] == false && $isRoot) {
		$skipLI = true;
	}
	
	if ($options['selectedPage'] && $options['selectedPage'] == $thisID) {
		$isSelectedPage = true;
	}
	
	if ($options['selectedPage']) {
		$selectedArticle = PolarBear_Article::getInstance( (int) $options['selectedPage'] );
	}
	
	
	// if we have a options[selectedPage] and that page is among the children of currentArticle
	// then mark current article as childSelected
	if ($options['selectedPage'] && $selectedArticle->isChildOrSubChildOf($article)) {
		$hasSelectedChild = true;
	}
	
	// check if article parent has a specific format
	$thisParentID = $article->getParentId();
	if (isset($thisParentID) && isset($options['articleSettings'][$thisParentID])) {
		// yeah, got some settings there
		$parentSettings = $options['articleSettings'][$thisParentID];
		if (isset($parentSettings['childrenFormat'])) {
			$thisFormat = $parentSettings['childrenFormat'];
		}
		
	}
	
	// check if current article has some specfic settings
	if (isset($options['articleSettings'][$thisID])) {
		// yeah, it has settings
		$thisSettings = $options['articleSettings'][$thisID];
		if (isset($thisSettings['format'])) {
			$thisFormat = $thisSettings['format'];
		}
		if (isset($thisSettings['childrenSort'])) {
			$thisChildrenSort = $thisSettings['childrenSort'];
		}
		if (isset($thisSettings['childrenSortDirection'])) {
			$thisChildrenSortDirection = $thisSettings['childrenSortDirection'];
		}
		if (isset($thisSettings['childrenLimitStart'])) {
			$thisChildrenLimitStart = $thisSettings['childrenLimitStart'];
		}
		if (isset($thisSettings['childrenLimitCount'])) {
			$thisChildrenLimitCount = $thisSettings['childrenLimitCount'];
		}

	}
	
	if ($options['openOnlyActive'] && !$isRoot && !$isSelectedPage) {
		// check that article in $_GET[page] is a child of the current article
		// if it is: output childs
		// if not: do not output childs
		if ($options['selectedPage']) {
			if ($selectedArticle->isChildOrSubChildOf($article)) {
				// ok, include children
			} else {
				// nah, don't include children
				$includeChildren = false;
			}
		}
	}
	
	$options["currentDepth"]++;
	if (isset($options["maxDepth"]) && $options["currentDepth"] > $options["maxDepth"]) {
		$includeChildren = false;
	}
	
	if (!$skipLI) {
		$classChildren = ($article->hasChildren()) ? 'hasChildren' : 'hasNoChildren';
		$classOpened = ($includeChildren) ? 'opened' : 'notOpened';
		$classSelected = ($isSelectedPage) ? 'selected' : 'notSelected';
		$classHasSelectedChild = ($hasSelectedChild) ? 'hasSelectedChild' : 'hasNotSelectedChild';
		$classFirst = ($options["numInLoop"]==0) ? "first" : "";
		$classLast = ($options["numInLoop"] == $options["numOfItemsInThisDepth"]-1) ? "last" : "";

		$format = "<li class='a-{\$id} $classFirst $classChildren $classOpened $classSelected $classHasSelectedChild $classLast'>";
		$format .= $thisFormat;
		$out .= $article->output($format);
		
	}
	
	// output children
	if ($includeChildren) {

		$childrenOptions = array(
			'sort' => $thisChildrenSort,
			'sortDirection' => $thisChildrenSortDirection,
			'limitStart' => $thisChildrenLimitStart,
			'limitCount' => $thisChildrenLimitCount
		);
		$children = $article->children($childrenOptions);
		$options["numOfItemsInThisDepth"] = sizeof($children);
		
		
		if (sizeof($children)>0) {
			if (!$skipLI) {
				$out .= '<ul>';
			}
			$numInLoop=0;
			foreach ($children as $oneChildArticle) {
				$options["numInLoop"] = $numInLoop;
				$out .= polarbear_menu_create_li($oneChildArticle, $options);
				$numInLoop++;
			}
			if (!$skipLI) {
				$out .= '</ul>';
			}
		}

	}
	
	if (!$skipLI) {
		$out .= '</li>';
	}

	return $out;

}

function polarbear_connect_db() {
	global $polarbear_db;

	pb_event_fire("pb_connect_db_start");
	$polarbear_db = new ezSQL_mysql();
	$polarbear_db->show_errors=true;
	$dbok = $polarbear_db->quick_connect(POLARBEAR_DB_USER,POLARBEAR_DB_PASSWORD,POLARBEAR_DB_DATABASE,POLARBEAR_DB_SERVER);
	if (!$dbok) {
		$content = "
			<h1>Oups, an error occured</h1>
			<p>We're sorry but we can't show you this page right now.</p>
			<p>Please try again later.</p>
			<p><small>Nerdy technical details: could not connect to database.</small></p>
		";
		echo polarbear_xhtml_page("Oups, an error occured", $content);
		exit;
	}
	# $polarbear_db->debug_all = true;
	// Fixa så att MySQL fixar UTF8 (http://se2.php.net/manual/en/function.mysql-set-charset.php#86455)
	$polarbear_db->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

	pb_event_fire("pb_connect_db_end");
}

function polarbear_script_end_stats() {
	global $polarbear_db, $polarbear_render_start_ms;
	$out = "";
	$out .= "SQL: " . $polarbear_db->num_queries . " queries.";
	$out .= "\nTime: " . round(microtime(true) - $polarbear_render_start_ms, 3) . " seconds.";			
	$out .= "\nMemory: " . round(memory_get_peak_usage() / 1024, 0) . " kB peak, " . round(memory_get_usage() / 1024, 0) . " kB end." ;
	// pre singleton       	: 439 queries före singleton and took 0.17 seconds
	// after multiton 		: 203 queries and took 0,0762989521027 seconds. 
	// after field-cache    : 113 queries and took 0,0478739738464 seconds.
	// after title-cache    : 110 queries and took 0,0452170372009 seconds. 
	// after settings-cache : 96 queries and took 0,038850069046 seconds. 
	// after fieldcontouse  : 58 queries and took 0,0302278995514 seconds.
	
	//* enable for more debug info. ezdb must be set to debug too for this to work
	if ($polarbear_db->debug_all) {
		$foundDupe = false;
		$firstDupe = true;
		foreach($polarbear_db->debug_queries as $one) {
			polarbear_d($one);
			if ($one["count"]>1) {
				$foundDupe = true;
			}
		}
		if ($foundDupe == false) {
			$out .= "\nNo duplicate queries found.";
		} else {
			$out .= "\nDuplicate queries found:\n";
			foreach($polarbear_db->debug_queries as $one) {
				if ($one["count"]>1) {
					$out .= print_r($one, true);
				}
			}
		}
	}
	//*
	return $out;
}


/**
 * outputs a complete xhtml-page, with some basic styles to make it look not completely superugly
 * @param $title 
 * @param $content The content inside the body-tag
 */
function polarbear_xhtml_page($title, $content) {
	$out = '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>' . $title . '</title>
		<style type="text/css">
			body {
				background-color: white;
				color: black;
				font-family: sans-serif;
				padding: 5em;
			}
			small {
				color: #666;
			}
		</style>
	</head>
	<body>
		' . $content . '
	</body>
	</html>
	';
	return $out;
}



/**
 * Resize an image
 *
 * @param $file input file. jpg, gif or png
 * @param $destFile file to save to. For example to the cache.
 * @param $max_width int
 * @param $max_height int
 */
function polarbear_scaleImageWithGD($file, $destFile, $max_width=null, $max_height=null) {

    list($width, $height, $image_type) = getimagesize($file);

    switch ($image_type)
    {
        case 1: $src = imagecreatefromgif($file); break;
        case 2: $src = imagecreatefromjpeg($file);  break;
        case 3: $src = imagecreatefrompng($file); break;
        default: false;
    }

	// if $max_width is det but not $max_height, don't freak out
	if (!empty($max_width) && empty($max_height)) {
		$max_height = $max_width*99;
	} elseif (empty($max_width) && !empty($max_height)) {
		$max_width = $max_height*99;
	}

    $x_ratio = $max_width / $width;
    $y_ratio = $max_height / $height;

    if( ($width <= $max_width) && ($height <= $max_height) ){
        $tn_width = $width;
        $tn_height = $height;
        } elseif (($x_ratio * $height) < $max_height){
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $max_width;
        } else {
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $max_height;
    }

    $tmp = imagecreatetruecolor($tn_width,$tn_height);

    /* Check if this image is PNG or GIF to preserve its transparency */
    if(($image_type == 1) OR ($image_type==3))
    {
        imagealphablending($tmp, false);
        imagesavealpha($tmp,true);
        $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
        imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
    }
    imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

    switch ($image_type)
    {
        case 1: imagegif($tmp, $destFile); break;
        case 2: imagejpeg($tmp, $destFile, 100);  break; // best quality
        case 3: imagepng($tmp, $destFile, 0); break; // no compression
        default: return false;
    }

	return true;

}




/**
 * Are we loaded through a shortname, i.e. are we rewrited
 * @return bool
 */
function pb_rewrite_is_in_use() {
	global $polarbear_rewrite_in_use;
	return (bool) $polarbear_rewrite_in_use;
}

function pb_article_is_autoloaded() {
	global $polarbear_article_is_autoloaded, $polarbear_rewrite_in_use;
	if (isset($_GET['polarbear-page'])) {
		return (bool) ($_GET['polarbear-page'] || $polarbear_rewrite_in_use);
	} else {
		return (bool) $polarbear_rewrite_in_use;
	}
	
}

function pb_add_site_stats($args) {
	$args["buffer"] = $args["buffer"] . "\n<!--\n" . polarbear_script_end_stats() . "\n-->";
	return $args;
}


/**
 * Wakes upp the bear
 * Connects to database, fetch the right template, creates variables for the current article and user.
 * And some other stuff too
 */
function polarbear_boot() {

	// Global for observers
	global $pb_observer, $polarbear_observers;

	pb_event_fire("pb_boot_start");
	// only enable the output buffer if we are viewing a article, i.e. don't use it admin area
	if (pb_article_is_autoloaded()) {
		register_shutdown_function("pb_shutdown_function");
		ob_start();
		pb_event_attach("pb_page_contents", "pb_add_site_edit");
		pb_event_attach("pb_page_contents", "pb_add_site_stats");
	}
	
	// enable shortcodes
	pb_event_attach("article_output", "pb_do_shortcode");

	polarbear_connect_db();

	define("POLARBEAR_STORAGEPATH", rtrim(polarbear_setting('storagepath'), "/") . "/");
	define("POLARBEAR_ATTACHPATH", POLARBEAR_STORAGEPATH . "files/");
	define("POLARBEAR_CACHEPATH", POLARBEAR_STORAGEPATH . 'cache/');
	define("POLARBEAR_IMAGEMAGICK", polarbear_setting('imagemagickpath'));

	// Try to determine what our domain is
	if ($_SERVER['HTTP_HOST'] == 'localhost') {
		DEFINE('POLARBEAR_DOMAIN', 'localhost');
	} else {
		$arrPolarbear_domain = polarbear_parseUrl($_SERVER['HTTP_HOST']);
		$polarbear_domain = "";
		if ($arrPolarbear_domain["subdomain"]) {
			$polarbear_domain .= $arrPolarbear_domain["subdomain"] . ".";
		}
		$polarbear_domain .= $arrPolarbear_domain['domain'] . $arrPolarbear_domain['extension']; // todo: denna måste testas på riktigt
		DEFINE('POLARBEAR_DOMAIN', $polarbear_domain);
	}

	/*
		starta session
		todo: behöver vi sessionen? använda $polarbear_u för att lagra saker i istället? 
		vore coolt.. och bra?! problem med session: tas bort hux-flux. fördel med session: om man behöver kundvagnar etc..
		session_cache_limiter('private');
	*/
	global $polarbear_cache_allow_client_caching;	
	if ($polarbear_cache_allow_client_caching) {
		session_cache_limiter('private_no_expire');
	}
	session_start();
	
	// fetch settings
	$polarbear_settings = polarbear_getGlobalSettings();

	// setup user
	global $polarbear_u;
	$polarbear_u = polarbear_user_login_from_cookie();
	
	// Fix magic quotes, code fom simple php framework
	if(get_magic_quotes_gpc())
	{
		$_POST    = polarbear_fix_slashes($_POST);
		$_GET     = polarbear_fix_slashes($_GET);
		$_REQUEST = polarbear_fix_slashes($_REQUEST);
	}

	// finally, load selected article, if any, and load it's template, if it exists. this is a truly awesom function!
	polarbear_article_bootload();

	pb_event_fire("pb_boot_end");

}


/**
 * Check if crrently logged in user can do the action specified in $what.
 * This function just forwards $what to polarbear_user->can().
 * If no user logged in this function returns false.
 *
 * @param string $what
 * @retun bool
 */
function polarbear_user_can($what) {
	global $polarbear_u;
	$can = false;
	if (isset($polarbear_u) && $polarbear_u != false) {
		$can = $polarbear_u->can($what);
	}
	return $can;
}

/**
 * Returns an array of words that are reserved for shortname use
 */
function pb_shortname_reserved_words() {
	return array("image","images","article","articles","file","files","polarbear","pb","tag");
}

/**
 * is site edit enabled
 * @return bool
 */
function pb_is_site_edit_enabled() {
	return (bool) $_COOKIE["pb_site_edit_icons_enabled"];
}


function pb_event_attach($event, $function, $prio=50) {

	global $polarbear_observers;

	if (!isset($polarbear_observers[$event])) {
		$polarbear_observers[$event] = array();
	}
	$polarbear_observers[$event][] = array(
		"what" => $event,
		"function" => $function,
		"prio" => $prio
	);

}

function pb_event_fire($event, $arrArgs = null) {
	global $polarbear_observers;
	if (!is_array($arrArgs)) {
		$arrArgs = array();
	}

	if (empty($polarbear_observers)) {
		return $arrArgs;
	}

	// auto add some stuff to the arguments-array
	global $polarbear_render_start_ms;
	$arrArgs["pb_microtime_since_start"] = microtime(true) - $polarbear_render_start_ms;
	$arrArgs["event"] = $event;
			
	// find and fire handler for event
	$contentToReturn = null;
	if (isset($polarbear_observers[$event])) {
		// actions for event exists, fire them
		// @todo: fire them in prio order
		foreach ($polarbear_observers[$event] as $oneEvent) {
			if (is_callable($oneEvent["function"])) {
				$arrArgsReturned = call_user_func($oneEvent["function"], $arrArgs);
				$arrArgs = polarbear_extend($arrArgs, $arrArgsReturned);
			}
		}
	}

	// return whole array
	return $arrArgs;
}


/**
 * 
 */
function pb_add_site_edit($args) {

	global $polarbear_u;
	$pb_been_logged_in = (bool) $_COOKIE["pb_been_logged_in"];
	$visibleStyle = "";
	$includeJS = false;

	$pb_show_site_edit_tab = (isset($_SESSION["pb_show_site_edit_tab"])) ? $_SESSION["pb_show_site_edit_tab"] : null;
	$pb_wrong_login = (isset($_SESSION["pb_wrong_login"])) ? $_SESSION["pb_wrong_login"] : null;
	$pb_ok_login = (isset($_SESSION["pb_ok_login"])) ? $_SESSION["pb_ok_login"] : null;
	$pb_logged_out = (isset($_SESSION["pb_logged_out"])) ? $_SESSION["pb_logged_out"] : null;
	unset($_SESSION["pb_show_site_edit_tab"]);
	unset($_SESSION["pb_wrong_login"]);
	unset($_SESSION["pb_ok_login"]);
	unset($_SESSION["pb_logged_out"]);
	
	// make the pb-box visible if we a) just (tried) logged in or b) just logged out
	if ($pb_show_site_edit_tab == "1") {
		$visibleStyle = "left: 0px;";
	}

	$out = "";
	if ($polarbear_u && $polarbear_u->isAdmin()) {

		$includeJS = true;

		$okLoginTxt = "";
		if ($pb_ok_login == "1") {
			$okLoginTxt = "<p class='pb-site-edit-msg'>You are now logged in.</p>";
		}	

		$editiconslink = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=siteEditToggleEditIcons";
		if (pb_is_site_edit_enabled()) {
			$showHideIcons = "<br />show | <a href='$editiconslink'>hide</a>";
		} else {
			$showHideIcons = "<br /><a href='$editiconslink'>show</a> | hide";
		}
		$out .= "
			<div id='polarbear-site-edit-tab' style='$visibleStyle'>
				<a href='#' id='polarbear-site-edit-tab-logo'><img src='" . POLARBEAR_WEBPATH . "images/polarbear/pb-tab.png' alt='PolarBearCMS' width='39' height='69' /></a>
				<div id='polarbear-site-edit-tab-menu'>
					$okLoginTxt
					<strong>Edit icons</strong>
					$showHideIcons
					<br />
					<br /><strong>Go to</strong>
					<br /><a href='" . POLARBEAR_WEBPATH . "?treepage=gui/overview.php'>Articles</a>
					<br /><a href='" . POLARBEAR_WEBPATH . "?treepage=gui/files.php'>Images</a>
					<br /><a href='" . POLARBEAR_WEBPATH . "?treepage=gui/users.php'>Users</a>
					<br />
					<br /><strong>" . $polarbear_u . "</strong>
					<br /><a href='" . POLARBEAR_WEBPATH . "login.php?logout=1&amp;returnto=referer'>Log out</a>
				</div>
			</div>
			";
	} elseif ($pb_been_logged_in) {

		$includeJS = true;
	
		// not currently logged in, but has been
		$wrongLoginTxt = "";
		if ($pb_wrong_login == "1") {
			$wrongLoginTxt = "<p class='pb-site-edit-msg'>Wrong email or password. Please try again.</p>";
		}
		$loggedOutTxt = "";
		if ($pb_logged_out == "1") {
			$loggedOutTxt = "<p class='pb-site-edit-msg'>You have been logged out.</p>";
		}
		$out .= "
			<div id='polarbear-site-edit-tab' style='$visibleStyle'>
				<a href='#' id='polarbear-site-edit-tab-logo'><img src='" . POLARBEAR_WEBPATH . "images/polarbear/pb-tab.png' alt='PolarBearCMS' /></a>
				<div id='polarbear-site-edit-tab-menu'>
						
					<form method='post' action='" . POLARBEAR_WEBPATH . "login.php'>
						$wrongLoginTxt
						$loggedOutTxt
						<p>
							<label for='pb-login-email'>Email</label>
							<input id='pb-login-email' name='login-email' type='text' size='20' class='text ui-widget-content ui-corner-all' />
						</p>
						<p>
							<label for='pb-login-password'>Password</label>
		
							<input id='pb-login-password' name='login-password' type='password' size='20' class='password text ui-widget-content ui-corner-all' />
						</p>
						<p>
							<input type='checkbox' class='checkbox' value='1' name='login-remember-me' id='pb-login-remember-me' />
							<label class='checkbox' for='pb-login-remember-me'>Remember me on this computer</label>
						</p>						
						<p>
							<input type='submit' value='Log in' class='submit fg-button ui-state-default ui-priority-primary ui-corner-all' name='login' />
							<input type='hidden' name='returnto' value='referer' />
						</p>
						<p>
							<a href='" . POLARBEAR_WEBPATH . "login.php?forgotPassword'>Forgot your password?</a>
						</p>
					</form>

				</div>
			</div>
			";

	}
	
	if ($includeJS) {
		$out .= "
		<script type='text/javascript'>
			/* <![CDATA[ */
			(function() {
				var $ = function(e) {
					return document.getElementById(e);
				}
				// From http://v3.thewatchmakerproject.com/zebra.html
				var Event = {
					add: function(obj,type,fn) {
						if (obj.attachEvent) {
							obj['e'+type+fn] = fn;
							obj[type+fn] = function() { obj['e'+type+fn](window.event); }
							obj.attachEvent('on'+type,obj[type+fn]);
						} else
						obj.addEventListener(type,fn,false);
					},
					remove: function(obj,type,fn) {
						if (obj.detachEvent) {
							obj.detachEvent('on'+type,obj[type+fn]);
							obj[type+fn] = null;
						} else
						obj.removeEventListener(type,fn,false);
					}
				}
				var tab = $('polarbear-site-edit-tab');
				if (tab.style.left=='') {
					tab.style.left = '-200px';
				}
				var isOpen = function() {
					leftPos = parseInt(tab.style.left);
					if (leftPos == 0) {
						return true;
					} else {
						return false;
					}
				}
				// add stylesheet, code (slighlty modified) from http://cse-mjmcl.cse.bris.ac.uk/blog/2005/08/18/1124396539593.html
				if(document.createStyleSheet) {		
					document.createStyleSheet('" . POLARBEAR_WEBPATH . "includes/css/siteEdit.css');
				} else {
					var style = '" . POLARBEAR_WEBPATH . "includes/css/siteEdit.css';
					var newSS=document.createElement('link');
					newSS.rel='stylesheet';
					newSS.href=style;
					document.getElementsByTagName('head')[0].appendChild(newSS);
				}
				var animate = function(direction) {
					var curLeft = parseInt(tab.style.left);
					if (direction == 'out') {
						if (curLeft<0) {
							tab.style.left = (curLeft + 20) + 'px';
							//setTimeout(animate, 0, direction);
							setTimeout(function() { animate(direction); }, 0);
						}
					} else if (direction == 'in') {
						if (curLeft>-200) {
							tab.style.left = (curLeft - 20) + 'px';
							setTimeout(function() { animate(direction) }, 0);
						}
					}
		
				}
				Event.add(window, 'load', function() {
				
					// attach events
					$('polarbear-site-edit-tab-logo').onclick = function() {
						if (tab.style.left == '0px') {
							animate('in');
						} else {
							animate('out');
						}
						return false;
		
					}
					$('polarbear-site-edit-tab-logo').onmouseover = function () {
						if (!isOpen()) {
							tab.style.opacity = '1';
						}
					}
					$('polarbear-site-edit-tab-logo').onmouseout = function () {
						if (!isOpen()) {
							tab.style.opacity = '.9';
						}
					}
					
				});
				
			})(); // end PolarBear on site javascript edit-thingie
			/* ]]> */
		</script>";
	} // if includeJS

	// attach right after <body> or right before </body>
	$newBuffer = str_replace("</body>", "$out</body>", $args["buffer"]);
	$args["buffer"] = $newBuffer;
	return $args;
}

/**
 * Shutdown = get buffer and fire away events
 */
function pb_shutdown_function() {
	$buffer = ob_get_clean();
	$args = pb_event_fire("pb_page_contents", array("buffer"=>$buffer));
	echo $args["buffer"];

}

/**
 * Simpy returns the buffer. Attached by defautl to event "pb_page_contents"
 * to make sure something always gets returned
 */
function pb_ob_output_callback_return_buffer($args) {
	return $args["buffer"];
}

/**
 * Removes all files in the cache
 */
function pb_clear_cache() {
	foreach (new DirectoryIterator(POLARBEAR_CACHEPATH) as $oneFile) {
		if($oneFile->isDot()) continue;
		unlink(POLARBEAR_CACHEPATH . $oneFile->getFilename());
	}
}


/**
 * fetches articles
 * example:
 * $fetcher = new polarbear_articlefetcher();
 * $fetcher->tagMustInclude(8); // must include tag with id 8
 * $fetcher->articles(); // an array with all articles that match
 *
 * Existing methods:
 * tagMustIncludeAnyOf()
 * tagMustInclude()
 * titleIs
 *
 * @todo: why is not this in it's own file...?
 */
class polarbear_articlefetcher {

	private $options = array();
	private $tagMustInclude = array(); // ID på taggar som måste finnas
	private $tagMustNotInclude = array(); // ID på taggar som inte får finnas
	private $tagMustIncludeAnyOf = array(); // array med IDn på taggar som det måste finnas nån av iaf
	private $articleParentMustNotBe = array(); // ID på föräldrar om artikeln inte får ha
	private $articleParentMustBe = array(); // ID på föräldrar som artikeln måste ha (should only be one since an article only can have one parent...)
	private 
		$includeNonPublished = false,
		$titleIs = null,
		$orderBy = "prio";

	function __construct() {	
	
	}

	function articleParentMustBe($parentID) {
		if ($parentID) {
			$this->articleParentMustBe[] = $parentID;
		}
	}
	function clearArticleParentMustBe() {
		$this->articleParentMusBe = array();
	}

	
	function articleParentMustNotBe($parentID) {
		if ($parentID) {
			$this->articleParentMustNotBe[] = $parentID;
		}
	}
	function clearArticleParentMustNotBe() {
		$this->articleParentMustNotBe = array();
	}
	
	function tagMustInclude($tagID) {
		if ($tagID) {
			$this->tagMustInclude[] = $tagID;
		}
	}
	function clearTagMustInclude() {
		$this->tagMustInclude = array();
	}

	
	// this one is not tested
	function tagMustNotInclude($tagID) {
		if ($tagID) {
			$this->tagMustNotInclude[] = $tagID;
		}
	}

	function clearTagMustNotInclude() {
		$this->tagMustNotInclude = array();
	}
	
	
	/**
	 *
	 */
	function titleIs($str) {
		$this->titleIs = $str;
	}
	function clearTitleIs() {
		$this->titleIs = null;
	}
	
	/**
	 * @param $arrTagsIDs array with IDs
	 */
	function tagMustIncludeAnyOf($arrTagIDs) {
		if (!empty($arrTagIDs)) {
			$this->tagMustIncludeAnyOf[] = $arrTagIDs;
		}
	}
	function clearTagMustIncludeAnyOf() {
		$this->tagMustIncludeAnyOf = array();
	}

	
	function articles() {
		$sql = $this->getSQL();
		# echo "<br>$sql<br>";
		global $polarbear_db;
		$arr = array();
		if ($r = $polarbear_db->get_results($sql)) {
			foreach ($r as $row) {
				$arr[] = polarbear_article::getInstance($row->id);
			}
		}
		return $arr;
	}
	
	function orderBy($orderBy) {
		$this->orderBy = $orderBy;
	}
	
	function getSQL() {

		global $polarbear_db;
		$mustIncludeNotFound = false;
	
		$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_articles";
		$where = "";

		// inte ta med icke publicerade etc.
		/*
			status = 'published' AND datePublish < now() AND (dateUnpublish > now() OR dateUnpublish IS NULL) ";
		*/
		$where .= " status = 'published' AND datePublish < now() AND (dateUnpublish > now() OR dateUnpublish IS NULL) ";

		if (isset($this->titleIs)) {
			$where .= " AND titleArticle = '" . mysql_real_escape_string($this->titleIs) . "' ";
		}
		
		// tags that the articles must have
		$strTagMustInclude = "";
		if (!empty($this->tagMustInclude)) {
			$foundTagMustInclude = false;
			$strTagIDs = join($this->tagMustInclude, ",");
			$sqlTags = "
				SELECT
					count(tr.articleID) as count, tr.articleID, tr.tagID
				FROM " . POLARBEAR_DB_PREFIX . "_article_tag_relation AS tr
				INNER JOIN " . POLARBEAR_DB_PREFIX . "_article_tags as t on t.id = tr.tagID
				WHERE
					tr.tagID IN ($strTagIDs)
					AND t.isDeleted = 0
				GROUP by tr.articleID
			";
			if ($r = $polarbear_db->get_results($sqlTags)) {
				// keep the hits that have count = sizeof(tagMustInclude) 
				foreach ($r as $row) {
					if ($row->count == sizeof($this->tagMustInclude)) {
						$strTagMustInclude .= "$row->articleID,";
					}
				}
			}
			$strTagMustInclude = preg_replace("/,$/", "", $strTagMustInclude);

			if ($strTagMustInclude) {
				$strTagMustInclude = " AND id IN ($strTagMustInclude) ";
			} else {
				$foundTagMustInclude = false;
				$strTagMustInclude = " AND false ";
			}
			
		}
		$where .= $strTagMustInclude;
		// end tagMustInclude

		// tags that the article must not have
		$arrStrTagMustNotInclude = "";
		if (!empty($this->tagMustNotInclude)) {
			$foundTagMustNotInclude = false;
			$strTagIDs = join($this->tagMustNotInclude, ",");
			$sqlTags = "
				SELECT
					count(tr.articleID) as count, tr.articleID, tr.tagID
				FROM " . POLARBEAR_DB_PREFIX . "_article_tag_relation AS tr
				INNER JOIN " . POLARBEAR_DB_PREFIX . "_article_tags as t on t.id = tr.tagID
				WHERE
					tr.tagID IN ($strTagIDs)
					AND t.isDeleted = 0
				GROUP by tr.articleID
			";
			if ($r = $polarbear_db->get_results($sqlTags)) {
				// keep the hits that have count = sizeof(tagMustInclude) 
				foreach ($r as $row) {
					if ($row->count == sizeof($this->tagMustInclude)) {
						$strTagMustNotInclude .= "$row->articleID,";
					}
				}
			}
			$strTagMustNotInclude = preg_replace("/,$/", "", $strTagMustInclude);
			if ($strTagMustNotInclude) {
				$strTagMustNotInclude = " AND id NOT IN ($strTagMustInclude) ";
			} else {
				$foundTagMustNotInclude = false;
				// $strTagMustNotInclude = " AND false ";
			}
		}
		$where .= $strTagMustInclude;
		// end tag must not include


		// include article that have any of these tags...
		if (!empty($this->tagMustIncludeAnyOf)) {
			$strTagMustIncludeAnyOf = "";
			foreach ($this->tagMustIncludeAnyOf as $oneArrTagIDs) {
				// ok, so at least one of the ids in $oneArrTagIDs must be found among the articles				
				$strTagIDs = join($oneArrTagIDs, ",");
				$sqlTags = "
					SELECT DISTINCT
						#count(tr.articleID) as count, 
						tr.articleID, tr.tagID
					FROM " . POLARBEAR_DB_PREFIX . "_article_tag_relation AS tr
					INNER JOIN " . POLARBEAR_DB_PREFIX . "_article_tags as t on t.id = tr.tagID
					WHERE
						tr.tagID IN ($strTagIDs)
						AND t.isDeleted = 0
					#GROUP by tr.articleID
				";
				if ($r = $polarbear_db->get_results($sqlTags)) {
					foreach ($r as $row) {
						$strTagMustIncludeAnyOf .= "$row->articleID,";
					}
					$strTagMustIncludeAnyOf = preg_replace("/,$/", "", $strTagMustIncludeAnyOf);
					$strTagMustIncludeAnyOf = " AND id IN ($strTagMustIncludeAnyOf) ";
				} else {
					// no articles found
					$strTagMustIncludeAnyOf = " AND false ";
				}
				
				$where .= $strTagMustIncludeAnyOf;

			}
		}

		// parentID, must not
		$strArticleParentMustNotBeWhere = "";
		if ($this->articleParentMustNotBe) {
			$strArticleParentMustNotBeWhere = join(",", $this->articleParentMustNotBe);
			$strArticleParentMustNotBeWhere = " AND parentID NOT IN ($strArticleParentMustNotBeWhere) ";
		}
		$where .= $strArticleParentMustNotBeWhere;

		// parentID, must be
		$strArticleParentMustBeWhere = "";
		if ($this->articleParentMustBe) {
			$strArticleParentMustBeWhere = join(",", $this->articleParentMustBe);
			$strArticleParentMustBeWhere = " AND parentID IN ($strArticleParentMustBeWhere) ";
		}
		$where .= $strArticleParentMustBeWhere;

		if ($where) {
			$where = preg_replace("/^ AND/", "", $where);
			$sql .= " WHERE $where";
		}
		
		$sql .= " ORDER BY $this->orderBy";

		return $sql;
	
	}
	
}



/**
 * Easy get and set values. For anything!
 */
function polarbear_storage($key = null, $value = null) {
	if (!isset($key) && !isset($value)) {
		// nothing set
		return null;
	}
	global $polarbear_db;

	# id key value
	if (isset($key) && !isset($value)) {
		$key = trim($key);
		// only key is set, so just fetch the current value of that key
		$sqlkey = mysql_real_escape_string($key);
		$sql = "SELECT thevalue FROM " . POLARBEAR_DB_PREFIX . "_storage WHERE thekey = '$sqlkey'";
		if ($r = $polarbear_db->get_var($sql)) {
			$value = unserialize($r);
			return $value;
		} else {
			// key does not exist
			return null;
		}
	}

	if (isset($key) && isset($value)) {
		$key = trim($key);
		$value = trim($value);
		// both key and value is set, so set the value for the key
		$sqlkey = mysql_real_escape_string($key);
		$sqlvalue = mysql_real_escape_string(serialize($value));
		
		// delete existing
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_storage WHERE thekey = '$sqlkey'";
		$polarbear_db->query($sql);
		
		// add new
		$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_storage SET thekey = '$sqlkey', thevalue = '$sqlvalue'";
		$rs = $polarbear_db->query($sql);
		
		return $value;
	}

}


function pb_search_split_terms($terms){

	$terms = preg_replace("/\"(.*?)\"/e", "pb_search_transform_term('\$1')", $terms);
	$terms = preg_split("/\s+|,/", $terms);

	$out = array();

	foreach($terms as $term){

		$term = preg_replace("/\{WHITESPACE-([0-9]+)\}/e", "chr(\$1)", $term);
		$term = preg_replace("/\{COMMA\}/", ",", $term);

		$out[] = $term;
	}

	return $out;
}

function pb_search_transform_term($term){
	$term = preg_replace("/(\s)/e", "'{WHITESPACE-'.ord('\$1').'}'", $term);
	$term = preg_replace("/,/", "{COMMA}", $term);
	return $term;
}

function pb_search_escape_rlike($string){
	return preg_replace("/([.\[\]*^\$])/", '\\\$1', $string);
}

function pb_search_db_escape_terms($terms){
	$out = array();
	foreach($terms as $term){
		$out[] = '[[:<:]]'.AddSlashes(pb_search_escape_rlike($term)).'[[:>:]]';
	}
	return $out;
}

function pb_search_perform($terms, $options){

	$defaults = array(
		"cats" => null
	);
	
	$options = polarbear_extend($defaults, $options);

	$terms = pb_search_split_terms($terms);
	$terms_db = pb_search_db_escape_terms($terms);
	$terms_rx = pb_search_rx_escape_terms($terms);

	$parts = array();
	foreach($terms_db as $term_db){
		$parts[] = "titleArticle RLIKE '$term_db'";
		$parts[] = "titleNav RLIKE '$term_db'";
		$parts[] = "titlePage RLIKE '$term_db'";
		$parts[] = "body RLIKE '$term_db'";
		$parts[] = "teaser RLIKE '$term_db'";
	}
	$parts = implode(' OR ', $parts);

	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE ( $parts ) ";
	
	// only include published articles
	$sql .= " AND (status = 'published' AND datePublish < now() AND (dateUnpublish > now() OR dateUnpublish IS NULL)) ";
	$sql .= " AND (isRevisionTo IS NULL)";
#	AND (isRevisionTo IS NULL OR isRevisionTo IS NOT NULL AND isRevisionTo <> $this->id)

	// if cats is set, only include articles that are below this category $options
	if (!empty($options["cats"])) {
		$arrChildIds = array();
		foreach ($options["cats"] as $val) {
			if ($tmpA = polarbear_article::getInstance($val)) {
				$descendants = $tmpA->descendants();
				foreach ($descendants as $oneA) {
					$arrChildIds[] = $oneA->getId();
				}
			}
		}
		$strChildIds = join(",", $arrChildIds);
		$sql .= " AND id IN ($strChildIds)";

	}
	// descendants
	
	#echo $sql;
	$rows = array();

	$result = mysql_query($sql) or die(mysql_error());
	while($row = mysql_fetch_array($result, MYSQL_ASSOC)){

		$row[score] = 0;

		foreach($terms_rx as $term_rx){
			$row["score"] += preg_match_all("/$term_rx/i", $row["titleArticle"], $null);
			$row["score"] += preg_match_all("/$term_rx/i", $row["titleNav"], $null);
			$row["score"] += preg_match_all("/$term_rx/i", $row["titlePage"], $null);
			$row["score"] += preg_match_all("/$term_rx/i", $row["body"], $null);
			$row["score"] += preg_match_all("/$term_rx/i", $row["teaser"], $null);
		}

		$rows[] = $row;
	}

	uasort($rows, 'pb_search_sort_results');

	return $rows;
}

function pb_search_rx_escape_terms($terms){
	$out = array();
	foreach($terms as $term){
		$out[] = '\b'.preg_quote($term, '/').'\b';
	}
	return $out;
}

function pb_search_sort_results($a, $b){

	$ax = $a[score];
	$bx = $b[score];

	if ($ax == $bx){ return 0; }
	return ($ax > $bx) ? -1 : 1;
}

function pb_search_html_escape_terms($terms){
	$out = array();

	foreach($terms as $term){
		if (preg_match("/\s|,/", $term)){
			$out[] = '"'.HtmlSpecialChars($term).'"';
		}else{
			$out[] = HtmlSpecialChars($term);
		}
	}

	return $out;	
}

function pb_search_pretty_terms($terms_html){

	if (count($terms_html) == 1){
		return array_pop($terms_html);
	}

	$last = array_pop($terms_html);

	return implode(', ', $terms_html)." and $last";
}



function pb_search_highlight($text, $terms_rx){

	$start = '(^|<(?:.*?)>)';
	$end   = '($|<(?:.*?)>)';

	return preg_replace(
		"/$start(.*?)$end/se",
		"StripSlashes('\\1').".
			"pb_search_highlight_inner(StripSlashes('\\2'), \$terms_rx).".
			"StripSlashes('\\3')",
		$text
	);
}

function pb_search_highlight_inner($text, $terms_rx){

	foreach($terms_rx as $term_rx){
		$text = preg_replace(
				"/($term_rx)/ise",
				"pb_search_highlight_do(StripSlashes('\\1'))", 
				$text
			);
	}

	return $text;
}


function pb_search_highlight_do($fragment){

	return "<strong>$fragment</strong>";
}

/**
 * From http://us2.php.net/manual/en/function.str-ireplace.php#87417
 * Slightly modified
 */
/*
function pb_highlightStr($haystack, $needle) {
     // return $haystack if there is no highlight color or strings given, nothing to do.
    if (strlen($haystack) < 1 || strlen($needle) < 1) {
        return $haystack;
    }
    preg_match_all("/$needle+/i", $haystack, $matches);
    if (is_array($matches[0]) && count($matches[0]) >= 1) {
        foreach ($matches[0] as $match) {
            $haystack = str_replace($match, '<strong>'.$match.'</strong>', $haystack);
        }
    }
    return $haystack;
}
*/

// from http://krijnhoetmer.nl/stuff/php/word-highlighter/
// slightly modified
function pb_highlightStr($haystack, $needle) {
	$output = preg_replace(
	  "/(>|^)([^<]+)(?=<|$)/esx",
	  "'\\1' . str_ireplace('" . $needle . "', '<b>" . $needle . "</b>', '\\2')",
	  $haystack
	);
	return $output;
}


/**
 * Search for articles
 * Much code from http://www.iamcal.com/publish/articles/php/search/
 *
 * Example:
 *
 *	$r = pb_search_results(array(
 *		"q" => $_GET["q"],
 *		"cats" => "2,16"
 *	));
 *	echo = $r["content"];
 *
 */
function pb_search_results($options) {

	$defaults = array(
		"q" => $_GET["q"],
		"cats" => null // only include articles that are descendents of any of the articleIDs in "categories"
	);
	
	$options = polarbear_extend($defaults, $options);

	$options["q"] = trim ($options["q"]);

	$arrCats = null;
	if (isset($options["cats"])) {
		$arrCats = explode(",", $options["cats"]);
	}

	$results = pb_search_perform($options["q"], array("cats"=>$arrCats));
	$arrTerms = pb_search_split_terms($options["q"]);
	
	$strSearchResults = "";
	$strSearchResultsFull = "";
	$numHits = 0;
	if (empty($options["q"])) {
		$strSearchResultsFull .= "<p>Please enter keywords to search for.</p>";
	} else if (empty($results)) {
		#$strSearchResults .= "<p>No hits</p>";
		$strSearchResultsFull .= "<p>Your search did not match any documents.</p>";
	} else {
		$strSearchResultsFull .=  "<p>" . sizeof($results) . " documents matched your search:</p>";
		$strSearchResults .= "<ul class='pb-search-results'>";
		foreach($results as $oneResult) {
			$numHits++;
			$domain = POLARBEAR_DOMAIN;
			$score = $oneResult["score"];
			$a = polarbear_article::getInstance($oneResult["id"]);
	
			$teaserAndBody = strip_tags($a->getTeaser() . $a->getBody());
			$fullPageTitle = $a->fullPageTitle();
			foreach ($arrTerms as $oneTerm) {
				$fullPageTitle = pb_highlightStr($fullPageTitle, $oneTerm);
				$teaserAndBody = pb_highlightStr($teaserAndBody, $oneTerm);
			}
			
			$format = '
				{$teaserAndBody=cat($teaser $body)}
				{$teaserAndBody=strip_tags($teaserAndBody)}
				
				{capture "summary"}
					'.$teaserAndBody.'
				{/capture}
	
				<li>
					<a href="{$href}">'.$fullPageTitle.'</a>
					<span class="summary">{truncate $.capture.summary length=150}</span>
					<span class="href">'.$domain.'{$href}</span>
				</li>
			';
			$strSearchResults .= $a->output($format);
		}
		$strSearchResults .= "</ul>";
	}
	
	$strSearchResultsFull .= $strSearchResults;
	
	$arrReturn = array(
		"hitsUL" => $strSearchResults,
		"numHits" => $numHits,
		"content" => $strSearchResultsFull
	);
	return $arrReturn;

}

/**
 * get all existing unique labels for user values
 * @return array
 */
function pb_users_values_all_unique_labels() {
	$arr = array();
	$sql = "SELECT DISTINCT name FROM " . POLARBEAR_DB_PREFIX . "_users_values ORDER BY name ASC";
	global $polarbear_db;
	if ($r = $polarbear_db->get_results($sql)) {
		foreach ($r as $one) {
			$arr[] = $one->name;
		}
	}
	return $arr;
}


/**
 * adds something to the log/"recent activities"
 */
function pb_log($options) {

	global $polarbear_db, $polarbear_u;
	
	/*
		$options = array
			event
			isNew
			article | file | user
			objectName - used by for example file since the file is deleted and the reference to the name is lost
	*/

	$user = (int) $polarbear_u->id;

	// what has been done? create, update, delete
	if ($options["event"] == "pb_article_deleted") {
		$type = "delete";
		$objectType = "article";
		$objectID = $options["article"]->getId();
	} elseif ($options["event"] == "pb_article_saved") {
		$type = "update";
		if ($options["isNew"]) { $type = "create"; }
		$objectType = "article";
		$objectID = $options["article"]->getId();
	} elseif ($options["event"] == "pb_user_saved") {
		$type = "update";
		if ($options["isNew"]) { $type = "create"; }
		$objectType = "user";
		$objectID = $options["user"]->id;
	} elseif ($options["event"] == "pb_user_deleted") {
		$type = "delete";
		$objectType = "user";
		$objectID = $options["userID"];
	} elseif ($options["event"] == "pb_file_saved") {
		$type = "update";
		$objectType = "file";
		$objectID = $options["file"]->id;
		if ($options["isNew"]) { $type = "create"; $user = $options["file"]->uploaderID; }
	} elseif ($options["event"] == "pb_file_deleted") {
		$type = "delete";
		$objectType = "file";
		$objectID = $options["file"]->id;
		if ($options["isNew"]) { $type = "create"; }
	}
	
	$objectName = null;
	if (isset($options["objectName"])) {
		$objectName = $options["objectName"];
		$sqlObjectName = ", objectName = '" . $polarbear_db->escape($objectName) . "' ";
	}
	
	$objectID = (int) $objectID;

	$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_log SET date = now(), user = $user, type = '$type', objectType='$objectType', objectID = $objectID $sqlObjectName ";

	$polarbear_db->query($sql);

}

?>