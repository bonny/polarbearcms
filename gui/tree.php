<?php
require("../polarbear-boot.php");

header("Content-type: text/html; charset=utf-8");

$id = $_GET["id"];
$type = (isset($_GET["type"])) ? $_GET["type"] : null; // can be "filebrowser"

/**
 * om id = 0 så är det rooten
 */
if ($id == "0") {
	?>
	[
		{ 
			attributes: { id : "root", data: "{type: \"root\", deletable: false, renameable: false, draggable: false, dropable: false, droppable: false, clickable: true }" }, 
			state: "open", 
			data: {
				title: "Articles",
				icon: "<?php polarbear_webpath() ?>images/silkicons/folder_page_white.png"
			},
			children: <?php
				$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID IS NULL AND status <> 'deleted' AND status <> 'revision' ORDER BY prio DESC";
				if ($r = $polarbear_db->get_results($sql)) {
					$rowNum = 0;
					echo "[";
					foreach ($r as $row) {
						if ($rowNum>0) {
							echo ",";
						}
						$a = new PolarBear_Article();
						$a->loadThroughObject($row);
						?>
							{ 
								attributes: { 
									id : "article-<?php echo $a->getId() ?>", 
									data: "{type: \"article\"}",
									title: "Article id: <?php echo $a->getId() ?>"
								}, 
								state: "closed", 
								data: {
									title: "<?php echo htmlentities($a->getTitleArticle(), ENT_COMPAT, "UTF-8") ?>",
									icon: "<?php echo $a->statusIcon() ?>"
								},
							}			
						<?php
						$rowNum++;
					}
					echo "]";
				} else {
					echo "[]";
				}
				?>
		}
		<?php
			// only add the rest of the nav if we are at the main nav, not for example in the file browser
			if ($type!="filebrowser") {
			?>
			,
			{
				data: {
					title: "Images and documents",
					icon: "<?php polarbear_webpath() ?>images/silkicons/folder_image.png",
					attributes: {
						href: "gui/files.php"
					}
				},
				attributes: {
					id: "categoryFiles"
				}
			},
			{
				data: {
					title: "Users",
					icon: "<?php polarbear_webpath() ?>images/silkicons/folder_user.png",
					attributes: {
						href: "gui/users.php"
					}
				
				},
				attributes: {
					id: "categoryUsers"
				}
			
			},
			{
				data: {
					title: "Settings",
					icon: "<?php polarbear_webpath() ?>images/silkicons/folder_wrench.png",
					attributes: {
						href: "#"
					}
				
				},
				attributes: {
					id: "categorySettings"
				},
				children: [{
					data: {
						title: "General",
						icon: "<?php polarbear_webpath() ?>images/silkicons/page_white_wrench.png",
						attributes: {
							href: "gui/settings.php"
						}

					},
					attributes: {
						id: "categorySettingsGeneral"
					}
				},
				{
					data: {
						title: "Fields",
						icon: "<?php polarbear_webpath() ?>images/silkicons/page_white_wrench.png",
						attributes: {
							href: "gui/fields.php"
						}
					},
					attributes: {
						id: "categorySettingsFields"
					}

				}
				]
			
			}
			/*
			,
			{
				data: {
					title: "GUI examples",
					icon: "<?php polarbear_webpath() ?>images/silkicons/folder_bug.png",
					attributes: {
						href: "gui/gui-test.php"
					}
				
				},
				attributes: {
					id: "categoryGui"
				}
			}
			*/
			<?php
		} // end if type
		?>
		
	]
	<?php
		
} else {

	//parent=article-20
	$parentID = str_replace("article-", "", $id);
	// klickat på en artikel-nod
	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID = '$parentID' AND status <> 'deleted' AND status <> 'revision' AND status <> 'preview' ORDER BY prio DESC";
	if ($r = $polarbear_db->get_results($sql)) {
		$rowNum = 0;
		echo "[";
		foreach ($r as $row) {
			if ($rowNum>0) {
				echo ",";
			}
			$a = new PolarBear_Article();
			$a->loadThroughObject($row);
			// if article dont have any children, make state opened so we don't get a plus-sign (which is confusing)
			if ($a->hasChildren("includeUnpublished=true")) {
				$state = "closed";
			} else {
				$state = "opened";
			}
			?>
				{ 
					attributes: { id : "article-<?php echo $a->getId() ?>", data: "{type: \"article\"}", title: "Article id: <?php echo $a->getId() ?>" }, 
					state: "<?php echo $state ?>", 
					data: {
						title: "<?php
							if (trim($a->getTitleArticle())) {
								echo htmlentities($a->getTitleArticle(), ENT_COMPAT, "UTF-8");
							} else {
								echo "Untitled";
							}

						?>",
						icon: "<?php echo $a->statusIcon() ?>"
					}
				}
			<?php
			$rowNum++;
		}
		echo "]";
	} else {
		echo "[]";
	}
}

exit();

?>