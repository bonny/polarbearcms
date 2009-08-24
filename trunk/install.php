<?php
require_once("./polarbear-boot.php");

// phpinfo();
// SELECT VERSION()

// Create tables and columns that don't exist
function pb_createAndUpdateTables() {
	$tables = "
	CREATE TABLE `polarbear_article_tag_relation` (
	  `articleID` int(10) unsigned NOT NULL default '0',
	  `tagID` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`articleID`,`tagID`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_article_tags` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) collate utf8_swedish_ci default NULL,
	  `parentID` int(10) unsigned default NULL,
	  `isDeleted` tinyint(4) default NULL,
	  `prio` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_collections` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `repeatable` tinyint(4) NOT NULL default '0',
	  `deleted` tinyint(4) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_connectors` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `deleted` tinyint(4) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_link_connectors_collections` (
	  `fieldConnectorID` int(10) unsigned NOT NULL default '0',
	  `fieldCollectionID` int(10) unsigned NOT NULL default '0',
	  `prio` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`fieldConnectorID`,`fieldCollectionID`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_fields_values` (
	  `fieldID` int(10) unsigned NOT NULL default '0',
	  `articleID` int(10) unsigned NOT NULL default '0',
	  `value` text NOT NULL,
	  `numInSet` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`fieldID`,`articleID`,`numInSet`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_files_tags` (
	  `fileID` int(10) unsigned NOT NULL auto_increment,
	  `tagName` varchar(255) collate utf8_swedish_ci NOT NULL default '',
	  PRIMARY KEY  (`fileID`,`tagName`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_settings` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `settings` text character set latin1 NOT NULL,
	  `date` datetime NOT NULL default '0000-00-00 00:00:00',
	  PRIMARY KEY  (`id`),
	  KEY `date` (`date`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_storage` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `thekey` varchar(255) NOT NULL default '',
	  `thevalue` text NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_usergroups` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) character set latin1 default NULL,
	  `isDeleted` tinyint(4) default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
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
	  `passwordResetCode` varchar(32) character set latin1 default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_users_groups_relation` (
	  `userID` int(10) unsigned NOT NULL default '0',
	  `groupID` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`userID`,`groupID`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
	
	CREATE TABLE `polarbear_users_values` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL,
	  `value` varchar(255) NOT NULL,
	  `userID` int(10) unsigned NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `userID` (`userID`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	
		
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
	";
	$didSomething = false;
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
				echo "<br><strong>$tableName did not exist</strong>, so I will now create it.";
				$oneTable = str_replace("polarbear_", POLARBEAR_DB_PREFIX . "_", $oneTable);
				$sql = "CREATE TABLE $oneTable";
				mysql_query($sql) or die(mysql_error());
				$didSomething = true;
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
							echo "<br><strong>Field $fieldName does not exist</strong>, so I will now create it.";
							$sqlAddField = "ALTER table $tableName ADD column $oneCol";
							echo $sqlAddField;
							$rsAddField = mysql_query($sqlAddField) or die(mysql_error());
							$didSomething = true;
						}

					}
				}
				
			}
			
		}
	}

	return $didSomething;

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
	
		<h1>PolarBear CMS Setup/Install</h1>
		
		<p style="infomsg"><strong>Please note: When you are done setting upp PolarBear CMS you must delete this file.</strong></p>

		<h2>Create/Update database</h2>
		<?
		if (pb_createAndUpdateTables()) {
			echo "<p>Tables or fields where added to the database.</p>";
		} else {
			echo "<p>The database was not touched.</p>";
		}
		?>

		<h2>Create administrator</h2>

		<?php		
		if ($_POST["action"]== "addAdmin") {

			// make sure admin-group exists
			$sql = "SELECT count(id) FROM " . POLARBEAR_DB_PREFIX . "_usergroups WHERE name = 'Administrators'";
			if ($polarbear_db->get_var($sql) == 0) {
				$sql = "INSERT INTO " . POLARBEAR_DB_PREFIX . "_usergroups SET name = 'Administrators', id = 1";
				$polarbear_db->query($sql);
			}

			$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_usergroups WHERE name = 'Administrators'";
			$adminGroupID = $polarbear_db->get_var($sql);

			$firstname = $_POST["firstname"];
			$lastname = $_POST["lastname"];
			$email = $_POST["email"];
			$password = $_POST["password"];
			
			$newUser = new PolarBear_User();
			$newUser->firstname = $firstname;
			$newUser->lastname = $lastname;
			$newUser->email = $email;
			$newUser->save();
			$newUser->changePassword($password);
			$newUser->addToGroup($adminGroupID);
			
			echo "<p class='okmsg'>Administrator created</p>";
			
		}
		?>
		
		<form method="post" action="install.php">

			<p>
				<label for="firstname">First name</label>
				<input type="text" name="firstname" id="firstname" />
			</p>
			
			<p>
				<label for="lastname">Last name</label>
				<input type="text" name="lastname" id="lastname" />
			</p>
			
			<p>
				<label for="email">Email</label>
				<input type="text" name="email" id="email" />
			</p>
			
			<p>
				<label for="password">Password</label>
				<input type="text" name="password" id="password" />
			</p>
			
			<p>
				<input type="submit" value="Create" />
				<input type="hidden" name="action" value="addAdmin" />
			</p>
			
		</form>
		

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
					mkdir(POLARBEAR_STORAGEPATH . "files");
					mkdir(POLARBEAR_STORAGEPATH . "cache");
					mkdir(POLARBEAR_STORAGEPATH . "dwoo");
					
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
	
	</body>
</html>