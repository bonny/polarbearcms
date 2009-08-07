<?php
$polarbear_cache_allow_client_caching = true;
require_once("../../polarbear-boot.php");
?>
function users_add_listener(where) {
	where = where || "groups";
	if (where == "groups") {
		$("#users-groups a").click(function(){
			$("#users-group-members").text("Loading ...");
			this.blur();
			$("#users-groups a.group-active").removeClass("group-active");
			$(this).addClass("group-active");
			// aktivera knappar
			$("a.button-group-edit").removeClass("ui-state-disabled");
			$("a.button-group-delete").removeClass("ui-state-disabled");
			$("a.button-user-edit").addClass("ui-state-disabled");
			$("a.button-user-delete").addClass("ui-state-disabled");
			$("#users-userdetails").text("");
			// men inte om man klickat på alla eller admin
			if ($(this).hasClass("groupID-all") || $(this).hasClass("groupID-latest") || $(this).hasClass("groupID-admins")) {
				$("a.button-group-edit").addClass("ui-state-disabled");
				$("a.button-group-delete").addClass("ui-state-disabled");
			}
			
			$("#users-group-members").load("gui/users.php", {
				action: "users_getUsersInGroup",
				groupID: this.className
			}, function() { users_add_listener("users") });
			
			
		});

		/*
		$("#users-groups a").droppable( { activeClass: "droppable-active", hoverClass: "droppable-hover", accept: "#users-userlist a", drop: function(e, ui) {
				// en användare har släppts på en grupp
				var groupID = $(this).attr("class").match(/groupID-([\d]+)/)[1];
				var userID = $(ui.draggable).attr("class").match(/userID-([\d]+)/)[1];
				$.post("gui/users.php", { action: "users_addUserToGroup", userID: userID, groupID: groupID });
			}
		} );
		*/

	} else if (where == "users") {
		
		$("#users-userlist a").click(function(){
			$("#users-userdetails").text("Loading...");
			this.blur();
			$("#users-userlist a").removeClass("user-active");
			$(this).addClass("user-active");
			$(".button-user-edit").removeClass("ui-state-disabled");
			$(".button-user-delete").removeClass("ui-state-disabled");
			var userID = users_get_selected_user_id();
			$("#users-userdetails").load("gui/users.php", { action: "users_viewOneUser", userID: userID });
		});
		
		/*
		$("#users-userlist a").draggable({ containment: $("table.users"), delay: 100, revert: true, helper: "clone", xsnap: true });
		*/

	}

}

function users_select_group(groupID){
	var className = "#users-group-list a.groupID-" + groupID;
	$(className).click();
	
};

function users_get_selected_group_id(){
	var groupID = $("#users-groups a.group-active");
	if (groupID.length>0) {
		groupID = groupID.attr("class").match(/groupID-([\d\w]+)/)[1];
		return groupID;
	} else {
		return false;
	}
}

function users_get_selected_group_name(){
	var groupName = $("#users-groups a.group-active");
	if (groupName.length>0) {
		groupName = groupName.text();
		return groupName;
	} else {
		return false;
	}
}

function users_get_selected_user_id(){
	var groupID = $("#users-userlist a.user-active");
	if (groupID.length > 0) {
		groupID = groupID.attr("class").match(/userID-([\d]+)/)[1];
		return groupID;
	} else {
		return false;
	}
}

// add custom value
$("#user_edit_value_add").live("click", function() {

	var html = '<div class="col full">';
	html += '<label>';
	html += '<select style="float:left" class="user_edit_values_select"><option>Choose label for this value</option><option>New label...</option>';
	<?php
	// add all existing unique labels
	$arrLabels = pb_users_values_all_unique_labels();
	foreach ($arrLabels as $oneLabel) {
		?>
		html += '<option value="<?php echo htmlspecialchars($oneLabel, ENT_COMPAT, "UTF-8") ?>"><?php echo htmlspecialchars($oneLabel, ENT_COMPAT, "UTF-8") ?></option>';
		<?php
	}
	?>
	html += '</select>';
	html += '<a class="user_edit_values_remove" style="float:left" title="Remove this custom value" class="" href=""><span class="ui-icon ui-icon-trash"></span></a>'
	html += '</label>';
	html += '<input type="text" class="user-edit-custom-value-thevalue text ui-widget-content ui-corner-all" value="" />';
	html += '</div>';

	$("#user_edit_values_container").append(html);
	
	// listener for the drop down
	$("select.user_edit_values_select").change(function(){
		if (this.selectedIndex==1) {
			// new
			var name = prompt("Enter value label\n(i.e. 'nickname', 'City', 'Favorite color', etc.):");
			if (name) {
				$(this).append("<option>"+name+"</option>");
				this.selectedIndex = this.length-1;
				$(this).parent().parent().find("input").focus();
			} else {
				this.selectedIndex=0;
			}
		} else {
		}
	});

});

// remove custom value
$(".user_edit_values_remove").live("click", function() {
	if (confirm("Remove this label and it's value?")) {
		$(this).closest("div").hide("slow", function() { $(this).remove(); });
	} else {
	}
	return false;
});

polarbear_page_users_onload();

