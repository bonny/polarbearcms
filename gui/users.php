<?php
/**
 * Hanterar användare
 * Todo: få layouten att fungera igen
 */


require_once("../polarbear-boot.php");

$page_class = "polarbear-page-users";

/**
 * hämtar en select + textruta för att välja values
 */
if ($_POST["action"] == "getUserValuesEditBox") {
	?>
	<div class="col full">
		<label>
			<select style="float:left" class="user_edit_values_select"><option>Choose label for this value</option><option>New label...</option>
				<?php
				// add all existing unique labels
				$arrLabels = pb_users_values_all_unique_labels();
				foreach ($arrLabels as $oneLabel) {
					?>
					<option value="<?php echo htmlspecialchars($oneLabel, ENT_COMPAT, "UTF-8") ?>"><?php echo htmlspecialchars($oneLabel, ENT_COMPAT, "UTF-8") ?></option>
					<?php
				}
				?>
			</select>
			<a class="user_edit_values_remove" style="float:left" title="Remove this custom value" class="" href=""><span class="ui-icon ui-icon-trash"></span></a>
		</label>
		<input type="text" class="user-edit-custom-value-thevalue text ui-widget-content ui-corner-all" value="" />
	</div>
	<?php			
	exit;
}


/**
 * listar alla användare i en grupp
 */
if ($_POST["action"] == "users_getUsersInGroup")
{
    $groupID = $_POST["groupID"];
	// hitta groupID-all eller groupID-66 eller liknande
	$matches = array();	
    preg_match("/groupID-([\w]*)/", $groupID, $matches);
	$groupID = $matches[1];
	$orderBy = $_POST["orderBy"];
	if (!$orderBy) { $orderBy = "firstName"; }
	if ($groupID == "admins") { $groupID = 1; }
    if ($groupID == "all") {
        // hämta alla
        $sql = "SELECT id as userID, firstName, lastName, email FROM " . POLARBEAR_DB_PREFIX . "_users where isDeleted = 0";
	    $sql .= " order by $orderBy";
    } elseif ($groupID == "latest") {
		// hämta senast tillagda
		$sql = "SELECT id as userID, firstName, lastName, email FROM " . POLARBEAR_DB_PREFIX . "_users where isDeleted = 0 ORDER BY dateCreated DESC LIMIT 5";
    } elseif ($groupID == "latestChanged") {
		// hämta senast ändrade
		$sql = "SELECT id as userID, firstName, lastName, email FROM " . POLARBEAR_DB_PREFIX . "_users where isDeleted = 0 ORDER BY dateChanged DESC LIMIT 5";
    } else  {
        // hämta specifik grupp
        $sql = "SELECT userID, firstName, lastName, email FROM " . POLARBEAR_DB_PREFIX . "_users_groups_relation INNER JOIN " . POLARBEAR_DB_PREFIX . "_users as u ON u.id = userID WHERE groupID = $groupID and u.isDeleted = 0";
	    $sql .= " order by $orderBy";
    }
    if ($users = $polarbear_db->get_results($sql))
    {
        $arr = array ();
        echo "<ul id='users-userlist'>";
        foreach ($users as $user)
        {
			if ($user->firstName || $user->lastName) {
				$nameToShow = $user->firstName . " " . $user->lastName;
			} else{
				$nameToShow = $user->email;
			}
			$strEmail = "";
			if ($user->email) {
				$strEmail = " <span class='email'>{$user->email}</span>";
			}
            echo "<li><a href='#' class='userID-$user->userID'>$nameToShow</a>$strEmail</li>";
        }
        echo "</ul>";
    } else
    {
        echo "<p>This group has no users.</p>";
    }
    exit ;
}


/**
 * skriver ut div med all info för att redigera en användare
 */
if ($_POST["action"] == "users_user_edit") {
	$userID = $_POST["userID"];
	$user = new PolarBear_User($userID);
	$arrGroups = $user->getGroups();
	?>

	<div class="fields">
	
		<div class="group">
			<label>User information</label>
			<div class="col left">
				<label for="user_edit_firstname">First name</label>
				<input id="user_edit_firstname" type="text" class="text ui-widget-content ui-corner-all" value="<?php echo $user->firstname ?>" />
			</div>
			<div class="col right">
				<label for="user_edit_lastname">Last name</label>
				<input id="user_edit_lastname" type="text" class="text ui-widget-content ui-corner-all" value="<?php echo $user->lastname ?>" />
			</div>
			
			<div class="col full">
				<label for="user_edit_email">Email</label>
				<input id="user_edit_email" type="text" class="text ui-widget-content ui-corner-all" value="<?php echo $user->email ?>" />
			</div>
		</div>
		
		<div id="user_edit_values_container">
			<?php
			$arrLabels = pb_users_values_all_unique_labels();
			$arrCustomValues = $user->customValues();
			if (!empty($arrCustomValues)) {
				foreach ($arrCustomValues as $one) {
					?>
					<div class="col full">
						<label>
							<select style="float: left;" class="user_edit_values_select">
								<!-- <option value="">Choose label for this value</option>
								<option value="_new">New label...</option> -->
								<?php
								foreach ($arrLabels as $oneLabel) {
									?><option <?php print ($oneLabel==$one->name)?" selected='selected' ":"" ?> value="<?php echo $oneLabel ?>"><?php echo $oneLabel ?></option><?php
								}
								?>
							</select>
						</label>
						<a class="user_edit_values_remove" style="float:left" title="Remove this custom value" class="" href=""><span class="ui-icon ui-icon-trash"></span></a>
						<input type="text" class="user-edit-custom-value-thevalue text ui-widget-content ui-corner-all" value="<?php echo $one->value ?>" />
					</div>
					<?
				}
			}
		?>
		</div>

		<div class="group">
			<a id="user_edit_value_add" href="#">Add custom label and value</a>
		</div>
		
		<div class="group">
			<label>Groups</label>
			<?php
			if (empty($arrGroups)) {
				?><!-- <span id="user_edit_no_groups">Not member of any groups.</span> --><?php
			}
			?>
			<div id="users-edit-add-to-group-select"><?php
				foreach ($arrGroups as $oneGroup) {
					?>
					<div><input type="hidden" value="<?php echo $oneGroup->groupID ?>" name="selectedGroupID" /><?php echo $oneGroup->name ?> <a href="#">remove</a></div>
					<?php
				}
			?></div>
			<div class="users-edit-groups"></div>
			<div class="users-edit-add-to-group"><a href="#">Add to group</a></div>
		</div>
	
		<div class="group">
			<label>Password</label>
			<p id="users-edit-change-password"><a href="#">Change password</a></p>
			<div id="users-edit-change-password-details" style="display: none;">
				<div class="col full">
					<label for="user-edit-change-password-password">Password</label>
					<input id="user-edit-change-password-password" type="password" class="text ui-widget-content ui-corner-all" />
				</div>
				<div class="col full">
					<label for="user-edit-change-password-password-repeat">Password again</label>
					<input id="user-edit-change-password-password-repeat" type="password" class="text ui-widget-content ui-corner-all" />
				</div>
			</div>
			<div class="clearer"></div>	
		</div>

		<div class="actions ui-helper-clearfix">
			<a class="users-user-edit-save fg-button ui-state-default fg-button-icon-left ui-corner-all" href="#" title="Save"><span class="ui-icon ui-icon-disk"></span>Save</a>
			<span class="polarbear-afterbuttons">
				<span class="polarbear-afterbuttons-text">or</span>
				<a id="users-user-edit-cancel" href="#">Cancel editing</a>
			</span>			
		</div>

	</div>

	<?php
	exit;
}

/**
 * Skriver ut info om en användare
 */
if ($_POST["action"] == "users_viewOneUser") {
	$userID = $_POST["userID"];
	$matches = array();
    preg_match("/userID-([\w]*)/", $userID, $matches);
	if (sizeof($matches)>1) {
		$userID = $matches[1];
	} else {
		$userID = $userID; // eh.. yeah!
	}

	$user = new PolarBear_User($userID);
	?>
	<div class="users-view-one-user">
		<h3>
			<?php echo $user->firstname ?>
			<?php echo $user->lastname ?>
		</h3>
		<p>
			<?php echo $user->email ?>
		</p>
		<?php
		
		$arrGroups = $user->getGroups();
		if (!empty($arrGroups)) {
			echo "<h4>Groups</h4><p>";
			$strGroups = "";
			foreach ($arrGroups as $oneGroup) {
				$strGroups .= "$oneGroup->name, ";
			}
			$strGroups = preg_replace("/, $/", "", $strGroups);
			echo $strGroups;
			echo "</p>";
		}

		// extra values for this user
		$arrCustomValues = $user->customValues();
		if (!empty($arrCustomValues)) {
			foreach ($arrCustomValues as $one) {
				echo "<h4>$one->name</h4>";
				echo "<p>$one->value</p>";
			}
		}

		// last login
		echo "<h4>Last login</h4>";
		if ($user->dateLastLogin) {
			echo $user->dateLastLogin . " (". polarbear_time2str($user->dateLastLogin) . ")";
		} else {
			echo "Never";
		}
		?>
		
	</div>
	<?php
	
	exit;
}


/**
 * spara användare (ny eller befintlig)
 */
if ($_POST["action"] == "users_user_save") {
	
	$userID = $_POST["userID"];
	
	$groupIDs = trim($_POST["groups"]);
	$arrGroupIDs = explode(" ", $groupIDs);
	
	$u = new PolarBear_User($userID);
	$u->firstname = $_POST["firstname"];
	$u->lastname = $_POST["lastname"];
	$u->email = $_POST["email"];
	$u->save();
	
	// om nytt lösenord är angivet ska vi spara det
	$newPassword = $_POST["newPassword"];
	$newPasswordRepeat = $_POST["newPasswordRepeat"];
	if (!empty($newPassword)) {
		$u->changePassword($newPassword);
	}
	
	// ta bort användaren från alla grupper och lägg sedan till användaren i ev. valda grupper
	$u->removeFromAllGroups();
	foreach ($arrGroupIDs as $groupID) {
		$u->addToGroup($groupID);
	}

	// add custom labels and values
	$u->clearCustomValues();
	if (!empty($_POST["customLabels"])) {
		for ($i=0; $i<sizeof($_POST["customLabels"]); $i++) {
			$u->addCustomValue($_POST["customLabels"][$i], $_POST["customValues"][$i]);
		}
	}
	echo "ok";
	exit;
}


if ($_POST["action"] == "users_getAddGroupToUser") {
	$userID = (int) $_POST["userID"];
	$u = new PolarBear_User($userID);
	$arrGroups = polarbear_getUserGroups();
	echo "<select>";
	echo "<option value=''>Choose group...</option>";
	foreach ($arrGroups as $oneGroup) {
		echo "<option value='$oneGroup->id'>$oneGroup->name</option>";
	}
	echo "</select>";
	echo " <a href='#'>remove</a>";
	exit;
}



if ($_POST["action"] == "users_deleteUser") {
	// @todo: delete should be in user class instead
	$userID = (int) $_POST["userID"];
	$polarbear_db->query("UPDATE " . POLARBEAR_DB_PREFIX . "_users SET isDeleted = 1 WHERE id = $userID");

	$args = array(
		"userID" => $userID
	);
	pb_event_fire("pb_user_deleted", $args);

	exit;
}


if ($_POST["action"] == "users_addUserToGroup") {
	$userID = $_POST["userID"];
	$groupID = $_POST["groupID"];
	$polarbear_db->query("INSERT INTO " . POLARBEAR_DB_PREFIX . "_users_groups_relation SET userID = '$userID', groupID = '$groupID'");
	exit;
}


/**
 * markerar en grupp som rader. relationerna består
 */
if ($_POST["action"] == "users_group_delete") {
    $groupID = $_POST["groupID"];
    $polarbear_db->query("UPDATE " . POLARBEAR_DB_PREFIX . "_usergroups SET isDeleted = 1 WHERE id = '$groupID'");
    exit ;
}

if ($_POST["action"] == "users_getUserGroupList") {
    admin_get_user_group_list();
    exit ;
}

if ($_POST["action"] == "users_group_rename")
{
    $newGroupName = $polarbear_db->escape($_POST["newGroupName"]);
    $newGroupName = preg_replace("/[\"'<>]/", "", $newGroupName);
    $groupID = $_POST["groupID"];
    $polarbear_db->query("UPDATE " . POLARBEAR_DB_PREFIX . "_usergroups SET name = '$newGroupName' WHERE id = '$groupID'");
    admin_get_user_group_list();
    exit ;
}

if ($_POST["action"] == "users_createNewGroup") {
    $groupName = $_POST["groupName"];
    // tillåt inte citattecken och sånt
    $groupName = preg_replace("/[\"'<>]/", "", $groupName);
    $groupName = $polarbear_db->escape($groupName);
    $polarbear_db->query("INSERT INTO " . POLARBEAR_DB_PREFIX . "_usergroups SET name = '$groupName'");
    //admin_get_user_group_list();
    $insertID = $polarbear_db->insert_id;
    echo $insertID;
    exit ;
}


#require ("includes/admin-header.php");
?> 
    <!-- en till layout -->
	<div class="ui-layout-west">
		<!-- west = gruppbrowser -->
		<div class="fg-toolbar ui-widget-header ui-corner-all ui-helper-clearfix">
			<a class="button-group-new fg-button ui-state-default fg-button-icon-left ui-corner-all" href="#" title="Create a new group"><span class="ui-icon ui-icon-circle-plus"></span>New</a>
			<a class="button-group-edit fg-button ui-state-default ui-state-disabled fg-button-icon-left ui-corner-all" href="#" title="Edit group"><span class="ui-icon ui-icon-pencil"></span>Edit</a>
			<a class="button-group-delete fg-button ui-state-default ui-state-disabled fg-button-icon-solo ui-corner-all" href="#" title="Delete group"><span class="ui-icon ui-icon-trash"></span>Remove</a>
		</div>

		<div id="users-group-list" class="clearer ui-layout-content">
			<?php
				admin_get_user_group_list();
			?>
		</div>
	</div>

	<div class="ui-layout-center">
		<!-- west = användarbrowser -->
		<div class="fg-toolbar ui-widget-header ui-corner-all ui-helper-clearfix">
			<div style="padding: .3em 0 .3em 0;font-weight:normal;visibility:hidden;" class="users-group-selectsort">
				Sort by
				<select>
					<option value="firstName">First name</option>
					<option value="lastName">Last name</option>
					<option value="email">E-mail</option>
				</select>
				<input type="button" value="Ok" />
			</div>
		</div>
		<div id="users-group-members" class="clearer ui-layout-content"></div>
	</div>

	<div class="ui-layout-east">
		<!-- center = details for selected user -->
		<div class="fg-toolbar ui-widget-header ui-corner-all ui-helper-clearfix">
			<a class="button-user-new fg-button ui-state-default fg-button-icon-left ui-corner-all" href="#" title="Creat a new user"><span class="ui-icon ui-icon-circle-plus"></span>New user</a>
			<a class="button-user-edit fg-button ui-state-default ui-state-disabled fg-button-icon-left ui-corner-all" href="#" title="Edit user"><span class="ui-icon ui-icon-pencil"></span>Edit</a>
			<a class="button-user-delete fg-button ui-state-default ui-state-disabled fg-button-icon-solo ui-corner-all" href="#" title="Delete user"><span class="ui-icon ui-icon-trash"></span>Delete</a>
		</div>
		<div id="users-userdetails" class="ui-layout-content"></div>
	</div>	

	<script type="text/javascript" src="includes/js/users.js.php"></script> 
<?php
#require ("includes/admin-footer.php");


// lägg till en massa namn för att testa
/*$arrNames1 = array("Peter", "Johan", "Johanna", "Simon", "Torkel", "Jan", "Matilda", "Oscar", "Carl", "Ester", "Anton", "Elias", "Malte");
$arrNames2 = array("Svensson", "Nilsson", "Blomqvist", "Englund", "Thomson", "Von Nilsson");
for ($i=0; $i<1000; $i++) {
	$firstname = $arrNames1[rand(0, sizeof($arrNames1)-1)];
	$lastname = $arrNames2[rand(0, sizeof($arrNames2)-1)];
	echo "<br>$firstname $lastname";
	$u = new PolarBear_User();
	$u->firstname = $firstname;
	$u->lastname = $lastname;
	$u->save();
}
*/

?>