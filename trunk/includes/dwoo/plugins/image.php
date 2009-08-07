<?php

/**
 * Outputs a mailto link with optional spam-proof (okay probably not) encoding
 * {polarbear_image id=123 width=135 alt="Alternativtext för denna bild"}
 * @author     Pär Thernström <par.thernstrom@gmail.com>
 */
function Dwoo_Plugin_image(Dwoo $dwoo, $id, $w=null, $h=null, $alt=null)
{
	
	if (!is_numeric($id)) {
		return "";
	}

	$image = new PolarBear_File($id);
	if (!$image->isImage()) {
		return "";
	}
	
	// calculate width and height, after resize
	// code from polarbear_scaleImageWithGD
	// @todo: combine code in above function with this
	$width = $image->width;
	$height = $image->height;
	$max_width = $w;
	$max_height = $h;
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
	$strWidthAndHeight = "";
	if (!empty($tn_width) && !empty($tn_height)) {
		$strWidthAndHeight = 'width="'.$tn_width.'" height="'.$tn_height.'" ';
	} else {
		$strWidthAndHeight = 'width="'.$width.'" height="'.$height.'" ';
	}
	
	$alt = htmlspecialchars($alt, ENT_COMPAT, "UTF-8");
	
	$tag = '<img ' . $strWidthAndHeight . 'src="' . $image->getImageSrc(array("w" => $w, "h" => $h)) . '" alt="' . $alt . '" />';

	return $tag;
	
}

