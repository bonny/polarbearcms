<?php
/**
 * url can be something like:
 * /hem/en-annan-sokvag/yeahha/
 * and we need to find the last segment: yeaahha
 * and with or without the last slash
 */
function polarbear_rewrite() {

	$uri = $_SERVER['REQUEST_URI'];
	
	// shortname kan innehŒlla bokstŠver, siffor, minustecken och underscore
	$pattern = "/\/([\w\d\-_]+)/i";
	$numOfHits = preg_match_all($pattern, $uri, $matches);
	$qs = $_SERVER["QUERY_STRING"];
	#echo "<pre>" . print_r($matches, true) . "</pre>";
	if ($numOfHits>0) {
		// one or several hits found

		// determine type of rewrite
		// image, file, or article
		if ($matches[1][0] == "image" || $matches[1][0] == "file") {
			// we have an image or a file
			/*
			    [1] => Array
			        (
			            [0] => image
			            [1] => 576
			            [2] => 75
			            [3] => 75
			            [4] => par-thernstrom
			        )
			*/
			$fileID = $_GET["fileID"] =  (int) $matches[1][1];
			
			if ($matches[1][0] == "image") {
				$_GET["w"] = $matches[1][2];
				$_GET["h"] = $matches[1][3];
			} elseif ($matches[1][0] == "file") {
				if ($matches[1][2] == "attachment") {
					$_GET['cd'] = "attachment";
				}
			}
			
			require_once(dirname(__FILE__) . "/gui/file.php");
			exit;
			
		} else {

			// it's an article

			// store the "breadcrumb" of shortnames i a var that we can pass on to polarbear-boot
			global $polarbear_rewrite_shortnames, $polarbear_rewrite_in_use;
			$polarbear_rewrite_shortnames = $matches[1];
			$polarbear_rewrite_in_use = true;
		
		}

	} else {
		// no shortname entered
		// go to 404?
		// can this happen?
	}

}
polarbear_rewrite();
require_once(dirname(__FILE__) . "/polarbear-boot.php");
?>