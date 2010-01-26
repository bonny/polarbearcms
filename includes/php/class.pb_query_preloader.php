<?php
/*
	Preload articles
	and stuff
*/
class pb_query_preloader {
	
	private static $instance = null;
	private $updateCount = 0;
	private $arrArticlesToPreload = array();
	private $arrArticlesWithChildsToPreload = array();
	private $arrPreloads = array(
		"article" => array(),
		"fields" => array(),
		"articleTags" => array()
	); // innehåller alla preloads i form av resultatet från en $polarbear_db->get_results($sql);

	public function getInstance() {

		if (isset(self::$instance)) {
			return self::$instance;
		} else {
			self::$instance = new pb_query_preloader;
			return self::$instance;
		}
	}
	
	/*
		Adds one or several articles
		comma separated
	*/
	public function addArticle($articles) {
		$articles = str_replace(" ", "", $articles);
		if (is_numeric($articles)) {
			$this->arrArticlesToPreload[] = (int) $articleID;
		} else {
			$arrArticles = explode(",", $articles);
			foreach ($arrArticles as $oneArticle) {
				$this->arrArticlesToPreload[] = (int) $oneArticle;
			}
		}
		$this->arrArticlesToPreload = array_unique($this->arrArticlesToPreload);
	}

	public function addArticleWithChilds($articles) {
		$articles = str_replace(" ", "", $articles);
		if (is_numeric($articles)) {
			$this->arrArticlesWithChildsToPreload[] = (int) $articles;
		} else {
			$arrArticles = explode(",", $articles);
			foreach ($arrArticles as $oneArticle) {
				$this->arrArticlesWithChildsToPreload[] = (int) $oneArticle;
			}
		}
		$this->arrArticlesWithChildsToPreload = array_unique($this->arrArticlesWithChildsToPreload);
		#echo "<br>::: ";pb_d($this->arrArticlesWithChildsToPreload);
	}

	
	function update() {
		$this->updateCount++;

		// uppdatera, men bara dom vi inte redan har
		// dvs. dom som inte redan ligger i $arrPreloads
		$arrArticlesToPreloadTmp = array();
		$arrArticlesToPreloadTmp = $this->arrArticlesToPreload;
		$arrArticlesWithChildsToPreloadTmp = $this->arrArticlesWithChildsToPreload; // inget åtgärd på denna ännu...
		/*
		$strPreloadedArticlesIds = "";
		foreach ($this->arrPreloads["article"] as $key => $val) {
			$strPreloadedArticlesIds .= ",$key";
			if (!in_array($key, $this->arrArticlesToPreload)) {
				// inte preloaded ännu, så den ska vi ta med
				$arrArticlesToPreloadTmp[] = $key;
			} else {
			}
		}*/

		foreach ($this->arrPreloads["article"] as $key => $val) {
			$strPreloadedArticlesIds .= ",$key";
		}
		$strPreloadedArticlesIds = trim($strPreloadedArticlesIds, ",");
		$sqlExcludePreloadedArticles = "";
		if ($strPreloadedArticlesIds) {
			$sqlExcludePreloadedArticles = " AND id NOT IN ($strPreloadedArticlesIds) ";
		}
		
		$strArticleIDs = join(",", $arrArticlesToPreloadTmp);
		$strArticleWithChildsIDs = join(",", $arrArticlesWithChildsToPreloadTmp);
		if ($strArticleWithChildsIDs) {
			$strArticleIDs .= ",$strArticleWithChildsIDs";
		}
		$strArticleIDs = ltrim($strArticleIDs, ",");
		
		// om inga idn, do nothing
		if (!$strArticleIDs) {
			return false;
		}

		// hämta artiklar
		// detta är samma fråga som polarbear_article->load annars kör
		// kunna få in parentID direkt, så man kan få alla childs i ett nafs. vore grymt!
		$sqlParentID = "";
		if ($this->arrArticlesWithChildsToPreload) {
			$sqlParentID = "OR parentID IN ($strArticleWithChildsIDs)";
		}
		$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE ( id IN ($strArticleIDs) $sqlParentID ) $sqlExcludePreloadedArticles AND status <> 'deleted'";
		#echo "<br>update: $sql";
		global $polarbear_db;
		$res = $polarbear_db->get_results($sql); // load använder get_results så vi måste ha samma
		
		// fortsätt bara om vi fått resultat
		if (!$res) {
			return false;
		}
		
		// lägg till varje resultat, med artikelID som nyckel
		$strAllFoundArticlesIds = "";
		foreach ($res as $oneRes) {
			$this->arrPreloads["article"][$oneRes->id] = $oneRes;
			$strAllFoundArticlesIds .= "{$oneRes->id},";
			if (!isset($this->arrPreloads["fields"][$oneRes->id])) {
				$this->arrPreloads["fields"][$oneRes->id] = array();
				$this->arrPreloads["articleTags"][$oneRes->id] = array();
			}
		}
		$strAllFoundArticlesIds = rtrim($strAllFoundArticlesIds, ",");
		
		// hämta in fältvärden för alla dess artiklar
		$sql = "
			SELECT 
				fv.fieldID, fv.articleID, fv.value, fv.numInSet,
				f.name as fieldName, f.type as fieldType
			FROM " . POLARBEAR_DB_PREFIX . "_fields_values as fv
			INNER JOIN " . POLARBEAR_DB_PREFIX . "_fields as f ON f.id = fv.fieldID
			WHERE articleID IN ($strAllFoundArticlesIds) ORDER BY fieldID ASC, numInSet ASC
		";
		$res = $polarbear_db->get_results($sql); // load använder get_results så vi måste ha samma
		foreach ($res as $oneRes) {
			$this->arrPreloads["fields"][$oneRes->articleID][] = $oneRes;
		}
		
		// hämta in tags
		$sql = "SELECT articleID, tagID FROM " . POLARBEAR_DB_PREFIX . "_article_tag_relation WHERE articleID IN ($strAllFoundArticlesIds)";
		$res = $polarbear_db->get_results($sql); // load använder get_results så vi måste ha samma
		if ($res) {
			foreach ($res as $oneRes) {
				$this->arrPreloads["articleTags"][$oneRes->articleID][] = $oneRes;
			}
		}

		# $this->arrPreloads["articleTags"][$oneRes->id]
		#$arrPreloads["articleTags"];
		#echo "<br><br>preload update count: " . $this->updateCount;
		#echo "<br>size of $this->arrPreloads[article] " . sizeof($this->arrPreloads["article"]);
		#echo "<br>arrArticlesToPreloadTmp: ";pb_d($arrArticlesToPreloadTmp);

	}
	
	function getPreloadArticleTag($articleID) {
		$articleID = (int) $articleID;
		if ($articleID && isset($this->arrPreloads["articleTags"][$articleID])) {
			return $this->arrPreloads["articleTags"][$articleID];
		} else {
			return false;
		}
	}

	
	function getPreloadArticleRow($articleID) {
		$articleID = (int) $articleID;
		if ($articleID && isset($this->arrPreloads["article"][$articleID])) {
			return $this->arrPreloads["article"][$articleID];
		} else {
			return false;
		}
	}

	function getPreloadFieldsRows($articleID) {
		$articleID = (int) $articleID;
		if ($articleID && isset($this->arrPreloads["fields"][$articleID])) {
			return $this->arrPreloads["fields"][$articleID];
		} else {
			return false;
		}
	}
	
}
?>