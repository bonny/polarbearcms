<?php
/**
 * hanterar artiklars ajax-anrop för att skapa, redigera, spara etc.
 */
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
$action = $_REQUEST['action'];
polarbear_require_admin();


if ($action == "siteEditToggleEditIcons") {
	if (pb_is_site_edit_enabled()) {
		// deactivate
		 setcookie("pb_site_edit_icons_enabled", "0", time()+60*60*24*30, "/");
	} else {
		// activate
		 setcookie("pb_site_edit_icons_enabled", "1", time()+60*60*24*30, "/");
	}
	$returnURL = $_SERVER["HTTP_REFERER"];
	header("Location: $returnURL");
	exit;
}

/**
 * 	 Delete an article
 */
if ($_GET['action'] == 'articleDelete') {
	$articleID = (int) $_GET['articleID'];
	// update status
	$a = new PolarBear_Article($articleID);
	$a->delete();
	
	// also delete descendant articles
	// i guess this loop will delete the same articles several times. 
	// shouldn't be any problem i think
	foreach ($a->descendants() as $oneChild) {
		$oneChild->delete();
	}
	
	/*
    [editSource] => external
    [editSourceURL] => /news/
	*/
	if ($_GET["editSource"] == "external") {
		$gotoURL = $_GET["editSourceURL"];
	} else {
		$gotoURL = polarbear_treepage("gui/overview.php?articleDeleted=1&okmsg=ArticleDeleted");
	}
	header("Location: $gotoURL");
	#echo "deleted$articleID";
	exit;
}


if ($action == "ajax-addFieldCollection") {
	$fieldCollectionID = $_POST["fieldCollectionID"];
	$polarbearArticleEditNumNewFields = $_POST["polarbearArticleEditNumNewFields"];
	$sql = "SELECT id, name, type, fieldcollectionID, deleted, prio, content FROM " . POLARBEAR_DB_PREFIX . "_fields WHERE fieldCollectionID = $fieldCollectionID AND deleted = 0 ORDER BY prio ASC";
	global $polarbear_db;
	$fieldCollectionRows = $polarbear_db->get_results($sql);
	echo "<li>";
	echo "
		<div class='polarbear-article-edit-fields-repeatable-remove'>
			<a href='#' class='ui-icon ui-icon-trash'>Remove</a>
		</div>
	";

	foreach ($fieldCollectionRows as $oneField) {
		echo polarbear_getFieldForArticleEdit(null, $oneField->id, "new".$polarbearArticleEditNumNewFields);
	}
	echo "</li>";
	exit;
}


// för att testa
// sleep(2);

/**
 * title is changed for the first time
 * or shortname is modified
 * validate shortname and then return it
 */
if ($action == "getValidatedShortname") {
	/*
	Array
(
	[action] => getValidatedShortname
	[title] => Om oss
	[articleID] => 123
)
	*/
	$a = new PolarBear_Article($_POST["articleID"]);
	$shortName = $a->setshortName($_POST["title"]);
	echo $shortName;
	exit;
}

/**
 * article is created
 * http://localhost/polarbearcms/gui/articles-ajax.php?action=articleCreateChild&articleID=434&editsource=external&editsourceurl=%2Fnews%2F
 */
if ($action == "articleCreate" || $action == "articleCreateChild") {

	if ($_GET["editsource"] == "external") {
		// create from site
		$articleID = (int) $_GET["articleID"]; // create in same category as in this article
		$editSourceURL = $_GET["editsourceurl"];
		$refArticle = new PolarBear_Article($articleID);
		$a = new PolarBear_Article();
		$a->setTitleArticle("");
		$a->setParent($refArticle->getParentId());
		#$a->setStatus("published");
		$a->setStatus("new");
		$a->save();
		$newArticleID = $a->getId();

		// if creating a sub-article, move it
		if ($action == "articleCreateChild") {
			$a->move($refArticle, "inside");
		}
			
		$editlink = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=articleEdit&articleID=$newArticleID&editsource=external&editsourceurl=$editSourceURL";
		header("Location: $editlink");
		exit;
	} else {
		$parent = $_POST["parent"]; // parent=article-20
		$type = $_POST["type"]; // parent=article-20
		$refarticle = $_POST["refarticle"]; // parent=article-20
		if (strpos($refarticle,"article-")!== false) {
			//har en artikel som parent
			//hämta in id på denna
			$refarticle = str_replace("article-", "", $parent);
		} else {
			$refarticle = ""; // in på root-nivå
		}
		$a = new PolarBear_Article();
	
		$a->setTitleArticle("");
		$a->setStatus("published");
		$a->save();
	
		if ($refarticle) {
			$a->move($refarticle, $type);
		}

		#if ($action == "articleCreateChild") {
		#	$a->setParent($parent);
		#}
		
		echo $a->getId();
		exit;
	}

}

/**
 * article is renamed
 */
if ($action == "articleRename") {
	$article = $_POST["article"];
	$article = (int) str_replace("article-", "", $article);
	$newName = $_POST["newName"];
	$a = new PolarBear_Article($article);
	$a->setTitleArticle($newName);
	$a->save();

	echo "ok";
}

/**
 * article is moved trough tree
	move($targetArticleID, $positionType) { before after inside
 */
if ($action == "articleMove") {

	$direction = $_GET["direction"];
	
	// if direction = move up/down from button on website
	if ($direction) {
		$articleID = (int) $_GET["articleID"];
		$a = new PolarBear_Article($articleID);
		
		if ($direction == "up") {
			// move up. refnode is the article above the article
			$prevArticle = $a->prevArticle();
			if ($prevArticle) {
				$a->move($prevArticle->getId(), "before");
			}
		} elseif ($direction == "down") {
			// move down. refnode is the article below the article
			$nextArticle = $a->nextArticle();
			if ($nextArticle) {
				$a->move($nextArticle->getId(), "after");
			}
		}
		$returnURL = $_GET["returnURL"];
		header("Location: $returnURL");
		exit;
		
	} else {
		// moved within tree
		$article = $_POST["article"];
		$article = (int) str_replace("article-", "", $article);
		$refnodeID = $_POST["refnodeID"];
		$refnodeID = (int) str_replace("article-", "", $refnodeID);
		$type = strtolower($_POST["type"]); // BELOW|ABOVE|INSIDE
		$a = new PolarBear_Article($article);
		$a->move($refnodeID, $type);
	}
	

	echo "\nok";
	
}


/**
 * Article is edited
 */
if ($action == "articleEdit") {

	$page_class	= "polarbear-page-article-edit";
	
	$articleID = $_REQUEST["articleID"];
	$articleID = str_replace("article-", "", $articleID);
	$articleID = (int) $articleID;
	if (!$articleID) {
		die("No article id found.");
	}
	
	$a = new PolarBear_Article($articleID);
	$arrFieldConnectors = polarbear_getFieldConnectors();

	$fieldConnectorType = $a->getFieldConnectorType();
	$fieldConnectorID = $a->getFieldConnectorID();
	$fieldConnectorToUse = $a->fieldConnectorToUse();
	$arrFields = $a->fieldsAndValues();
	
	$cancelURL = polarbear_treepage("gui/overview.php");
	$afterSave = "";
	$afterSaveURL = "";

	// if showing the page as a single page
	// i.e. we're editing it from the outside/website
	$editSource = (isset($_GET["editsource"])) ? $_GET["editsource"] : null;
	$editSourceURL = (isset($_GET["editsourceurl"])) ? $_GET["editsourceurl"] : null;
	if ($editSource == "external") {
		$cancelURL = $editSourceURL;
		$skip_layout=true;
		require_once(POLARBEAR_ROOT . "/includes/php/admin-header.php");
		if ($editSourceURL == $a->fullpath()) {
			$afterSave = "articlePath";
			$afterSaveURL = "articlePath";
		} else {
			// if $editSourceURL and fullpath are different then we were problably not beginning our edit on the articles page
			// but rather in a listing or such, so we go back to that listing.
			$afterSave = "url";
			$afterSaveURL = $editSourceURL;
		}
	}

	?>	
		<script type="text/javascript">
			var polarbearArticleEditArticleID = <?php echo $articleID ?>;
			var polarbearArticleEditNumNewFields = 0;

			<?php if ($editSource == "external") { ?>
				$(function(){
					// safari does not work without timeout (yes, I know it's zero). very strange indeed.
					setTimeout(function() {
						polarbear_article_onload();
					}, 0);

				});
			<?php } ?>

			<?php
			if ($_GET["okmsg"] == "ArticleSaved") { ?>pb_showMessage("<p>Article saved</p>");<? }
			?>

		</script>

		<form target="" id="article-edit-form" class="" method="post" action="<?php polarbear_webpath() ?>gui/articles-save.php">

			<div class="primary module">
				
				<input type="hidden" name="article-id" id="article-id" value="<?php echo $a->getId() ?>" />
				<input type="hidden" name="action" value="article-save" />
				<input type="hidden" name="isPreview" value="0" id="article-edit-ispreview" />
				<input type="hidden" name="afterSave" id="article-edit-afterSave" value="<?php echo $afterSave ?>" />
				<input type="hidden" name="afterSaveURL" id="article-edit-afterSaveURL" value="<?php echo $afterSaveURL ?>" />
				<input type="hidden" name="editSource" id="article-edit-editSource" value="<?php echo $editSource ?>" />
				<input type="hidden" name="editSourceURL" id="article-edit-editSourceURL" value="<?php echo $editSourceURL ?>" />
				<input type="hidden" name="pageURL" id="article-edit-pageURL" value="<?php echo $_SERVER["REQUEST_URI"] ?>" />

				<div class="row">
					<a class="article-button-save ui-state-default ui-priority-primary ui-corner-all fg-button">
						Save
					</a>
					<a class="article-button-save-continue-editing ui-state-default ui-corner-all fg-button">
						Save and Continue Editing
					</a>
					<a class="article-button-preview ui-state-default ui-corner-all fg-button" title="Preview article in a new window">
						Preview
					</a>
					<span class="polarbear-afterbuttons">
						or 
						<a href="<?php echo $cancelURL ?>" class="polarbear-article-edit-button-cancel">Cancel</a>
					</span>
				</div>
								
				<div class="row" style="clear:both; padding-top:1em">
					<div>
						<label for="article-title">Title</label>
						<input autocomplete="off" class="text ui-widget-content ui-corner-all" id="article-title" name="article-title" type="text" value="<?php echo htmlspecialchars($a->getTitleArticle()) ?>" />
					</div>

					<div>
						<input class="checkbox" <?php echo ($a->isUsingCustomTitleNav()) ? " checked='checked' " : "" ?> id="article-use-different-title" name="article-use-different-title" type="checkbox" value="1" /><label for="article-use-different-title" class="for-checkbox"> Different title in navigation and page</label>
					</div>
					<div id="article-edit-custom-titles-wrapper" style="<?php echo (!$a->isUsingCustomTitleNav()) ? "display:none;" : "" ?>">
						<div>
							<label for="article-title-nav">Navigation title</label>
							<input class="text ui-widget-content ui-corner-all" id="article-title-nav" name="article-title-nav" type="text" value="<?php echo htmlspecialchars($a->getTitleNav()) ?>" />
						</div>
						<div>
							<label for="article-title-page">Page title</label>
							<input class="text ui-widget-content ui-corner-all" id="article-title-page" name="article-title-page" type="text" value="<?php echo htmlspecialchars($a->getTitlePage()) ?>" />
						</div>
					</div>
				</div><!-- end title -->
				
				<?php
				// todo: denna måste stöda kataloger/hierarki
				?>
				<div class="row">
					<label for="article-shortName">Address</label>
					<div id="article-shortName-subwrapper">
						<?php 
						?>
						<div>
							http://<?php echo polarbear_domain(); ?><?php echo htmlspecialchars($a->fullpath(false)) ?><span title="Click to edit short name" id="article-shortName-preview"><?php echo htmlspecialchars($a->getshortName()) ?></span><span id="article-shortName-change-input-wrapper"><input class="text ui-widget-content ui-corner-all" style="display:none;" type="text" value="<?php echo htmlspecialchars($a->getShortName()) ?>" />/</span>

							<a id="article-shortName-change" href="#">Change</a>
							<?php 
							// a new article does not have shortname yet.
							// don't know if thats good or bad, but that's how it is for now anyway
							if ($a->fullpath() != "//" && $a->fullpath() != "") {?>
								 | <a id="article-shortName-view" target="_blank" title="View this page in a new window/tab" href="<?php echo $a->fullpath() ?>">View</a>
							<?php } ?>
							
							<input class="ui-state-default ui-corner-all" type="button" value="Ok" id="article-shortName-change-ok" style="display:none;" />
							<a id="article-shortName-change-cancel" href="#" style="display: none;">Cancel</a>
						</div>
						<input id="article-shortName" name="article-shortName" type="hidden" value="<?php echo htmlspecialchars($a->getShortName()) ?>" />
					</div>
				</div>
				
				<div class="row">
					<label for="article-teaser">Teaser</label>
					<textarea style="width: 100%" cols="50" rows="10" id="article-teaser" name="article-teaser"><?php echo htmlspecialchars($a->getTeaser()) ?></textarea>
				</div>
				
				<div class="row">
					<label for="article-body">Body</label>
					<textarea style="width: 100%" cols="50" rows="15" id="article-body" name="article-body"><?php echo htmlspecialchars($a->getBody()) ?></textarea>
				</div>

				
					<?php
					// here comes the fields little darling
					if (!empty($arrFields)) {
						foreach ($arrFields as $fieldConnector) {
											
							// fieldConnctor innehåller inget vi behöver skriva ut direkt
							foreach ($fieldConnector["fieldCollections"] as $fieldCollection) {

								echo "<div class='row'>";
	
								// en fieldcollection består av ett namn och ett gäng fält
								// fälten kan vara repeterbara
								// ett icke-repeatable field syns alltid
								// ett repeatable field måste läggas till för att synas
								if ($fieldCollection["repeatable"]) {
									// is repeatable
									echo "<fieldset>";
									echo "<legend title='Field id {$fieldCollection["id"]}'>{$fieldCollection["name"]}</legend>";
									?>
									<div style="display: block; height: 2em; text-align: right;">
										<a href="#" data="{'fieldCollectionID':'<?php echo $fieldCollection["id"] ?>'}" class="polarbear-article-edit-fields-add fg-button fg-button-icon-left ui-corner-all">
											<span class="ui-icon ui-icon-circle-plus"></span>
											Add
										</a>
									</div>
									<?php
									echo "<ul class='polarbear-article-edit-fields-repeatable'>";
									// only add fields if fields with values exists
									// så: finns värden? hur många "set" av denna fieldCollection finns?
									// fieldID, articleID, numInSet, value
									// hämta in första fältet, kolla om det finns lagrat värde för denna och vad högsta numInSet isf är
									$tmpKey = key($fieldCollection["fields"]);

									$maxNumInSet =  $fieldCollection["fields"][$tmpKey]['totalNumInSet'];

									#$firstFieldID = $fieldCollection["fields"][$tmpKey]["id"];
									#$sql = "SELECT MAX(numInSet) FROM " . POLARBEAR_DB_PREFIX . "_fields_values WHERE fieldID = $firstFieldID and articleID = $articleID";
									#$maxNumInSet = $polarbear_db->get_var($sql); // är null om ingen finns, annars kan det vara en nolla ("0")
									#echo "<br>maxNumInSet: $maxNumInSet";

									if ($maxNumInSet==0) {
										// do nothing
									} else {
										for ($i = 0; $i<$maxNumInSet; $i++) {
											echo "<li>";
											echo "
												<div class='polarbear-article-edit-fields-repeatable-remove'>
													<a href='#' class='ui-icon ui-icon-trash'>Remove</a>
												</div>
											";
											// Print existing values
											foreach ($fieldCollection["fields"] as $field) {
												echo polarbear_getFieldForArticleEdit($a->getId(), $field["id"], $i);
											}
											echo "</li>";
										}
									}

									echo "</ul>";
									echo "</fieldset>";

								} else {
									echo "<ul class='polarbear-article-edit-fields-nonrepeatable'><li>";
									// Not repeatable. Print fields and possibly existing values
									foreach ($fieldCollection["fields"] as $field) {
										echo polarbear_getFieldForArticleEdit($a->getId(), $field["id"], 0);
									}
									echo "</li></ul>";
								}

							echo "</div>"; // row

							}
							
						}
					}
					?>

				<!-- bottom save row -->
				<div class="row" style="padding-bottom: 3em;">
					<a class="article-button-save ui-state-default ui-priority-primary ui-corner-all fg-button">
						Save
					</a>
					<a class="article-button-save-continue-editing ui-state-default ui-corner-all fg-button">
						Save and Continue Editing
					</a>
					<a class="article-button-preview ui-state-default ui-corner-all fg-button" title="Preview article in a new window">
						Preview
					</a>
					<span class="polarbear-afterbuttons">
						or 
						<a href="<?php echo $cancelURL ?>" class="polarbear-article-edit-button-cancel">Cancel</a>
					</span>
				</div>


			</div> <!-- end primary -->
			
			<div class="secondary">

				<div class="polarbear-optionbox">

					<div>
						<h3><a href="#">Article status</a></h3>
						<div>
							<?php
							// If new article, then status = new
							$status = $a->getStatus();
							?>
							<div>
								<div>
									<input <?php echo ($status=='draft') ? "checked='checked'" : "" ?> type="radio" name="article-status" id="article-status-draft" value="draft" /><label class="for-radio" for="article-status-draft"> Draft</label>
								</div>
								<div>
									<input <?php echo ($status=='published' || $status=='new') ? "checked='checked'" : "" ?> type="radio" name="article-status" id="article-status-published" value="published" /><label class="for-radio" for="article-status-published"> Published</label>
								</div>
								<div style="margin-left: 25px;">
									<div>
										From:
										<span id="article-datePublish-when-text">
											<?php
											$datePublish = $a->getDatePublish();
											if (empty($datePublish)) {
												echo "Now";
											} else {
												$dateTmp = strftime("%b %d, %Y %H:%M", $datePublish);
												echo $dateTmp;
											}
											?>
										</span>
										<a id="article-datePublish-change" href="#">Change</a>
										<!-- värdet som används för att spara -->
										<input type="hidden" name="article-datePublish-val" id="article-datePublish-val" value="<?php echo $datePublish ?>" />
										<!-- ms som används för att fixa datePicker-datumet -->
										<input type="hidden" id="article-datePublish-valY" value="<?php echo (int) date("Y", $datePublish) ?>" />
										<input type="hidden" id="article-datePublish-valM" value="<?php echo (int) (date("m", $datePublish)-1) ?>" />
										<input type="hidden" id="article-datePublish-valD" value="<?php echo (int) date("d", $datePublish) ?>" />
										<input type="hidden" id="article-datePublish-valHM" value="<?php echo date("H:i", $datePublish) ?>" />
										<input type="hidden" id="article-datePublish-valHours" value="<?php echo date("H", $datePublish) ?>" />
										<input type="hidden" id="article-datePublish-valMins" value="<?php echo date("i", $datePublish) ?>" />
									</div>
									<div>
										Until:
										<span id="article-dateUnpublish-when-text">
											<?php
											$dateUnpublish = $a->getDateUnpublish();
											if (empty($dateUnpublish)) {
												echo "Forever";
											} else {
												$dateTmp = strftime("%b %d, %Y %H:%M", $dateUnpublish);
												echo $dateTmp;
											}
											?>
										</span>
										<a id="article-dateUnpublish-change" href="#">Change</a>
										<input type="hidden" name="article-dateUnpublish-val" id="article-dateUnpublish-val" value="<?php echo $dateUnpublish ?>" />
										<!-- ms som används för att fixa datePicker-datumet -->
										<input type="hidden" id="article-dateUnpublish-valY" value="<?php echo (int) date("Y", $dateUnpublish) ?>" />
										<input type="hidden" id="article-dateUnpublish-valM" value="<?php echo (int) (date("m", $dateUnpublish)-1) ?>" />
										<input type="hidden" id="article-dateUnpublish-valD" value="<?php echo (int) date("d", $dateUnpublish) ?>" />
										<input type="hidden" id="article-dateUnpublish-valHM" value="<?php echo date("H:i", $dateUnpublish) ?>" />
										<input type="hidden" id="article-dateUnpublish-valHours" value="<?php echo date("H", $dateUnpublish) ?>" />
										<input type="hidden" id="article-dateUnpublish-valMins" value="<?php echo date("i", $dateUnpublish) ?>" />
									</div>

									<!-- publish dialog -->
									<div id="article-datePublish-window" style="display: none;">
										<div><input name="article-datePublish-when" id="article-datePublish-when-never" type="radio" value="" /><label class="for-radio" for="article-datePublish-when-never"> Publish immediently</label></div>
										<div><input name="article-datePublish-when" id="article-datePublish-when-date" type="radio" value="date" /><label class="for-radio" for="article-datePublish-when-date"> Publish at a specific date and time</label></div>
										<div id="article-datePublish-dateAndTime" style="display: none; margin-left:25px;">
											<div>Date</div>
											<input type="hidden" value="" name="article-datePublish-selecteddate" />
											<div id="article-datePublish-datepicker"></div>
											<div>Time</div>
											<div>
												<select id="article-datePublish-selectedtime-hh">
													<?php
													// here. 2 dropdowns: 1 hh, 2 mm
													for ($i=0; $i<24; $i++) {
														?>
														<option value="<?php echo $i ?>"><?php echo sprintf("%02d", $i) ?></option>
														<?php
													}
													?>
												</select>
												:
												<select id="article-datePublish-selectedtime-mm">
													<?php
													// here. 2 dropdowns: 1 hh, 2 mm
													for ($i=0; $i<60; $i=$i+15) {
														?>
														<option value="<?php echo $i ?>"><?php echo sprintf("%02d", $i) ?></option>
														<?php
													}
													?>
												</select>
											</div>
										</div>
									</div>
									
									<!-- unpublish dialog -->
									<div id="article-dateUnpublish-window" style="display: none;">
										<div><input name="article-dateUnpublish-when" id="article-dateUnpublish-when-never" type="radio" value="" /><label class="for-radio" for="article-dateUnpublish-when-never"> Never unpublish, show forever</label></div>
										<div><input name="article-dateUnpublish-when" id="article-dateUnpublish-when-date" type="radio" value="date" /><label class="for-radio" for="article-dateUnpublish-when-date"> Unpublish at a specific date and time</label></div>
										<div id="article-dateUnpublish-dateAndTime" style="display: none; margin-left:25px;">
											<div>Date</div>
											<input type="hidden" value="" name="article-dateUnpublish-selecteddate" />
											<div id="article-dateUnpublish-datepicker"></div>
											<div>Time</div>
											<div>
												<select id="article-dateUnpublish-selectedtime-hh">
													<?php
													// here. 2 dropdowns: 1 hh, 2 mm
													for ($i=0; $i<24; $i++) {
														?>
														<option value="<?php echo $i ?>"><?php echo sprintf("%02d", $i) ?></option>
														<?php
													}
													?>
												</select>
												:
												<select id="article-dateUnpublish-selectedtime-mm">
													<?php
													// here. 2 dropdowns: 1 hh, 2 mm
													for ($i=0; $i<60; $i=$i+15) {
														?>
														<option value="<?php echo $i ?>"><?php echo sprintf("%02d", $i) ?></option>
														<?php
													}
													?>
												</select>
											</div>

										</div>
									</div>
																		
								</div>
								
							</div>
							
						</div>
					</div>


					<div>
						<h3><a href="#">Official author</a></h3>
						<div>
							<div>
								<?php
								if ($a->getOfficialAuthorType() == "user") {
									$user_author = $a->getOfficialUserAsMajUser();
									$author = $user_author;
								} elseif ($a->getOfficialAuthorType() == "text") {
									$author = htmlspecialchars($a->getOfficialAuthorText(), ENT_COMPAT, "UTF-8");
								} elseif ($a->getOfficialAuthorType() == "none") {
									$author = "None selected";
								}
								?>
								<div>
									<span id="official-author-name-onscreen"><?php echo $author ?></span>
									<a href="#" id="official-author-change">Change</a>
								</div>
								<input type="hidden" name="official-author-type" value="<?php echo $a->getOfficialAuthorType() ?>" />
								<input type="hidden" name="official-author-text" value="<?php echo htmlspecialchars($a->getOfficialAuthorText(), ENT_COMPAT, "UTF-8") ?>" />
								<input type="hidden" name="official-author-userID" value="<?php echo $a->getOfficialAuthorID() ?>" />
							</div>
						</div>
					</div>



					<div>
						<h3><a href="#"><abbr title="Search Engine Optimization">SEO</abbr></a></h3>
						<div>
							<div>
								<div>
									<div>
										<label>Meta description</label>
										<textarea cols="25" rows="5" class="ui-widget-content ui-corner-all" name="metaDescription"><?php echo htmlspecialchars ($a->getMetaDescription(), ENT_COMPAT, "UTF-8") ?></textarea>
									</div>
									<div>
										<label>Meta keywords</label>
										<textarea cols="25" rows="5" class="ui-widget-content ui-corner-all" name="metaKeywords"><?php echo htmlspecialchars ($a->getMetaKeywords(), ENT_COMPAT, "UTF-8") ?></textarea>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div>
						<h3><a href="#">Template and Fields</a></h3>
						<div>
							<div>
								<?php
								$arrTemplates = polarbear_getTemplates(); // existing templates
								$articleTemplateType = $a->getTemplateType();
								$articleTemplateName = $a->getTemplateName();
								$articleTemplateCustom = $a->getTemplateCustom();
								?>
								<div>
									Template
								</div>
								<div>
									<input <?php echo ($articleTemplateType=='inherit') ? " checked='checked' " : "" ?> type="radio" name="article-template-type" id="article-template-type-inherit" value="" />
									<label class="for-radio" for="article-template-type-inherit"> Inherit from parent</label>
								</div>
								<div>
									<div>
										<input <?php echo ($articleTemplateType=='name') ? " checked='checked' " : "" ?> type="radio" name="article-template-type" id="article-template-type-name" value="name" />
										<select name="article-template-type-name-value" id="article-template-type-name-value">
											<?php
											foreach ($arrTemplates as $key => $val) {
												?>
												<option <?php echo ($articleTemplateName==$key) ? " selected='selected' ":""?> value="<?php echo htmlspecialchars ($key, ENT_COMPAT, "UTF-8") ?>"><?php echo htmlspecialchars ($key, ENT_COMPAT, "UTF-8")?></option>
												<?php
											}
											?>
										</select>
									</div>
								</div>
								<div>
									<input <?php echo ($articleTemplateType=='custom') ? " checked='checked' " : "" ?> type="radio" name="article-template-type" id="article-template-type-custom" value="custom" />
									<input class="ui-widget-content ui-corner-all" type="text" value="<?php echo htmlspecialchars ($articleTemplateCustom, ENT_COMPAT, "UTF-8")?>" name="article-template-type-custom-value" id="article-template-type-custom-value" />
								</div>
							</div>

							<div>
								<div>
									Fields
								</div>
								<div>
									<input <?php echo ($fieldConnectorType=='inherit') ? " checked='checked' " : "" ?> type="radio" name="article-template-field" id="article-template-field-inherit" value="" />
									<label class="for-radio" for="article-template-field-inherit"> Inherit from parent</label>
								</div>
								<div>
									<input <?php echo ($fieldConnectorType=='id') ? " checked='checked' " : "" ?> type="radio" name="article-template-field" id="article-template-field-name" value="id" />
									<select name="article-template-field-name-value" id="article-template-field-value">
										<?php
										foreach ($arrFieldConnectors as $oneField) {
											?>
											<option <?php print($fieldConnectorID == $oneField->id) ? " selected='selected' " : "" ?> value="<?php echo $oneField->id ?>"><?php echo $oneField->name ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>


					<!-- tags -->
					<?php
					$tagsJS = "";
					// hämta alla taggar i systemet
					$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_article_tags WHERE isDeleted = 0 ORDER BY prio ASC, parentID ASC";
					$rows = $polarbear_db->get_results($sql);
					$arrAllTags = array();
					if (!empty($rows)) {
						foreach ($rows as $row) {
							$arrAllTags[] = polarbear_tag::getInstance($row->id);
						}
					}
					foreach($arrAllTags as $oneTag) {
						$tagParentID = (is_numeric($oneTag->parentID)) ? $oneTag->parentID : "null";
						$tagsJS .= "\ntags.addTag('$oneTag->name', $tagParentID, $oneTag->id);";
					}
					
					// hämta alla taggar som är kopplad till artikeln
					$tags = $a->tags();
					foreach ($tags as $oneTag) {
						$tagsJS .= "\ntags.toggleTagById($oneTag->id);";
					}
					?>
					<div>
						<h3><a href="#">Tags</a></h3>
						<div>
							<div>
								<div>
									<div style="xclear: both;" id="pb-article-edit-tags">Tags here</div>
									<script>
										$(function() {
											var tags = new majTags();
											tags.attachTo("#pb-article-edit-tags");
											<?php echo $tagsJS ?>
											tags.update();
										});
									</script>
								</div>
							</div>
						</div>

					
				</div><!-- // optionboxes -->
				
				<!-- Delete article -->
				<?php
				$deleteURL = POLARBEAR_WEBPATH . "gui/articles-ajax.php?action=articleDelete&amp;articleID=$articleID&amp;editSource=$editSource&amp;editSourceURL=$editSourceURL";
				?>
				<p>
					<input type="hidden" id="polarbear-article-edit-delete-url" value="<?php echo $deleteURL ?>" />
					<a id="polarbear-article-edit-delete" href="#" class="fg-button fg-button-icon-left">
						<span class="ui-icon ui-icon-trash"></span>
						Delete
					</a>
				</p>
				

				
			</div><!-- // secondary -->
							
		</form>

		
		<!-- change official author, to be used with blockUI -->
		<div id="official-author-window" style="display: none; cursor: default;" class="module">
			<div>
				<input checked="checked" type="radio" name="author-type" id="official-author-type-none" value="none" /><label for="official-author-type-none"> Do not use an official author</label>
			</div>
			<div>
				<input type="radio" name="author-type" value="text" id="official-author-type-custom" /><label for="official-author-type-custom"> Use a custom name:</label>
				<input class="ui-widget-content ui-corner-all" type="text" name="author-type-custom-name" value="" />
			</div>
			<div>
				<input id="author-type-choose-from-existing" type="radio" name="author-type" value="user" /><label for="author-type-choose-from-existing"> Choose an existing user</label>
			</div>
			
			<div id="author-type-choose-userbrowser" style="width: 100%; display: none;">
				<div style="width: 30%; float: left;">
					<div>Groups</div>
					<div style="height: 200px; overflow: auto;">
						<?php
						admin_get_user_group_list("article-edit-choose-author-users-groups");
						?>
					</div>
				</div>
				<div style="width: 30%; float: left;">
					<div>Users</div>
					<div id="author-type-choose-users" style="height: 200px; overflow: auto;">
					</div>
				</div>
				<div style="width: 30%; float: left;" id="author-type-choose-oneUserInfo">
				
				</div>
			</div>			
			
		</div>
		
		<div id="polarbear-article-edit-fields-files-dialog" style="display:none;">
			<iframe style="width: 100%; height: 100%" src=""></iframe>
		</div>

		<div id="polarbear-article-edit-dialog-save" style="display:none;">
			Saving article...
		</div>
		
	<?php
	
	if ($editSource == "external") {
		require_once(POLARBEAR_ROOT . "/includes/php/admin-footer.php");
	}

} // end edit

?>