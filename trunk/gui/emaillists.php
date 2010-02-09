<?php
/**
 * hanterar artiklar
 */
$page_class = "polarbear-page-settings";
require realpath(dirname(__FILE__)."/../") . "/polarbear-boot.php";
polarbear_require_admin();

$skip_layout = true;
$emaillistAction = $_GET["action"];

class pb_emaillists {
	
	/**
	 * hämtar lista på existerande listor
	 * @return array (tom array om inga listor finns)
	 */
	function getLists() {
		global $polarbear_db;
		$sql = "SELECT id, name FROM " . POLARBEAR_DB_PREFIX . "_emaillist_lists WHERE isDeleted=0 ORDER by name ASC";
		$arr = array();

		if ($r = $polarbear_db->get_results($sql)) {
			foreach ($r as $row) {
				// get num of susbcribers in this list
				$sql = "SELECT count(email) FROM " . POLARBEAR_DB_PREFIX . "_emaillist_emails WHERE listID = '{$row->id}'";
				$antal = $polarbear_db->get_var($sql);
				$row->count = $antal;
				$arr[] = $row;
			}
		}
		return $arr;
		
	}
	
}


/**
 * download as CSV
 */
if ($emaillistAction == "emaillist-downloadCSV") {

	$oneList = new pb_emaillist($_GET["emailListID"]);	

	// It will be called downloaded.pdf
	header('Content-Disposition: attachment; filename="'.$oneList->name.' (' . strftime("%e-%b-%Y %H:%M", time()) . ').csv"');
	
	$out = "";
	
	$out .= "Email\tDate added";

	$emails = $oneList->getEmails();
	foreach ($emails as $oneEmail) {
		$date = $oneEmail->dateAdded;
		$out .= "\n";
		$out .= $oneEmail->email . "\t$date";
	}

	$out = utf8_decode($out);
	echo $out;

	exit;

}

/**
 * save a new or edited list
 */
if ($_POST["action"] == "emaillistSave") {

	$oneList = new pb_emaillist($_POST["emailListID"]);
	$oneList->name = $_POST["emaillistName"];
	$oneList->isDeleted = $_POST["isDeleted"];
	$oneList->save();
	$okmsg = urlencode("Saved");
	header("Location: " . polarbear_treepage("gui/emaillists.php?okmsg=$okmsg"));
	exit;
}

/**
 * remove an email emaillists-removeEmail
 */
if ($emaillistAction == "emaillists-removeEmail") {
	/*
	Array
	(
	    [action] => emaillists-removeEmail
	    [email] => par.thernstrom@gmail.com
	    [emailListID] => 1
	)
	*/
	$emailListID = (int) $_GET["emailListID"];
	$oneList = new pb_emaillist($emailListID);
	$oneList->removeEmail($_GET["email"]);

	// ok, move on and show the list again
	$okmsg = urlencode("Email address removed");
	$url = polarbear_treepage("gui/emaillists.php?action=emaillists-viewList&emailListID=$emailListID&okmsg=$okmsg");
	header("Location: $url");
	exit;
}

if ($_POST["action"] == "emaillists-addAddresses") {

	$addresses = $_POST["addresses"];
	$emailListID = $_POST["emailListID"];
	$oneList = new pb_emaillist($emailListID);
	
	$addresses = preg_replace("/[,;:\n\r\t ]+/", " ", $addresses);
	$arrAddresses = explode(" ", $addresses);

	$numAdded = 0;
	foreach ($arrAddresses as $oneEmail) {
		if ($oneList->addEmail($oneEmail)) {
			$numAdded++;
		}
	}

	$okmsg = urlencode("Added $numAdded addresses");
	$url = polarbear_treepage("gui/emaillists.php?action=emaillists-viewList&emailListID=$emailListID&okmsg=$okmsg");
	header("Location: $url");
	exit;
}

pb_must_come_through_tree();

$okmsg = $_GET["okmsg"];
#echo "<br>action: $emaillistAction";
#echo "<br>okmsg: $okmsg";
?>

<?php
polarbear_infomsg($_GET["okmsg"], $errmsg);
?>

<style type="text/css">
	ul.emaillists-existing-lists li {
		margin-top: .5em;
	}
	
	table.pb-emaillists {
		margin-top: 2em;
	}
	table.pb-emaillists th,
	table.pb-emaillists td {
		text-align: left;
		padding: 5px 10px 5px 5px;
	}
	table.pb-emaillists tr:nth-child(even) {
		background-color: #eee;
	}
	
	table.pb-emaillists tr img {
		visibility: hidden;
	}
	table.pb-emaillists tr:hover img {
		visibility: visible;
	}
	
</style>

<script type="text/javascript">

	$(".pb-emaillists-email-remove").live("click", function() {
		var $t = $(this);
		jConfirm("Remove email?", null, function(r) {
			if (r) {
				document.location = $t.attr("href");
			}
		});
		return false;
	});
	
	$("#pb-emaillists-add-addresses-link").live("click", function() {
		$("#pb-emaillists-add-addresses").slideDown("slow");
	});

</script>

<div class="polarbear-page-emaillists polarbear-content-main-inner">

	<?
	$emaillist = new pb_emaillists;
	$existingEmaillists = $emaillist->getLists();
	
	if ($emaillistAction == "emaillists-editList") {

		// lägg till eller redigera formulär
		$emailListID = (int) $_GET["emailListID"];
		$name = "";
		$isDeleted = 0;
		if ($emailListID) {
			$oneList = new pb_emaillist($emailListID);
			#pb_d($oneList);
			$name = $oneList->name;
			$isDeleted = $oneList->isDeleted;
		}
		?>
		<h1>Edit email list</h1>
		<form method="post" action="gui/emaillists.php">
			<p>
				<label for="emaillistName">Name</label>
				<input id="emaillistName" name="emaillistName" type="text" value="<?php echo htmlspecialchars($name, ENT_COMPAT, "UTF-8"); ?>" />
			</p>
			<p>
				<input <?php echo ($isDeleted) ? " checked='checked' " : "" ?> type="checkbox" name="isDeleted" value="1" /> Delete list
			</p>
			<p>
				<input type="hidden" name="emailListID" value="<?php echo $emailListID ?>" />
				<input type="hidden" name="action" value="emaillistSave" />
				<input type="submit" value="Save" />
				or
				<a href="<?php echo polarbear_treepage("gui/emaillists.php") ?>">cancel</a>
			</p>
		</form>
		<?
	
	} else if ($emaillistAction == "emaillists-viewList") {
	
		$emailListID = (int) $_GET["emailListID"];
		$oneList = new pb_emaillist($emailListID);
		$emails = $oneList->getEmails();
		
		?>
		<p><a href="gui/emaillists.php">« Back to all lists</a></p>
		<h1><?php echo $oneList->name ?></h1>

		<p><?php echo $oneList->count ?> emails in this list.</p>
		<ul>
			<?php $editLink = polarbear_treepage("gui/emaillists.php?action=emaillists-editList&emailListID={$emailListID}"); ?>
			<li><a href="<?php echo $editLink ?>">Edit</a></li>
			<li><a href="gui/emaillists.php?action=emaillist-downloadCSV&emailListID=<?php echo $emailListID ?>">Download as CSV</a></li>
			<li><a id="pb-emaillists-add-addresses-link" href="#">Add email addresses</a></li>
		</ul>
		
		<div id="pb-emaillists-add-addresses" style="display: none;">
			<form method="post" action="gui/emaillists.php">
				<p>Enter or paste the addresses in the textbox below. Separate the adresses with spaces, colons, newlines or tabs.</p>
				<textarea name="addresses" cols="50" rows="7"></textarea>
				<p><input type="submit" value="Add" /></p>
				<input type="hidden" name="action" value="emaillists-addAddresses" />
				<input type="hidden" name="emailListID" value="<?php echo $emailListID ?>" />
			</form>
		</div>

		<?
		
		if (empty($emails)) {
			
		} else {
			?>
			
			<?
			echo "<table class='pb-emaillists'>";
			echo "<tr><th>Email</th><th>Date added</th><th></th></tr>";
			foreach ($emails as $oneEmail) {
				$dateAdded = strftime("%a %e %b, %H:%M", $oneEmail->dateAddedUnix);
				$emailEscaped = urlencode($oneEmail->email);
				$removeLink = "gui/emaillists.php?action=emaillists-removeEmail&email={$emailEscaped}&emailListID=$emailListID";
				echo "<tr>";
				echo "<td>{$oneEmail->email}</td>";
				echo "<td>$dateAdded (".polarbear_time2str($oneEmail->dateAdded).")</td>";
				echo "<td><a class='pb-emaillists-email-remove' href='$removeLink'><img src='./images/silkicons/delete.png' alt='Remove' /></a></td>";
				echo "</tr>";
			}
			echo "</table>";
		}

	
	} else {
		?>
		<h1>Email lists</h1>
		<p><a href="<?php echo polarbear_treepage("gui/emaillists.php?action=emaillists-editList") ?>">+ Add</a></p>
		<?
		if (empty($existingEmaillists)) {
			?><p>There are no email lists yet.</p><?
		} else {
			echo "<ul class='emaillists-existing-lists'>";
			foreach ($existingEmaillists as $oneList) {
				$viewLink = polarbear_treepage("gui/emaillists.php?action=emaillists-viewList&emailListID={$oneList->id}");
				echo "<li>";
				echo "<a href='$viewLink'>{$oneList->name}</a> ({$oneList->count})";
				#echo "<br><a href='$viewLink'>View</a> | <a href='$editLink'>edit</a>";
				echo "</li>";
			}
			echo "</ul>";
		}
	}
	?>
	
	
</div>

<?
/*
<a href="<?php echo polarbear_treepage("gui/fields.php") ?>">Cancel</a>
header("Location: " . polarbear_treepage("gui/fields.php?okmsg=$okmsg"));
*/
?>