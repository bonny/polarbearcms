<?php
/**
 * Klass som representerar en användare
 */
class PolarBear_User {

	var $id,
		$firstname,
		$lastname,
		$password,
		$email,
		$dateCreated,
		$dateChanged,
		$isDeleted,
		$arrGroups,
		$isAdmin,
		$loginToken;

	private static $instances = array();

	function __construct($id = "") {
		global $polarbear_db;
		if (is_numeric($id)) {
			$this->id = $id;
			$this->load();
		}
	}

	function getInstance($userID = null) {
		if ($userID == null) {
			return new PolarBear_User();
		} elseif (isset(self::$instances[$userID])) {
			return self::$instances[$userID];
		} else {
			self::$instances[$userID] = new PolarBear_User($userID);
			return self::$instances[$userID];
		}
	}


	/**
	 * ger tillbaka förnamn + efternamn eller email
	 */
	function __toString() {
		if ($this->firstname || $this->lastname) {
			return "$this->firstname $this->lastname";
		} elseif ($this->email) {
			return $this->email;
		} else {
			return '';
		}
	}


	function load() {
		global $polarbear_db;
		if ($r = $polarbear_db->get_row("SELECT * FROM " . POLARBEAR_DB_PREFIX . "_users WHERE id = $this->id")) {
			$this->loadThroughRow($r);
		}
		
		$this->arrGroups = $this->getGroups();
		$this->isAdmin = $this->isAdmin();
		
	}


	function loadThroughRow($r) {
		$this->firstname = $r->firstname;
		$this->lastname = $r->lastname;
		$this->email = $r->email;
		$this->password = $r->password;
		$this->dateCreated = $r->dateCreated;
		$this->dateChanged = $r->dateChanged;
		$this->isDeleted = $r->isDeleted;
		$this->loginToken = $r->loginToken;
	}

	
	function save() {
		$fetchNewID = false;
		if (is_numeric($this->id)) {
			$sql1 = "UPDATE ";
			$sql3 = " WHERE id = $this->id ";
			$dateCreated = "";
		} else {
			$sql1 = "INSERT INTO ";
			$sql3 = "";
			$fetchNewID = true;
			$dateCreated = "dateCreated = now(), ";
		}
		
		// fixa alla värden...
		$email = polarbear_fix_nasty_chars($this->email);
		$firstname = polarbear_fix_nasty_chars($this->firstname);
		$lastname = polarbear_fix_nasty_chars($this->lastname);
		$password = $this->password;
		
		global $polarbear_db;
		$email = $polarbear_db->escape($email);
		$firstname = $polarbear_db->escape($firstname);
		$lastname = $polarbear_db->escape($lastname);
		$password = $polarbear_db->escape($password);
		
		$sql2 = " " . POLARBEAR_DB_PREFIX . "_users SET isDeleted = '$this->isDeleted', $dateCreated email = '$email', password = '$password', firstname = '$firstname', lastname = '$lastname', dateChanged = now() ";
		$sql = $sql1 . $sql2 . $sql3;
		$polarbear_db->query($sql);
		if ($fetchNewID) {
			$this->id = $polarbear_db->insert_id;
		}
		
	}
	
	/**
	 * @return array
	 */
	function getGroups() {
		// finns inget id är inte användaren skapad och finns därmed inte i några grupper
		if (!is_numeric($this->id)) {
			return array();
		}
		if (isset($this->arrGroups)) {
			return $this->arrGroups;
		} else {
			// hämta relationer, men se till att inte inkludera grupper som är raderade
			$sql = "SELECT 
						groupID, g.name from " . POLARBEAR_DB_PREFIX . "_users_groups_relation as r 
						INNER JOIN " . POLARBEAR_DB_PREFIX . "_users as u on u.id = r.userID 
						INNER JOIN " . POLARBEAR_DB_PREFIX . "_usergroups as g on g.id = groupID 
						WHERE userID = $this->id
						AND g.isDeleted = 0
					";
			$arr = array();
			global $polarbear_db;
			if ($r = $polarbear_db->get_results($sql)) {
				foreach ($r as $row) {
					$arr[] = $row;
				}
				return $arr;
			} else {
				return array();
			}
		}
	}

	function removeFromAllGroups() {
		if ($this->id) {
			$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_users_groups_relation WHERE userID = $this->id";
			global $polarbear_db;
			$polarbear_db->query($sql);
		}
	}
	
	function addToGroup($groupID) {
		if (is_numeric($groupID)) {
			$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_users_groups_relation SET userID = '$this->id', groupID = $groupID";
			global $polarbear_db;
			$polarbear_db->query($sql);
		}
	}

	/**
	 * krypterar och lagrar lösenordet
	 */
	function changePassword($password) {
		global $polarbear_db;
		if (POLARBEAR_PASSWORD_HASHTYPE == "MD5") {
			$password = md5($password . POLARBEAR_SALT);
		} else {
			$password = sha1($password . POLARBEAR_SALT);
		}
		$password = $polarbear_db->escape($password);
		$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_users SET password = '$password', passwordResetCode = NULL WHERE id = '$this->id'";
		$polarbear_db->query($sql);
	}

	/**
	 * loggar in en användare, dvs. skapar cookie osv.
	 * @param bool $persistant Ska inloggningen vara permanent, dvs. gälla även om man stänger ner webbläsaren. Permanent = 30 dagar...
	 */
	function login($persistant = false) {
		
		if ($persistant == true) {
			$expire = time()+60*60*24*30; // 30 dagar = persistant
		} else {
			$expire = "0";
		}
		
		// skapa unik kod (token) för att lagra i cookie och databas
		// Reference: http://se.php.net/uniqid
		$token = md5(uniqid(rand(), true));
		global $polarbear_db;
		
		// sätt logintoken samt datum för senaste login
		$polarbear_db->query("UPDATE " . POLARBEAR_DB_PREFIX . "_users SET loginToken = '$token', dateLastLogin = now() WHERE id = '$this->id'");
		
		$domain = POLARBEAR_DOMAIN;
		if ($domain == "localhost") {
			$domain = false;
		} else {
			$domain = '.' . $domain;
		}
		#setcookie('polarbear_user', $this->id, $expire, '/', $domain);
		#setcookie('polarbear_token', $token, $expire, '/', $domain);
		setcookie('polarbear_user', $this->id, $expire, '/');
		setcookie('polarbear_token', $token, $expire, '/');
		
	}
	
	function logout() {
		$domain = POLARBEAR_DOMAIN;
		if ($domain == "localhost") {
			$domain = false;
		} else {
			$domain = '.' . $domain;
		}

		$expire = time()-1000;
		setcookie('polarbear_user', "", $expire, '/');
		setcookie('polarbear_token', "", $expire, '/');
		
	}

	/**
	 * Kollar om användaren är medlem i en grupp
	 * @param string $groupName
	 * @return bool
	 */
	function isMemberOfGroup($groupName) {
		foreach ($this->arrGroups as $group) {
			if ($group->name == $groupName) {
				return true;
			}
		}
		return false;
	}

	/**
	 * är denna användare administratör?
	 * @return bool
	 */
	function isAdmin() {
		if (!is_bool($this->isAdmin)) {
			$this->isAdmin = $this->isMemberOfGroup('Administrators');
		}
		return $this->isAdmin;
	}


	/**
	 * Kontrollerar om en användare får/kan göra grej x eller grej y
	 * @param $what Vad vi undrar om användaren får göra
	 * @return bool
	 */
	function can($what) {
		// ... just nu så får en admin göra allt, men det är förberedd för fler saker iaf!
		// i framtiden can $what vara t.ex. "editArticle", "editUser", "createUser", "removeComment" etc.
		if ($this->isAdmin) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 
	 */
	function clearCustomValues() {
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_users_values WHERE userID = '$this->id'";
		global $polarbear_db;
		$polarbear_db->query($sql);
	}

	function addCustomValue($name, $value) {
		global $polarbear_db;
		$name = $polarbear_db->escape($name);
		$value = $polarbear_db->escape($value);
		$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_users_values SET userID = '$this->id', name = '$name', value = '$value'";
		$polarbear_db->query($sql);
	}
	
	/**
	 * get custom values
	 */
	function customValues() {
		
		$arr = array();

		$sql = "SELECT name, value FROM " . POLARBEAR_DB_PREFIX . "_users_values WHERE userID = '$this->id' ORDER BY name ASC";
		global $polarbear_db;
		if ($r = $polarbear_db->get_results($sql)) {
			foreach ($r as $one) {
				$arr[] = $one;
			}
		}
		
		return $arr;
		
	}

}
?>