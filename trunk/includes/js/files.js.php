<?php
header("Content-Type: application/x-javascript");
$polarbear_cache_allow_client_caching = true;
require_once("../../polarbear-boot.php");
?>
var swfu;
var currentUploadingFileNum;
var numOfFilesInQUeue;
var errorsOccuredDuringUpload;
var uploadStatus;


// ger id på den fils vars LI är hover
function filesGetSelectedFileID(str) {
	var re = /fileID-([\d]+)/;		
	var res = re.exec(str);
	if (res != null) {
		return res[1];
	} else {
		return false;
	}
	
}


/**
 * lägger till en tag till en fil
 * ...eller tar bort om taggen redan finns
 */
function polarbear_addTagToFile(fileID, tagName) {
	// se till att div med taggar är synlig
	var li = $("li.fileID-"+fileID);
	$("span.tags", li).show();
	
	$.post("<?php polarbear_webpath() ?>gui/files.php", { action: "addTagToFile", tagName: tagName, fileID: fileID }, function(data) {
		// klart, taggen har lagts till i databasen. visa även den för filen
		// är data = 1 så lades taggen till
		// är data = 0 så fanns redan taggen
		//if (data == "1") {
			var tagsDiv = $("span.tags", li).load("<?php polarbear_webpath() ?>gui/files.php", { action: "getTagsLinks", fileID: fileID });

			var selectedTag = $("#polarbear-page-files-nav-tags li.selected a").text();
			var selectedSort = $("#polarbear-page-files-nav-sort li.selected a").text();

			// skicka med: 
			// - file_tag
			// - file_sort
			// - filebrowser_type
			$("#polarbear-page-files-nav-tags").load("<?php polarbear_webpath() ?>gui/files.php", { action: "getNavTags", selectedTag: selectedTag, selectedSort: selectedSort });
			
		//}
	});
	
}

/**
 * lägger till en tag till databasen
 */
function addFileTagToDatabase(tagName) {
	$.post("<?php polarbear_webpath() ?>gui/files.php", { action: "addTagToDatabase", tagName: tagName }, function(data) {
		//alert(data);
	});
}

// todo: ta bort denna?
var arrTags; // array med alla existerande taggar. Verkar inte användas längre. 

/**
 * Körs när sidan med filerna blivit uppdaterad
 * Attach events etc.
 * This one should use delegation instead
 */
function polarbear_files_onload() {

	// create uploadify

	$('#uploadify1').uploadify({
		'uploader':'<?php polarbear_webpath() ?>includes/jquery.uploadify/uploadify.swf',
		'script':'<?php polarbear_webpath() ?>includes/php/files-upload.php',
		'cancelImg':'<?php polarbear_webpath() ?>includes/jquery.uploadify/cancel.png',
		'auto':true,
		multi: true,
		buttonText: "Upload files...",
		onComplete: function(event, queueID, fileObj, response, data) {
			//alert(response);
		},
		onAllComplete: function(event, data) {
			var field_name = $.query.get('field_name');
			var type = $.query.get('type');
			var url = "<?php polarbear_webpath() ?>gui/files.php?uploaded=1&field_name=" + field_name+"&type=" + type;
			$("#polarbear-page-files-content, #tabs-files").load(url, { action: "getFilesTable" }, function() {
				polarbear_files_onload();
			});
		},
		onInit: function() {
			// alert(123);
			// if div #uploadify1Uploader exists that means that the flash was added. if not: show alternative upload form
			setTimeout(function() {
				var flashDiv = $("#uploadify1Uploader");
				// alert(flashDiv.attr("tagName"));
				// object = flash, div = inte flash
				if (flashDiv.attr("tagName") == "DIV") {
					// not flash, show alternative upload form
					$("#pb-files-upload-no-flash").show();
				}
			}, 500);
			
			
		},
		scriptData: {uploaderID: <?php echo (int) $polarbear_u->id ?>}
	});

	// fixa mouse-over på varje li med filer
	/*
	$(".files-fileslist li").hover(function() {
		$(this).addClass("hover");
	}, function() {
		$(this).removeClass("hover");
	});
	*/

	// fixa actions-länkarna för varje fil
	$(".files-fileslist li a.delete").click(function() {
		var li = $(this).parent("div").parent("li");
		var fileID = filesGetSelectedFileID(li.attr("class"));
		var fileName = li.find("span.name").text();
		if (confirm("Delete file \"" + fileName + "\"?\nThis cannot be undone.")) {
			// ok, radera filen
			li.block({ message: "Deleting..." });
			$.post("<?php polarbear_webpath() ?>gui/files.php", { action: "deleteFile", fileID: fileID }, function(data) {
				pb_showMessage("<p>File deleted</p>");
				// Ta bort diven
				li.fadeOut("slow");
			});
		}
		return false;
	});
	
	// fixa edit-in-place med jedit för filnamn
	$(".files-fileslist .name").editable('<?php polarbear_webpath() ?>gui/files.php?action=editName', {
		event		: "click",
		loadurl  	: '<?php polarbear_webpath() ?>gui/files.php?action=editNameGetSource',
		tooltip		: 'Click to edit name',
		cancel		: 'Cancel',
		submit 		: 'Save',
		indicator	: 'Saving...',
		onblur		: "ignore",
		callback	: function() { pb_showMessage("<p>Filename updated</p>"); }
	});
		

	// klick på tags-länken (för att redigara taggar)
	$("div.actions span.actions-tags-wrapper").click(function() {
		var li = $(this).parent("div").parent("li");
		var fileID = filesGetSelectedFileID(li.attr("class"));
		var tagsDiv = $(".the-tags", this);
		if (tagsDiv.is(':visible')) {
			tagsDiv.hide();
		} else {
			// göm ev. andra synliga tag-divvar
			if ($('span.the-tags').is(':visible')) {
				$('span.the-tags:visible').hide();
			}

			//tagsDiv.text("Loading...");
			tagsDiv.load("<?php polarbear_webpath() ?>gui/files.php", { action: "getTagActionsDiv", fileID: fileID }, function() {
				tagsDiv.show();

				// make sure the tag-div is visble and not below the fold
				var docHeight = $(document).height();
				var tagsDivBottom = tagsDiv.height()+tagsDiv.offset().top;
				if (tagsDivBottom > docHeight-20) {
					var diff = docHeight - tagsDivBottom - 40;
					tagsDiv.css("top", diff);
				}
				
				// koppa på lyssnare
				$("a", tagsDiv).click(function() {
					// hämta namnet på taggen
					var tagName = $(this).text();
					// om tagName == "New tag..." ska en ny tag skapas
					// todo: inte särskilt stabilt när polarbear blir flerspråkigt
					if (tagName == "New tag...") {
						tagName = jPrompt("Enter name of new tag", "", "PolarBear CMS", function(r) {
							if (r) {
								// lägg till och uppdatera taggarna för filen, både databasmässigt och visuellt
								polarbear_addTagToFile(fileID, r);
							}
						});
					} else {
						polarbear_addTagToFile(fileID, tagName);
					}
				});
			});
		}
		return false;
	});
	// klick någonstans på dokumentet = göm ev. öppna tag-editors
	$(document).click(function() {
		if ($('span.the-tags').is(':visible')) {
			$('span.the-tags:visible').hide();
		}
	});
	

	/**
	 * When in browser mode, a button to "use this image/file"" will be visible.
	 * Different actions depending on if the browser came from a tiny editor or a field
	*/
	 $(".files-fileslist .button-insert input.fromTiny").click(function() {
		 var fileSrc = $(this).next("[name='file-src']").val();
		 window.tinyUpdate(fileSrc);
	 });
	 $(".files-fileslist .button-insert input.fromField").click(function() {
		var fileSrc = $(this).nextAll("[name='file-src']").val();
		var fileID = $(this).nextAll("[name='file-id']").val();
		var fileName = $(this).nextAll("[name='file-name']").val();
		var fileIcon = $(this).nextAll("[name='file-icon']").val();
		var fieldSourceType = $(this).nextAll("[name='file-sourceType']").val();
		var fieldSourceThumb = $(this).nextAll("[name='file-src-thumb']").val();
		window.polarbearFieldChooseFile({
			src: fileSrc,
			srcThumb: fieldSourceThumb,
			id: fileID,
			name: fileName,
			icon: fileIcon,
			sourceType: fieldSourceType
		});
	 });


}; // end function polarbear_files_onload


/**
 * attach ajax-loader to the links
 * ps. to self: don't call after each reload! doh!
 */
function polarbear_files_create_ajaxlinks(page) {

	if (page == "files") {
		var el = "#polarbear-page-files-content";
	} else if (page == "fileBrowser") {
		var el = "#tabs-files";
	}
	$(el).click(function(e) {
		var target = $(e.target);
		var url = "";
		if ((target.attr("tagName") == "A") && (target.attr("href").substr(0,<?php echo strlen(POLARBEAR_WEBPATH) ?>+19) == "<?php echo POLARBEAR_WEBPATH ?>gui/files.php?page=")) {
			// click on page-num-navigation
			url = target.attr("href");

		} else if ((target.attr("tagName") == "A") && (target.attr("href").substr(0,<?php echo strlen(POLARBEAR_WEBPATH) ?>+25) == "<?php echo POLARBEAR_WEBPATH ?>gui/fileBrowser.php?page=")) {
			url = target.attr("href");
			// we get the wrong URL, change it
			url = url.replace("fileBrowser.php", "files.php");

	 	} else if (target.hasClass("polarbear-files-search-submit")) {
	 		// on search-form submit
	 		// QS must be added here
	 		var qs = $("#polarbear-files-search-qs").val();
			url = "<?php echo polarbear_webpath() ?>gui/files.php?" + qs + "&search=" + $(".polarbear-files-search-text").val();

		} else if (target.hasClass("polarbear-files-ajaxload")) {
			// link with class='polarbear-files-ajaxload'
			url = target.attr("href");

		}
	
		if (url != "") {
			$(el).load(url, { action: "getFilesTable" }, function() {
				polarbear_files_onload();
			});
			return false;
		}
		
	});
} // attach ajaxlinks


/**
 * grab file from file/image browser
 */
function polarbearFieldChooseFile(obj) {
	// find what field the call came from
	var callerDiv = parent.$(".polarbear-article-edit-fields-repeatable div.fieldImageActive, .polarbear-article-edit-fields-nonrepeatable div.fieldImageActive");

	// update image and name
	if (obj.sourceType == "image") {
		callerDiv.find(".polarbear-article-edit-fields-fieldImage-image").html("<img src='" + obj.srcThumb + "' alt='' />");
		callerDiv.find(".polarbear-article-edit-fields-fieldImage-imageName").text(obj.name);
		callerDiv.find(".polarbear-article-edit-fields-fieldImage-value").val(obj.id);
	} else if (obj.sourceType == "file") {
		callerDiv.find(".polarbear-article-edit-fields-fieldImage-image").html("<img src='" + obj.icon + "' alt='' />");
		callerDiv.find(".polarbear-article-edit-fields-fieldImage-imageName").text(obj.name);
		callerDiv.find(".polarbear-article-edit-fields-fieldImage-value").val(obj.id);
	}
	
	parent.$("#polarbear-article-edit-fields-files-dialog").dialog("close");

}

