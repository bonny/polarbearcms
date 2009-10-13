<?php
$polarbear_cache_allow_client_caching = true;
require_once("../../polarbear-boot.php");
?>
/**
 * Class for handling tags
 * Requires jQuery
 * Author: Pär Thernström <par@marsapril.se>
 *
 * Usage is something like this:
 * var t = new majTags();
 * t.attachTo("#tag-container");
 * t.addTag("Blogg", null, 1); // name, parentID, tagID
 * t.addTag("Musik", 1, 2);
 * t.addTag("Söder", 1, 3);
 * t.update();
 *
 * The tags will be available in the hidden input called maj-tags-serialized
 * To use in PHP:
 * $tags = $_POST["maj-tags-serialized"]; parse_str($tags, $arrTags);
 */
var majTags = function () {
	
	var 
		tags = [], 
		selectedTags = [],
		tagsInEdit = [],
		tree_edit,
		element,
		newCount = 0, // antal nya skapade tags, för att få unikt id på alla
		counter = 0,
		newPrio = 0;
	
	tags = $(tags);
	selectedTags = $(selectedTags);
	
	/**
	 * lägger till en existerande eller ny tag
	 * addTag(name, parentID, id, prio);
	 */
	this.addTag = function() {
		var uniqueID;
		
		if (arguments.length==1 || arguments.length==2) {
			uniqueID = "new" + ++newCount;
		}
		
		if (arguments[3] == undefined) {
			newPrio++;
			prio = newPrio;
			//prio = null;
		} else {
			prio = arguments[3];
		}
		
		if (arguments.length==1) {
			// bara namn. ny. på toppnivå.
			tags.push({ name: arguments[0], selected: false, id: uniqueID, parentID: null, deleted: false, prio: prio });
		} else if (arguments.length==2) {
			// ny, men med parent
			tags.push({ name: arguments[0], parentID: arguments[1], selected: false, id: uniqueID, deleted: false, prio: prio });
		} else if (arguments.length==3) {
			// existerande: namn, parentID, id
			tags.push({ name: arguments[0], parentID: arguments[1], id: arguments[2], selected: false, deleted: false, prio: prio });
		}
		//this.alphabetize();
		//this.orderByPrio();
	}
	
	/**
	 * hämtar bockade taggar
	 * tror den är deprecated... se 
	 */
	this.getSelectedTags = function() {
		var selectedTags = {};
		tags.each(function() {
			if (this.selected) {
				 selectedTags[this.name] = 1;
			}
		});
		return $.param(selectedTags);
	}
	
	/**
	 * ger alla taggar
	 */
	this.getTags = function() {
		return tags;
	}
	
	/**
	 * ändrar checkad-värdet på en tag
	 * uppdaterar även serialized-inputen
	 */
	this.toggleTag = function(tagName) {
		// leta upp
		var foundAtPos, t = this;
		tags.each(function(i) {
			if (this.name == tagName) {
				this.selected = !this.selected;
				foundAtPos = i;
				element.find("[name='maj-tags-serialized']").val(t.getSerialized());
				return false;
			}
		});
	}
	
	this.toggleTagById = function(tagId) {
		var foundAtPos, t = this;
		tags.each(function(i) {
			if (this.id == tagId) {
				this.selected = !this.selected;
				foundAtPos = i;
				element.find("[name='maj-tags-serialized']").val(t.getSerialized());
				return false;
			}
		});

	}

	
	/**
	 * attach the tags to an element
	 */
	this.attachTo = function(e) {
		var t = this;
		element = $(e);
		element.html("");
		element.click(function(e, b) {
			// inte funka om man är i redigera-läget
			if (element.find(".maj-tags-link-edit").is(":visible")) {
				if (e.target.tagName == "INPUT") {
					var selectedTagName = $(e.target).val();
					t.toggleTag(selectedTagName);
				}
			}
			//return false;
		});
		element.append("<div class='maj-tags-north'></div>");
		element.append("<div class='maj-tags-south'>" + 
		"<a href='#' class='maj-tags-link-edit'>Edit tags</a> " +
		"<input type='button' style='display: none;' class='maj-tags-link-ok' value='Ok'/> " +
		"<a href='#' style='display: none;' class='maj-tags-link-cancel'>Cancel</a> " +
		"</div>");
		
		element.append("<input type='hidden' name='maj-tags-serialized'></div>");
				
		element.find(".maj-tags-link-edit").click(function() {
			element.find(".maj-tags-link-edit").hide();
			element.find(".maj-tags-link-ok").show();
			element.find(".maj-tags-link-cancel").show();
			t.edit2();
			return false;
		});

		// click ok = updates names and relation to parent
		element.find(".maj-tags-link-ok").click(function() {

			var sel,i ,j, tag;

			element.find(".maj-tags-link-edit").show();
			element.find(".maj-tags-link-ok").hide();
			element.find(".maj-tags-link-cancel").hide();
			
			// update positions/order
			// hur? hämta alla li i trädet, loopa igenom (för vi får väl dom i ordning?)
			// hämta id på varje och öka prio med ett för varje loop
			// uppdatera prio på varje tag i tags
			//var j = tree_edit.getJSON(element.find("div.tree li"));
			var j = tree_edit.get(element.find("div.tree li"), "json");
			j = $(j);
			var prio = 0;
			j.each(function() {
				var nodeID = this.attributes.id.replace("maj-tags-edit-tag-", "");
				var tagPos = getPosById(tagsInEdit, nodeID);
				if (tagPos !== false) {
					tagsInEdit[tagPos].prio = prio;
					prio++;
				}
			});
			
			// save back changes
			tags = [];
			$.extend(true, tags, tagsInEdit);
			t.update();

			return false;
		});

		// click cancel
		element.find(".maj-tags-link-cancel").click(function() {
			element.find(".maj-tags-link-edit").show();
			element.find(".maj-tags-link-ok").hide();
			element.find(".maj-tags-link-cancel").hide();
			t.update();
			return false;
		});

		
	}
	
	// return a tag by id
	this.getTagByID = function(tagID) {
		var tag = null;
		tags.each(function(){
			if (this.id == tagID) {
				tag = this;
				return false;
			}
		});
		return tag;
	}
	
	/**
	 * order the tags by prio
	 */
	this.orderByPrio = function() {
		var 
			sorted = {},
			didSort = true,
			i,
			tmpObj = {};

		while(didSort) {
			didSort = false;
			for (i=0; i<tags.length-1; i++) {
				if (tags[i+1].prio < tags[i].prio) {
					tmpObj = tags[i+1];
					tags[i+1] = tags[i];
					tags[i] = tmpObj;
					didSort = true;
				}
			}
		}
	}
	
	/**
	 * alphabetize the tags
	 * no longer in use since we show tags by prio instead
	 */
	this.alphabetize = function() {
		var 
			sorted = {},
			didSort = true,
			i,
			tmpObj = {};

		while(didSort) {
			didSort = false;
			for (i=0; i<tags.length-1; i++) {
				if (tags[i+1].name < tags[i].name) {
					tmpObj = tags[i+1];
					tags[i+1] = tags[i];
					tags[i] = tmpObj;
					didSort = true;
				}
			}
		}
	}
	

	
	/**
	 * get a ul-li list for a tag
	 */
	this.getList = function(parentID, isEditMode) {
		// skapa li-lista med alla taggar som har parent som parent
		// är parent === undefined så är den roten/huvudnivån
		var html = "",
			t = this,
			checked;
			;
		if (parentID === undefined) {
			parentID = null;
		}
		
		if (isEditMode === undefined) {
			isEditMode = false;
		}

		html += "<ul>";
		
		tags.each(function(i) {

			// ta bara med de som är på översta nivån
			var doAdd = false;
			if (parentID === null && this.parentID === null) {
				doAdd = true;
			} else if (parentID == this.parentID) {
				doAdd = true;
			}
			if (this.deleted == true) {
				doAdd = false;
			}
			if (doAdd) {
				counter++;
				if (this.selected) {
					checked = " checked='checked' "
				} else  {
					checked = "";
				}

				if (isEditMode) {
					html += "<li rel='tag' id='maj-tags-edit-tag-" + this.id + "'>";
					html += "<a href='#' style='background-image: url(<?php polarbear_webpath() ?>/images/silkicons/tag_blue.png);background-repeat: no-repeat;padding-left:20px;'>";
					html += this.name;
					html += "</a>";
				} else {
					html += "<li>";
					html += "<input " + checked + " type='checkbox' id='maj-tags-checkbox-" + this.id + "' value='"+ this.name +"' />";
					html += "<label for='maj-tags-checkbox-" + this.id + "'>" + this.name + "</label>";
				}
				html += t.getList(this.id, isEditMode); // leta childs
				html += "</li>";
			}
		
		});

		html += "</ul>";

		// remove empty <ul></ul>
		html = html.replace("<ul></ul>", "");

		return html;
		
	}
	
	this.update = function() {
		this.orderByPrio();
		var html = this.getList();
		element.find(".maj-tags-north").html(html);
		element.find("[name='maj-tags-serialized']").val(this.getSerialized());
	}

	// gets all tags with allt their properites
	// great for get or post (in combo with php function parse_str)
	this.getSerialized = function() {
		var objTags = {}, i, oneTag;
		for (i=0; i<tags.length; i++) {
			oneTag = tags[i];
			objTags["tags[" + oneTag.id  + "][id]"] = oneTag.id,
			objTags["tags[" + oneTag.id  + "][name]"] = oneTag.name,
			objTags["tags[" + oneTag.id  + "][deleted]"] = oneTag.deleted,
			objTags["tags[" + oneTag.id  + "][selected]"] = oneTag.selected,
			objTags["tags[" + oneTag.id  + "][parentID]"] = oneTag.parentID,
			objTags["tags[" + oneTag.id  + "][prio]"] = oneTag.prio
		}
		return $.param(objTags);
	}


	/**
	 * new edit tags function. uses jstree for drag and drop and stuff. mmmmm... drag n drop!
	 */
	this.edit2 = function() {
		var t = this;
		
		// create copy of tags that we work on in the tree
		// if user click ok = then we overwrite the orginal tags-object
		$.extend(true, tagsInEdit, tags);
		// print out a standard ul-li-tree
		var html;
		html = "<div>";
		html += "<a class='maj-tag-edit-new' href='#'>New</a> ";
		html += "<a href='#' class='maj-tag-edit-rename'>Rename</a> ";
		html += "<a href='#' class='maj-tag-edit-delete'>Delete</a>";
		html += "</div>";
		html += "<div class='maj-tag-edit-tree'>";
		html += "<ul>";
		html += "<li id='maj-tags-edit-roottag' rel='tagroot'><a href='#' style='background-image: url(<?php polarbear_webpath() ?>images/silkicons/tag_blue.png);background-repeat:no-repeat;padding-left: 20px;'>Tags</a>";
		html += t.getList(null, true);
		html += "</li></ul>";
		html += "</div>";
		
		var treeOptions = {
			rules: {
				type_attr 	: "rel",
				deletable	: ["tag"],
				renameable	: ["tag"],
				creatable	: "all",
				draggable	: "all",
				dragrules	: ["tag * tag"],
				createat	: "top"
			},
			ui: {
				xxxcontext: false,
				theme_path: "/maj/includes/jstree/themes/"
			},
			lang: {
				new_node: "New tag"
			},
			xopened: ["maj-tags-edit-roottag"],
			callback: {
				oncreate: function(NODE,REF_NODE,TYPE,TREE_OBJ) {
				
					/*
						type: inside | before
					*/
					var tagName = ($(NODE).find("a:first").text());
					var nodeID = NODE.id.replace("maj-tags-edit-tag-", "");
					var newNodeID = "new" + ++newCount;
					NODE.id = newNodeID;
					var parentID = "";
					if (REF_NODE.id == "maj-tags-edit-roottag") {
						// lägg i rooten
						//tagsInEdit.push({ name: tagName, selected: false, id: newNodeID, parentID: null, deleted: false });
						parentID = null;
					} else if (TYPE == "inside") {
							parentID += REF_NODE.id;
					} else if (TYPE == "before") {
						// parent = samma parent som ref_node
						// denna får inte parentID... hur tar jag reda på den...?
						// jo: REF_NODE:s parent-li:s id
						if ($(REF_NODE).parent().parent().get(0).id == "maj-tags-edit-roottag") {
							parentID = null;
						} else {
							parentID += $(REF_NODE).parent().parent().get(0).id;
						}						
					}
					// maj-tags-edit-tag-new-3
					if (parentID != null) {
						parentID = parentID.replace("maj-tags-edit-tag-", "");
					}
					tagsInEdit.push({ name: tagName, selected: false, id: newNodeID, parentID: parentID, deleted: false });
					
					// börja med att döpa om den nya
					tree_edit.select_branch(NODE);
					tree_edit.rename();

				},
		        onrename: function(NODE,LANG,TREE_OBJ) {
					// node renamed ISNEW - TRUE|FALSE, current language
					var nodeID = NODE.id.replace("maj-tags-edit-tag-", "");
					var nodePos = getPosById(tagsInEdit, nodeID);
					var tagName = ($(NODE).find("a:first").text());
					tagsInEdit[nodePos].name = tagName;
					return true;
				},
		        ondelete: function(NODE, TREE_OBJ) {
					// mark tag as deleted
					var node = NODE;
					//var node = NODE.get(0);
					var nodeID = node.id.replace("maj-tags-edit-tag-", "");
					var nodePos = getPosById(tagsInEdit, nodeID);
					tagsInEdit[nodePos].deleted = true;

					// find and remove all childtags
					var childs = NODE.find("li");
					childs.each(function(){
						var nodeID = $(this).attr("id")
						var nodePos = getPosById(tagsInEdit, nodeID);
						if (nodePos) {
							tagsInEdit[nodePos].deleted = true;
						}
					});
					return true;
				},
				onmove: function(NODE,REF_NODE,TYPE,TREE_OBJ) {

					// move completed (TYPE is BELOW|ABOVE|INSIDE)
					// move tag = update it's parent id
					var newParentID;
					
					var nodeID = NODE.id.replace("maj-tags-edit-tag-", "");
					var nodePos = getPosById(tagsInEdit, nodeID);
					
					var refNodeID = REF_NODE.id.replace("maj-tags-edit-tag-", "");
					var refNodePos = getPosById(tagsInEdit, refNodeID);

					if (TYPE == "inside") {
						//tag a läggs direkt i tag b. Man har dragit och släppt tag a direkt på tag b
						// vår tags parentID ska bli id som ref nodes id
						newParentID = refNodeID;
						
					} else if (TYPE == "after") {
						// tag a läggs efter tag b.
						// vår parent ska bli samma parent som refNode:s parentID
						newParentID = tagsInEdit[refNodePos].parentID;
						
					} else if (TYPE == "before") {
						// tag a läggs före tag b.
						// även här ska vår parent bli samma parent som refNode:s parent
						newParentID = tagsInEdit[refNodePos].parentID;
												
					}

					// ändra parentID på noden man gjort något med
					tagsInEdit[nodePos].parentID = newParentID;
					
					return true;
				}
		        
			}

		}
		
		var tagsNorth = element.find(".maj-tags-north");
		tagsNorth.hide().html(html).fadeIn("fast");
		tree_edit = $.tree.create();
		tree_edit.init(tagsNorth.find(".maj-tag-edit-tree"), treeOptions);
		tree_edit.open_all();
		
		tagsNorth.find(".maj-tag-edit-new").click(function () {
			newCount++;
			var newTagID = "maj-tags-edit-tag-new-" + newCount;
			//tree_edit.create("tag", null, null, "<?php polarbear_webpath() ?>images/silkicons/tag_blue.png", newTagID);
			tree_edit.create({ data: { title:"New tag", icon: "<?php polarbear_webpath() ?>images/silkicons/tag_blue.png"}, attributes: { rel: "tag", id: newTagID }});
			return false;
		});

		tagsNorth.find(".maj-tag-edit-rename").click(function () {
			tree_edit.rename();
			return false;
		});

		tagsNorth.find(".maj-tag-edit-delete").click(function () {
			var selNode = tree_edit.selected;
			if (selNode.attr("id") == "maj-tags-edit-roottag") {
				return false;
			}
			var selNodeName = selNode.find("a:first").text();
			if (confirm("Delete the tag '" + selNodeName + "'?\nIf this tag has any child-tags they will also be removed.")) {
				tree_edit.remove();
			}
			return false;
		});
		
	}

	/**
	 * find a node by id
	 * returns the position if found, or false if not found
	 **/
	getPosById = function(tagObject, tagID) {
		var foundAtPos = false, i=0; 
		tagObject.each(function() {
			if (this.id == tagID) {
				foundAtPos = i;
				return false;
			}
			i++;
		});
		return foundAtPos		
	}
	
}
