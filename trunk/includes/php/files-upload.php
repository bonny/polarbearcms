<?php
/**
 * Tar emot filuppladdningar. Just nu från flash-uploadern i files.php
 */
require("../../polarbear-boot.php");
#polarbear_require_admin();

/*
print_r($_FILES);
print_r($_POST);
print_r($_GET);
Array
(
    [Filedata] =&gt; Array
        (
            [name] =&gt; en till bild.jpg
            [type] =&gt; application/octet-stream
            [tmp_name] =&gt; C:\wamp\tmp\phpCDD.tmp
            [error] =&gt; 0
            [size] =&gt; 131394
        )

)
*/

// 1. kontrollera att uppladdningen gick igenom
$file_error = $_FILES["Filedata"]["error"];
$uploadOK = true;
if ($file_error !== UPLOAD_ERR_OK) {
	switch ($file_error) {
		case UPLOAD_ERR_INI_SIZE:
			$errtxt = "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$errtxt = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
			break;
		case UPLOAD_ERR_PARTIAL:
			$errtxt = "The uploaded file was only partially uploaded. ";
			break;
		case UPLOAD_ERR_NO_FILE:
			$errtxt = "No file was uploaded.";
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$errtxt = "Missing a temporary folder.";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$errtxt = "Failed to write file to disk.";
			break;
		case UPLOAD_ERR_EXTENSION:
			$errtxt = "File upload stopped by extension.";
			break;
		default:
			$errtxt = "An unknown error occured.";
			break;
	}
	// Meddela att det inte gick vägen.
	// Tror http code "500 Internal Server Error" är rätt representativ
	$uploadOK = false;
} else {
	
	// hämta in info om filen
/*
<br />
<b>Warning</b>:  mime_content_type(Bild 1.png) [<a href='function.mime-content-type'>function.mime-content-type</a>]: 
failed to open stream: No such file or directory in <b>/Users/marsapril/www.marsapril.se/polarbear/includes/php/files-upload.php</b> 
on line <b>62</b><br />
ok
*/
	$filemime = pb_mime_content_type_by_name($_FILES["Filedata"]["name"]);
	$filename = $_FILES["Filedata"]["name"];
	$filesize = $_FILES["Filedata"]["size"];
	$filetmpname = $_FILES["Filedata"]["tmp_name"];

	// flytta till attachpach
	if (!is_uploaded_file($filetmpname)) {

		$uploadOK = false;
	} else {

		// ok, flytta
		// Ska detta vara en generell funktion/klass?
		$file = new PolarBear_File();
		$file->name = $filename;
		$file->size = $filesize;
		$file->mime = $filemime;
		
		// todo: kontrollera att även loginToken är ok
		// $polarbear_u->id
		if ($_GET["uploaderID"]) {
			$file->uploaderID = $_GET["uploaderID"];
		}
		
		// om bild: spara även bild & höjd
		if ($imagesize = getimagesize($filetmpname)) {
			$file->width = $imagesize[0];
			$file->height = $imagesize[1];
		}
		
		$file->save();
		if (!$file->setContentFromFile($filetmpname)) {
			$uploadOK = false;
		}
		//print_r($file);
		
	}
	
}

if ($uploadOK) {
	echo "ok";
} else {
	header("HTTP/1.0 500 Internal Server Error");
	echo "There was an error during upload: $errtxt"; // denna syns aldrig i klienten... hm...
}

?>