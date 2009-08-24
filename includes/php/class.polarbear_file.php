<?php
/**
 * Klass som representerar en fil i POLARBEAR
 */
class PolarBear_File {

	private static $instances = array();

	var 
		$id,
		$name,
		$size,
		$mime,
		$uploaderID,
		$user_creator,
		$filepath,
		$dateUploaded,
		$dateModified,
		$width,
		$height,
		$arrTags = array();
	

	public function getInstance($fileID = null) {

		if ($fileID == null) {
			return new PolarBear_File($fileID);
		} elseif (isset(self::$instances[$fileID])) {
			return self::$instances[$fileID];
		} else {
			self::$instances[$fileID] = new PolarBear_File($fileID);
			return self::$instances[$fileID];
		}
		
	}

	/**
	 * skickar med id = hämta befintlig
	 * skickar inte med id = skapa ny
	 */
	function __construct($fileID = NULL, $create = true) {

		if (is_numeric($fileID)) {
			$this->id = $fileID;
			if (!$this->load()) {
				// filen verkar inte finnas in databasen...
			}
		} else {
			// skapa ny fil
			$this->user_creator = PolarBear_User::getInstance($polarbear_u->id);
			$this->save();
		}
		$this->filepath = POLARBEAR_ATTACHPATH . "{$this->id}.file";
	}
	
	function load() {
		global $polarbear_db;
		$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_files WHERE id = '$this->id'";
		if ($row = $polarbear_db->get_row($sql)) {
			$this->id = $row->id;
			$this->mime = $row->mime;
			$this->name = $row->name;
			$this->size = $row->size;
			$this->width = $row->width;
			$this->height = $row->height;
			$this->dateUploaded = $row->dateUploaded;
			$this->dateModified = $row->dateModified;
			$this->uploaderID = $row->uploaderID;
			$this->user_creator = PolarBear_User::getInstance($this->uploaderID);
			
			$this->arrTags = $this->getTags();
		} else {
			// todo: filen fanns inte. now what?
			return false;
		}
	}
	
	function save() {
		if (is_numeric($this->id)) {
			// befintlig fil
			$isNew = false;
			$sql1 = 'UPDATE ' . POLARBEAR_DB_PREFIX . '_files ';
			$sqlExtraSet = '';
			$sql3 = " WHERE id = $this->id";
		} else {
			// ny fil
			$isNew = true;
			$sql1 = 'INSERT INTO ' . POLARBEAR_DB_PREFIX . '_files ';
			$sqlExtraSet = ' dateUploaded = now(), ';
			// todo: userID skickas ju inte med via flashen..
			//$this->uploaderID = $polarbear_u->id;
			$sql3 = '';
		}
		global $polarbear_db;
		$mime = $polarbear_db->escape($this->mime);
		$name = $polarbear_db->escape($this->name);
		$sql2 = " SET $sqlExtraSet mime = '$mime', name = '$name', size = '$this->size', uploaderID = '$this->uploaderID', width = '$this->width', height = '$this->height', dateModified = now() ";
		$sql = $sql1 . $sql2 . $sql3;

		if ($polarbear_db->query($sql) && $isNew) {
			$this->id = $polarbear_db->insert_id;
		}
		
		// ladda om för att få fräscha värden
		$this->load();
		
		$args = array(
			"file" => $this,
			"isNew" => $isNew,
			"objectName" => $this->name
		);
		if (!$isNew) {
			pb_event_fire("pb_file_saved", $args);
		}
				
	}
	
	/**
	 * sätter en fils innehåll från en fil
	 * @param string $filename
	 * @return bool success
	 */
	function setContentFromFile($filename) {
		// kontrollera att fil finns
		if (file_exists($filename)) {
			if (copy($filename, $this->filepath)) {
				//echo "copy ok";
				return true;
			} else {
				//echo "copy fail";
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Är filen en bild?
	 */
	function isImage() {
		$arrImageMimes = array(
			'image/png',
			'image/jpeg',
			'image/jpeg',
			'image/jpeg',
			'image/gif',
			'image/bmp',
			'image/vnd.microsoft.icon',
			'image/tiff',
			'image/tiff',
			'image/svg+xml',
			'image/svg+xml'
		);
		if (in_array($this->mime, $arrImageMimes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Ger sökväg till en passade ikon till denna filtyp
	 * @return string sökväg
	 */
	function getIcon() {
		
			/*
		switch ($this->mime) {
			case "image/jpeg":
			case "image/png":
			case "image/jpg":
			case"image/gif":
			case "image/bmp":
			case "image/tiff":
				$image = "picture.png";
				break;
			case "audio/mpeg";
				$image = "sound_none.png";
				break;
			case "application/pdf":
				$image = "page_white_acrobat.png";
				break;
			case "application/zip":
			case "application/x-rar-compressed":
				$image = "page_white_compressed.png";
				break;
			default:
				$image = "page_attach.png";
		}
			 * 
			 */
		if (strpos($this->mime, "image/") !== false) {
			$image = "picture.png";
		} elseif (strpos($this->mime, "video/") !== false) {
			$image = "film.png";
		} elseif (strpos($this->mime, "audio/") !== false) {
			$image = "sound_none.png";
		} elseif (strpos($this->mime, "application/pdf") !== false) {
			$image = "page_white_acrobat.png";
		} else {
			$image = "attach.png";
		}
	
		return POLARBEAR_WEBPATH . "images/silkicons/$image";


	}

	// Paamayim Nekudotayim-function
	// http://se.php.net/manual/en/language.oop5.paamayim-nekudotayim.php
	/**
	 * kontrollerar att en fil finns
	 * todo: göra..eller ta bort
	 */
	function file_exists() {
		//$sql = "SELECT";
	}
	
	/**
	 * @param $options string or array
	 */
	function getImageSrc($options = "") {
	
		if (!$this->isImage()) {
			return "";
		}
		$defaults = array();
		$options = polarbear_extend($defaults, $options);
		
		// om modrewrite
		// http://localhost/image/576/750/750/par-thernstrom.jpg
		$usemodrewrite = polarbear_setting("usemodrewrite");
		if ($usemodrewrite) {
			$name = $this->getNameForWeb();
			$return = "/image/{$this->id}/";

			$w = (int) $options["w"];
			$h = (int) $options["h"];
			
			$return .= "$w/$h/$name";

			return $return;

		} else {
			return POLARBEAR_WEBPATH . "gui/file.php?fileID=$this->id&amp;w={$options["w"]}&amp;h={$options["h"]}";
		}
	}


	function getFileSrc($options = "") {
		if (isset($options["attachment"]) && $options["attachment"] == true) {
			$cdModrewrite = "attachment/";
			$cd = "&amp;cd=attachment";
		}
	
		// om modrewrite
		$usemodrewrite = polarbear_setting("usemodrewrite");
		if ($usemodrewrite) {
			$name = $this->getNameForWeb();
			$return = "/file/{$this->id}/{$cdModrewrite}{$name}";
			return $return;
		} else {
			return POLARBEAR_WEBPATH . "gui/file.php?fileID={$this->id}{$cd}";
		}
	}
	
	/** 
	 * smart getSrc-version. calls getFileSrc or getImageSrc depending of the file type
	 */
	function getSrc($options = "") {
		if ($this->isImage()) {
			return $this->getImageSrc($options);
		} else {
			return $this->getFileSrc($options);
		}
	}


	/**
	 * returns the name with spaces and & and < and > and stuff removed
	 */
	function getNameForWeb() {
		$pattern = "/[^a-z0-9\-_]/i";
		$name = $this->getNameWithoutExtension();
		$name = utf8_strtolower($name);
		$arr = array("å", "ä", "ö", "Å", "Ä", "Ö");
		$name = str_replace($arr, "a", $name);
		return preg_replace($pattern, "-", $name) . $this->getExtension(true);
	}

	/**
	 * get the extension of the file
	 * currently only supports images
	 */
	function getExtension($includeDot = false) {
		$mimeTypes = polarbear_getMimeTypes(); //  array('txt' => 'text/plain')
		$dot = '';
		
		if ($includeDot) { $dot = '.'; }
		
		// look for mime in $mimeTypes
		foreach ($mimeTypes as $extension => $mime) {
			if ($this->mime == $mime) {
				return "{$dot}{$extension}";
			}
		}

	}


	function getLink() {
		return POLARBEAR_WEBPATH . "gui/file.php?fileID=$this->id";
	}

	function getDownloadLink() {
		return POLARBEAR_WEBPATH . "gui/file.php?fileID=$this->id&amp;cd=attachment";
	}
	
	/**
	 * bra för att sätta width- och height-attribut på img-tag
	 * todo: gör denna! den är bra och smart
	 * @param int $width
	 */
	function getHeightIfWidthIs($width) {
		// tänkte fel.. måste ta både bredd och höjd i beaktning såklart
	}

	function getWidthIfHeightIs($height) {
	}

	/**
	 * tar bort en fil och alla referenser till den
	 * inklusive cachade filer!
	 */
	function delete() {
		global $polarbear_db;
		#unlink(POLARBEAR_ATTACHPATH);
		// 1.file
		// cache-image-1-2d9d0085b385fb6995dd641668b840e2
		$d = dir(POLARBEAR_CACHEPATH);
		$pattern = "/cache-image-{$this->id}-/";
		while (false !== ($entry = $d->read())) {
			// om detta är en cache'ad variant av filen
			if (preg_match($pattern, $entry)) {
				unlink(POLARBEAR_ATTACHPATH . $entry);
			}
		}
		$d->close();

		// ta bort själva filen från filsystemet
		unlink(POLARBEAR_ATTACHPATH . $this->id . '.file');
		
		// ta bort från databasen
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_files WHERE id = '$this->id'";
		$polarbear_db->query($sql);
		
		// ta bort från taggar
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_files_tags WHERE fileID = '$this->id'";
		$polarbear_db->query($sql);

		$args = array(
			"file" => $this,
			"objectName" => $this->name
		);
		polarbear_d($args);
		pb_event_fire("pb_file_deleted", $args);		


	}
	
	
	/**
	 * todo: funktionen som kollar om filen har en filändelse, dvs <filnamn>.<filändelse>
	 * @return bool
	 */
	function hasExtension() {
		return true;
	}
	
	/**
	 * ger filnamnet utan filändelse
	 * @return string namn
	 */
	function getNameWithoutExtension() {
		$name = $this->name;

		// leta upp sista punkten
		$lastDotPos = utf8_strrpos($name, ".");
		if ($lastDotPos) {
			$nameWithoutExtension = utf8_substr($name, 0, $lastDotPos);
		} else {
			$nameWithoutExtension = $name;
		}
		return $nameWithoutExtension;
	}

	/**
	 * ger filnamnets filändelse
	 * @return string filändelse
	 */
	function getNameExtension() {
		$name = $this->name;
		preg_match("/.([\w]+)$/", $name, $matches);
		$nameExtension = $matches[1];
		return $nameExtension;
	}

	/**
	 * Kopplar en tag till en fil
	 * @param string $tagName
	 * @return bool true om den lades till, false om den fanns sen tidigare eller av annan anledning inte lades till
	 */
	function addTag($tagName) {
		global $polarbear_db;
		
		// en tag får inte vara tom
		if (trim($tagName) == '') {
			return false;
		}
		
		if ($this->hasTag($tagName)) {
			return false;
		} else {
			$tagName = $polarbear_db->escape($tagName);
			$sql = "INSERT INTO polarbear_files_tags SET fileID = '$this->id', tagName = '$tagName'";
			$polarbear_db->query($sql);
			return true;
		}
	}
	
	/**
	 * tar bort en tag från filen
	 * param string $tagName Namn på tag som ska läggas till
	 */
	function removeTag($tagName) {
		global $polarbear_db;
		$tagName = $polarbear_db->escape($tagName);
		$polarbear_db->query("DELETE FROM " . POLARBEAR_DB_PREFIX . "_files_tags WHERE fileID = '$this->id' AND tagName = '$tagName'");
	}
	
	/**
	 * Kollar om denna fil en specifik tag
	 * @param string $tagName Namn på tag som ska läggas till
	 * @return $tagName
	 */
	function hasTag($tagName) {
		global $polarbear_db;
		$tagName = $polarbear_db->escape($tagName);
		$sql = "SELECT count(*) from " . POLARBEAR_DB_PREFIX . "_files_tags WHERE fileId = '$this->id' AND tagName = '$tagName'";
		$antal = $polarbear_db->get_var($sql);
		return (bool) $antal;
	}
	
	/**
	 * Does this file have any tags?
	 * @return bool
	 */
	function hasTags() {
		return (sizeof($this->arrTags) > 0) ? true : false;
	}
	
	/**
	 * Retrives all tags as an array
	 * @return array tags
	 */
	function getTags() {
		global $polarbear_db;
		$sql = "SELECT tagName FROM " . POLARBEAR_DB_PREFIX . "_files_tags WHERE fileID = '$this->id' ORDER BY tagName ASC";
		if ($r = $polarbear_db->get_results($sql)) {
			$arr = array();
			foreach ($r as $row) {
				$arr[] = $row->tagName;
			}
			return $arr;
		} else {
			return array();
		}
		
	}
	
	/**
	 * lägger till eller tar bort en tag ("togglar")
	 * dvs. finns taggen redan tas den bort
	 * finns den inte läggs den till
	 * @parram string $tagName
	 */
	function toggleTag($tagName) {
		if ($this->hasTag($tagName)) {
			$this->removeTag($tagName);
		} else {
			$this->addTag($tagName);
		}
	}
	
}
?>