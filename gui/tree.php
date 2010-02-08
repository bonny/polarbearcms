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
		<?php if($type!="filebrowser") { ?>
		{
			data: {
				title: "Overview",
				icon: "<?php polarbear_webpath() ?>images/silkicons/layout_header.png",
				attributes: {
					href: "gui/overview.php"
				}
			
			},
			attributes: {
				id: "categoryOverview"
			}
		},
		<?php } ?>
		{
			attributes: { rel: "root", id : "root", data: "{type: \"root\", deletable: false, renameable: false, draggable: false, dropable: false, droppable: false, clickable: true }" }, 
			state: "open", 
			data: {
				title: "Articles",
				icon: "<?php polarbear_webpath() ?>images/silkicons/folder_page_white.png",
				attributes: {
					href: "#"
				}
			},
			children: <?php
				$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID IS NULL AND status <> 'deleted' AND status <> 'revision' AND status <> 'new' AND status <> 'preview' ORDER BY prio DESC";
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
									title: "Article id: <?php echo $a->getId() ?>",
									rel: "article",
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
					id: "categoryFiles",
					rel: "folder"
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
					id: "categoryUsers",
					rel: "folder"
				}
			
			},
			{
				data: {
					title: "Email lists",
					icon: "<?php polarbear_webpath() ?>images/silkicons/folder_table.png",
					attributes: {
						href: "gui/emaillists.php"
					}
				
				},
				attributes: {
					id: "categoryEmaillists",
					rel: "folder"
				}
			
			},
			{
				data: {
					title: "Settings & Tools",
					icon: "<?php polarbear_webpath() ?>images/silkicons/folder_wrench.png",
					attributes: {
						href: "gui/tools.php"
					}
				
				},
				attributes: {
					id: "categorySettings",
					rel: "folder"
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
						id: "categorySettingsGeneral",
						rel: "folder"
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
						id: "categorySettingsFields",
						rel: "folder"
					}

				}
				<?php
				// If POLARBEAR_PLUGINS_PATH is not false, that means we have a plugins-directory
				if (POLARBEAR_PLUGINS_PATH != false) {
				?>
				,
				{
					data: {
						title: "Plugins",
						icon: "<?php polarbear_webpath() ?>images/silkicons/page_white_wrench.png",
						attributes: {
							href: "gui/plugins.php"
						}
					},
					attributes: {
						id: "categorySettingsPlugins",
						rel: "folder"
					}

				}
				<?php
				}
				?>
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

			global $pb_tree_added;

			/*
			pb_d($pb_tree_added);
		    [0] => Array
		        (
		            [name] => En testplugin
		            [icon] => sökväg till icon
		            [filename] => example.php
		        )

			*/
			if (is_array($pb_tree_added)) {
				$treeAddedNum = 0;
				foreach ($pb_tree_added as $oneAddedTreeItem) {
					?>
					,{
						data: {
							title: "<?php echo htmlspecialchars ($oneAddedTreeItem["name"], ENT_COMPAT, "UTF-8") ?>",
							icon: "<?php polarbear_webpath() ?>images/silkicons/folder.png",
							attributes: {
								href: "<?php echo POLARBEAR_WEBPATH . "includes/php/plugin-load.php?pluginFilename=" . rawurlencode($oneAddedTreeItem["filename"]) ?>"
							}
						
						},
						attributes: {
							id: "treeAddedNum<?php echo $treeAddedNum ?>"
						}
					}
					<?php
					$treeAddedNum++;
				}
			}
			
			
		} // end if type
		?>
		
	]
	<?php
		
} else {

	//parent=article-20
	$parentID = str_replace("article-", "", $id);
	// klickat på en artikel-nod
	$sql = "SELECT * FROM " . POLARBEAR_DB_PREFIX . "_articles WHERE parentID = '$parentID' AND status <> 'deleted' AND status <> 'revision' AND status <> 'preview' AND status <> 'new' ORDER BY prio DESC";
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
					attributes: { 
						rel: "article",
						id : "article-<?php echo $a->getId() ?>", 
						data: "{type: \"article\"}", 
						title: "Article id: <?php echo $a->getId() ?>"
					}, 
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