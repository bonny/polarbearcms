<?php
/**
 * Klass som representerar en artikel
 */
class PolarBear_Article {

	private static $instances = array();
	
	private
		$id,
		$parentID,
		$isRevisionTo,
		$status,
		$dateCreated,
		$datePublish,
		$dateUnpublish,
		$dateChanged,
		$templateType,
		$templateName,
		$templateCustom,
		$officialAuthorID,
		$officialAuthorType, // typ av författare: användarID (user), fritext (freetext) eller ingen (none)
		$officialAuthorText,
		$titleArticle,
		$titleNav,
		$titlePage,
		$useCustomTitleNav,
		$useCustomTitlePage,
		$teaser,
		$body,
		$metaDescription,
		$metaKeywords,
		$shortName,
		$prio,
		$fieldConnectorType,
		$fieldConnectorID,
		$polarbear_user_officialAuthor, // är authorType = text så lagras användaren som objekt här
		$polarbear_article_parent,
		$polarbear_article_parents,
		$polarbear_article_children,
		$fieldValues,
		$fieldsAndValues,
		$numChildren,
		$childrenCache
		;


	/**
	 * for multiton
	 */	
	public function getInstance($articleID = null) {

		if ($articleID == null) {
			return new PolarBear_article($articleID);
		} elseif (isset(self::$instances[$articleID])) {
			return self::$instances[$articleID];
		} else {
			self::$instances[$articleID] = new PolarBear_Article($articleID);
			return self::$instances[$articleID];
		}
		
	}
	
		
	/**
	 * Sätter lite defaults
	 */
	function __construct($articleID = NULL) {

		// om id finns så ladda in artikeln, annars sätt lite defaults
		if (is_numeric($articleID)) {
		
			$this->load($articleID);
		
		} else {
		
			$this->useCustomTitleNav = false;
			$this->useCustomTitlePage = false;
			$this->status = 'draft';
			global $polarbear_u;
			if ($polarbear_u) {
				$this->officialAuthorID = $polarbear_u->id;
				$this->officialAuthorText = "$polarbear_u->firstname $polarbear_u->lastname";
				$this->officialAuthorType = 'user';
			} else {
				$this->officialAuthorType = 'none';
			}
			
			// eftersom vi inte satt något parentID ännu ligger artikeln
			// i rooten och ska där placeras sist
			$sql = "SELECT max(prio) FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID IS NULL";
			global $polarbear_db;
			$prio = (int) $polarbear_db->get_var($sql);
			$this->prio = ++$prio;
		}
		
	}

	
	
	/**
	 * laddar in en artikel
	 * todo: får man ladda in en artikel som är deleted? I vanlig fall: nej!
	 */
	function load($id) {
	
		$id = (int) $id;

		// leta efter artikeln i preload först
		$preloader = pb_query_preloader::getInstance();
		$preloadRow = $preloader->getPreloadArticleRow($id);
		if ($preloadRow) {
			// in preload, get it from there
			$this->loadThroughObject($preloadRow);
			#echo "<br>got from preload: $id";
		} else {
			// not in preload, fetch from db
			global $polarbear_db;
			#echo "<br>not in preload: $id";
			$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE id = '$id' AND status <> 'deleted'";
			$rows = $polarbear_db->get_results($sql);
			$this->loadThroughObject($rows[0]);
		}

		// update fields
		$this->fieldsAndValues(true);

		pb_pqp_log_speed("Article $id loaded");
	
	}
	
	function loadThroughObject($row) {

		$this->id = $row->id;
		$this->prio = $row->prio;
		$this->parentID = $row->parentID;
		$this->isRevisionTo = $row->isRevisionTo;
		$this->status = $row->status;
		$this->dateCreated = $row->dateCreated;

		$this->datePublish = $row->datePublish;
		if (empty($this->datePublish)==false && is_numeric($this->datePublish)==false) {
			$this->datePublish = strtotime($this->datePublish);
		}
		
		$this->dateUnpublish = $row->dateUnpublish;
		if (empty($this->dateUnpublish)==false && is_numeric($this->dateUnpublish)==false) {
			$this->dateUnpublish = strtotime($this->dateUnpublish);
		}

		$this->dateChanged = strtotime($row->dateChanged);
		$this->templateType = $row->templateType;
		$this->templateName = $row->templateName;
		$this->templateCustom = $row->templateCustom;
		$this->officialAuthorID = $row->officialAuthorID;
		$this->officialAuthorType = $row->officialAuthorType;
		$this->officialAuthorText = $row->officialAuthorText;
		$this->titleArticle = $row->titleArticle;
		$this->titleNav = $row->titleNav;
		$this->titlePage = $row->titlePage;
		$this->useCustomTitleNav = (bool) $row->useCustomTitleNav;
		$this->useCustomTitlePage = (bool) $row->useCustomTitlePage;
		$this->teaser = $row->teaser;
		$this->body = $row->body;
		$this->metaDescription = $row->metaDescription;
		$this->metaKeywords = $row->metaKeywords;
		$this->shortName = $row->shortName;
		$this->fieldConnectorType = $row->fieldConnectorType;
		if (empty($this->fieldConnectorType)) { $this->fieldConnectorType = "inherit"; }
		$this->fieldConnectorID = $row->fieldConnectorID;

		// ta hand om saker som inte är lagrade, typ användarobjekt å så
		#$this->setupOfficialAuthor();

	}
	
	/*
	function setupOfficialAuthor() {
		if ($this->officialAuthorType == "user") {
			$this->officialAuthorUser = new PolarBear_User($this->officialAuthorUserID);
		}
	}*/
	

	function setTitleArticle($str) {
		$this->titleArticle = $str;
		if (!$this->useCustomTitleNav) {
			$this->titleNav = $str;
		}
		if (!$this->useCustomTitlePage) {
			$this->titlePage = $str;
		}
	}
	
	/**
	 * Returns the "shortName" of this article
	 *
	 * @return string
	 */
	function getshortName() {
		return $this->shortName;
	}

	/**
	 * Sets the shortname for this article
	 * Since a shortname can not contain certain characters
	 * the new shortname is validated and possibly changed.
	*
	 * @return string The (possible changed) shortname
	 */
	function setShortName($newShortName) {
		// we keep it simple,
		// a shortname can contain a-z 0-9 _ , . 
		/*
		48-57 number
		65-90 a-z
		97-122 A-Z
		46 .
		45 -
		95 _
		*/
	
		// a shortname must not be one of these
		$arrReservedWords = pb_shortname_reserved_words();
		
		$convertTable = array(
			" " => "-",
			"å" => "a",
			"ä" => "a",
			"ö" => "o",
			"Å" => "a",
			"Ä" => "ä",
			"Ö" => "o"
		);
		$validatedShortName = '';
	
		// empty shortnames or only numbers is not ok
		$newShortName = trim($newShortName);
		if (empty($newShortName)) {
			$newShortName = trim($this->getTitleArticle());
			if (empty($newShortName) || is_numeric($newShortName)) {
				$newShortName = "article";
			}
		}

		// todo: this code should probably use the utf8-functions instead of this homebuilt utf-convert-thingie
		for ($i=0; $i<=strlen($newShortName); $i++) {
			#$char = $newShortName[$i]; // probs with chars like å, ä and ö
			$char = utf8_encode(substr(utf8_decode($newShortName), $i, 1));
			if (array_key_exists($char, $convertTable)) {
				$char = $convertTable[$char];
			}
			$code = ord($char);
			if (
					($code>=48 && $code <= 57) // number
					||
					($code>=65 && $code <= 90) // a-z
					||
				($code>=97 && $code <= 122) // A-Z
					||
					($code == 45 || $code == 95) // -
				) {
				// ok
				$validatedShortName .= $char;
			}

		}
		$validatedShortName = strtolower($validatedShortName);
		
		// we got a name, make sure its unique and not a reserved word
		if (in_array($validatedShortName, $arrReservedWords)) {
			$validatedShortName .= rand(1,9);
		}
				
		// no other article may have this name, mkay?
		// article that has isRevisionTo = this id is ok
		// or article that
		global $polarbear_db;
		$isUnique = false;
		while (!$isUnique) {
			$sql = "
				SELECT count(id) FROM " . POLARBEAR_DB_PREFIX . "_articles 
				WHERE
				shortName = '$validatedShortName' 
				AND id != $this->id
				AND (isRevisionTo IS NULL OR isRevisionTo IS NOT NULL AND isRevisionTo <> $this->id)
				AND status <> 'deleted'
				AND status <> 'preview'
			";
			$isUnique = ! (int) $polarbear_db->get_var($sql);
			if (!$isUnique ) {
				$validatedShortName = $validatedShortName . rand(1,9999);
			}
		}

		$this->shortName = $validatedShortName;

		return $this->shortName;
	}
	function getTeaser() {
		return $this->teaser;
	}
	function setTeaser($newTeaser) {
		$this->teaser = $newTeaser;
	}
	function getBody() {
		return $this->body;
	}
	function setBody($newBody) {
		$this->body = $newBody;
	}	
	function getId() {
		return $this->id;
	}
	
	function setParent($parentID) {
		if ($parentID == 0) {
			$parentID = null;
		}
		if ($this->parentID != $parentID) {
			// if we're setting a new parent, add this article as the top most article among the children in that category
			$sql = "SELECT max(prio) FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID = $parentID";
			global $polarbear_db;
			$prio = (int) $polarbear_db->get_var($sql);
			$this->prio = ++$prio;
			
		}
		$this->parentID = $parentID;
		

	}

	function getParent() {
		return $this->parentID;
	}
	function getParentId() {
		return $this->parentID;
	}
	
	function save() {
		
		global $polarbear_db;
		
		if (is_numeric($this->id)) {
			$isNew = false;
			$sqlUpdateOrInsert = "UPDATE " . POLARBEAR_DB_PREFIX . "_articles ";
			$sqlWhere = " WHERE id = $this->id ";
			$dateCreated = '';
		} else {
			$isNew = true;
			$sqlUpdateOrInsert = " INSERT INTO " . POLARBEAR_DB_PREFIX . "_articles ";
			$sqlWhere = '';
			$dateCreated = ' dateCreated = now(), ';
		}
		$dateChanged = " dateChanged = now(), ";

		if (empty($this->datePublish)) {
			$datePublish = "datePublish = NULL, ";
		} else {
			$datePublish = "datePublish = FROM_UNIXTIME($this->datePublish), ";
			#$datePublish = "datePublish = $this->datePublish, ";
		}

		if (empty($this->dateUnpublish)) {
			$dateUnpublish = "dateUnpublish = NULL, ";
		} else {
			$dateUnpublish = "dateUnpublish = FROM_UNIXTIME($this->dateUnpublish), ";
			#$dateUnpublish = "dateUnpublish = $this->dateUnpublish, ";
		}
		
		if (is_numeric($this->parentID)) {
			$setParentID = " parentID = '$this->parentID', ";
		} else {
			$setParentID = " parentID = NULL, ";
		}
		
		if (is_numeric($this->isRevisionTo)) {
			$setIsRevisionTo = " isRevisionTo = '$this->isRevisionTo', ";
		} else {
			$setIsRevisionTo = " isRevisionTo = NULL, ";
		}
		
		if (is_numeric($this->officialAuthorID)) {
			$setOfficialAuthorID = " officialAuthorID = '$this->officialAuthorID', ";
		} else {
			$setOfficialAuthorID = " officialAuthorID = NULL, ";
		}

		if ($this->officialAuthorType) {
			$setOfficialAuthorType = " officialAuthorType = '$this->officialAuthorType', ";
		} else {
			$setOfficialAuthorType = " officialAuthorType = 'none', ";
		}
			
		$templateType = $polarbear_db->escape($this->templateType);
		$templateName = $polarbear_db->escape($this->templateName);
		$templateCustom = $polarbear_db->escape($this->templateCustom);
		$officialAuthorText = $polarbear_db->escape($this->officialAuthorText);
		$titleArticle = $polarbear_db->escape($this->titleArticle);
		$titleNav = $polarbear_db->escape($this->titleNav);
		$titlePage = $polarbear_db->escape($this->titlePage);
		$useCustomTitleNav = (int) $this->useCustomTitleNav;
		$useCustomTitlePage = (int) $this->useCustomTitlePage;
		$teaser = $polarbear_db->escape($this->teaser);
		$body = $polarbear_db->escape($this->body);
		$metaDescription = $polarbear_db->escape($this->metaDescription);
		$metaKeywords = $polarbear_db->escape($this->metaKeywords);
		$shortName = $polarbear_db->escape($this->shortName);
		$sqlSet = "
			SET
				$setParentID
				$setIsRevisionTo
				$dateCreated
				$dateChanged
				$datePublish
				$dateUnpublish
				$setOfficialAuthorID
				$setOfficialAuthorType
				templateType = '$templateType',
				templateName = '$templateName',
				templateCustom = '$templateCustom',
				officialAuthorText = '$officialAuthorText',
				titleArticle = '$titleArticle',
				titleNav = '$titleNav',
				titlePage = '$titlePage',
				useCustomTitleNav = '$useCustomTitleNav',
				useCustomTitlePage = '$useCustomTitlePage',
				teaser = '$teaser',
				body = '$body',
				status = '$this->status',
				metaDescription = '$metaDescription',
				metaKeywords = '$metaKeywords',
				shortName = '$shortName',
				prio = '$this->prio',
				fieldConnectorType = '$this->fieldConnectorType',
				fieldConnectorID = '$this->fieldConnectorID'
		";
		$sql = $sqlUpdateOrInsert . $sqlSet . $sqlWhere;
		$polarbear_db->query($sql);
			
		// save fields
		$fieldValues = $this->fieldValues();

		if ($isNew) {
			$this->id = $polarbear_db->insert_id;
		}

		// empty fields and then update
		$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_fields_values WHERE articleID = '$this->id'";
		$polarbear_db->query($sql);
		foreach ($fieldValues as $oneField) {
			$numInSet = 0;
			foreach ($oneField as $oneFieldWithValue) {
				$fieldID = $oneFieldWithValue["fieldID"];
				$sqlFieldValue = $polarbear_db->escape($oneFieldWithValue["value"]);
				$numInSet = $oneFieldWithValue["numInSet"];
				$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_fields_values SET fieldID = $fieldID, articleID = $this->id, value = '$sqlFieldValue', numInset = $numInSet";
				$polarbear_db->query($sql);
				$numInSet++;
			}
		}

		$args = array(
			"article" => $this,
			"isNew" => $isNew,
			"objectName" => $this->getTitleArticle(),
			"status" => $this->getStatus()
		);
		// getStatus
		pb_event_fire("pb_article_saved", $args);
		
	}
	
	
	function hasPrevArticle() {
		return (bool) $this->prevArticle();
	}

	function hasNextArticle() {
		return (bool) $this->nextArticle();
	}

	
	/**
	 * gets the previous published article
	 */
	function prevArticle() {
		$parent = $this->parent();
		if (!$parent) {
			return false;
		}
		$parentChildren = $parent->children();
		$aCount = sizeof($parentChildren);
		$foundAtPos=null;
		for ($i=0;$i<$aCount;$i++) {
			if ($parentChildren[$i]->getId() == $this->getId()) {
				$foundAtPos=($i-1);
				break;
			}
		}
		
		pb_pqp_log_speed("article prevArticle");
		
		if ($foundAtPos>=0) {
			return $parentChildren[$foundAtPos];
		} else {
			return false;
		}
	}

	/**
	 * gets the next published article
	 */
	function nextArticle() {
		$parent = $this->parent();
		if (!$parent) {
			return false;
		}
		$parentChildren = $parent->children();
		$aCount = sizeof($parentChildren);
		$foundAtPos=null;
		for ($i=0;$i<$aCount;$i++) {
			if ($parentChildren[$i]->getId() == $this->getId()) {
				$foundAtPos=($i+1);
				break;
			}
		}
		
		pb_pqp_log_speed("article nextArticle");
				
		if ($foundAtPos>=0 && $foundAtPos<$aCount) {
			return $parentChildren[$foundAtPos];
		} else {
			return false;
		}
	}

	
	/**
	 * flyttar en artikel, dvs. sätter ny prio
	 * och ev. ny parentID
	 * obs att högst prio = artikeln hamnar överst (inte sist, vilket man lätt kan få för sig ibland)
	 * @param int $targetArticleID
	 * @param string $positionType - before|inside|after, idea from jstree
	 */
	function move($targetArticleID, $positionType) {

		global $polarbear_db;

		if (is_numeric($targetArticleID)) {
			// integer, instanstiate article
			$targetArticle = PolarBear_Article::getInstance($targetArticleID);			
		} else {
			// assume it's a polarbear_article
			$targetArticle = $targetArticleID;
		}

		$targetPrio = (int) $targetArticle->getPrio();
		$targetParent = $targetArticle->getParent();
		$positionType = strtolower($positionType);
		if ($positionType == 'before' || $positionType == 'after') {
			$this->setParent($targetParent);

			// uppdatera vår artikelns prio
			if ($positionType == 'after') {
				/*
				 * 10 orginalet
				 * 5 target
				 *
				 * Ge utrymme till att flytta upp target-artikeln genom att öka prio på alla artiklar inkl target
				 * Ge artikeln vi ska flytta samma prio som target
				 */
				if ($targetParent) {
					$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_articles SET prio = prio+1 WHERE parentID = $targetParent AND prio >= $targetPrio";
				} else {
					$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_articles SET prio = prio+1 WHERE parentID IS NULL AND prio >= $targetPrio";
				}
				$polarbear_db->query($sql);
				$newPrio = $targetPrio;
			} elseif ($positionType == 'before') {
				// prio på artikeln nedanför oss: $targetPrio
				// 
				/*
				 * 10 target
				 * 5 orginalet
				 *
				 * Ge utrymme till den flyttade artikeln genom att öka prio på alla artikeln ovanför target-artikeln
				 * Ge artikeln vi ska flytta samma prio som target fast + 1
				 */
				$sql = "";
				if ($targetParent) {
					// öka prio på alla artiklar som har samma parent och vars nuyvarande prio är högre än artikeln under artikeln vi flyttat
					$sql1 = "UPDATE " . POLARBEAR_DB_PREFIX . "_articles SET prio = prio+1 WHERE parentID = $targetParent AND prio > $targetPrio";
				} else {
					$sql1 = "UPDATE " . POLARBEAR_DB_PREFIX . "_articles SET prio = prio+1 WHERE parentID IS NULL AND prio > $targetPrio";
				}
				$polarbear_db->query($sql1);
				$newPrio = $targetPrio+1;
			}
			$this->setPrio($newPrio);
			$this->save();
			
		} elseif ($positionType == 'inside') {
			// vi placerar artikeln på en annan artikel
			// ändra parent till det id som targetArticle har (inte dess parent alltså) och ändra sedan prio till parent-nivåns högsta id + 1
			$targetArticleID = $targetArticle->getId();
			$this->setParent($targetArticleID);
			if ($targetArticleID) {
				$sql = "SELECT max(prio) FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID = '$targetArticleID'";
			} else {
				$sql = "SELECT max(prio) FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID IS NULL";
			}
			$maxPrio = $polarbear_db->get_var($sql);
			$this->setPrio($maxPrio+1);
			$this->save();
		}
		
	}
	
	function setPrio($newPrio) {
		$this->prio = $newPrio;
	}

	function getPrio() {
		return $this->prio;
	}
	
	
	/*
	setTitlePage finns redan...
	den vs. setCustom??
	*/
	function getTitleArticle() {
		return $this->titleArticle;
	}
	function getTitlePage() {
		return $this->titlePage;
	}
	function setTitlePage($newTitle) {
		$this->titlePage = $newTitle;
	}
	function getTitleNav() {
		return $this->titleNav;
	}
	function setTitleNav($newTitle) {
		$this->titleNav = $newTitle;
	}

	function isUsingCustomTitleNav() {
		return (bool) $this->useCustomTitleNav;
	}
	function setIsUsingCustomTitleNav($bool) {
		$this->useCustomTitleNav = (bool) $bool;
	}
	function isUsingCustomTitlePage() {
		return (bool) $this->useCustomTitlePage;
	}
	function setIsUsingCustomTitlePage($bool) {
		$this->useCustomTitlePage = (bool) $bool;
	}

	function setCustomTitleNav($str) {
		$this->useCustomTitleNav = true;
		$this->titleNav = $str;
	
	}
	function setCustomTitlePage($str) {
		$this->useCustomTitlePage = true;
		$this->titlePage = $str;
	}
	
	function getOfficialAuthorType() {
		return $this->officialAuthorType;
	}
	function getOfficialAuthorText() {
		return $this->officialAuthorText;
	}
	function getOfficialAuthorID() {
		return $this->officialAuthorID;
	}

	function setOfficialAuthorType($val) {
		$this->officialAuthorType = $val;
	}
	function setOfficialAuthorText($val) {
		$this->officialAuthorText = $val;
	}
	function setOfficialAuthorID($val) {
		$this->officialAuthorID = $val;
	}
	
	/**
	 * ger officiell författare som ett PolarBear_User-objekt
	 * @return mixed object eller false om ingen officiell författare finns
	 */
	function getOfficialUserAsMajUser() {
		if ($this->officialAuthorID==null) {
			// official author är inte en användare
			return false;
		} else {
			if ($this->polarbear_user_officialAuthor == null) {
				$this->polarbear_user_officialAuthor = PolarBear_User::getInstance($this->officialAuthorID);
			}
			return $this->polarbear_user_officialAuthor;
		}
	}

	function setStatus($newStatus) {
		$this->status = $newStatus;
	}
	
	function getStatus() {
		return $this->status;
	}
	
	/**
	 * ger publiceringsdatum i unix-tid
	 * @return unix time
	 */
	function getDatePublish($format = null) {
		if ($format == 'human') {
			return polarbear_time2str($this->datePublish);
		} else if ($format) {
			return strftime($format, $this->datePublish);
		} else {
			return $this->datePublish;
		}
	}

	/**
	 * Datum för publicering
	 * @param string $date Datum i unix-tid. 
	 * Är $date inte enbart siffror så körs strtotime. Är $date tom så antas att det är nu som gäller, 
	 * vilket (i motsats till unpublish) gör att tidpunkten just nu 
	 */
	function setDatePublish($date) {
		$date = trim($date);
		if (empty($date)) {
			//$this->datePublish = null;
			$this->datePublish = time();
		} else if (is_numeric($date)) {
			$this->datePublish = $date;
		} else {
			$this->datePublish = strtotime($date);
		}
	}


	/**
	 * Date article was modified/changed
	 * @return unix time
	 */
	function getDateChanged($format = null) {
		if ($format == 'human') {
			return polarbear_time2str($this->dateChanged);
		} else if ($format) {
			return strftime($format, $this->dateChanged);
		} else {
			return $this->dateChanged;
		}
	}

	
	/**
	 * Returns when this article is to be unpublished
	 * @return unix time
	 */
	function getDateUnpublish($unix = false) {
		return $this->dateUnpublish;
	}

	function setDateUnpublish($date) {
		$date = trim($date);
		if (empty($date)) {
			$this->dateUnpublish = null;
		} else if (is_numeric($date)) {
			$this->dateUnpublish = $date;
		} else {
			$this->dateUnpublish = strtotime($date);
		}
	}

	
	function setMetaDescription($str) {
		$this->metaDescription = $str; 	
	}
	function getMetaDescription() {
		return $this->metaDescription;
	}
	function setMetaKeywords($str) {
		$this->metaKeywords = $str;
	}
	function getMetaKeywords() {
		return $this->metaKeywords;
	}
	
	/**
	 * Type of template to use
	 * If articles for any reason don't have a template, "inherit" will be used as a default
	 * @return string type of template inherit | name | custom
	 */
	function getTemplateType() {
		if (!$this->templateType) {
			return 'inherit';
		} else {
			return $this->templateType;
		}
	}
	/**
	 * Sätter typ av template som ska användas
	 * @param string $newType Typ av sidmall inherit | name | custom
	 */
	function setTemplatetype($newType) {
		$newType = strtolower($newType);
		if ($newType != 'inherit' && $newType != 'name' && $newType != 'custom') {
			// no valid type given, default to inherit? or don't change?
			$newType = 'inherit';
		}
		$this->templateType = $newType;
	}
	
	/**
	 * Get name of template used for this article
	 * todo: if a template has been removed, check for that and.. do what? default to inherit?
	 *
	 * @return string Name of template
	 */
	function getTemplateName() {
		return $this->templateName;
	}
	function setTemplateName($str) {
		$this->templateName = $str;
	}
	
	function getTemplateCustom() {
		return $this->templateCustom;
	}
	function setTemplateCustom($str) {
		$this->templateCustom = $str;
	}
	
	
	/**
	 * get the template we actually are gonna use
	 * taking inherit in consideration
	 * @return path to templae. may be http://
	 */
	function templateToUse() {
		// loop until templateType = name | custom
		$templateType = $this->getTemplateType();
		$templateCustom = $this->getTemplateCustom();
		$templateName = $this->getTemplateName();
		$articleName = $this->getTitleArticle();
		$articleId = $this->getId();
		$parent = $this->parent();
		
		$i = 0;
		while ($templateType != 'name' && $templateType != 'custom' && $parent !== null) {
			$templateType = $parent->getTemplateType();
			$templateCustom = $parent->getTemplateCustom();
			$templateName = $parent->getTemplateName();
			$articleName = $parent->getTitleArticle();
			$articleId = $parent->getId();
			$parent = $parent->parent();
		}
		
		/*
		echo "<br><br>templateType: " . $templateType;
		echo "<br>templateCustom: " . $templateCustom;
		echo "<br>templateName: " . $templateName;
		echo "<br>articleName: " . $articleName;
		echo "<br>articleID: $articleId";
		// */
		
		$templates = polarbear_getTemplates();
		
		pb_pqp_log_speed("article templateToUse");
		
		if ($templateType == 'custom') {
			return $templateCustom;
		} else if (isset($templates[$templateName])) {
			return $templates[$templateName]['file'];
		} else {
			return null;
		}
		
	}

	/**
	 * Does this article has a parent article
	 * If not it is at the root
	 * @return bool  
	 */	
	function hasParent() {
		return (bool) ($this->parentID);
	}

	/**
	 * Does article have child nodes?
	 */
	function hasChildren($options = null) {
		return (bool) $this->numChildren($options);
	}
	
	/**
	 * get number of children
	 * 
	 */
	function numChildren($options = null) {

#		if (!isset($this->numChildren)) {
			$children = $this->children($options);
			return sizeof($children);
#			$this->numChildren = sizeof($children);
#		}

#		return $this->numChildren;

	}
	
	/**
	 * Get child articles as array
	 * Array $options sort, direction, how many to get etc. See $defaults for details.
	 */
	function children($options = null) {
		
		$defaults = array(
			'sort' => 'prio',
			'sortDirection' => 'desc',
			'limitStart' => 0,
			'limitCount' => null,
			'includeUnpublished' => false,
			'tagsMustInclude' => null, // comma seperated?
			'tagsMustNotInclude' => null
		);
		
		$options =  polarbear_extend($defaults, $options);
		if ($options['limitCount'] === null) {
			// To retrieve all rows from a certain offset up to the end of the result set, you can use some large number for the second parameter.
			$limitCount = 2147483647;
		} else {
			$limitCount = $options['limitCount'];
		}

		// done with options, check cache
		$optionsQuery = http_build_query($options);
		if (isset($this->childrenCache[$optionsQuery])) {
			return $this->childrenCache[$optionsQuery];
		}

		// not in cache, go on and query
		$sql =  "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_articles ";
		$sql .= "WHERE (parentID = '$this->id' AND status <> 'deleted' AND status <> 'revision' ) ";
		
		if ($options["includeUnpublished"]) {
			// include unpublished articles
		} else {
			// don't include unpublished, remove'em
			$sql .= " AND status = 'published' AND datePublish < now() AND (dateUnpublish > now() OR dateUnpublish IS NULL) ";
		}
		
		// tags
		if ($options["tagsMustInclude"]) {
			$tagsMustInclude = explode(",", $options["tagsMustInclude"]);
			$strTagArticleIds = "";
			foreach($tagsMustInclude as $oneTagID) {
				$oneTag = polarbear_tag::getInstance($oneTagID);
				// fetch articles for this tag
				$tagArticles = $oneTag->articles();
				foreach ($tagArticles as $oneA) {
					$strTagArticleIds .= $oneA->getId() . ",";
				}
			}
			$strTagArticleIds = preg_replace("/,$/", "", $strTagArticleIds);
			if (!empty($strTagArticleIds)) {
				$strTagArticleIds = " AND id IN ($strTagArticleIds) ";
			}
			$sql .= $strTagArticleIds;
		}
		
		$sql .= "ORDER BY $options[sort] $options[sortDirection] ";
		$sql .= "LIMIT $options[limitStart], $limitCount";
		// children = all articles that have current article as parentID
		global $polarbear_db;
		$childrenArr = array();
		if ($r = $polarbear_db->get_results($sql)) {

			$preloader = pb_query_preloader::getInstance();
			$strArticleIDs = "";
			foreach ($r as $row) {		
				// in med hittade id:n i preloadern
				$strArticleIDs .= ",".$row->id;
			}
			$preloader->addArticle($strArticleIDs);
			$preloader->update();

			foreach ($r as $row) {		
				$childrenArr[] = PolarBear_Article::getInstance($row->id);
			}
			
		}
		
		pb_pqp_log_speed("article children()");
		
		$this->childrenCache[$optionsQuery] = $childrenArr;
		return $this->childrenCache[$optionsQuery];
	}
	
	/**
	 * Returns all descendant childs
	 * Does not care about the order, just returns what it finds.
	 *
	 * @return array with articles
	 */
	function descendants() {
		/*
		get all children of this article
		for each child, run the same query..
		*/
		$childs = $this->children();
		$subChilds = array();
		foreach ($childs as $oneChild) {
			$subChilds = array_merge($subChilds, $oneChild->descendants());
		}
		$childs = array_merge($childs, $subChilds);
		pb_pqp_log_speed("article descendants()");
		return $childs;
	}


	/**
	 * return the first child
	 * @todo include options to send? To be able to get sort and order
	 *
	 * @return obj child or null if 
	 */
	function firstChild() {
		$children = $this->children();
		if (sizeof($children>0)) {
			return $children[0];
		} else {
			return null;
		}
	}
	
	/**
	 * Returns the parent article
	 * If no parent article exists, null is returned
	 *
	 * @param int $index Fetch parent at positionx $index. Index 0 = topmost. Default = direct parent.
	 * @return mixed polarbear_article or null
	 */
	function parent($index = null) {
		if (isset($index) && is_numeric($index)) {
			$parents = $this->parents();
			if ($index < sizeof($parents)) {
				$parents = array_reverse($parents);
				return $parents[$index];
			}
		} else {
			if ($this->hasParent()) {
				return PolarBear_Article::getInstance($this->parentID);
			} else {
				return null;
			} 
		}
	}
	
	/**
	 * return all parents
	 * @return $array
	 */
	function parents() {
		if (!$this->hasParent()) {
			return null;
		} elseif ($this->polarbear_article_parents === NULL) {
			$arr = array();
			if ($this->hasParent()) {
				$current = $this->parent();
				$arr[] = $current;
				while ($pa = $current->parent()) {
					$current = $pa;
					$arr[] = $current;
				}
			}
			pb_pqp_log_speed("article parents()");
			$this->polarbear_article_parents = $arr;
			return $this->polarbear_article_parents;
		} else {
			pb_pqp_log_speed("article parents()");
			return $this->polarbear_article_parents;
		}
				
	}
	
	/**
	 * ger full sökväg till artikeln
	 * dock utan servernamn
	 * @param bool $includeLast Should the last article = this article be included 
	 * @return string path to article, including parent shortnames, i.e. /article1/article2/this-article/ 
	 */
	function fullpath($includeLast = true) {

		$arrShortnames = array();
		$arrShortnames[] = $this->getShortName();
		$txtPath = "";
		if ($this->parents()) {

			foreach ($this->parents() as $oneParent) {
				$arrShortnames[] = $oneParent->getShortName();
			}
			// remove last one, that is the name of the site
			array_pop($arrShortnames);
			$arrShortnames = array_reverse($arrShortnames);
			// removes the last, the current article, if we want to
			if (!$includeLast) {
				array_pop($arrShortnames);
			}
			foreach ($arrShortnames as $val) {
				$txtPath .= "/" . $val;		
			}
		}
		$txtPath .= "/";
		pb_pqp_log_speed("article fullpath()");
		return $txtPath;
		
	}
	
	
	/**
	 * returns the url for this article
	 */
	function href() {
		$usemodrewrite = polarbear_setting('usemodrewrite');
		if ($usemodrewrite) {
			return $this->fullpath();
		} else {
			return $this->templateToUse() . '?polarbear-page=' . $this->getId();
		}
	}
	
	function hrefFull() {
		return "http://" . POLARBEAR_DOMAIN . $this->href();
	}

	function getFieldConnectorID() {
		return $this->fieldConnectorID;
	}
	
	/**
	 * gets fieldID taking inherit into account
	 * @return id på fält
	 */
	function fieldConnectorToUse() {

		// check if we have selected a specific field for this article or is it inherit?
		if ($this->getFieldConnectorType() == "id") {
			$fieldConnectorID = $this->getFieldConnectorID();
		} else {
			// if inherit
			$tmpA = $this;
			$doLoop = true;
			while ($doLoop) {
				$tmpA = $tmpA->parent();
				if ($tmpA === null) {
					$doLoop = false;
				} else {
					$fieldConnectorType = $tmpA->getFieldConnectorType();
					$fieldConnectorID = $tmpA->getFieldConnectorID();
					if ($fieldConnectorType == 'id') {
						$doLoop = false;
					}
				}
			}
		}
		pb_pqp_log_speed("article fieldConnectorToUse()");
		return $fieldConnectorID;
		
	}

	function setFieldConnectorType($newType) {
		$fieldConnectorType = "inherit";
		if ($newType == "id") {
			$fieldConnectorType = "id";
		}
		$this->fieldConnectorType = $fieldConnectorType;
	}

	function getFieldConnectorType() {
		return $this->fieldConnectorType;
	}

	
	function setFieldConnectorID($newID) {
		$this->fieldConnectorID = (int) $newID;
	}


	/**
	 * get all existing field values for this article
	 * see @fieldsAndValues if you want the structure/hierarchy/context for the fields
	 * 
	 * @return array
	 */
	function fieldValues() {
	
		if (isset($this->fieldValues)) {
			return $this->fieldValues;
		}

		// fieldvalues fanns inte.
		// kolla preload först och om inte där så ladda in
		$preloader = pb_query_preloader::getInstance();
		$preloadRows = $preloader->getPreloadFieldsRows($this->id);
		if ($preloadRows === false) {
			#echo "<br>nah, no preload row for field, article: $this->id ";
			// no preload for this articles field, fetch them from db
			global $polarbear_db;		
			$sql = "
				SELECT 
					fv.fieldID, fv.articleID, fv.value, fv.numInSet,
					f.name as fieldName, f.type as fieldType
				FROM " . POLARBEAR_DB_PREFIX . "_fields_values as fv
				INNER JOIN " . POLARBEAR_DB_PREFIX . "_fields as f ON f.id = fv.fieldID
				WHERE articleID = '$this->id' ORDER BY fieldID ASC, numInSet ASC";
			$rows = $polarbear_db->get_results($sql);
		} else {
			#echo "<br>got preload row for field, article: $this->id";
			// horay! we got the field values preloaded
			$rows = $preloadRows;
		}
	
		$arrFieldValues = array();
		$arrFieldCount = array();
		if (is_array($rows)) {
			foreach ($rows as $oneRow) {
				if (!isset($arrFieldValues[$oneRow->fieldID])) {
					$arrFieldValues[$oneRow->fieldID] = array();
				}
				
				/*
					todo: om det är en fil/bild ska det kanske finnas
					link, downloadLink, imageSrc osv.
				*/
				$arrFieldValues[$oneRow->fieldID][] = array(
					"fieldID" => $oneRow->fieldID,
					"articleID" => $oneRow->articleID,
					"value" => $oneRow->value,
					"numInSet" => $oneRow->numInSet,
					// and some useful "extras" too
					"valueEscaped" => htmlspecialchars ($oneRow->value, ENT_COMPAT, "UTF-8"),
					"valueNoTags" => strip_tags($oneRow->value),
					"fieldName" => $oneRow->fieldName,
					"fieldType" => $oneRow->fieldType
				);
				if (!isset($arrFieldCount[$oneRow->fieldID])) {
					$arrFieldCount[$oneRow->fieldID]=0;
				}
				$arrFieldCount[$oneRow->fieldID]++;
			}
		}
		
		// update count
		foreach ($arrFieldCount as $fieldID => $fieldCount) {
			for ($i=0; $i<sizeof($arrFieldValues[$fieldID]); $i++) {
				$arrFieldValues[$fieldID][$i]["fieldSetCount"] = $fieldCount;
			}
		}
		
		pb_pqp_log_speed("article fieldValues()");
		
		$this->fieldValues = $arrFieldValues;
		return $this->fieldValues;
	}


	/**
	 * Gets all fields that are connected to this article, and their values
	 *
	 * @return array
	 */
	function fieldsAndValues($forceReload = false) {

		if (isset($this->fieldsAndValues) && $forceReload==false) {
			return $this->fieldsAndValues;
		}
	
		$fieldValues = $this->fieldValues();		

		$fieldConnectorToUse = $this->fieldConnectorToUse();
		
		$arrFields = polarbear_getFieldStructureForFieldConnector($fieldConnectorToUse);

		if (is_array($arrFields)) {

			foreach ($arrFields as $oneFieldConnector) {
				// i en connector har vi collections
				foreach ($oneFieldConnector['fieldCollections'] as $oneFieldCollection) {
					// i en collection har vi en eller flera fältsamlingar
					// fältsamlingen kan vara repeatable, så lagra värden i array
					$isRepeatable = $oneFieldCollection['repeatable'];
					foreach ($oneFieldCollection['fields'] as $oneField) {

						// find out max numInSet
						// xxx this...does not work.. i even dont know how i was thinking..
						$maxNumInSet = null;
						if (isset($fieldValues[$oneField['id']]) && is_array($fieldValues[$oneField['id']])) {
							$maxNumInSet = sizeof($fieldValues[$oneField['id']]);
							/*foreach ($fieldValues[$oneField['id']] as $oneFieldValue) {
								if ($oneFieldValue['numInSet'] > $maxNumInSet) {
									$maxNumInSet = $oneFieldValue['numInSet'];
								}
							}
							$maxNumInSet++;
							*/
						}
						$arrFields[$oneFieldConnector['id']]['fieldCollections'][$oneFieldCollection['id']]['fields'][$oneField['id']]['totalNumInSet'] = (int) $maxNumInSet;
						if (isset($fieldValues[$oneField['id']])) {
							$arrFields[$oneFieldConnector['id']]['fieldCollections'][$oneFieldCollection['id']]['fields'][$oneField['id']]['values'] = $fieldValues[$oneField['id']];
						}
					}
					
				}
			}
		}

		pb_pqp_log_speed("article fieldsAndValues()");
		
		$this->fieldsAndValues = $arrFields;
		return $this->fieldsAndValues;
		
	}

	/**
	 * Prepare an article for copy
	 * unsets the id (so a the article will get a new id when saved)
	 * Does NOT change things like name or shortname for the article
	 */
	function prepareForCopy() {
		$this->id = null;
	}
	
	/**
	 * return a copy of this article
	 * stuff that will get changed: id, shortname
	 */
	function getCopyOfArticle() {
		$newArticle = new PolarBear_Article($this->id);
		$newArticle->prepareForCopy();
		$newArticle->save();
		return $newArticle;
	}
	
	/**
	 * Delete article
	 * Deletion is done immediately
	 */
	function delete() {
		$this->status = "deleted";
		$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_articles SET status = '$this->status' WHERE id = '$this->id'";
		global $polarbear_db;
		$polarbear_db->query($sql);

		$args = array(
			"article" => $this,
			"objectName" => $this->getTitleArticle()
		);
		pb_event_fire("pb_article_deleted", $args);

		return true;
	}

	/**
	 * get icon for article based on status
	 */
	function statusIcon() {
		// published, draft
		// page_white_clock.png
		// page_white_edit.png

		switch ($this->status) {
			case 'published':
				// varit, eller ska bli, publicerad?
				// $datePublish $dateUnpublish
				if ($this->isPublished()) {
					return POLARBEAR_WEBPATH . 'images/silkicons/page_white_text.png';
				} else {
					return POLARBEAR_WEBPATH . 'images/silkicons/page_white_text_clock.png';
				}				
				break;
			case 'draft':
				return POLARBEAR_WEBPATH . 'images/silkicons/page_white_text_edit.png';
				break;
			default:
				return POLARBEAR_WEBPATH . 'images/silkicons/page_white_text.png';
		}
		
	}

	/**
	 * Check if article is published
	 * published = status = published and datePublish >= now and dateUnpublish == null or now < dateUnpublish
	 * @return bool
	 */
	function isPublished() {
		if ($this->status == 'published') {
			$now = time();
			if ($this->datePublish <= $now) {
				if ($this->dateUnpublish > $now || $this->dateUnpublish == null) {
					return true;
				} else {
					return false;
				}				
			}
		}
		return false;
	}

	/**
	 * returns a full page title path
	 * i.e. the titles of this article + all parent articles (except the top level one)
	 */
	function fullPageTitle() {
		$title = htmlspecialchars($this->getTitlePage(), ENT_COMPAT, "UTF-8");
		$separator = ' | ';
		$parents = $this->parents();
		for ($i=0; $i<sizeof($parents)-1; $i++) {
			$oneParent = $parents[$i];
			$title .= $separator;
			$title .= htmlspecialchars($oneParent->getTitlePage(), ENT_COMPAT, "UTF-8");
		}
		pb_pqp_log_speed("article fullPageTitle()");
		return $title;
	}

	/**
	 * Does this article have a teaser?
	 * Somthing must be written i the teaser for it to be considered as not empyu
	 * So for example a teaser with only an empty paragraph is considered empty
	 */
	function hasTeaser() {
		$teaser = strip_tags($this->teaser, "<img><a>");
		$teaser = trim($teaser);
		if (empty($teaser)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Does this article have a body?
	 * Somthing must be written i the teaser for it to be considered as not empty
	 * So for example a teaser with only an empty paragraph is considered empty
	 */
	function hasBody() {
		$body = strip_tags($this->body, "<img><a>");
		$body = trim($body);
		if (empty($body)) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * check if this article is a direct child or a sub-child of the article $refArticle
	 * @param $refArticle polarbear_article object or article ID
	 */
	function isChildOrSubChildOf($refArticle) {
		if (is_numeric($refArticle)) {
			$refArticle = polarbear_article::getInstance($refArticle);
		}
		$isChild = false;
		// loop our way up until we reach the top article or refArticle
		// $refArticle
		$doLoop = true;
		$tmpA = $this;
		while ($doLoop) {
			// get parent article
			$tmpA = $tmpA->parent();
			if ($tmpA == null) {
				return false;
			}
			#echo "<br><br>tmpA:".$tmpA->getTitleArticle();
			#echo "<br> refArticle->getId():" . $refArticle->getId();
			#echo "<br> tmpA->getId():" . $tmpA->getId();
			#echo "<br> tmpA->getParentId():"; var_dump($tmpA->getParentId());
			// check if the parent article is the refArticle. if it is, that means that $this is a child of $refArticle
			if ($tmpA->getId() == $refArticle->getId()) {
				return true;
			}
			// if parent is null = is at top level
			if ($tmpA->getParentId()==null) {
				return false;
			}

		}

		pb_pqp_log_speed("article isChildOrSubChildOf()");

		return false;
	}
	/**
	 * alias of isChildOrSubChildOf
	 */
	function isDescendantOf($refArticle) {
		return $this->isChildOrSubChildOf($refArticle);
	}

	/**
	 * returns output this article according to the templateformat in $template
	 * if the article is not published, an empty string will be returned
	 * 
	 * @param strint $template mars-templater compatible template-string
	 * @return string output
	 */
	function output($template) {

		global $polarbear_u, $polarbear_a;
		$isOk = false;

		if ($this->isPublished()) {
			$isOk = true;
		} else if ($this->getStatus()=="preview" && $polarbear_u->isAdmin()) {
			$isOk = true;
		}
		
		if ($isOk == false) {
			return '';
		}
		$dwoo = new Dwoo();
		$dwoo->setCompileDir(POLARBEAR_STORAGEPATH . 'dwoo');
		$data = new Dwoo_Data(); 
		$tpl = new Dwoo_Template_String($template);

		// add variables
		$data->assign('id', $this->getId());
		$data->assign('titleArticle', htmlspecialchars($this->getTitleArticle(), ENT_COMPAT, "UTF-8"));
		$data->assign('titleArticleRaw', $this->getTitleArticle());
		$data->assign('titlePage', htmlspecialchars($this->getTitlePage(), ENT_COMPAT, "UTF-8"));
		$data->assign('titleNav', htmlspecialchars($this->getTitleNav(), ENT_COMPAT, "UTF-8"));
		$data->assign('href', $this->href());
		$data->assign('hrefFull', $this->hrefFull());
		$data->assign('fullPageTitle', $this->fullPageTitle());
		$data->assign('teaser', $this->getTeaser());
		$data->assign('teaserNoTags', strip_tags($this->getTeaser()));
		$data->assign('hasTeaser', $this->hasTeaser());
		$data->assign('body', $this->getBody());
		$data->assign('bodyNoTags', strip_tags($this->getBody()));
		$data->assign('hasBody', $this->hasBody());
		$data->assign('datePublish', $this->getDatePublish());
		$data->assign('dateChanged', $this->getDateChanged());
		$data->assign('metaDescription', trim($this->getMetaDescription()));
		$data->assign('metaKeywords', trim($this->getMetaKeywords()));

		if ($this->getStatus()=="preview") {
			// no edit icons in preview mode
		} else {
			$data->assign('edit', $this->getEditString());
			$data->assign('editPrio', $this->getEditPrioString());
			$data->assign('editAdd', $this->getEditAddString());
			$data->assign('editAddChild', $this->getEditAddChildString());
		}

		$SEOMetaTags = "";
		if (trim($this->getMetaDescription())) {
			$SEOMetaTags .= '<meta name="description" content="' . htmlspecialchars(trim($this->getMetaDescription()), ENT_COMPAT, "UTF-8") . '" />';
		}
		if (trim($this->getMetaKeywords())) {
			$SEOMetaTags .= '<meta name="keywords" content="' . htmlspecialchars(trim($this->getMetaKeywords()), ENT_COMPAT, "UTF-8") . '" />';
		}
		$data->assign('SEOMetaTags', $SEOMetaTags);

		// $isCurrentArticle, good for using i lists
		$isCurrentArticle = false;
		if ($polarbear_a && $polarbear_a->getId() == $this->getId()) {
			$isCurrentArticle = true;
		}
		$data->assign('isCurrentArticle', $isCurrentArticle);
		

		// fetch parent so vi easily can check if current article is child of some article
		// todo: since these are the same as for the current article maybe we could make a wrapper for it.. somehow
		$parentA = PolarBear_Article::getInstance($this->parentID);
		$data->assign('parentId', $parentA->getId());
		$data->assign('parentTitleArticle', htmlspecialchars($parentA->getTitleArticle(), ENT_COMPAT, "UTF-8"));
		$data->assign('parentTitlePage', htmlspecialchars($parentA->getTitlePage(), ENT_COMPAT, "UTF-8"));
		$data->assign('parentTitleNav', htmlspecialchars($parentA->getTitleNav(), ENT_COMPAT, "UTF-8"));
		$data->assign('parentHref', $parentA->href());
		$data->assign('parentHrefFull', $parentA->href());
		$data->assign('parentFullPageTitle', $parentA->fullPageTitle());
		$data->assign('parentTeaser', $parentA->getTeaser());
		$data->assign('parentHasTeaser', $parentA->hasTeaser());
		$data->assign('parentBody', $parentA->getBody());
		$data->assign('parentHasBody', $parentA->hasBody());
		$data->assign('parentDatePublish', $parentA->getDatePublish());
		$data->assign('parentDateChanged', $parentA->getDateChanged());
		$data->assign('parentMetaDescription', $parentA->getMetaDescription());
		$data->assign('parentMetaKeywords', $parentA->getMetaKeywords());

		$fieldValues = $this->fieldValues();
		$data->assign('fields', $fieldValues);
		
		$out = $dwoo->get($tpl, $data);
		$out = pb_event_fire("article_output", array("article" => $this, "output" => $out));
		$out = $out["output"];

		pb_pqp_log_speed("article output()");

		return $out;
	}

	/**
	 * Loops through all, or some children (according to $options) and outputs template
	 * Great for outputing lists with news or stuff like that 
	 * Has two special formatting keywords that output() does not have: 
	 * {$cssFirst}, {$cssLast} and {$loopNum}. Good for styling
	 * Also {$editAdd} will only be added for the first item
	 *
	 * @param string $format
	 * @param array $options Like polarbear_article->children(). Default = null.
	 */
	function outputChildren($format, $options = null) {
		$children = $this->children($options);
		$out = '';
		
		$count = sizeof($children);
		for ($i=0; $i<$count; $i++) {
			$cssFirst = ($i==0) ? "first" : "";
			$cssLast = ($i==$count-1) ? "last" : "";
			$editAdd = ($i==0) ? '{$editAdd}' : "";
			$loopNum = $i;
			$formatTmp = $format;
			$formatTmp = str_replace('{$loopNum}', $loopNum, $formatTmp);
			$formatTmp = str_replace('{$cssFirst}', $cssFirst, $formatTmp);
			$formatTmp = str_replace('{$cssLast}', $cssLast, $formatTmp);
			$formatTmp = str_replace('{$editAdd}', $editAdd, $formatTmp);
			$out .= $children[$i]->output($formatTmp);
		}

		pb_pqp_log_speed("article outputChildren()");

		return $out;
	}
	

	function getEditString() {
		$out = "";
		if (polarbear_user_can("edit_article") && pb_is_site_edit_enabled()) {
			$requestURI = urlencode($_SERVER["REQUEST_URI"]);
			$articleID = $this->getId();
			$link = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=articleEdit&amp;articleID=$articleID&amp;editsource=external&amp;editsourceurl=$requestURI";
			$out = "<span class='polarbear-edit polarbear-edit-article'><a href='$link' title='Edit this article'><img width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/edit.png' alt='Edit' /></a></span>";
		}
		return $out;
	}
	
	function getEditPrioString() {
		$out = "";
		if (polarbear_user_can("edit_article") && pb_is_site_edit_enabled()) {
			$requestURI = urlencode($_SERVER["REQUEST_URI"]);
			$articleID = $this->getId();
			$link = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=articleMove&amp;articleID=$articleID&amp;returnURL=$requestURI";
			$linkUp = "{$link}&amp;direction=up";
			$linkDown = "{$link}&amp;direction=down";
			if ($this->hasPrevArticle()) {
				$out = "<span class='polarbear-edit polarbear-edit-prio'><a href='$linkUp' title='Move article'><img width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/arrow-up.png' alt='Arrow up' /></a></span>";
			} else {
				$out = "<span class='polarbear-edit polarbear-edit-prio'><img style='opacity: .3;' width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/arrow-up.png' alt='Arrow up' /></span>";
			}
			if ($this->hasNextArticle()){
				$out .= "<span class='polarbear-edit polarbear-edit-prio'><a href='$linkDown' title='Move article'><img width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/arrow-down.png' alt='Arrow down' /></a></span>";
			} else {
				$out .= "<span class='polarbear-edit polarbear-edit-prio'><img style='opacity: .3;' width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/arrow-down.png' alt='Arrow down' /></span>";
			}
		}
		return $out;
	}
	
	function getEditAddString() {
		$out = "";
		if (polarbear_user_can("edit_article") && pb_is_site_edit_enabled()) {
			$requestURI = urlencode($_SERVER["REQUEST_URI"]);
			$articleID = $this->getId();
			$link = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=articleCreate&amp;articleID=$articleID&amp;editsource=external&amp;editsourceurl=$requestURI";
			$out .= "<span class='polarbear-edit polarbear-edit-prio'><a href='$link' title='Add article'><img width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/plus.png' alt='Arrow down' /></a></span>";
		}
		return $out;
	}

	function getEditAddChildString() {
		$out = "";
		if (polarbear_user_can("edit_article") && pb_is_site_edit_enabled()) {
			$requestURI = urlencode($_SERVER["REQUEST_URI"]);
			$articleID = $this->getId();
			$link = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=articleCreateChild&amp;articleID=$articleID&amp;editsource=external&amp;editsourceurl=$requestURI";
			$out .= "<span class='polarbear-edit polarbear-edit-prio'><a href='$link' title='Add sub-article'><img width='16' height='16' border='0' src='" . POLARBEAR_WEBPATH . "images/polarbear/plus.png' alt='Arrow down' /></a></span>";
		}
		return $out;
	}


	/**
	 * not sure this one is in use. idea that never went anywhere
	 */
	function fieldValuesSimplified() {
		$arrFieldValuesSimplified = array();
		$fieldValues = $this->fieldValues();
		foreach ($fieldValues as $fieldID => $fieldVal) {
			if (!isset($arrFieldValuesSimplified[$fieldID])) {
				$arrFieldValuesSimplified[$fieldID] = array();
			}
			foreach ($fieldVal as $fieldSubVal) {
				$arrFieldValuesSimplified[$fieldID][] = $fieldSubVal['value'];
			}
		}
		return $arrFieldValuesSimplified;
	}


	/**
	 * @param string shortname
	 * @return int If article found: id of that article. If no artcle found: bool false.
	 */
	function getArticleByShortname($shortname) {

		global $polarbear_db;
		$shortnameForSQL = $polarbear_db->escape($shortname);
		$sql = "SELECT id from " . POLARBEAR_DB_PREFIX . "_articles WHERE shortname = '$shortnameForSQL' AND status <> 'deleted' AND status <> 'revision'";
		$articleID = $polarbear_db->get_var($sql);
		pb_pqp_log_speed("article getArticleByShortname()");
		if($articleID) {
			$a = PolarBear_Article::getInstance($articleID);
			return $a;
		} else {
			return false;
		}
	}


	function setIsRevisionTo($articleID) {
		$this->isRevisionTo = $articleID;
	}
	function getIsRevisionTo() {
		return $this->isRevisionTo;
	}

	/**
	 * Get all tags this article is tagged with
	 * @return array with tags. Can be empty.
	 */
	function tags() {

		$preloader = pb_query_preloader::getInstance();
		$preloadRow = $preloader->getPreloadArticleTag($this->id);

		if ($preloadRow) {
			$rows = $preloadRow;
		} else {
			global $polarbear_db;
			$sql = "SELECT tagID FROM " . POLARBEAR_DB_PREFIX . "_article_tag_relation WHERE articleID = '$this->id'";
			$rows = $polarbear_db->get_results($sql);
		}

		$arrTags = array();
		if ($rows) {
			foreach ($rows as $row) {
				$arrTags[] = polarbear_tag::getInstance($row->tagID);
			}
		}

		pb_pqp_log_speed("article tags()");

		return $arrTags;
	}
	
	/**
	 * Get all tags that are child of tag with ID parentID and selected for the article
	 * @param int $parentID
	 * @return array
	 */
	function tagsSelectedChildren($parentID) {
		$arr = array();
		$arrTags = $this->tags();
		foreach ($arrTags as $oneTag) {
			if ($oneTag->parentID==$parentID) {
				$arr[] = $oneTag;
			}
		}
		return $arr;
	}
	
}

?>