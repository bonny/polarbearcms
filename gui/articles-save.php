<?php
/**
 * sparar en artikel
 */
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();
 
#polarbear_d($_POST);exit;

$articleID = (int) $_POST["article-id"];
$isPreview = (bool) $_POST["isPreview"];


// debug
#parse_str($_POST["maj-tags-serialized"], $arrSupertmp); pb_d($arrSupertmp);exit;
// end debug

$a = new PolarBear_Article($articleID);

if ($_POST["article-use-different-title"]) {
	$a->setCustomTitleNav($_POST["article-title-nav"]);
	$a->setCustomTitlePage($_POST["article-title-page"]);
} else {
	$a->setIsUsingCustomTitlePage(false);
	$a->setIsUsingCustomTitleNav(false);
}

$a->setTitleArticle($_POST["article-title"]); // måste sättas *efter* custom page/nav

$a->setOfficialAuthorType($_POST["official-author-type"]);
$a->setOfficialAuthorText($_POST["official-author-text"]);
$a->setOfficialAuthorID($_POST["official-author-userID"]);

if ($isPreview) {
	// create a preview shortname
	$a->setShortName($_POST["article-shortName"] . "-preview");
} else {
	$a->setShortName($_POST["article-shortName"]);
}

$a->setTeaser($_POST["article-teaser"]);
$a->setBody($_POST["article-body"]);

$a->setStatus($_POST["article-status"]);

$a->setDatePublish($_POST["article-datePublish-val"]);
$a->setDateUnpublish($_POST["article-dateUnpublish-val"]);

$a->setMetaDescription($_POST["metaDescription"]);
$a->setMetaKeywords($_POST["metaKeywords"]);

$a->setTemplateType($_POST["article-template-type"]);
$a->setTemplateName($_POST["article-template-type-name-value"]);
$a->setTemplateCustom($_POST["article-template-type-custom-value"]);

$a->setFieldConnectorType($_POST["article-template-field"]);
$a->setFieldConnectorID($_POST["article-template-field-name-value"]);


// We're saving a preview
/*
	status=preview
	isRevisionTo=org articleID
	save as new
	goto articleURL+?pb-preview=1
*/
if ($isPreview) {
	$a->prepareForCopy(); // unsets ID
	$a->setStatus("preview");
	$a->setIsRevisionTo($articleID);
}

$a->save();
$articleID = $a->getId(); // fetch possibly new id (in case of preview)

// lagra fälten
$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_fields_values WHERE articleID = '$articleID'"; // töm fälten för denna artikel
$polarbear_db->query($sql);
if (is_array($_POST["fields"])) {

	foreach ($_POST["fields"] as $arrFieldCollection) {
		// fieldColletion är en array och kan vara mer än 1 om den är repeatable
		if (is_array($arrFieldCollection)) {
			$numInSet = 0;
			foreach ($arrFieldCollection as $numInSetOrNewNum => $oneFieldCollection) {
				foreach ($oneFieldCollection as $fieldID => $fieldValue) {
					$sqlFieldValue = $polarbear_db->escape($fieldValue);
					$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_fields_values SET fieldID = '$fieldID', articleID = '$articleID', value = '$sqlFieldValue', numInset = '$numInSet'";
					$polarbear_db->query($sql);
				}
				$numInSet++;
			}
		}
		
	}
	
}

$a->fieldsAndValues(true);


// save tags
parse_str($_POST["maj-tags-serialized"], $arrTags);
#polarbear_d($arrTags);
// tags%5Bnew2%5D%5Bid%5D=new2&tags%5Bnew2%5D%5Bname%5D=1&tags%5Bnew2%5D%5Bdeleted%5D=false&tags%5Bnew2%5D%5Bselected%5D=false&tags%5Bnew2%5D%5BparentID%5D=null&tags%5Bnew2%5D%5Bprio%5D=0&tags%5Bnew4%5D%5Bid%5D=new4&tags%5Bnew4%5D%5Bname%5D=1.1&tags%5Bnew4%5D%5Bdeleted%5D=false&tags%5Bnew4%5D%5Bselected%5D=true&tags%5Bnew4%5D%5BparentID%5D=new2&tags%5Bnew4%5D%5Bprio%5D=1&tags%5Bnew8%5D%5Bid%5D=new8&tags%5Bnew8%5D%5Bname%5D=1.2&tags%5Bnew8%5D%5Bdeleted%5D=false&tags%5Bnew8%5D%5Bselected%5D=true&tags%5Bnew8%5D%5BparentID%5D=new2&tags%5Bnew8%5D%5Bprio%5D=2&tags%5Bnew10%5D%5Bid%5D=new10&tags%5Bnew10%5D%5Bname%5D=1.3&tags%5Bnew10%5D%5Bdeleted%5D=false&tags%5Bnew10%5D%5Bselected%5D=true&tags%5Bnew10%5D%5BparentID%5D=new2&tags%5Bnew10%5D%5Bprio%5D=3&tags%5Bnew6%5D%5Bid%5D=new6&tags%5Bnew6%5D%5Bname%5D=1.1.1&tags%5Bnew6%5D%5Bdeleted%5D=false&tags%5Bnew6%5D%5Bselected%5D=false&tags%5Bnew6%5D%5BparentID%5D=new10&tags%5Bnew6%5D%5Bprio%5D=4
/*
Array
(
    [tags] => Array
        (
            [new2] => Array
                (
                    [id] => new2
                    [name] => ett
                    [deleted] => false
                    [selected] => true
                    [parentID] => null
                    [prio] => 0
                )

*/
$sql = "DELETE FROM " . POLARBEAR_DB_PREFIX . "_article_tag_relation WHERE articleID = '$articleID'";
mysql_query($sql);

if (is_array($arrTags) && is_array($arrTags["tags"])) {
	$arrNewTags = array(); // när nya taggar får id måste vi spara en koppling mellan namnet och IDet den får
	foreach ($arrTags["tags"] as $oneTag) {
	if (!empty($oneTag["name"])) {

			$Tag = new polarbear_tag($oneTag["id"]);
			$Tag->id = $oneTag["id"];

			// dont know why but sometimes the name has some weird chars in it, like hard spaces
			$Tag->name = trim($oneTag["name"]);
			$Tag->name = str_replace(chr(194), " ", $Tag->name);
			$Tag->name = str_replace(chr(160), " ", $Tag->name);
			$Tag->name = trim($Tag->name);
			
			// finns parentID i $arrNewTags[$oneTag["id"]] så ska vi använda det värdet istället
			if (isset($arrNewTags[$oneTag["parentID"]])) {
				$parentID = $arrNewTags[$oneTag["parentID"]];
			} else {
				$parentID = $oneTag["parentID"];
			}
			
			$Tag->parentID = $parentID;
			$Tag->isDeleted = $oneTag["deleted"];
			$Tag->prio = $oneTag["prio"];
			$tagID = $Tag->save();
			
			if (!is_numeric($oneTag["id"])) {
				$arrNewTags[$oneTag["id"]] = $tagID;
			}
			
			// taggen är sparad/uppdaterad
			// koppla ihop med artikeln
			if ($oneTag["selected"]=="true") {
				$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_article_tag_relation SET tagID = $tagID, articleID = $articleID";
				mysql_query($sql) or die("<br>$sql<br>" . mysql_error());
			}

		}
		
	}
}
// end save tags


// save is done, now go on/back/forward/backward/somewhere
$afterSave = $_POST["afterSave"];
$afterSaveURL = $_POST["afterSaveURL"];
$editSource = $_POST["editSource"];
$pageURL = $_POST["pageURL"];

/*
echo "<br>afterSave: " . $afterSave;
echo "<br>editSource: " . $editSource;
echo "<br>pageURL: $pageURL";
//*/

pb_event_fire("article_saved", array("article"=>$a));

$doHeaderRefresh = true;
if ($isPreview) {

	// if $a is not published, this will fail
	$url = $a->fullpath();
	$url .= "?pb-preview=1";
} elseif ($afterSave == "continueEditing") {
	// just return. the tree will take us further...
	// but we may not go back to tree, may go back to url instead
	$doHeaderRefresh = false;
	if ($editSource == "external") {
		$url = $pageURL;
	} else {
		$okmsg = "ArticleSaved";
		$url = polarbear_treepage("gui/articles-ajax.php?action=articleEdit&articleID=" . $articleID . "&okmsg=$okmsg");
	}
} else if ($afterSave == "overview") {
	$okmsg = urlencode("Article saved");
	$url = polarbear_treepage("gui/overview.php?okmsg=$okmsg");
} else if ($afterSave == "url") {
	$url = $afterSaveURL;
} else if ($afterSave == "articlePath") {
	$url = $a->fullpath();
}

// refresh since IE had a problem not recognizing changes sometimes
if ($doHeaderRefresh) {
	header("refresh: 1; url=$url");
	echo "Article saved.<br /><a href='$url'>Continue &raquo;</a>";
} else {
	header("Location: $url");
}

exit;

?>