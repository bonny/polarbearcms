<?php
header("Content-Type: application/x-javascript");

/**
 * Global javascript functions
 * todo: not really global. mostly tree-related...
 */
$polarbear_cache_allow_client_caching = true;
require_once("../../polarbear-boot.php");
?>
$.metadata.setType("attr", "data");

var tree;
var articleStatus; // status for article beeing/just been edited
// do things ondomready
$(function() {

	var treeOptions = {
		path: "<?php polarbear_webpath() ?>includes/jstree/",
		data: {
			type	: "json",
			async 	: true,
			url   	: "gui/tree.php",
			opts	: {
				url   	: "gui/tree.php"
			}
		},
		xcookies	: {
			prefix	: "articles"				
		},
		ui: {
			dots	: true,
			context	: false
		},
		rules: {
			type_attr 	: "rel",
			metadata	: "data",
			use_inline	: true,
			deletable	: "all",
			renameable	: "",
			creatable	: "all",
			draggable	: "all",
			dragrules	: ["article * article", "article inside root"],
			createat	: "top"
		},
		lang : {
			new_node	: "New article"
		},
		callback: {
			onchange : treeOnChange,
			oncreate : treeOnCreate,
			onrename : treeOnRename,
			onmove   : treeOnMove,
			onload	 : treeOnLoad
		},
		plugins: {
			cookie : { prefix: "articles" }
		}


	}

	// Initialize article tree
	if ($("#tree-articles").length==1) {
		tree = $.tree.create();
		tree.init($("#tree-articles"), treeOptions);
	}

	// button "new article"
	$("#button-article-new").click(function() {
		var a = $(this);
		if (!a.hasClass("ui-state-disabled")) {
			// tree.create("article");
			//tree.create({ type: "article", data: "abc", attributes: { data: "{type: \"article\"}" } } );
			tree.create({
				data: {
					icon: "<?php polarbear_webpath() ?>images/silkicons/page_white_text.png",
					title: "New article"
				}
			});
		}
		return false;
	}).hover(function() {$(this).toggleClass("ui-state-hover");}, function() {$(this).toggleClass("ui-state-hover");});
	
	// button rename
	$("#button-article-rename").click(function() {
		var a = $(this);
		if (!a.hasClass("ui-state-disabled")) {
			tree.rename();
		}
		return false;
	});
	
	$("#button-article-delete").click(function() {
		var a = $(this);
		if (!a.hasClass("ui-state-disabled")) {
			if (confirm("Delete selected article?")) {
				tree.remove();
			}
		}
		return false;
	});

}); // end ondomready

// efter att en nod har döpts om
// körs även när en ny nod skapas, och man ändrar namn på den
function treeOnRename(node,lang,tree) {
	var newName = $(node).text();
	$.post("gui/articles-ajax.php", { action: "articleRename", article: node.id, newName: newName }, function(data) {
		
	});
}


/**
 * tree is loaded
 * todo: try this one more. I believe it's fired several times with multiple branches open
 */
function treeOnLoad(tree) {

	// if tree is loaded and no node is selected, 
	// check treepage and possibly go there instead
	if (tree.selected == undefined) {
		if (treepage == "") {
			treepage = "<?php polarbear_webpath() ?>gui/overview.php";
		}
		var polarbearContentMain = $("#polarbear-content-main");
		polarbearContentMain.attr("scrollTop", 0);
		polarbearContentMain.load(treepage, function(data) { 
			polarbear_article_onload();
		});
		treepage = ""; // we have to clear it so we can go elsewhere in the tree afterwards
	}
}


/**
 * A node (=article) gets focus
 * Load article/content in right pane
 */
function treeOnChange(node,tree) {

	// destroy dialogs, they stop working at the next run otherwise
	$("#polarbear-article-edit-fields-files-dialog").dialog("destroy");
	$("#polarbear-article-edit-fields-files-dialog").remove();
	$("#official-author-window").dialog("close");
	$("#official-author-window").remove();
	$("#article-datePublish-window").dialog("close");
	$("#article-datePublish-window").remove();
	$("#article-dateUnpublish-window").dialog("close");
	$("#article-dateUnpublish-window").remove();

	// remove layout for users
	$("#polarbear-content-main").layout().destroy();

	var nodeId = node.id;

	// disable all buttons
	$("#button-article-new").addClass("ui-state-disabled");
	$("#button-article-rename").addClass("ui-state-disabled");
	$("#button-article-delete").addClass("ui-state-disabled");			

	// if node is false then no node has focused (we have probably clicked on the arrow to close)
	if (node==false || nodeId == undefined || nodeId == "root") {
		// return false;
		treepage = "gui/overview.php";
	}

	var polarbearContentMain = $("#polarbear-content-main");
	polarbearContentMain.attr("scrollTop", 0);

	if (nodeId == "root") {
		$("#button-article-new").removeClass("ui-state-disabled");
		// root = go to article overview
		if (articleStatus == "articleEditCanceled") {
			href = "<?php echo polarbear_webpath() ?>overview.php?from=articleEditCanceled";
		} else {
			href = "<?php echo polarbear_webpath() ?>overview.php";
		}
		articleStatus = "";
	}

	// if treepage is set to something, that's where we're goin in the first place
	if (treepage != "") {
		
		// if treepage contains gui/overview.php make sure first branch is selected (it doesn't get selected when we for example save and article)
/*		if (treepage.indexOf("gui/overview.php") >= 0) {
			if (tree.selected[0].id != "categoryOverview") {
				tree.select_branch("#categoryOverview");
				return false;
			}
		}*/

		polarbearContentMain.load(treepage, function() { polarbear_article_onload(); });
		treepage = ""; // we have to clear it so we can go elsewhere in the tree afterwards
		return true;

	}
	var node = $(node);

	// if href is a link, go to that link
	var href = node.find("a").attr("href");
	if (href != "#" && href != "") {
		var hrefTree = "<?php polarbear_webpath() ?>?treepage=" + href;		
		document.location = hrefTree;
		return false;
	}

	// gör knapparna aktiva, såvida det inte är root-noden eller liknande
	var nodeType = node.metadata().type;

	if (nodeType == "article") {
		// check if articleStatus has a value. if we clicked on just "save" 
		// we just want to go to the overview and show a message insetad of editing the article
		if (articleStatus == "articleEditSaved") {
			// @todo: all inside this if is never executed?
			href = "<?php echo polarbear_webpath() ?>overview.php?from=articleEditSaved";
			//iframe.attr("src", href);
			articleStatus = "";
			// select
			//tree.select_branch("#categoryOverview");
			return false;
		}
		$("#button-article-new").removeClass("ui-state-disabled");
		$("#button-article-rename").removeClass("ui-state-disabled");
		$("#button-article-delete").removeClass("ui-state-disabled");
	}

	// No special cases, go ahead and load article edit
	//tree.close_branch($("#categorySettings")); // make sure settings is closed. seems to be a bug in jstree with multiple branches open
	$("#polarbear-article-edit-dialog-loading").dialog({modal:true, resizable: false, title: "Loading"}).dialog("open");
	articleStatus = "articleEditInProgress";	
	href = "gui/articles-ajax.php?action=articleEdit&articleID=" + nodeId;
	polarbearContentMain.load(href, {}, function() { polarbear_article_onload(); });
	return false;
	
} // end treeOnChange

/**
 * Helper for TinyMCE
 * Browse for files
 */
function polarbearTinyCustomFileBrowser(field_name, url, type, win) {
	tinyMCE.activeEditor.windowManager.open({
		file : "<?php polarbear_webpath(); ?>gui/fileBrowser.php?field_name=" + field_name + "&url=" + url + "&type=" + type,
		title : 'My File Browser',
		width : 700,
		height : 450,
		resizable : "no",
		inline : "yes",
		close_previous : "no"
	}, {
		window : win,
		input : field_name
	});

	return false;
}



/**
 * A new node is created = a new article is created
 **/
function treeOnCreate(node,refnode,type,tree) {
	var rn = $(refnode);
	var a = rn.find("a");
	a.css("background-image", "url(<?php polarbear_webpath() ?>images/silkicons/page_white_text.png)")
	var rnMeta = rn.metadata();
	if (rnMeta.type == "root") {
		// lägg till sist i rooten
		polarbear_articleCreate(node, "root", type);
		return true;
	} else {
		// lägg till under vald artikel
		polarbear_articleCreate(node, refnode.id, type);
		return true;
	}
}

/**
 * Helper for the helper!
 * type: inside | before | above
 **/
function polarbear_articleCreate(node, parentTypeOrID, type) {

	$.ajax({
		type: "post",
		cache: false,
		async: false, // false så vi hinner vi id på nya artikeln
		url: "gui/articles-ajax.php",
		data: {
			action: "articleCreate", 
			parent: parentTypeOrID,
			refarticle: parentTypeOrID,
			type: type
		},
		success: function(data) {
			node.id = "article-" + data;
			// select the new node
			tree.select_branch(node);
		}
	});
}

/**
 * article has been moved in the tree
 */
function treeOnMove(node,refnode,type,tree) {
	//(TYPE is BELOW|ABOVE|INSIDE)
	// ta reda på id på artikeln som flyttats
	// ta reda på id på artikeln den nu ligger under
	$.post("gui/articles-ajax.php", {
		action: "articleMove",
		article: node.id,
		refnodeID: refnode.id,
		type: type
	});
}

/**
 * attach stuff to the users-page
 */
var usersLayout1, usersLayout2;
function polarbear_page_users_onload() {

	users_add_listener();

	// hover-states on the buttons
	$(".fg-button").hover(function() { $(this).toggleClass("ui-state-hover"); },function(){ $(this).toggleClass("ui-state-hover"); });

	// redigera grupp
	$(".button-group-edit").click(function(){
		if (!$(this).hasClass("ui-state-disabled")) {
			var oldName = $("#users-groups a.group-active").text();
			
			jPrompt("Enter new name", oldName, "", function(newName) {
				
				var groupID = users_get_selected_group_id();
				newName = $.trim(newName);
				if (newName) {
					pb_showMessage("<p>Group saved</p>");
					$("#users-group-list").load("gui/users.php", {
						action: "users_group_rename",
						groupID: groupID,
						newGroupName: newName
					}, function(){
						users_add_listener();
						users_select_group(groupID);
					});
				}

			});

		}
	});

	// ta bort grupp
	$(".button-group-delete").click(function(){
		if (!$(this).hasClass("ui-state-disabled")) {
			
			jConfirm("Delete group?", "", function(r) {

				if (r) {
					pb_showMessage("<p>Group deleted</p>");
					var groupID = users_get_selected_group_id();
					$(".button-group-edit").addClass("ui-state-disabled");
					$(".button-group-delete").addClass("ui-state-disabled");
					$.post("gui/users.php", {
						action: "users_group_delete",
						groupID: groupID
					}, function(){
						$("#users-group-list").load("gui/users.php", {
							action: "users_getUserGroupList"
						}, function(){
							users_add_listener();
						});
					});
				}
				
			});

		}

	});

	// vid klick på ny-gruppp-knapp
	$(".button-group-new").click(function(){
		jPrompt("Enter name of new group", "", "PolarBear CMS", function(name) {
			name = $.trim(name);
			if (name) {
				pb_showMessage("<p>Group added</p>");
				$.post("gui/users.php", {
					action: "users_createNewGroup",
					groupName: name
				}, function(data){
					$("#users-group-list").load("gui/users.php", {
						action: "users_getUserGroupList"
					}, function(){
						users_add_listener();
						users_select_group(data);
					});
				});
			}
		});
		return false;
	});

	// radera användare
	$(".button-user-delete").click(function() {
		if (!$(this).hasClass("ui-state-disabled")) {

			jConfirm("Delete user?", "", function(r){
				if (r) {
					var userID = users_get_selected_user_id();
					var groupID = users_get_selected_group_id();
					$.post("gui/users.php", { action: "users_deleteUser", userID: userID }, function() {
						// användaren raderad. ladda om grupper och så
						pb_showMessage("<p>User deleted</p>");
						$("#users-group-list").load("gui/users.php", { action: "users_getUserGroupList" }, function() {
							users_add_listener();
							users_select_group(groupID);
						});
					});
				}
			});

		}
	});

	// redigera användare
	$("a.button-user-edit").click(function() {
		if (!$(this).hasClass("ui-state-disabled")) {
			var userID = users_get_selected_user_id();
			$("#users-userdetails").load("gui/users.php", { action: "users_user_edit", userID: userID }, function() {
				$("#users-user-edit-cancel").click(function() {
					$("#users-userlist a.userID-" + userID).click();
				});

				// finns grupper så ska man kunna klicka på remove-länken
				$("#users-edit-add-to-group-select a").click(function() {
					// ta bort hela raden
					$(this).parent("div").remove();
				});

				// byta lösenord
				$("#users-edit-change-password a").click(function() {
					$("#users-edit-change-password-details").slideToggle("fast");
					$("#user-edit-change-password-password").focus();
				});

				users_edit_user_add_save_listener();
			});
		}
	});

	// ny användare
	$(".button-user-new").click(function() {
		if (!$(this).hasClass("ui-state-disabled")) {
			$("#users-userlist a.user-active").removeClass("user-active");
			$(".button-user-edit").addClass("ui-state-disabled");
			$(".button-user-delete").addClass("ui-state-disabled");
			$("#users-userdetails").load("gui/users.php", { action: "users_user_edit", userID: "" }, function() {
				// aktivera avbryt-knappen
				$("#users-user-edit-cancel").click(function() {
					$("#users-userdetails").text("");
				} );

				// finns grupp markerad ska denna vara tillagd hos den nya användaren
				var selectedGroupID = users_get_selected_group_id();
				selectedGroupID = parseInt(selectedGroupID);
				if (selectedGroupID && !isNaN(selectedGroupID)) {
					$("#users-edit-add-to-group-select").append("<div></div>");
					var lastDiv = $("#users-edit-add-to-group-select div:last");
					lastDiv.prepend(users_get_selected_group_name() + " <a href='#'>remove</a>");
					// lägg också till en dold input som vi kan hämta grouppens ID från
					lastDiv.prepend("<input type='hidden' name='selectedGroupID' value='" + selectedGroupID + "' />");
					$("#users-edit-add-to-group-select div:last a").click(function() {
						// ta bort den sista diven
						lastDiv.remove();
					});
				}

				// visa password-fälten eftersom vi troligtvis vill fylla i dom
				$("#users-edit-change-password").hide();
				$("#users-edit-change-password-details").show();

				users_edit_user_add_save_listener();

				// ...och fokus på first name så vi kan börja skriva direkt. yeah. we're good and nice people.
				$("#user_edit_firstname").focus();
			} );
		}
	});

	// gör spara-knappen funktionell i rediga användare-rutan
	// save user-button
	function users_edit_user_add_save_listener() {
		// aktivera spara-knappen
		$("a.users-user-edit-save").click(function() {
			var userID = users_get_selected_user_id();
			if (userID == false) { userID = ""; }
			var firstname = $("#user_edit_firstname").val();
			var lastname = $("#user_edit_lastname").val();
			var email = $("#user_edit_email").val();

			// något av förnamn, efternamn, epost måste vara ifyllt
			firstname = $.trim(firstname);
			lastname = $.trim(lastname);
			email = $.trim(email);

			if (!firstname && !lastname && !email) {
				jAlert("Please give the user a name or an email address");
				return false;
			}

			// om man fyllt i någon i lösenordsfälten
			var newPassword = $("#user-edit-change-password-password").val();
			var newPasswordRepeat = $("#user-edit-change-password-password-repeat").val();
			if (newPassword != "" || newPasswordRepeat != "") {
				if (newPassword == newPasswordRepeat) {
					// ok, nya lösenorden är lika
				} else {
					jAlert("The passwords you entered are not the same.");
					return false;
				}
			}

			// hämta in grupper
			// nya grupper ligger i <select>
			var selectedGroupsID = "";
			$("#users-edit-add-to-group-select input[name=selectedGroupID]").each(function(i, e) {
				selectedGroupsID = selectedGroupsID + e.value + " ";
			});
			
			// hämta in "extrafält"
			// värdena ligger i input.user-edit-custom-value-thevalue
			// och nyckeln/namnet/labeln ligger i en ovanliggande select.user_edit_values_select
			var customLabels = $("select.user_edit_values_select");
			var arrLabels = [];
			var arrValues = [];
			customLabels.each(function(e,i) {
				var label = this[this.selectedIndex].value;
				var value = $(this).closest("div").find(".user-edit-custom-value-thevalue").val();
				//customLabelsAndValues.push({label:label, value: value});
				arrLabels.push(label);
				arrValues.push(value);
			});

			$.post("<?php polarbear_webpath() ?>gui/users.php", { action: "users_user_save", userID: userID, firstname: firstname, lastname: lastname, email: email, groups: selectedGroupsID, newPasswordRepeat: newPasswordRepeat, newPassword: newPassword, "customLabels[]":arrLabels, "customValues[]":arrValues }, function(data) {
				// användaren är sparad. Ladda om kategorier + välj kategori + välj användare
				$("#users-group-list").load("gui/users.php", { action: "users_getUserGroupList" }, function() {
	
					pb_showMessage("<p>User saved</p>");
					
					users_add_listener();
					// ny användare = gå till senast addade, befintlig användare = gå till senast ändrde
					if (userID) {
						users_select_group("latestChanged");
					} else {
						users_select_group("latest");
					}
				});

			});
		}).hover(function() { $(this).toggleClass("ui-state-hover"); },function(){ $(this).toggleClass("ui-state-hover"); });;

		// aktivera lägg-till-i-grupp-knappen
		$("div.users-edit-add-to-group a").click(function() {
			// users_get_selected_user_id
			var userID = users_get_selected_user_id();
			//$("div.users-edit-add-to-group").hide();
			// skapa ny div att lägga drop down i
			$("#users-edit-add-to-group-select").append("<div></div>");
			var lastDiv = $("#users-edit-add-to-group-select div:last");
			//$("#users-edit-add-to-group-select").load("gui/users.php", { action: "users_getAddGroupToUser", userID: userID });
			lastDiv.load("gui/users.php", { action: "users_getAddGroupToUser", userID: userID }, function() {
				// vid click på remove ska denna grupp visuellt tas bort
				$("#users-edit-add-to-group-select div:last a").click(function() {
					// ta bort den sista diven
					lastDiv.remove();
				});
				// vid ändring av värde i drop-down ska drop-downen försvinna och ersättas med 
				// dold input + gruppens namn som text
				$("select", lastDiv).change(function() {
					var selectedGroupID = $(this).val();
					var selectedGroupName = $(this).find(":selected").text();
					lastDiv.find("select").remove();
					lastDiv.prepend(selectedGroupName);
					// lägg också till en dold input som vi kan hämta grouppens ID från
					lastDiv.prepend("<input type='hidden' name='selectedGroupID' value='" + selectedGroupID + "' />");
				});
			});
		});
	}

	// koppla på en layout till
	usersLayout1 = $("#polarbear-content-main").layout({
		applyDefaultStyles: false,
		defaults: {
			resizable: true,
			closable: false
		},
		west: {
			size: .26
		},
		east: {
			size: .40
		}
	});

}

/**
 * shows a info-message at top, like in gmail and other apps
 */
function pb_showMessage(text, type) {
	$("#pb-message").html(text).slideDown("slow");
	setTimeout(function() { $("#pb-message").slideUp("fast"); }, 5000);
}