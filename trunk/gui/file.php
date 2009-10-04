<?php

/**
 * Hanteringar visning av bilder och filer
 * todo: hämta bara från cache varannan gång i FF
 * todo: header skrivs ut på samma sätt för bild, borde vara funktion
 * todo: borde det mesta här överföras till polarbear-file-klassen istället?
 *
 *
 * https://developer.mozilla.org/En/Using_Firefox_1.5_caching
 */
global $polarbear_cache_allow_client_caching;
$polarbear_cache_allow_client_caching = true;
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";

pb_event_fire("file_show_start");

$fileID = (int) $_GET["fileID"];
if (!$fileID) {
	header("HTTP/1.0 404 Not Found"); 
	pb_event_fire("file_show_not_found", array("fileID" => $fileID));
	die('Not Found');
}
// Kontrollera att fil finns
$file = new PolarBear_File($fileID);
if (empty($file->size) || !file_exists($file->filepath)) {
	header("HTTP/1.0 404 Not Found"); 
	pb_event_fire("file_show_not_found", array("fileID" => $fileID));
	die('Not Found');
}

$isImage = $file->isImage();

// De parametrar en bild kan ha
$arrImageOptions = array('size', 'unsharp', 'w', 'h', 'q');

// kolla om någon av parametrarna finns i GET
$imageOptionsExists = false;
foreach ($_GET as $key => $val) {
	if (in_array($key, $arrImageOptions) && !empty($val)) {
		$imageOptionsExists = true;
		break;
	}
}

$lastModified = gmdate("D, d M Y H:i:s", strtotime($file->dateModified)) . ' GMT';
$etag = md5($lastModified . serialize($_GET));

// om vanlig fil eller bild utan argument (dvs. inte resize eller liknande)
if (!$imageOptionsExists) {
	
	#polarbear_hd("file type: regular file");

	// om clienten skickar med http_if_modified_since eller http_if_none_match ska vi kolla det
	// bug: firefox verkar godta en 304 första gången men nästa gång så skickar den ingen if-modified-since...
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastModified) {
			header("HTTP/1.1 304 Not Modified");
			header("ETag: \"$etag\"");
			exit;
		} elseif ($_SERVER['HTTP_IF_NONE_MATCH'] == '"' . $etag . '"') {
			header("HTTP/1.1 304 Not Modified");
			header("ETag: \"$etag\""); 
			exit;
		}
	}

	// ladda ner eller visa i webbläsaren
	// ?cd=attachment = ladda hem
	$contentDisposition = isset($_GET['cd']) ? $_GET['cd'] : 'inline';
	if ($contentDisposition == 'attachment') {
		header("Content-Disposition: $contentDisposition; filename=\"".$file->getNameForWeb()."\"");
	} elseif ($contentDisposition == 'inline') {
		header("Content-Disposition: $contentDisposition; filename=\"".$file->getNameForWeb()."\"");
	}

	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.1
	header("Cache-Control: public"); // 3600 firefox börjar kolla if-modified-since och in-none-match
	header("Content-Type: $file->mime");
	header("Last-Modified: $lastModified"); 
	header("ETag: \"$etag\""); 
	header("Content-Length: " . $file->size);
	readfile($file->filepath);
	exit;
	
} else {

	/**
	 * visa bild, skalad eller med effekter
	 * hämta från cache eller skapa ny?
	 * POLARBEAR_ATTACHPATH
	 * vi lagrar även cachade filer i POLARBEAR_ATTACHPATH för att minimera konfigurationsalternativen
	 */

	// format. f=<value>. Default jpg
	// används både av cachen och imagemagick
	if (!isset($_GET['format'])) {
		$_GET["format"] = "";
	}
	switch ($_GET['format']) {
		case "gif":
			$fileformat = 'gif';
			$outfile = 'GIF:';
			$contentType = 'image/gif';
			break;
		case "png":
		$fileformat = 'png';
			$outfile = 'PNG:';
			$contentType = 'image/png';
			break;
		case "jpg":
		default:
			$fileformat = 'jpg';
			$outfile = 'JPG:';
			$contentType = 'image/jpg';
			break;
	}

	
	// Unikt filnamn att spara till
	$cachefilename = "cache-image-{$file->id}-" . md5(serialize($_GET));
	$getmd5 = md5(serialize($_GET));
	
	// Hämta fil från cachen eller skapa ny fil?
	if (file_exists(POLARBEAR_CACHEPATH . $cachefilename)) {

		// Fil finns. Men är den up to date?
		// eller är en fil alltid up to date?
		// modifieras en fil tar man alltid bort cachen, så finns en cache-fil är den alltid aktuellt för vald fil?

		$cacheLastModified = filemtime(POLARBEAR_CACHEPATH . $cachefilename);
		$cacheLastModified = gmdate("D, d M Y H:i:s", $cacheLastModified) . ' GMT';

		// om clienten skickar med http_if_modified_since eller http_if_none_match ska vi kolla det
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $cacheLastModified) {
				header("HTTP/1.1 304 Not Modified");
				header("ETag: \"$getmd5\"");
				exit;
			} elseif ($_SERVER['HTTP_IF_NONE_MATCH'] == '"' . $getmd5. '"') {
				header("HTTP/1.1 304 Not Modified");
				header("ETag: \"$getmd5\"");
				exit;
			}
		}

		// Fixa till headers
		$cacheFileSize = filesize(POLARBEAR_CACHEPATH . $cachefilename);
		header("Cache-Control: public"); // firefox börjar kolla if-modified-since och in-none-match
		header("Last-Modified: $cacheLastModified"); 
		header("ETag: \"$getmd5\""); 
		header("Content-Type: $contentType");
		header("Content-Length: $cacheFileSize");
		
		// ladda ner eller visa i webbläsaren
		// ?cd=attachment = ladda hem
		$contentDisposition = isset($_GET['cd']) ? $_GET['cd'] : 'inline';
		if ($contentDisposition == 'attachment') {
			header("Content-Disposition: $contentDisposition; filename=\"$file->name\"");
		}

		readfile(POLARBEAR_CACHEPATH . $cachefilename);
		exit;

	} else {
	
		// generate image and save in cache
		$outfile .= '"' . POLARBEAR_CACHEPATH . $cachefilename . '"';

		$width = (isset($_GET['w']) && is_numeric($_GET['w']) && $_GET['w']>0) ? $_GET['w'] : '';
		$height = (isset($_GET['h']) && is_numeric($_GET['h']) && $_GET["h"]>0) ? $_GET['h'] : '';
		$quality = isset($_GET['q']) ? (int) $_GET['q'] : 85;
				
		// check if imagemagick is set and is executable
		$imagemagick = POLARBEAR_IMAGEMAGICK;
		if (is_executable($imagemagick)) {

			$infile = "\"$file->filepath\"";	
			$commands = '';
				
			// skala. w=<value> h=<value>
			// som default skalas vi endast ned, inte upp
			// todo: skala endast om bilden inte redan har rätt bredd el. höjd
			if (isset($_GET['w']) || isset($_GET['h'])) {
				#if (!empty($width) || !empty($height)) {
				if ($width || $height) {
					$commands .= " -resize \"{$width}x{$height}>\"";
				}
			}
			
			// unsharp. unsharp=<value>
			if (isset($_GET['unsharp']) && ctype_digit($_GET['unsharp'])) {
				$commands .= " -unsharp $_GET[unsharp] ";
			}
			
			// kvalitet q=<value>
			$commands .= " -quality $quality ";
				
			$cmd = "$imagemagick $infile $commands $outfile";
			// kommentera bort för att debugga
			// echo "cmd:<br>$cmd";exit;
			// echo($cmd . "<br><br>\n");
			exec($cmd, $arr, $return);
			// echo "cmd returned:";polarbear_d($arr);var_dump($return);exit;

		} else {

			// no imagemagick, use GD

			// Yes, GD uses more memory..
			ini_set("memory_limit","32M");
		
			// Resize, code, slightly modified, from http://se.php.net/manual/en/function.imagecreatefromjpeg.php#89865
			polarbear_scaleImageWithGD($file->filepath, POLARBEAR_CACHEPATH . $cachefilename, $width, $height);
			
		}
		
		$cacheFileSize = filesize(POLARBEAR_CACHEPATH . $cachefilename);
		$cacheLastModified = filemtime(POLARBEAR_CACHEPATH . $cachefilename);
		$cacheLastModified = gmdate("D, d M Y H:i:s", $cacheLastModified) . ' GMT';
		header("Cache-Control: public"); // firefox börjar kolla if-modified-since och in-none-match
		header("Last-Modified: $cacheLastModified"); 
		header("ETag: \"$getmd5\""); 
		header("Content-Type: $contentType");
		header("Content-Length: $cacheFileSize");
		readfile(POLARBEAR_CACHEPATH . $cachefilename);
		exit;
	}

}


// session_cache_limiter('');