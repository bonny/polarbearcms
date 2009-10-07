<?php
header("Content-Type: application/x-javascript");
$polarbear_cache_allow_client_caching = true;
require_once("../../polarbear-boot.php");

$tinymce_theme_advanced_styles = polarbear_setting("tinymce_theme_advanced_styles");
?>
// formuläret inladdat, på med lyssnare och sånt

function polarbear_article_onload() {

	// Change of title = change shortname, but only the first time or if shortname is empty
	$("#article-title").blur(function() {
		if (($("#article-shortName").val() == "") && ($("#article-title").val() != "")) {
			// update new name through ajax
			$("#article-shortName-preview").text("Updating...");
			$("#article-shortName-preview").load("<?php polarbear_webpath() ?>gui/articles-ajax.php", { action: "getValidatedShortname", title: $("#article-title").val(), articleID: $("#article-id").val() }, function(data) {
				$("#article-shortName").val(data);
			});
		}
	});
	
	// fokusera titel-fälet
	$("#article-title").focus();

	tinyMCE.init({
		mode: "exact",
		theme : "advanced",
		elements: "article-teaser",
		plugins : "table,paste,visualchars,media,advimage,inlinepopups,tabfocus",
		dialog_type : "modal",
		custom_shortcuts : false,
		entity_encoding : "raw",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_buttons1 : "bold,italic,|,link,unlink,|,pastetext,|,undo,redo,|,cleanup,code,",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_resizing : true,
		theme_advanced_resize_horizontal : false,
		button_tile_map : true,
		content_css : "<?php polarbear_webpath() ?>includes/css/tinymce.css",
		file_browser_callback : "polarbearTinyCustomFileBrowser",
		relative_urls : false,
		document_base_url : "http://<?php polarbear_domain();?><?php polarbear_webpath();?>"
	});

	tinyMCE.init({
		mode: "exact",
		theme : "advanced",
		elements: "article-body",
		plugins : "table,paste,visualchars,media,advimage,inlinepopups,tabfocus",
		dialog_type : "modal",
		custom_shortcuts : false,
		entity_encoding : "raw",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_buttons1 : "bold,italic,|,strikethrough,|,bullist,numlist,outdent,indent,|,pastetext,|,undo,redo,|,cleanup,code",
		theme_advanced_buttons2 : "link,unlink,image,media,|,formatselect<?php print ($tinymce_theme_advanced_styles)?",styleselect":"" ?>,|,tablecontrols",
		theme_advanced_buttons3 : "",
		theme_advanced_styles : "<?php echo $tinymce_theme_advanced_styles ?>",
		theme_advanced_resizing : true,
		theme_advanced_resize_horizontal : false,
		button_tile_map : true,
		theme_advanced_blockformats : "p,h2,h3,h4",
		content_css : "<?php polarbear_webpath() ?>includes/css/tinymce.css",
		file_browser_callback : "polarbearTinyCustomFileBrowser",
		relative_urls : false,
		document_base_url : "http://<?php polarbear_domain();?><?php polarbear_webpath();?>",
		extended_valid_elements : "iframe[align<bottom?left?middle?right?top|class|frameborder|height|id|longdesc|marginheight|marginwidth|name|scrolling<auto?no?yes|src|style|title|width]"
	});

	
	// hide ok-msg-box after a short period of time
	// no, don't. it's confusing.
	// or do it? its irritating to have the msg there also..
	if ($(".polarbear-okmsg").length > 0) {
		setTimeout(function() { $(".polarbear-okmsg").slideUp("slow", function() {}); }, 3000);
	}
	
	/* optionsboxes */
	$(".polarbear-optionbox > div").css({ "marginBottom": "1em" });
	$(".polarbear-optionbox > div > div").hide();
	$(".polarbear-optionbox > div > div > div").css("padding", ".5em 1em 1em 16px");
	$(".polarbear-optionbox div h3").css("position", "relative").append("<span class='ui-icon ui-icon-triangle-1-e'></span>");
	$(".polarbear-optionbox div h3 span.ui-icon").css({ position: "absolute", top: "-2px", left: "0px"  });
	$(".polarbear-optionbox div h3 a").css({ paddingLeft: "16px", display: "block", textDecoration: "none" }).toggle(function() {
		$(this).parent("h3").next("div").slideDown();
		$(this).parent("h3").find("span").removeClass("ui-icon-triangle-1-e");
		$(this).parent("h3").find("span").addClass("ui-icon-triangle-1-s");
	},function(){
		$(this).parent("h3").next("div").slideUp();
		$(this).parent("h3").find("span").removeClass("ui-icon-triangle-1-s");
		$(this).parent("h3").find("span").addClass("ui-icon-triangle-1-e");
	});
	// öppna översta som standard
	$(".polarbear-optionbox div:first h3 a").click();

	$("ul.polarbear-article-edit-fields-repeatable").sortable({ 
		axis: 'y', 
		cursor: 'crosshair', 
		revert: true, 
		start: function(event, ui) {
			// sort begins
			// Find all tiny editors in this list and temporary disable them. Tiny + sortable kinda don'y work all times (saving does not work, don't know why)
			var tinys = $(event.target).find("textarea.fieldsTextHTML");
			tinys.each(function(i) {
				tinyMCE.execCommand('mceRemoveControl',false,this.id); 
			});
		},
		stop: function(event, ui) {
			// sort ends, re-enable tiny editors
			var tinys = $(event.target).find("textarea.fieldsTextHTML");
			tinys.each(function(i) {
				tinyMCE.execCommand('mceAddControl',false,this.id); 
			})
		}
	});
	
	// attach tiny to fields
	tinyMCE.init({
		mode : "specific_textareas",
		editor_selector : "fieldsTextHTML",
		theme : "advanced",
		plugins : "table,paste,visualchars,media,advimage,inlinepopups,tabfocus",
		dialog_type : "modal",
		custom_shortcuts : false,
		entity_encoding : "raw",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_buttons1 : "bold,italic,|,strikethrough,|,bullist,numlist,|,pastetext,|,undo,redo,|,cleanup,code",
		theme_advanced_buttons2 : "link,unlink,image,media,|,formatselect,|,table",
		theme_advanced_buttons3 : "",
		theme_advanced_resizing : true,
		theme_advanced_resize_horizontal : false,
		button_tile_map : true,
		theme_advanced_blockformats : "p,h2,h3,h4",
		content_css : "<?php polarbear_webpath() ?>includes/css/tinymce.css",
		file_browser_callback : "polarbearTinyCustomFileBrowser",
		relative_urls : false,
		document_base_url : "http://<?php polarbear_domain();?><?php polarbear_webpath();?>"
	});

	// dialog for files
	$("#polarbear-article-edit-fields-files-dialog").dialog({
		xbuttons: {
			"Ok": function() {},
			"Cancel": function() {}
		},
		modal: true,
		title: "Choose file",
		autoOpen: false,
		width: 650,
		height: 500
	});
		
	// close loading-dialog
	$("#polarbear-article-edit-dialog-loading").dialog("close");

} // end articleonload


// custom nav/page-titles
$("#article-use-different-title").live("click", function() {
	$("#article-edit-custom-titles-wrapper").slideToggle("slow");
});

// modify public author
$("#official-author-change").live("click", function() {
	// visa fönstret för byte av officiell författare
	//$.blockUI( {message: $("#official-author-window"), css: { top: "10%" } } );
	$("#official-author-window").dialog({
		modal: true,
		width: 500,
		title: "Select type of new official author",
		buttons: {
			"Ok": function() {
				/*
				hämta in typ
				om text eller user: hämta in namn
				*/
				// start button ok
				var type = $("#official-author-window input[name='author-type']:checked");
				var authorType = type.val();
				var authorText = "";
				var authorUserID = "";
				var ok = true;
				if (authorType == "text") {
					// om text - se till att något är inskrivet
					var customName = $.trim($("#official-author-window input[name='author-type-custom-name']").val());
					if (customName=="") {
						jAlert("Please enter a name");
						ok = false;
					} else {
						authorText = customName;
					}
				} else if (authorType=="user") {
					// kontrollera att en användare är valt
					var selectedUser = $("#users-userlist a.selected");
					if (selectedUser.length == 0) {
						jAlert("Please choose a user");
						ok = false;
					} else {
						authorText = selectedUser.text();
						var re = /userID-([\d]+)/;
						authorUserID = re.exec(selectedUser.attr("className"))[1]; // userID-1035 selected
					}
				} else if (authorType == "none") {
					authorText = "None selected";
				}
			
				if (ok) {
					// ok, stäng rutan och lagra värden
					$("input[name='official-author-type']").val(authorType);
					$("input[name='official-author-text']").val(authorText);
					$("input[name='official-author-userID']").val(authorUserID);
					$("#official-author-name-onscreen").text(authorText);
					$("#official-author-window").dialog("close");
				}
				// end button ok
			},
			"Cancel": function() {
				$(this).dialog("close");
			}
		}
	}).dialog("open");
	return false;
});


// show userbrowser, or hide when one of the other radiobuttons are selected
$("#official-author-type-none, #official-author-type-custom").live("click",function(){
	$("#author-type-choose-userbrowser").hide();
});
$("#author-type-choose-from-existing").live("click", function(){
	$("#author-type-choose-userbrowser").show();
});
// Click on custom user, focus input
$("#official-author-type-custom").live("click", function() {
	$("#official-author-window input[name='author-type-custom-name']").focus();
});
// Click on custom user input = select radiobutton
$("#official-author-window input[name='author-type-custom-name']").live("click",function() {
	$("#official-author-type-custom").attr("checked", "checked");
});

// on click on a group or a user in the user browser
// todo: is this using the same id as the real user admin?
// todo: this is also getting called in gui/users.php. that's baaaaad!
$("#article-edit-choose-author-users-groups").live("click", function(e) {
	// users_getUsersInGroup
	// $_POST["groupID"]; groupID-<id>
	var groupClassName = e.target.className;
	$("#author-type-choose-users").load("<?php polarbear_webpath() ?>gui/users.php", { action: "users_getUsersInGroup", groupID: groupClassName }, function() {
		// personerna i gruppen har laddats in
		$("#users-userlist").click(function(e){
			$("#users-userlist a.selected").removeClass("selected");
			var userClassName = e.target.className;
			// klick på person = visa kort info om personen
			$(e.target).addClass("selected");
			$("#author-type-choose-oneUserInfo").load("<?php polarbear_webpath() ?>gui/users.php", { action: "users_viewOneUser", userID: userClassName });
		});
	});
	return false;
});



/**
 * Change publish
 */
$("#article-datePublish-change").live("click",function() {

	$("#article-datePublish-window").dialog({
		width: 400,
		modal: true,
		title: "When should this article be published",
		buttons: {
			"Ok": function() {
				// begin ok-button
				var ok = true;
				// if date/time something must be selected
				var type = $("#article-datePublish-window input[name='article-datePublish-when']:checked").val();
				var val = "";
				if (type=="date") {
					var date = $("#article-datePublish-window input[name='article-datePublish-selecteddate']").val();
					var hh = $("#article-datePublish-selectedtime-hh option:selected").text();
					var mm = $("#article-datePublish-selectedtime-mm option:selected").text();
					var time = hh + ":" + mm;
					if (date == "") {
						ok = false;
						jAlert("You must select a date");
					} else {
						// ok, skriv värde i klartext + de olika selectarna så det blir rätt när vi tar upp dialogen igen utan att spara emellan
						$("#article-datePublish-when-text").text(date + " " + time);
						val = date + " " + time;
					}
				} else {
					$("#article-datePublish-when-text").text("Immediately");
				}
				if (ok) {
					$("#article-datePublish-val").val(val);
					var selectedDate = $("#article-datePublish-datepicker").datepicker("getDate");
					$("#article-datePublish-valY").val(selectedDate.getFullYear());
					$("#article-datePublish-valM").val(selectedDate.getMonth());
					$("#article-datePublish-valD").val(selectedDate.getDate());
					$("#article-datePublish-valHM").val(time);
					$("#article-datePublish-valHours").val(hh);
					$("#article-datePublish-valMins").val(mm);
					$("#article-datePublish-window").dialog("close");
				}
				// end ok-button
			},
			"Cancel": function() {
				$("#article-datePublish-window").dialog("close");
			}
		}
	}).dialog("open");

	// article-datePublish-window
	$("#article-datePublish-when-never").click(function() {
		$("#article-datePublish-dateAndTime").hide();
	});
	$("#article-datePublish-when-date").click(function() {
		$("#article-datePublish-dateAndTime").show();
	});
	var val = $("#article-datePublish-val").val(); // tomt eller specifikt datum
	$("#article-datePublish-datepicker").datepicker({
		firstDay: 1,
		showWeeks: true,
		showStatus: true,
		changeYear: true,
		changeMonth: true,
		dateFormat: "yy-mm-dd",
		highlightWeek: true,
		onSelect: function(date) {
			$("#article-datePublish-window input[name='article-datePublish-selecteddate']").val(date);
		}
	});

	if (val=="") {
		$("#article-datePublish-when-never").click();
	} else {
		$("#article-datePublish-when-date").click();
		var y = parseInt($("#article-datePublish-valY").val());
		var m = parseInt($("#article-datePublish-valM").val());
		var d = parseInt($("#article-datePublish-valD").val());
		var hours = parseInt($("#article-datePublish-valHours").val());
		var mins = parseInt($("#article-datePublish-valMins").val());
		var hm = $("#article-datePublish-valHM").val();
		$("#article-datePublish-datepicker").datepicker("setDate", new Date(y, m, d) );

		$("#article-datePublish-window input[name='article-datePublish-selecteddate']").val(y + "-" + (m+1) + "-" + d);

		// select/check the correct values in the drop down
		$("#article-datePublish-selectedtime-hh option[value=" + hours + "]").attr("selected", "selected");
		$("#article-datePublish-selectedtime-mm option[value=" + mins + "]").attr("selected", "selected");
		
	}
}); // publish


/**
 * unpublish
 */
$("#article-dateUnpublish-change").live("click", function() {
	
	$("#article-dateUnpublish-window").dialog({
		width: 400,
		modal: true,
		title: "When should this article be unpublished",
		buttons: {
			"Ok": function() {
				var ok = true;
				// if date/time something must be selected
				var type = $("#article-dateUnpublish-window input[name='article-dateUnpublish-when']:checked").val();
				var val = "";
				if (type=="date") {
					var date = $("#article-dateUnpublish-window input[name='article-dateUnpublish-selecteddate']").val();
					var hh = $("#article-dateUnpublish-selectedtime-hh option:selected").text();
					var mm = $("#article-dateUnpublish-selectedtime-mm option:selected").text();
					var time = hh + ":" + mm;

					if (date == "") {
						ok = false;
						jAlert("You must select a date");
					} else {
						// ok, skriv värde i klartext
						$("#article-dateUnpublish-when-text").text(date + " " + time);
						val = date + " " + time;
					}
				} else {
					$("#article-dateUnpublish-when-text").text("Forever");
				}
				if (ok) {
					$("#article-dateUnpublish-val").val(val);
		
					var selectedDate = $("#article-dateUnpublish-datepicker").datepicker("getDate");
					$("#article-dateUnpublish-valY").val(selectedDate.getFullYear());
					$("#article-dateUnpublish-valM").val(selectedDate.getMonth());
					$("#article-dateUnpublish-valD").val(selectedDate.getDate());
					$("#article-dateUnpublish-valHM").val(time);
					$("#article-dateUnpublish-valHours").val(hh);
					$("#article-dateUnpublish-valMins").val(mm);
					$("#article-dateUnpublish-valHM").val(time);
					$("#article-dateUnpublish-window").dialog("close");
				}
			},
			"Cancel": function() {
				$("#article-dateUnpublish-window").dialog("close");
			}
		}
	}).dialog("open");
	
	var val = $("#article-dateUnpublish-val").val(); // tomt eller specifikt datum
	$("#article-dateUnpublish-datepicker").datepicker({
		firstDay: 1,
		showWeeks: true,
		showStatus: true,
		changeYear: true,
		changeMonth: true,
		dateFormat: "yy-mm-dd",
		onSelect: function(date) {
			$("#article-dateUnpublish-window input[name='article-dateUnpublish-selecteddate']").val(date);
		}
	});

	if (val=="") {
		$("#article-dateUnpublish-when-never").click();
	} else {
		$("#article-dateUnpublish-when-date").click();
		var y = parseInt($("#article-dateUnpublish-valY").val());
		var m = parseInt($("#article-dateUnpublish-valM").val());
		var d = parseInt($("#article-dateUnpublish-valD").val());
		var hm = $("#article-dateUnpublish-valHM").val();
		var hours = parseInt($("#article-dateUnpublish-valHours").val());
		var mins = parseInt($("#article-dateUnpublish-valMins").val());
		
		$("#article-dateUnpublish-datepicker").datepicker("setDate", new Date(y, m, d) );
		$("#article-dateUnpublish-window input[name='article-dateUnpublish-selecteddate']").val(y + "-" + (m+1) + "-" + d);
		
		//$("#article-dateUnpublish-window input[name='article-dateUnpublish-selectedtime']").val(hm);
		$("#article-dateUnpublish-selectedtime-hh option[value=" + hours + "]").attr("selected", "selected");
		$("#article-dateUnpublish-selectedtime-mm option[value=" + mins + "]").attr("selected", "selected");

	}
}); // unpublish


// article-dateUnpublish-window
$("#article-dateUnpublish-when-never").live("click", function() {
	$("#article-dateUnpublish-dateAndTime").hide();
});
$("#article-dateUnpublish-when-date").live("click", function() {
	$("#article-dateUnpublish-dateAndTime").show();
});

// template, click på custom-input ska bocka i radiobutton
$("#article-template-type-custom-value").live("click", function() {
	$("#article-template-type-custom").click();
})	
// template, click på custom-radio ska fokusera input
$("#article-template-type-custom").live("click", function() {
	$("#article-template-type-custom-value").focus();
});
// template, click på drop down ska välja motsvarande radiobutton
$("#article-template-type-name-value").live("click", function() {
	$("#article-template-type-name").click();
});



// shortname
$("#article-shortName-change, #article-shortName-preview").live("click", function() {
	$("#article-shortName-change-input-wrapper input").val($("#article-shortName").val());
	$("#article-shortName-change").hide();
	$("#article-shortName-preview").hide();
	$("#article-shortName-change-input-wrapper input").show().focus();
	$("#article-shortName-change-ok").show();
	$("#article-shortName-change-cancel").show();
	$("#article-shortName-view").hide();
});
$("#article-shortName-change-cancel").live("click",function() {
	$("#article-shortName-change").show();
	$("#article-shortName-preview").show();
	$("#article-shortName-change-input-wrapper input").hide();
	$("#article-shortName-change-ok").hide();
	$("#article-shortName-change-cancel").hide();
	$("#article-shortName-view").show();
});
$("#article-shortName-change-ok").live("click", function() {
	var newVal = $("#article-shortName-change-input-wrapper input").val();
	$("#article-shortName-change-cancel").click();
	// update new name through ajax
	$("#article-shortName-preview").text("Updating...");
	$("#article-shortName-preview").load("<?php polarbear_webpath() ?>gui/articles-ajax.php", { action: "getValidatedShortname", title: newVal, articleID: $("#article-id").val() }, function(data) {
		$("#article-shortName").val(data);
	});
	$("#article-shortName-view").show();
});


// Save-buttons
// article-button-save-and-continue-editing
$(".article-button-save, .article-button-save-continue-editing").live("click", function(e) {
	$("#polarbear-article-edit-dialog-save").dialog({title: "Saving", modal: true, resizable: false});
	$("#article-edit-ispreview").val("0");
	$("#article-edit-form").attr("target", "");
	var target = $(e.target);
	if (target.hasClass("article-button-save-continue-editing")) {
		$("#article-edit-afterSave").val("continueEditing");
	} else if (target.hasClass("article-button-save")) {
		if ($("#article-edit-afterSave").val()=="") {
			$("#article-edit-afterSave").val("overview");
		}
	}
	$("#article-edit-form").submit();
});
$(".article-button-save, .article-button-save-continue-editing, .article-button-preview").live("mouseover", function() {
	$(this).addClass("ui-state-hover"); 
});
$(".article-button-save, .article-button-save-continue-editing, .article-button-preview").live("mouseout", function() {
	$(this).removeClass("ui-state-hover");
});
$(".article-button-save, .article-button-save-continue-editing, .article-button-preview").live("mousedown", function(e) {
	$(this).addClass("ui-state-active");
});
$(".article-button-save, .article-button-save-continue-editing, .article-button-preview").live("mouseup", function(e) {
	$(this).removeClass("ui-state-active");
});


// cancel-buttons
$(".polarbear-article-edit-button-cancel").click(function() {
	// if in polarbear, goto the overview node in the tree
	if (confirm("Unsaved changes will be lost.\n\nCancel editing?")) {
		return true;
	} else {
		return false;
	}
});

// preview button
// save the article to a new window, with some hidden fields changed..
$(".article-button-preview").live("click", function() {
	$("#article-edit-form").attr("target", "_blank");
	$("#article-edit-ispreview").val("1");
	$("#article-edit-form").submit();
});


// fields
$("#article-template-field-value").live("click", function() {
	$("#article-template-field-name").click();
});


// add repeatable field-button
$(".polarbear-article-edit-fields-add").live("click", function () {
	var t = $(this);
	var ul = t.parents("fieldset").find("ul.polarbear-article-edit-fields-repeatable");
	var m = t.metadata();
	$.post("<?php polarbear_webpath() ?>gui/articles-ajax.php", { action:"ajax-addFieldCollection", fieldCollectionID: m.fieldCollectionID, polarbearArticleEditNumNewFields: polarbearArticleEditNumNewFields }, function(data) {
		// data for field recieved, add it and attach some things
		ul.prepend(data);
		var firstLI = ul.find("li:first");
		firstLI.hide().slideDown("slow").effect("highlight", {}, 2000);
		
		// if a textarea with class fieldsTextHTML exists, then attach a tiny editor to it
		var textarea = firstLI.find("textarea.fieldsTextHTML");
		if (textarea.length>0) {
			var firstTextareaID = textarea.attr("id");
			// attach tiny
			tinyMCE.init({
				mode: "exact",
				theme : "advanced",
				elements: firstTextareaID,
				plugins : "table,paste,visualchars,media,advimage,inlinepopups,tabfocus",
				dialog_type : "modal",
				custom_shortcuts : false,
				entity_encoding : "raw",
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				theme_advanced_statusbar_location : "bottom",
				theme_advanced_buttons1 : "bold,italic,|,strikethrough,|,bullist,numlist,|,pastetext,|,undo,redo,|,cleanup,code",
				theme_advanced_buttons2 : "link,unlink,image,media,|,formatselect,|,table",
				theme_advanced_buttons3 : "",
				theme_advanced_resizing : true,
				theme_advanced_resize_horizontal : false,
				button_tile_map : true,
				theme_advanced_blockformats : "p,h2,h3,h4",
				content_css : "<?php polarbear_webpath() ?>includes/css/tinymce.css",
				file_browser_callback : "polarbearTinyCustomFileBrowser",
				relative_urls : false,
				document_base_url : "http://<?php polarbear_domain();?><?php polarbear_webpath();?>"
			});
		}
					
	});
	polarbearArticleEditNumNewFields++;
	return false;
});



// delete article
$("#polarbear-article-edit-delete").live("click", function() {

	jConfirm("Delete article? All sub-articles will also be deleted.", "PolarBear CMS", function(r) {
		if (r) {
			// yeah, delete
			document.location = $("#polarbear-article-edit-delete-url").val();
		} else {
			// no delete
		}
	});
	return false;
	/*
	if (confirm("Delete article? All sub-articles will also be deleted.")) {
		// where to go?
		// overview.php?action=deleted
		return true;
	} else {
		return false;
	}
	*/
});


// Add yellow color on mouse click for repeatable fields
$("ul.polarbear-article-edit-fields-repeatable li").live("click", function() {
	var t = $(this);
	$("ul.polarbear-article-edit-fields-repeatable li").removeClass("active");
	t.addClass("active");
});

$(".polarbear-article-edit-fields-repeatable-remove a").live("click", function() {
	var t = $(this);
	t.parents("li").slideUp("slow", function(){
		$(this).remove();
	});
	return false;
});


// attach field choose IMAGE-listener
$("a.polarbear-article-edit-fields-image-choose").live("click", function() {
	
	// clear prev active
	$(".polarbear-article-edit-fields-repeatable div.fieldImageActive,.polarbear-article-edit-fields-nonrepeatable div.fieldImageActive").removeClass("fieldImageActive");
	
	// mark field image as currently active
	var parentDivs = $(this).parents("div");
	var containerDiv = $(parentDivs[2]).addClass("fieldImageActive");

	// open dialog
	$("#polarbear-article-edit-fields-files-dialog").dialog("open");
	var iframe = $("#polarbear-article-edit-fields-files-dialog iframe").attr("src", "<?php polarbear_webpath() ?>gui/fileBrowser.php?field_name=src&url=&type=fieldImage");
	
	return false;	
});

// attach field choose FILE-listener
$("a.polarbear-article-edit-fields-file-choose").live("click", function() {

	// clear prev active
	$(".polarbear-article-edit-fields-repeatable div.fieldImageActive,.polarbear-article-edit-fields-nonrepeatable div.fieldImageActive").removeClass("fieldImageActive");
	
	// mark field image as currently active
	var parentDivs = $(this).parents("div");
	var containerDiv = $(parentDivs[2]).addClass("fieldImageActive");

	// open dialog
	$("#polarbear-article-edit-fields-files-dialog").dialog("open");
	var iframe = $("#polarbear-article-edit-fields-files-dialog iframe").attr("src", "<?php polarbear_webpath() ?>gui/fileBrowser.php?field_name=src&url=&type=fieldFile");
	
	return false;
});


// clear image
$("a.polarbear-article-edit-fields-fieldImage-clear").live("click", function() {
	
	var containerDiv = $(this).parents(".polarbear-article-edit-fields-fieldImage");
	containerDiv.find(".polarbear-article-edit-fields-fieldImage-image").html("");
	containerDiv.find(".polarbear-article-edit-fields-fieldImage-imageName").text("");
	containerDiv.find(".polarbear-article-edit-fields-fieldImage-value").val("");	

});
