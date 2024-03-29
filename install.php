<?php
#require_once("./polarbear-boot.php");

require_once("./polarbear-config.php");
require_once("./includes/php/class.ezsql_mysql.php");

// phpinfo();
// SELECT VERSION()

/*
 * Create tables and columns that don't exist
 * $action check | perform
 */
function pb_createAndUpdateTables($action = "check", & $whatToBeDone = null) {
	$tables = "
	CREATE TABLE `polarbear_article_tag_relation` (
	  `articleID` int(10) unsigned NOT NULL default '0',
	  `tagID` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`articleID`,`tagID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_article_tags` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) default NULL,
	  `parentID` int(10) unsigned default NULL,
	  `isDeleted` tinyint(4) default NULL,
	  `prio` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`id`),
	  KEY `parentID` (`parentID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_articles` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `parentID` int(10) unsigned default NULL,
	  `isRevisionTo` int(10) unsigned default NULL,
	  `prio` int(10) unsigned default NULL,
	  `status` varchar(50) collate utf8_swedish_ci default NULL,
	  `titleArticle` varchar(255) collate utf8_swedish_ci default NULL,
	  `titleNav` varchar(255) collate utf8_swedish_ci default NULL,
	  `titlePage` varchar(255) collate utf8_swedish_ci default NULL,
	  `useCustomTitleNav` tinyint(4) default NULL,
	  `useCustomTitlePage` tinyint(4) default NULL,
	  `dateCreated` datetime default NULL,
	  `datePublish` datetime default NULL,
	  `dateUnpublish` datetime default NULL,
	  `dateChanged` datetime default NULL,
	  `templateType` varchar(255) character set latin1 default NULL,
	  `templateName` varchar(255) collate utf8_swedish_ci NOT NULL default '',
	  `templateCustom` varchar(255) collate utf8_swedish_ci NOT NULL default '',
	  `officialAuthorID` int(10) unsigned default NULL,
	  `officialAuthorType` varchar(16) collate utf8_swedish_ci default NULL,
	  `officialAuthorText` varchar(255) collate utf8_swedish_ci default NULL,
	  `teaser` text collate utf8_swedish_ci,
	  `body` text collate utf8_swedish_ci,
	  `metaDescription` text collate utf8_swedish_ci,
	  `metaKeywords` varchar(255) collate utf8_swedish_ci default NULL,
	  `shortName` varchar(255) collate utf8_swedish_ci default NULL,
	  `fieldConnectorType` varchar(255) collate utf8_swedish_ci NOT NULL default '',
	  `fieldConnectorID` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`id`),
	  KEY `parentID` (`parentID`),
	  KEY `shortName` (`shortName`),
	  KEY `status` (`status`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `type` varchar(32) character set latin1 NOT NULL default '',
	  `fieldcollectionID` int(10) unsigned NOT NULL default '0',
	  `deleted` tinyint(4) NOT NULL default '0',
	  `prio` int(11) NOT NULL default '0',
	  `content` text NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `fieldCollectionID` (`fieldcollectionID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_collections` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `repeatable` tinyint(4) NOT NULL default '0',
	  `deleted` tinyint(4) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_connectors` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `deleted` tinyint(4) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_link_connectors_collections` (
	  `fieldConnectorID` int(10) unsigned NOT NULL default '0',
	  `fieldCollectionID` int(10) unsigned NOT NULL default '0',
	  `prio` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`fieldConnectorID`,`fieldCollectionID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_values` (
	  `fieldID` int(10) unsigned NOT NULL default '0',
	  `articleID` int(10) unsigned NOT NULL default '0',
	  `value` text NOT NULL,
	  `numInSet` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`fieldID`,`articleID`,`numInSet`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_files` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `mime` varchar(255) character set latin1 default NULL,
	  `name` varchar(255) collate utf8_swedish_ci default NULL,
	  `size` int(11) default NULL,
	  `dateUploaded` datetime default NULL,
	  `dateModified` datetime default NULL,
	  `uploaderID` int(11) default NULL,
	  `width` int(11) default NULL,
	  `height` int(11) default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_files_tags` (
	  `fileID` int(10) unsigned NOT NULL auto_increment,
	  `tagName` varchar(255) collate utf8_swedish_ci NOT NULL default '',
	  PRIMARY KEY  (`fileID`,`tagName`),
  	  KEY `tagName` (`tagName`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_settings` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `settings` text character set latin1 NOT NULL,
	  `date` datetime NOT NULL default '0000-00-00 00:00:00',
	  PRIMARY KEY  (`id`),
	  KEY `date` (`date`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_storage` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `thekey` varchar(255) NOT NULL default '',
	  `thevalue` text NOT NULL,
	  `dateExpire` datetime default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

	
	CREATE TABLE `polarbear_usergroups` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) character set latin1 default NULL,
	  `isDeleted` tinyint(4) default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_users` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `isDeleted` tinyint(4) default '0',
	  `firstname` varchar(255) collate utf8_swedish_ci default NULL,
	  `lastname` varchar(255) collate utf8_swedish_ci default NULL,
	  `email` varchar(255) character set latin1 default NULL,
	  `password` varchar(40) character set latin1 default NULL,
	  `dateCreated` datetime default NULL,
	  `dateChanged` datetime default NULL,
	  `loginToken` varchar(32) character set latin1 default NULL,
	  `dateLastLogin` datetime default NULL,
	  `dateLastSeen` datetime default NULL,
	  `passwordResetCode` varchar(32) character set latin1 default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_users_groups_relation` (
	  `userID` int(10) unsigned NOT NULL default '0',
	  `groupID` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`userID`,`groupID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_users_values` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL,
	  `value` varchar(255) NOT NULL,
	  `userID` int(10) unsigned NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `userID` (`userID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
		
	CREATE TABLE `polarbear_log` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `date` datetime NOT NULL,
	  `user` int(10) unsigned NOT NULL,
	  `type` varchar(255) character set latin1 NOT NULL,
	  `objectType` varchar(255) character set latin1 NOT NULL,
	  `objectID` int(10) unsigned NOT NULL,
	  `objectName` varchar(255) collate utf8_swedish_ci NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `date` (`date`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	

	CREATE TABLE `polarbear_plugins` (
	  `id` int(11) NOT NULL auto_increment,
	  `filename` varchar(255) character set latin1 NOT NULL,
	  `name` varchar(255) character set latin1 NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

	
	CREATE TABLE `polarbear_emaillist_lists` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `name` varchar(255) NOT NULL,
	  `isDeleted` tinyint(4) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_emaillist_emails` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `listID` int(11) NOT NULL,
	  `email` varchar(255) NOT NULL,
	  `dateAdded` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `listID` (`listID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;


	
	";
	$didSomething = false;
	$somethingNeedsToBeDone = false;
	$somethingNeedsToBeDoneStr = "<ul>";
	$arrTables = explode("CREATE TABLE ", $tables);
	$arrTablesFixed = array();
	foreach ($arrTables as $oneTable) {
		$oneTable = trim($oneTable);
		if (trim($oneTable)) {
			// ta reda på tabellens namn
			// `polarbear_article_tags` (
			#preg_match(string pattern, string subject [, array &matches [, int flags [, int offset]]])
			preg_match("/('|`)([a-zA-Z\-_]*)('|`)/", $oneTable, $matches);
			$tableName = $matches[2];
			$tableName = str_replace("polarbear_", POLARBEAR_DB_PREFIX . "_", $tableName);
	
			//check if table exists
			$sql = "show tables like '$tableName'";
			$rs = mysql_query($sql) or die(mysql_error());
			if (mysql_num_rows($rs) == 0) {
			
				if ($action == "perform") {
					#echo "<br><strong>$tableName did not exist</strong>, so I will now create it.";
					$oneTable = str_replace("polarbear_", POLARBEAR_DB_PREFIX . "_", $oneTable);
					$sql = "CREATE TABLE $oneTable";
					mysql_query($sql) or die(mysql_error()."<br>Query was: <pre>$sql</pre>");
					$didSomething = true;
				} else {
					$somethingNeedsToBeDoneStr .= "<li>Table <strong>$tableName</strong></li>";
					$somethingNeedsToBeDone = true;
				}

			} else {
				#echo "<br><br>$tableName did exist. Checking if all fields also exist.";
				// ta bort första och sista raden
				$arrOneTable = explode("\n", $oneTable);
				$arrOneTable = array_slice($arrOneTable, 1, sizeof($arrOneTable)-2);
				foreach ($arrOneTable as $oneCol) {
					$oneCol = trim($oneCol);
					$oneCol = rtrim($oneCol, ",");
					// fields börjar med ' eller `
					if ((strpos($oneCol, "`") == 0) || (strpos($oneCol, "`") == 0)) {
						// ta fram fältnamnet
						preg_match("/('|`)([a-zA-Z0-9_|-]*)('|`)/", $oneCol, $matches);
						$fieldName = $matches[2];
						#$sqlFields = "SHOW COLUMNS IN $tableName WHERE Field = '$fieldName'";
						$sqlFields = "SHOW COLUMNS IN $tableName";
						$rsFields = mysql_query($sqlFields) or die(mysql_error()."<br>sqlFields:<br>$sqlFields");
						// see if $fieldName did exist in the table
						$foundField = false;
						while ($oneFieldRow = mysql_fetch_assoc($rsFields)) {
							if ($oneFieldRow["Field"] == $fieldName) {
								$foundField = true;
							}
						}
						if ($foundField == false) {
							if ($action == "perform") {
								#echo "<br><strong>Field $fieldName does not exist</strong>, so I will now create it.";
								$sqlAddField = "ALTER table $tableName ADD column $oneCol";
								#echo $sqlAddField;
								$rsAddField = mysql_query($sqlAddField) or die(mysql_error());
								$didSomething = true;
							} else {
								$somethingNeedsToBeDoneStr .= "<li>Field <strong>$fieldName</strong> (in table $tableName)</li>";
								$somethingNeedsToBeDone = true;
							}
						}

					}
				}
				
			}
			
		}
	}
	
	if ($action == "peform") {
		return $didSomething;
	} else {
		$somethingNeedsToBeDoneStr .= "</ul>";
		$whatToBeDone = $somethingNeedsToBeDoneStr;
		return $somethingNeedsToBeDone;
	}

} // end function
		
/*

Finns inte tabell
	Skapa tabell
Finns tabell
	Saknas någon kolumn?
		Lägg till

*/



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>PolarBear CMS Setup/Install</title>
		<link rel="stylesheet" type="text/css" href="./includes/css/styles.css" />
	</head>
	<body>
	
		<p><img src="images/polarbear/polarbear-logo.gif" alt="PolarBear CMS logotype" /></p>
		
		<h1>PolarBear CMS Setup/Install</h1>
	
		<p class="xinfomsg">Please note: When you are done setting upp PolarBear CMS <strong>you must delete this file</strong>.</p>


		<?php
			// check database connection and stuff like that
			$polarbear_db = new ezSQL_mysql();
			$polarbear_db->show_errors=true; // @todo: should be false, right? or set through config
			if (!$polarbear_db->connect(POLARBEAR_DB_USER, POLARBEAR_DB_PASSWORD, POLARBEAR_DB_SERVER)) {
				?>
				<h2>Check your config</h2>
				<p>I could not connect to the database.</p>
				<p>Please make sure that servername, username, password and database are correct.</p>
				</body></html>
				<?php
				exit;
			} else {
				if (!$polarbear_db->select(POLARBEAR_DB_DATABASE)) {
					?>
					<h2>Check your config</h2>
					<p>I could not select the database "<?php echo POLARBEAR_DB_DATABASE ?>".</p>
					</body></html>
					<?php
					exit;
				}
			}
		?>
		
		<h2>Create/Update database</h2>
		<?php
		if (isset($_POST["action"]) && $_POST["action"] == "databasePerformUpdate") {
			pb_createAndUpdateTables("perform");
			echo "<p class='okmsg'>Updated database</p>";
		}
		
		if (pb_createAndUpdateTables("check", $returnWhat)) {
			echo "
				<p>The following tables or fields needs to be updated:</p>
				$returnWhat
				<form method='post' action='install.php'>
					<input type='submit' value='Perform update' />
					<input type='hidden' name='action' value='databasePerformUpdate' />
				</form>
			";
		} else {
			echo "<p>The database seems to be up to date.</p>";
		}
		?>

		<h2>Create administrator</h2>

		<?php		
		if ($_POST["action"]== "addAdmin") {
			require_once("./polarbear-boot.php");
			// make sure admin-group exists
			$sql = "SELECT count(id) FROM " . POLARBEAR_DB_PREFIX . "_usergroups WHERE name = 'Administrators'";
			if ($polarbear_db->get_var($sql) == 0) {
				$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_usergroups SET name = 'Administrators', id = 1";
				$polarbear_db->query($sql);
			}

			$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_usergroups WHERE name = 'Administrators'";
			$adminGroupID = $polarbear_db->get_var($sql);

			$firstname = trim($_POST["firstname"]);
			$lastname = trim($_POST["lastname"]);
			$email = trim($_POST["email"]);
			$password = trim($_POST["password"]);
			
			if ($firstname && $lastname && $email && $password) {

				$newUser = new PolarBear_User();
				$newUser->firstname = $firstname;
				$newUser->lastname = $lastname;
				$newUser->email = $email;
				$newUser->save();
				$newUser->changePassword($password);
				$newUser->addToGroup($adminGroupID);

				echo "<p class='okmsg'>Created administrator \"$firstname $lastname\"</p>";

			} else {
				
				echo "<p class='errmsg'>Please fill in all fields</p>";

			}
			

			
		}
		?>
		
		<form method="post" action="install.php">

			<p>
				<label for="firstname">First name</label>
				<br />
				<input type="text" name="firstname" id="firstname" />
			</p>
			
			<p>
				<label for="lastname">Last name</label>
				<br />
				<input type="text" name="lastname" id="lastname" />
			</p>
			
			<p>
				<label for="email">Email</label>
				<br />
				<input type="text" name="email" id="email" />
			</p>
			
			<p>
				<label for="password">Password</label>
				<br />
				<input type="text" name="password" id="password" />
			</p>
			
			<p>
				<input type="submit" value="Create" />
				<input type="hidden" name="action" value="addAdmin" />
			</p>
			
		</form>
		

		<!--
		<h2>Attach- and cachepaths</h2>
		<dl>

			<dt>$_SERVER["DOCUMENT_ROOT"]</dt>
			<dd><?php echo $_SERVER["DOCUMENT_ROOT"] ?></dd>

			<dt>POLARBEAR_WEBPATH</dt>
			<dd><?php echo POLARBEAR_WEBPATH ?></dd>

			<dt>POLARBEAR_DOMAIN</dt>
			<dd><?php echo POLARBEAR_DOMAIN ?></dd>
			
			<dt>POLARBEAR_DOC_ROOT</dt>
			<dd><?php echo POLARBEAR_DOC_ROOT ?></dd>

			<dt>POLARBEAR_ROOT</dt>
			<dd><?php echo POLARBEAR_ROOT ?></dd>
			
			<dt>POLARBEAR_STORAGEPATH</dt>
			<dd>
				<?php echo POLARBEAR_STORAGEPATH ?>
				<?php
				// must be a writable dir
				if (is_dir(POLARBEAR_STORAGEPATH) && is_writeable(POLARBEAR_STORAGEPATH)) {
					echo "<br />Ok: is a writable directory.";
					
					// try to creae attach, cache and dwoo-dirs
					// update: don't do this. problems with safe mode
					#mkdir(POLARBEAR_STORAGEPATH . "files");
					#mkdir(POLARBEAR_STORAGEPATH . "cache");
					#mkdir(POLARBEAR_STORAGEPATH . "dwoo");
					
				} else {
					echo "<br /><strong>Error: Is not a writable directory.</strong>";
				}
				?>
			</dd>

			<dt>POLARBEAR_ATTACHPATH</dt>
			<dd>
				<?php echo POLARBEAR_ATTACHPATH ?>
				<?php
				// must be a writable dir
				if (is_dir(POLARBEAR_ATTACHPATH) && is_writeable(POLARBEAR_STORAGEPATH)) {
					echo "<br />Ok: is a writable directory.";
				} else {
					echo "<br /><strong>Error: Is not a writable directory.</strong>";
				}
				?>
			</dd>

			<dt>POLARBEAR_CACHEPATH</dt>
			<dd>
				<?php echo POLARBEAR_CACHEPATH ?>
				<?php
				// must be a writable dir
				if (is_dir(POLARBEAR_CACHEPATH) && is_writeable(POLARBEAR_CACHEPATH)) {
					echo "<br />Ok: is a writable directory.";
				} else {
					echo "<br /><strong>Error: Is not a writable directory.</strong>";
				}
				?>
			</dd>
			
			
			<dt>DWOO cache dir</dt>
			<dd>
				<?php
				$dwoo_cachedir = POLARBEAR_STORAGEPATH . "/dwoo/";
				echo $dwoo_cachedir;
				// must be a writable dir
				if (is_dir($dwoo_cachedir) && is_writeable($dwoo_cachedir)) {
					echo "<br />Ok: is a writable directory.";
				} else {
					echo "<br /><strong>Error: Is not a writable directory.</strong>";
				}
				?>
			</dd>		
			
		</dl>
		
		-->
	
	</body>
</html>