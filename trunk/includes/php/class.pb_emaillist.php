<?

class pb_emaillist {
	var $id, $name, $isDeleted;
	
	function __construct($id = null) {
		$this->id = (int) $id;
		$this->name = "";
		$this->isDeleted = 0;
		$this->count = 0;
		if ($id) {
			$this->load($id);
		}
		
	}
	
	function load($id) {
		$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_emaillist_lists WHERE id = '{$id}' AND isDeleted = 0";
		global $polarbear_db;
		$row = $polarbear_db->get_row($sql);
		if ($row) {
			$this->id = $id;
			$this->name = $row->name;
			$this->isDeleted = $row->isDeleted;
			
			$sql = "SELECT count(email) FROM " . POLARBEAR_DB_PREFIX . "_emaillist_emails WHERE listID = '{$this->id}'";
			$antal = $polarbear_db->get_var($sql);
			$this->count = $antal;
			
			return true;
		} else {
			return false;
		}
	}
	
	function save() {
		if ($this->id) {
			$sql = "UPDATE ";
			$sqlWhere = " WHERE id = '{$this->id}'";
		} else {
			$sql = "INSERT INTO ";
			$sqlWhere = "";
		}
		global $polarbear_db;
		$nameEscaped = $polarbear_db->escape($this->name);
		$sql .= POLARBEAR_DB_PREFIX . "_emaillist_lists SET name = '$nameEscaped', isDeleted = '{$this->isDeleted}' $sqlWhere";
		$polarbear_db->query($sql);
		
		if (!$this->id) {
			$this->id = $polarbear_db->insert_id;
		}
		
		return true;
	}
	
	/**
	 * checks if an email already is in da list
	 */
	function isEmailInList($email) {
		global $polarbear_db;
		$emailSafe = $polarbear_db->escape($email);
		$sql = "SELECT count(id) AS antal FROM " . POLARBEAR_DB_PREFIX . "_emaillist_emails WHERE listID = '{$this->id}' AND email = '$emailSafe'";
		global $polarbear_db;
		$antal = $polarbear_db->get_var($sql);
		return (bool) $antal;
	}
	
	/**
	 * remove email from list
	 */
	function removeEmail($email) {
		global $polarbear_db;
		$emailSafe = $polarbear_db->escape($email);
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_emaillist_emails WHERE listID = '{$this->id}' AND email = '$emailSafe'";
		$polarbear_db->query($sql);
	}
	
	/**
	 * adds an email to this list, if it does not already exist
	 */
	function addEmail($email) {
		$email = trim($email);
		if (is_rfc3696_valid_email_address($email)) {
			
			global $polarbear_db;
			$emailSafe = $polarbear_db->escape($email);

			if ($this->isEmailInList($email)) {
				#echo "already in list!";
				return false;
			} else {
				#echo "not inlist";
				$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_emaillist_emails SET listID = '{$this->id}', email = '$emailSafe', dateAdded = now()";
				$polarbear_db->query($sql);
				return true;
			}
			
		} else {
			return false;
		}
	}
	
	function getEmails() {
		$arr = array();
		$sql = "SELECT email, dateAdded, UNIX_TIMESTAMP(dateAdded) as dateAddedUnix FROM " . POLARBEAR_DB_PREFIX . "_emaillist_emails WHERE listID = '{$this->id}' ORDER BY dateAdded DESC";
		global $polarbear_db;
		if ($r = $polarbear_db->get_results($sql)) {
			foreach ($r as $oneEmail) {
				$arr[] = $oneEmail;
			}
		}
		return $arr;
		
	}
	
}

?>