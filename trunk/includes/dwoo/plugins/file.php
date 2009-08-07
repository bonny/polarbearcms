<?php

/**
 * Outputs a mailto link with optional spam-proof (okay probably not) encoding
 * {polarbear_image id=123 width=135 alt="Alternativtext för denna bild"}
 * @author     Pär Thernström <par.thernstrom@gmail.com>
 */
function Dwoo_Plugin_file(Dwoo $dwoo, $id, $text=null, $attachment=false, $title="")
{
	
	if (!is_numeric($id)) {
		return "";
	}

	$file = new PolarBear_File($id);
	$fileSrc = $file->getFileSrc(array("attachment"=>$attachment));

	$title = trim($title);
	if (!empty($title)) {
		$title = htmlspecialchars($title, ENT_COMPAT, "UTF-8");
		$title = " title=\"$title\" ";
	}
	
	$class = "";
	if ($extension = $file->getExtension()) {
		$class = "filetype-$extension";
	}
	$output = "<a class='$class' href=\"{$fileSrc}\" $title>$text</a>";
	return $output;
	
}
