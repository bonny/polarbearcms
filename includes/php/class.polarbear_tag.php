<?php
/**
 * Klass som representerar en tag
 */
class polarbear_tag {

	private static $instances = array();

	var
		$name,
		$id,
		$parentID,
		$isDeleted,
		$tableName,
		$prio
		;

	public function getInstance($tagID = null) {

		if ($tagID == null) {
			return new polarbear_tag($tagID);
		} elseif (isset(self::$instances[$tagID])) {
			return self::$instances[$tagID];
		} else {
			self::$instances[$tagID] = new polarbear_tag($tagID);
			return self::$instances[$tagID];
		}
		
	}
	
	function polarbear_tag($tagNameOrID = NULL) {
		$this->tableName = POLARBEAR_DB_PREFIX . "_article_tags";
	
		if (is_numeric($tagNameOrID)) {
			$this->load($tagNameOrID);
		}
		# else {
		#	$this->loadByName($tagNameOrID);
		#}
	}
	
	/**
	 * load a tag by it's name
	 * should not be used anymore since serveral tags can have the same name
	 */
	function loadByName($str) {
		$str = mysql_real_escape_string($str);
		$sql = "SELECT id FROM $this->tableName WHERE name = '$str' AND isDeleted = 0";
		$rs = mysql_query($sql);
		if (mysql_num_rows($rs)!=1) {
			return false;
		} else {
			$this->load(mysql_result($rs, 0, "id"));
		}
	}
	
	function load($tagID) {
		if (!is_numeric($tagID)) {
			return false;
		}
		
		$sql = "SELECT * FROM $this->tableName WHERE id = $tagID AND isDeleted = 0";
		$rs = mysql_query($sql) or die("<br>\n$sql\n<br>" . mysql_error());
		
		if (!mysql_num_rows($rs)) {
			return false;
		}
		$row = mysql_fetch_assoc($rs);
		$this->loadThroughRow($row);

	}
	
	function loadThroughRow($row) {
		$this->id = $row["id"];
		$this->name = $row["name"];
		$this->parentID = $row["parentID"];
		$this->isDeleted = $row["isDeleted"];
		$this->prio = $row["prio"];
	}
	
	function save() {
		$sqlName = mysql_real_escape_string($this->name);
		if ($this->isDeleted == "true" || $this->isDeleted === true || $this->isDeleted === 1) {
			$sqlIsDeleted = 1;
		} else {
			$sqlIsDeleted = 0;
		}

		if (is_numeric($this->parentID)) {
			$sqlParentID = " $this->parentID ";
		} else {
			$sqlParentID = " NULL ";
		}
				
		// om ny, fixa id
		if (!is_numeric($this->id)) {
			$sql = "INSERT INTO $this->tableName SET name='' ";
			mysql_query($sql);
			$this->id = mysql_insert_id();
		}
		$sql = "UPDATE $this->tableName SET name = '$sqlName', parentID = $sqlParentID, isDeleted = '$sqlIsDeleted', prio = '$this->prio' WHERE id = $this->id";
		#echo "<br>$sql";
		mysql_query($sql) or die("\n$sql\n" . mysql_error());

		return $this->id;
	}
	
	/**
	 * Return the children of this tag, if any
	 * @return array Array with all the children, or an empty array of no children exists
	 */
	function getChildren($options=null) {
		if (!$this->id) {
			return array();
		}
		$arr = array();
		$sql = "SELECT * FROM $this->tableName WHERE parentID = $this->id AND isDeleted = 0 ORDER BY prio ASC";
		$rs = mysql_query($sql) or die("<br>$sql<br>" . mysql_error());
		while ($row = mysql_fetch_assoc($rs)) {
			$TagTmp = new PolarBear_Tag();
			$TagTmp->loadThroughRow($row);
			$arr[] = $TagTmp;
		}
		return $arr;
	}
	/**
	 * same as getChildren()
	 */
	function children($options = null) {
		return $this->getChildren($options);
	}
	
	/**
	 * get all articles that has this tag
	 */
	function articles($orderBy = "titleArticle ASC") {
		$arr = array();
		$sql = "
			SELECT 
				tr.articleID, tr.tagID, a.titleArticle
			FROM
				" . POLARBEAR_DB_PREFIX . "_article_tag_relation AS tr
			INNER JOIN
				" . POLARBEAR_DB_PREFIX . "_articles AS a ON a.id = tr.articleID
			INNER JOIN
				" . POLARBEAR_DB_PREFIX . "_article_tags AS at ON at.id = tr.tagID
			WHERE
				tr.tagID = '$this->id' AND 
				at.isDeleted = 0
				ORDER BY $orderBy
		";
		global $polarbear_db;
		if ($r = $polarbear_db->get_results($sql)) {
			foreach ($r as $row) {
				$tmpArticle = polarbear_article::getInstance($row->articleID);
				if ($tmpArticle->isPublished()) {
					$arr[] = $tmpArticle;
				}
			}
		}
		return $arr;
	}

	function tagEscaped() {
		return htmlspecialchars($this->name, ENT_COMPAT, "UTF-8");
	}


	/**
	 * Get number of published  articles with this tag
	 */
	function getArticleCount() {
		$numberOfArticles = 0;
		$sql = "
			SELECT 
				count(tr.articleID) as numberOfArticles
			FROM
				" . POLARBEAR_DB_PREFIX . "_article_tag_relation AS tr
			INNER JOIN
				" . POLARBEAR_DB_PREFIX . "_articles AS a ON a.id = tr.articleID
			INNER JOIN
				" . POLARBEAR_DB_PREFIX . "_article_tags AS at ON at.id = tr.tagID
			WHERE
				tr.tagID = '$this->id' AND 
				at.isDeleted = 0
		";

		global $polarbear_db;
		if ($r = $polarbear_db->get_row($sql)) {
			$numberOfArticles = $r->numberOfArticles;
		}
		return $numberOfArticles;
	}
	
}

?>