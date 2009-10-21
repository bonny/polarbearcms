<?php
/**
 * Class that represents a plugin
 */
class pb_plugin {

	var $name, $filename, $author, $version, $description;
	
	/**
	 * Load a plugin by using it's filename
	 * @return true|false
	 */
	function loadByFilename($filename) {

		$fileFullPath = POLARBEAR_PLUGINS_PATH . "/$filename";
		if (is_file($fileFullPath) && is_readable($fileFullPath)) {
			$fileContents = file_get_contents($fileFullPath);

			preg_match("/Plugin_name: (.+)/i", $fileContents, $matches);
			if (isset($matches[1])) {
				$this->name = $matches[1];
			}
		
			preg_match("/Plugin_version: (.+)/i", $fileContents, $matches);
			if (isset($matches[1])) {
				$this->version = $matches[1];
			}
		
			preg_match("/Plugin_description: (.+)/i", $fileContents, $matches);
			if (isset($matches[1])) {
				$this->description = $matches[1];
			}

			preg_match("/Plugin_author: (.+)/i", $fileContents, $matches);
			if (isset($matches[1])) {
				$this->author = $matches[1];
			}
			
			$this->filename = $filename;
			$this->filename_full_path = $fileFullPath;
		
			return true;
		
		} else {
		
			return false;
		}

	}
	
	/**
	 * Check if plugin is enabled/active
	 */
	function is_enabled() {
		global $polarbear_db;
		$sql = "SELECT count(id) AS antal FROM " . POLARBEAR_DB_PREFIX . "_plugins WHERE filename = '" . $polarbear_db->escape($this->filename) . "'";
		return (bool) $polarbear_db->get_var($sql);
	}

	/**
	 * Enable plugin
	 * @return bool success
	 */	
	function enable() {
		global $polarbear_db;
		if ($this->is_enabled() == false) {
			$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_plugins SET ";
			$sql .= " filename = '" . $polarbear_db->escape($this->filename) . "', ";
			$sql .= " name = '" . $polarbear_db->escape($this->name) . "'";
			$polarbear_db->query($sql);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Disable plugin
	 */
	function disable() {
		global $polarbear_db;
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_plugins WHERE filename = '" . $polarbear_db->escape($this->filename) . "'";
		$polarbear_db->query($sql);
	}

}

?>