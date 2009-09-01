/*
 * jquery.layout 1.3.rc4.2 - Release Candidate 4.2
 *
 * Copyright (c) 2009 
 *   Fabrizio Balliano (http://www.fabrizioballiano.net)
 *   Kevin Dalman (http://allpro.net)
 *
 * Dual licensed under the GPL (http://www.gnu.org/licenses/gpl.html)
 * and MIT (http://www.opensource.org/licenses/mit-license.php) licenses.
 *
 * $Date: 2009-08-25 08:00:00 -0800 (tue, 25 aug 2009) $
 * $Rev: 203 $
 * 
 * NOTE: For best code readability, view this with a fixed-width font and tabs equal to 4-chars
 */
(function($) {

$.fn.layout = function (opts) {

/*
 * ###########################
 *   WIDGET CONFIG & OPTIONS
 * ###########################
 */

	// PREFIX for ALL selectors and classNames
	var prefix = "ui-layout-"; 

	// LANGUAGE CUSTOMIZATION - will be *externally customizable* in next version
	var lang = {
		Pane:	"Pane"
	,	Open:	"Open"	// eg: "Open Panel"
	,	Close:	"Close"
	,	Resize:	"Resize"
	,	Slide:	"Slide Open"
	,	Pin:	"Pin"
	,	Unpin:	"Un-Pin"
	};

	// DEFAULT PANEL OPTIONS - CHANGE IF DESIRED
	var options = {
		name:						""			// Not required, but useful for buttons and used for the state-cookie
	,	scrollToBookmarkOnLoad:		true		// after creating a layout, scroll to bookmark in URL (.../page.htm#myBookmark)
	,	resizeWithWindow:			true		// bind thisLayout.resizeAll() to the window.resize event
	,	resizeWithWindowDelay:		250			// delay calling resizeAll because makes window resizing very jerky
	,	onresizeall_start:			null		// CALLBACK when resizeAll() STARTS	- NOT pane-specific
	,	onresizeall_end:			null		// CALLBACK when resizeAll() ENDS	- NOT pane-specific
	,	autoBindCustomButtons:		true
	//	PANE SETTINGS
	,	defaults: { // default options for 'all panes' - will be overridden by 'per-pane settings'
			applyDefaultStyles: 	false		// apply basic styles directly to resizers & buttons? If not, then stylesheet must handle it
		,	closable:				true		// pane can open & close
		,	resizable:				true		// when open, pane can be resized 
		,	slidable:				true		// when closed, pane can 'slide' open over other panes - closes on mouse-out
		,	initClosed:				false		// true = init pane as 'closed'
		,	initHidden: 			false 		// true = init pane as 'hidden' - no resizer or spacing
		//	SELECTORS
		//,	paneSelector:			[ ]			// MUST be pane-specific!
		,	contentSelector:		"."+prefix+"content"// INNER div/element to auto-size so only it scrolls, not the entire pane!
		,	contentIgnoreSelector:	"."+prefix+"ignore"	// elem(s) to 'ignore' when measuring 'content'
		//	GENERIC ROOT-CLASSES - for auto-generated classNames
		,	paneClass:				prefix+"pane"		// border-Pane - default: 'ui-layout-pane'
		,	resizerClass:			prefix+"resizer"	// Resizer Bar		- default: 'ui-layout-resizer'
		,	togglerClass:			prefix+"toggler"	// Toggler Button	- default: 'ui-layout-toggler'
		,	buttonClass:			prefix+"button"		// CUSTOM Buttons	- default: 'ui-layout-button-toggle/-open/-close/-pin'
		//	ELEMENT SIZE & SPACING
		//,	size:					100			// inital size of pane - defaults are set 'per pane'
		,	minSize:				0			// when manually resizing a pane
		,	maxSize:				0			// ditto, 0 = no limit
		,	spacing_open:			6			// space between pane and adjacent panes - when pane is 'open'
		,	spacing_closed:			6			// ditto - when pane is 'closed'
		,	togglerLength_open:		50			// Length = WIDTH of toggler button on north/south sides - HEIGHT on east/west sides
		,	togglerLength_closed: 	50			// 100% OR -1 means 'full height/width of resizer bar' - 0 means 'hidden'
		,	togglerAlign_open:		"center"	// top/left, bottom/right, center, OR...
		,	togglerAlign_closed:	"center"	// 1 => nn = offset from top/left, -1 => -nn == offset from bottom/right
		,	togglerTip_open:		lang.Close	// Toggler tool-tip (title)
		,	togglerTip_closed:		lang.Open	// ditto
		//	RESIZING OPTIONS
		,	autoResize:				false		// IF size is 'auto' or a percentage, then recalc 'pixel size' whenever the layout resizes
		,	autoReopen:				false		// IF a pane was auto-closed due to noRoom, reopen it when there is room? False = leave it closed
		,	resizerDragOpacity:		1			// option for ui.draggable
		//,	resizerCursor:			""			// MUST be pane-specific - cursor when over resizer-bar
		,	maskIframesOnResize:	true		// true = all iframes OR = iframe-selector(s) - adds masking-div during resizing/dragging
		,	resizeWhileDragging:	false		// true = LIVE Resizing as resizer is dragged
		//,	paneTooBigAction:		"resize"	// ('resize' or 'close') What to do if pane is 'too big', but NOT 'noRoom'
		//,	noRoomToOpenAction:		"close"		// ('close' or 'hide') What to do when no room to open a pane at minSize
		//	TIPS & MESSAGES
		,	noRoomToOpenTip:		"Not enough room to open this "+ lang.Pane.toLowerCase() +"."
		,	resizerTip:				lang.Resize	// Resizer tool-tip (title)
		,	sliderTip:				lang.Slide // resizer-bar triggers 'sliding' when pane is closed
		,	sliderCursor:			"pointer"	// cursor when resizer-bar will trigger 'sliding'
		,	slideTrigger_open:		"click"		// click, dblclick, mouseover
		,	slideTrigger_close:		"mouseout"	// click, mouseout
		,	hideTogglerOnSlide:		false		// when pane is slid-open, should the toggler show?
		,	togglerContent_open:	""			// text or HTML to put INSIDE the toggler
		,	togglerContent_closed:	""			// ditto
		//	HOT-KEYS & MISC
		,	showOverflowOnHover:	false		// will bind allowOverflow() utility to pane.onMouseOver
		,	enableCursorHotkey:		true		// enabled 'cursor' hotkeys
		//,	customHotkey:			""			// MUST be pane-specific - EITHER a charCode OR a character
		,	customHotkeyModifier:	"SHIFT"		// either 'SHIFT', 'CTRL' or 'CTRL+SHIFT' - NOT 'ALT'
		//	PANE ANIMATION
		//	NOTE: fxSss_open & fxSss_close options (eg: fxName_open) are auto-generated if not passed
		,	fxName:					"slide" 	// ('none' or blank), slide, drop, scale
		,	fxSpeed:				null		// slow, normal, fast, 200, nnn - if passed, will OVERRIDE fxSettings.duration
		,	fxSettings:				{}			// can be passed, eg: { easing: "easeOutBounce", duration: 1500 }
		//	CALLBACKS
		,	triggerEventsOnLoad:	true		// true = trigger onopen OR onclose callbacks when layout initializes
		,	onshow_start:			null		// CALLBACK when pane STARTS to Show	- BEFORE onopen/onhide_start
		,	onshow_end:				null		// CALLBACK when pane ENDS being Shown	- AFTER  onopen/onhide_end
		,	onhide_start:			null		// CALLBACK when pane STARTS to Close	- BEFORE onclose_start
		,	onhide_end:				null		// CALLBACK when pane ENDS being Closed	- AFTER  onclose_end
		,	onopen_start:			null		// CALLBACK when pane STARTS to Open
		,	onopen_end:				null		// CALLBACK when pane ENDS being Opened
		,	onclose_start:			null		// CALLBACK when pane STARTS to Close
		,	onclose_end:			null		// CALLBACK when pane ENDS being Closed
		,	onresize_start:			null		// CALLBACK when pane STARTS to be ***MANUALLY*** Resized
		,	onresize_end:			null		// CALLBACK when pane ENDS being Resized ***FOR ANY REASON***
		}
	,	north: {
			paneSelector:			"."+prefix+"north" // default = .ui-layout-north
		,	size:					"auto"
		,	resizerCursor:			"n-resize"
		}
	,	south: {
			paneSelector:			"."+prefix+"south" // default = .ui-layout-south
		,	size:					"auto"
		,	resizerCursor:			"s-resize"
		}
	,	east: {
			paneSelector:			"."+prefix+"east" // default = .ui-layout-east
		,	size:					200
		,	resizerCursor:			"e-resize"
		}
	,	west: {
			paneSelector:			"."+prefix+"west" // default = .ui-layout-west
		,	size:					200
		,	resizerCursor:			"w-resize"
		}
	,	center: {
			paneSelector:			"."+prefix+"center" // default = .ui-layout-center
		,	minWidth:				0
		,	minHeight:				0
		}

	//	STATE MANAGMENT
	,	autoStateManagement:		false		// Enable cookie - can fine-tune with cookie.load/.save
	,	cookie: {								// State Managment options
			name:					""			// If not specified, will use Layout.name, else just "Layout"
		,	autoLoad:				true		// Load the state cookie when Layout inits?
		,	autoSave:				true		// Save a state cookie when page exits?
		//	Cookie Options
		,	domain:					""
		,	path:					""
		,	expires:				""			// 'days' to keep cookie - leave blank for 'session cookie'
		,	secure:					false
		//	List of options to save in the cookie - must be pane-specific
		,	keys:					"north.size,south.size,east.size,west.size,"+
									"north.isClosed,south.isClosed,east.isClosed,west.isClosed,"+
									"north.isHidden,south.isHidden,east.isHidden,west.isHidden"
		}
	};


	var effects = { // LIST *PREDEFINED EFFECTS* HERE, even if effect has no settings
		slide:	{
			all:	{ duration:  "fast"	} // eg: duration: 1000, easing: "easeOutBounce"
		,	north:	{ direction: "up"	}
		,	south:	{ direction: "down"	}
		,	east:	{ direction: "right"}
		,	west:	{ direction: "left"	}
		}
	,	drop:	{
			all:	{ duration:  "slow"	} // eg: duration: 1000, easing: "easeOutQuint"
		,	north:	{ direction: "up"	}
		,	south:	{ direction: "down"	}
		,	east:	{ direction: "right"}
		,	west:	{ direction: "left"	}
		}
	,	scale:	{
			all:	{ duration:  "fast"	}
		}
	};


	// DYNAMIC DATA
	var state = {
		// generate random 'ID#' to identify layout - used to create global namespace for timers
		id:			Math.floor(Math.random() * 10000)
	,	container:	{}
	,	north:		{}
	,	south:		{}
	,	east:		{}
	,	west:		{}
	,	center:		{}
	,	cookie:		{} // State Managment data storage
	};


	// STATIC, INTERNAL CONFIG - DO NOT CHANGE THIS!
	var config = {
		namespace:		"layout"+ state.id
	,	allPanes:		"north,south,west,east,center"
	,	borderPanes:	"north,south,west,east"
	//	CSS used in multiple places
	,	hidden:			{ visibility: "hidden" }
	,	visible:		{ visibility: "visible" }
	,	zIndex: { // set z-index values here
			resizer_normal:	1		// normal z-index for resizer-bars
		,	pane_normal:	2		// normal z-index for panes
		,	iframe_mask:	4		// overlay div used to mask pane(s) during resizing
		,	pane_sliding:	100		// applied to *BOTH* the pane and its resizer when a pane is 'slid open'
		,	pane_animate:	1000	// applied to the pane when being animated - not applied to the resizer
		,	resizer_drag:	10000	// applied to the CLONED resizer-bar when being 'dragged'
		}
	,	resizers: {
			cssReq: {
				position: 	"absolute"
			,	padding: 	0
			,	margin: 	0
			,	fontSize:	"1px"
			,	textAlign:	"left" // to counter-act "center" alignment!
			,	overflow: 	"hidden" // keep toggler button from overflowing
			,	zIndex: 	1
			}
		,	cssDef: { // DEFAULT CSS - applied if: options.PANE.applyDefaultStyles=true
				background: "#DDD"
			,	border:		"none"
			}
		}
	,	togglers: {
			cssReq: {
				position: 	"absolute"
			,	display: 	"block"
			,	padding: 	0
			,	margin: 	0
			,	overflow:	"hidden"
			,	textAlign:	"center"
			,	fontSize:	"1px"
			,	cursor: 	"pointer"
			,	zIndex: 	1
			}
		,	cssDef: { // DEFAULT CSS - applied if: options.PANE.applyDefaultStyles=true
				background: "#AAA"
			}
		}
	,	content: {
			cssReq: {
				overflow:	"auto"
			}
		,	cssDef: {}
		}
	,	defaults: { // defaults for ALL panes - overridden by 'per-pane settings' below
			cssReq: {
				position: 	"absolute"
			,	margin:		0
			,	zIndex: 	2
			}
		,	cssDef: {
				padding:	"10px"
			,	background:	"#FFF"
			,	border:		"1px solid #BBB"
			,	overflow:	"auto"
			}
		}
	,	north: {
			side:			"Top"
		,	sizeType:		"Height"
		,	dir:			"horz"
		,	cssReq: {
				top: 		0
			,	bottom: 	"auto"
			,	left: 		0
			,	right: 		0
			,	width: 		"auto"
			//	height: 	DYNAMIC
			}
		}
	,	south: {
			side:			"Bottom"
		,	sizeType:		"Height"
		,	dir:			"horz"
		,	cssReq: {
				top: 		"auto"
			,	bottom: 	0
			,	left: 		0
			,	right: 		0
			,	width: 		"auto"
			//	height: 	DYNAMIC
			}
		}
	,	east: {
			side:			"Right"
		,	sizeType:		"Width"
		,	dir:			"vert"
		,	cssReq: {
				left: 		"auto"
			,	right: 		0
			,	top: 		"auto" // DYNAMIC
			,	bottom: 	"auto" // DYNAMIC
			,	height: 	"auto"
			//	width: 		DYNAMIC
			}
		}
	,	west: {
			side:			"Left"
		,	sizeType:		"Width"
		,	dir:			"vert"
		,	cssReq: {
				left: 		0
			,	right: 		"auto"
			,	top: 		"auto" // DYNAMIC
			,	bottom: 	"auto" // DYNAMIC
			,	height: 	"auto"
			//	width: 		DYNAMIC
			}
		}
	,	center: {
			dir:			"center"
		,	cssReq: {
				left: 		"auto" // DYNAMIC
			,	right: 		"auto" // DYNAMIC
			,	top: 		"auto" // DYNAMIC
			,	bottom: 	"auto" // DYNAMIC
			,	height: 	"auto"
			,	width: 		"auto"
			}
		}
	};


	var 
		altEdge = {
			top:	"bottom"
		,	bottom: "top"
		,	left:	"right"
		,	right:	"left"
		}
	,	altSide = {
			north:	"south"
		,	south:	"north"
		,	east: 	"west"
		,	west: 	"east"
		}
	;


/*
 * ###########################
 *  INTERNAL HELPER FUNCTIONS
 * ###########################
 */

	/**
	 * isStr
	 *
	 * Returns true if passed param is EITHER a simple string OR a 'string object' - otherwise returns false
	 */
	var isStr = function (o) {
		if (typeof o == "string")
			return true;
		else if (typeof o == "object") {
			try {
				var match = o.constructor.toString().match(/string/i); 
				return (match !== null);
			} catch (e) {} 
		}
		return false;
	};

	/**
	 * str
	 *
	 * Returns a simple string if the passed param is EITHER a simple string OR a 'string object',
	 *  else returns the original object
	 */
	var str = function (o) {
		if (typeof o == "string" || isStr(o)) return $.trim(o); // trim converts 'String object' to a simple string
		else return o;
	};

	/**
	 * min / max / floor
	 *
	 * Aliases for Math methods to simplify coding
	 */
	var min =	function (x,y) { return Math.min(x,y); };
	var max =	function (x,y) { return Math.max(x,y); };
	var floor =	function (x,y) { return Math.floor(x,y); };

	/**
	 * _transformData
	 *
	 * Processes the options passed in and transforms them into the format used by layout()
	 * Missing keys are added, and converts the data if passed in 'flat-format' (no sub-keys)
	 * In flat-format, pane-specific-settings are prefixed like: north__optName  (2-underscores)
	 * To update effects, options MUST use nested-keys format, with an effects key ???
	 *
	 * @callers	initOptions()
	 * @params  JSON	d	Data/options passed by user - may be a single level or nested levels
	 * @returns JSON		Creates a data struture that perfectly matches 'options', ready to be imported
	 */
	var _transformData = function (d) {
		var json = { cookie:{}, defaults:{fxSettings:{}}, north:{fxSettings:{}}, south:{fxSettings:{}}, east:{fxSettings:{}}, west:{fxSettings:{}}, center:{fxSettings:{}} };
		d = d || {};
		if (d.effects || d.cookie || d.defaults || d.north || d.south || d.west || d.east || d.center)
			json = $.extend( true, json, d ); // already in json format - add to base keys
		else
			// convert 'flat' to 'nest-keys' format - also handles 'empty' user-options
			$.each( d, function (key,val) {
				a = key.split("__");
				if (!a[1] || json[a[0]]) // check for invalid keys
					json[ a[1] ? a[0] : "defaults" ][ a[1] ? a[1] : a[0] ] = val;
			});
		return json;
	};

	/**
	 * _queue
	 *
	 * Set an INTERNAL callback to avoid simultaneous animation
	 * Runs only if needed and only if all callbacks are not 'already set'!
	 *
	 * @param String   action  Either 'open' or 'close'
	 * @pane  String   pane    A valid border-pane name, eg 'west'
	 * @pane  Boolean  param   Extra param for callback (optional)
	 */
	var _queue = function (action, pane, param) {
		var
			cb = action +","+ pane +","+ (param ? 1 : 0)
		,	cP, cbPane
		;
		$.each(c.borderPanes.split(","), function (i,p) {
			if (c[p].isMoving) {
				bindCallback(p); // TRY to bind a callback
				return false; // BREAK
			}
		});

		function bindCallback (p, test) {
			cP = c[p];
			if (!cP.doCallback) {
				cP.doCallback = true;
				cP.callback = cb;
			}
			else { // try to 'chain' this callback
				cpPane = cP.callback.split(",")[1]; // 2nd param is 'pane'
				if (cpPane != p && cpPane != pane) // callback target NOT 'itself' and NOT 'this pane'
					bindCallback(cpPane, true); // RECURSE
			}
		}
	};

	/**
	 * _dequeue
	 *
	 * RUN the INTERNAL callback for this pane - if one exists
	 *
	 * @param String   action  Either 'open' or 'close'
	 * @pane  String   pane    A valid border-pane name, eg 'west'
	 * @pane  Boolean  param   Extra param for callback (optional)
	 */
	var _dequeue = function (pane) {
		var cP = c[pane];

		// RESET flow-control flags
		c.isLayoutBusy = false;
		delete cP.isMoving;
		if (!cP.doCallback || !cP.callback) return;

		cP.doCallback = false; // RESET logic flag

		// EXECUTE the callback
		var
			cb = cP.callback.split(",")
		,	param = (cb[2] > 0 ? true : false)
		;
		if (cb[0] == "open")
			open( cb[1], param  );
		else if (cb[0] == "close")
			close( cb[1], param );

		if (!cP.doCallback) cP.callback = null; // RESET - unless callback above enabled it again!
	};

	/**
	 * _execCallback
	 *
	 * Executes a Callback function after a trigger event, like resize, open or close
	 *
	 * @param String  pane   This is passed only so we can pass the 'pane object' to the callback
	 * @param String  v_fn  Accepts a function name, OR a comma-delimited array: [0]=function name, [1]=argument
	 */
	var _execCallback = function (pane, v_fn) {
		if (!v_fn) return;
		var fn;
		try {
			if (typeof v_fn == "function")
				fn = v_fn;	
			else if (typeof v_fn != "string")
				return;
			else if (v_fn.indexOf(",") > 0) {
				// function name cannot contain a comma, so must be a function name AND a 'name' parameter
				var
					args = v_fn.split(",")
				,	fn = eval(args[0])
				;
				if (typeof fn=="function" && args.length > 1)
					return fn(args[1]); // pass the argument parsed from 'list'
			}
			else // just the name of an external function?
				fn = eval(v_fn);

			if (typeof fn=="function") {
				if (pane && $Ps[pane])
					// pass data: pane-name, pane-element, pane-state, pane-options, and layout-name
					return fn( pane, $Ps[pane], $.extend({},state[pane]), $.extend({},options[pane]), options.name );
				else // must be a layout/container callback - pass suitable info
					return fn( 'container', $Container, $.extend({},state.container), $.extend({},options), options.name );
			}
		}
		catch (ex) {}
	};

	/**
	 * _showInvisibly
	 *
	 * Returns hash container 'display' and 'visibility'
	 */
	var _showInvisibly = function ($E, force) {
		if (!$E) return {};
		if (!$E.jquery) $E = $($E);
		var CSS = {
			display:	$E.css('display')
		,	visibility:	$E.css('visibility')
		};
		if (force || CSS.display == "none") { // only if not *already hidden*
			$E.css({ display: "block", visibility: "hidden" }); // show element 'invisibly' so can be measured
			return CSS;
		}
		else return {};
	};

	/**
	 * cssNum
	 *
	 * Returns the 'current CSS value' for an element - returns 0 if property does not exist
	 *
	 * @callers  Called by many methods
	 * @param jQuery  $Elem  Must pass a jQuery object - first element is processed
	 * @param String  property  The name of the CSS property, eg: top, width, etc.
	 * @returns Variant  Usually is used to get an integer value for position (top, left) or size (height, width)
	 */
	var cssNum = function ($E, prop) {
		if (!$E.jquery) $E = $($E);
		var CSS = _showInvisibly($E);
		var val = parseInt($.curCSS($E[0], prop, true), 10) || 0;
		$E.css( CSS ); // RESET
		return val;
	};

	var _borderWidth = function (E, side) {
		if (E.jquery) E = E[0];
		var b = "border"+ side.substr(0,1).toUpperCase() + side.substr(1); // left => Left
		return $.curCSS(E, b+"Style", true) == "none" ? 0 : (parseInt($.curCSS(E, b+"Width", true), 10) || 0);
	};

	/**
	 * cssW / cssH / cssSize
	 *
	 * Contains logic to check boxModel & browser, and return the correct width/height for the current browser/doctype
	 *
	 * @callers  initPanes(), sizeMidPanes(), initHandles(), sizeHandles()
	 * @param Variant  elem  Can accept a 'pane' (east, west, etc) OR a DOM object OR a jQuery object
	 * @param Integer  outerWidth/outerHeight  (optional) Can pass a width, allowing calculations BEFORE element is resized
	 * @returns Integer  Returns the innerWidth/Height of the elem by subtracting padding and borders
	 *
	 * @TODO  May need additional logic for other browser/doctype variations? Maybe use more jQuery methods?
	 */
	var cssW = function (e, outerWidth) {
		var $E;
		if (isStr(e)) {
			e = str(e);
			$E = $Ps[e];
		}
		else
			$E = $(e);

		if (isNaN(outerWidth)) // not specified
			outerWidth = isStr(e) ? getPaneSize(e) : $E.outerWidth();

		// a 'calculated' outerHeight can be passed so borders and/or padding are removed if needed
		if (outerWidth <= 0) return 0;

		if (!$.boxModel) return outerWidth;

		// strip border and padding size from outerWidth to get CSS Width
		var W = outerWidth
			- cssNum($E, "paddingLeft")		
			- cssNum($E, "paddingRight")
			- _borderWidth($E, "Left")
			- _borderWidth($E, "Right")
		;
		return W > 0 ? W : 0;
	};

	var cssH = function (e, outerHeight) {
		var $E;
		if (isStr(e)) {
			e = str(e);
			$E = $Ps[e];
		}
		else
			$E = $(e);

		if (isNaN(outerHeight)) // not specified
			outerHeight = (isStr(e)) ? getPaneSize(e) : $E.outerHeight();

		// a 'calculated' outerHeight can be passed so borders and/or padding are removed if needed
		if (outerHeight <= 0) return 0;

		if (!$.boxModel) return outerHeight;

		// strip border and padding size from outerHeight to get CSS Height
		var H = outerHeight
			- cssNum($E, "paddingTop")
			- cssNum($E, "paddingBottom")
			- _borderWidth($E, "Top")
			- _borderWidth($E, "Bottom")
		;
		return H > 0 ? H : 0;
	};

	var cssSize = function (pane, outerSize) {
		if (c[pane].dir=="horz") // pane = north or south
			return cssH(pane, outerSize);
		else // pane = east or west
			return cssW(pane, outerSize);
	};

	var cssMinSize = function (pane) {
		return 1001 - cssSize(pane, 1000); // width/height = 1px
	};

	// TODO: see if these methods are useful...

	var setOuterWidth = function (e, outerWidth, autoHide) {
		var $E = e, w;
		if (typeof e == "string") $E = $Ps[e]; // west
		else if (!e.jquery) $E = $(e);
		w = cssW($E, outerWidth);
		$E.css({ width: w });
		if (w > 0) {
			if (autoHide && $E.data('autoHidden') && $E.innerHeight() > 0) {
				$E.show().data('autoHidden', false);
				if (!$.browser.mozilla) // FireFox refreshes iframes - IE doesn't
					$E.css(c.hidden).css(c.visible);
			}
		}
		else if (autoHide && !$E.data('autoHidden'))
			$E.hide().data('autoHidden', true);
	};

	var setOuterHeight = function (e, outerHeight, autoHide) {
		var $E = e;
		if (typeof e == "string") $E = $Ps[e]; // west
		else if (!e.jquery) $E = $(e);
		h = cssH($E, outerHeight);
		$E.css({ height: h });
		if (h > 0) {
			if (autoHide && $E.data('autoHidden') && $E.innerWidth() > 0) {
				$E.show().data('autoHidden', false);
				if (!$.browser.mozilla) // FireFox refreshes iframes - IE doesn't
					$E.css(c.hidden).css(c.visible);
			}
		}
		else if (autoHide && !$E.data('autoHidden'))
			$E.hide().data('autoHidden', true);
	};

	var setOuterSize = function (e, outerSize, autoHide) {
		if (c[pane].dir=="horz") // pane = north or south
			setOuterHeight(e, outerSize, autoHide);
		else // pane = east or west
			setOuterWidth(e, outerSize, autoHide);
	};


	/**
	 * parseSize
	 *
	 * Converts any 'size' params to a pixel/integer size, if not already
	 * If 'auto' or a decimal/percentage is passed as 'size', a pixel-size is calculated
	 *
	 * @returns Integer
	 */
	var parseSize = function (pane, size, dir) {
		if (!dir) dir = c[pane].dir;

		if (typeof size=='string' && size.indexOf('%') > 0)
			size = parseInt(size) / 100; // convert % to decimal

		if (size === 0)
			return 0;
		else if (size >= 1)
			return parseInt(size,10);
		else if (size > 0) { // percentage, eg: .25
			var o = options, avail;
			if (dir=="horz") // north or south or center.minHeight
				avail = sC.innerHeight - ($Ps.north ? o.north.spacing_open : 0) - ($Ps.south ? o.south.spacing_open : 0);
			else if (dir=="vert") // east or west or center.minWidth
				avail = sC.innerWidth - ($Ps.west ? o.west.spacing_open : 0) - ($Ps.east ? o.east.spacing_open : 0);
			return floor(avail * size);
		}
		else if (pane=="center")
			return 0;
		else { // size < 0 || size=='auto' || size==Missing || size==Invalid
			// auto-size the pane
			var
				$P	= $Ps[pane]
			,	dim	= (dir == "horz" ? "height" : "width")
			,	vis	= _showInvisibly($P) // show pane invisibly if hidden
			,	s	= $P.css(dim); // SAVE current size
			;
			$P.css(dim, "auto");
			size = (dim == "height") ? $P.outerHeight() : $P.outerWidth(); // MEASURE
			$P.css(dim, s).css(vis); // RESET size & visibility
			return size;
		}
	};

	/**
	 * getPaneSize
	 *
	 * Calculates the current 'size' (width or height) of a border-pane - optionally with 'pane spacing' added
	 *
	 * @returns Integer  Returns EITHER Width for east/west panes OR Height for north/south panes - adjusted for boxModel & browser
	 */
	var getPaneSize = function (pane, inclSpace) {
		var 
			$P	= $Ps[pane]
		,	o	= options[pane]
		,	s	= state[pane]
		,	oSp	= (inclSpace ? o.spacing_open : 0)
		,	cSp	= (inclSpace ? o.spacing_closed : 0)
		;
		if (!$P || s.isHidden)
			return 0;
		else if (s.isClosed || (s.isSliding && inclSpace))
			return cSp;
		else if (c[pane].dir == "horz")
			return $P.outerHeight() + oSp;
		else // dir == "vert"
			return $P.outerWidth() + oSp;
	};

	/**
	 * setSizeLimits
	 *
	 */
	var setSizeLimits = function (pane, slide) {
		var 
			o				= options[pane]
		,	s				= state[pane]
		,	cP				= c[pane]
		,	dir				= cP.dir
		,	side			= cP.side.toLowerCase()
		,	type			= cP.sizeType.toLowerCase()
		,	isSliding		= (slide != undefined ? slide : s.isSliding) // only open() passes 'slide' param
		,	$P				= $Ps[pane]
		,	paneSpacing		= o.spacing_open
		//	measure the pane on the *opposite side* from this pane
		,	altPane			= altSide[pane]
		,	altS			= state[altPane]
		,	$altP			= $Ps[altPane]
		,	altPaneSize		= (!$altP || altS.isVisible===false || altS.isSliding ? 0 : (dir=="horz" ? $altP.outerHeight() : $altP.outerWidth()))
		,	altPaneSpacing	= (!$altP || altS.isHidden ? 0 : options[altPane][ altS.isClosed !== false ? "spacing_closed" : "spacing_open" ]) || 0
		//	limitSize prevents this pane from 'overlapping' opposite pane
		,	containerSize	= (dir=="horz" ? sC.innerHeight : sC.innerWidth)
		,	minCenterSize	= options.center[dir=="horz" ? "minHeight" : "minWidth"]
		//	if pane is 'sliding', then ignore center and alt-pane sizes - because 'overlays' them
		,	limitSize		= containerSize - paneSpacing - (isSliding ? 0 : (parseSize("center", minCenterSize, dir) + altPaneSize + altPaneSpacing))
		,	minSize			= s.minSize = max( parseSize(pane, o.minSize), cssMinSize(pane) )
		,	maxSize			= s.maxSize = min( parseSize(pane, o.maxSize) || 100000, limitSize )
		,	r				= s.resizerPosition = {} // used to set resizing limits
		,	top				= sC.insetTop
		,	left			= sC.insetLeft
		,	W				= sC.innerWidth
		,	H				= sC.innerHeight
		,	rW				= o.spacing_open // subtract resizer-width to get top/left position for south/east
		;
		switch (pane) {
			case "north":	r.min = top + minSize;
							r.max = top + maxSize;
							break;
			case "west":	r.min = left + minSize;
							r.max = left + maxSize;
							break;
			case "south":	r.min = top + H - maxSize - rW;
							r.max = top + H - minSize - rW;
							break;
			case "east":	r.min = left + W - maxSize - rW;
							r.max = left + W - minSize - rW;
							break;
		};
	};

	/**
	 * calcNewCenterPaneDims
	 *
	 * Returns data for setting the size/position of center pane. Also used to set Height for east/west panes
	 *
	 * @returns JSON  Returns a hash of all dimensions: top, bottom, left, right, (outer) width and (outer) height
	 */
	var calcNewCenterPaneDims = function () {
		var d = {
			top:	getPaneSize("north", true) // true = include 'spacing' value for pane
		,	bottom:	getPaneSize("south", true)
		,	left:	getPaneSize("west", true)
		,	right:	getPaneSize("east", true)
		,	width:	0
		,	height:	0
		};

		with (d) {
			width	= sC.innerWidth - left - right;  // center.outerWidth
			height	= sC.innerHeight - bottom - top; // center.outerHeight
			// now add the 'container border/padding' to get final positions - relative to the container
			top		+= sC.insetTop;
			bottom	+= sC.insetBottom;
			left	+= sC.insetLeft;
			right	+= sC.insetRight;
		}

		return d;
	};


	/**
	 * getElemDims
	 *
	 * Returns data for setting size of an element (container or a pane).
	 *
	 * @callers  create(), onWindowResize() for container, plus others for pane
	 * @returns JSON  Returns a hash of all dimensions: top, bottom, left, right, outerWidth, innerHeight, etc
	 */
	var getElemDims = function ($E) {
		var
			d = {}				// dimensions hash
		,	x = d.css = {}		// CSS hash
		,	i = {}				// TEMP insets
		,	e, b, p				// TEMP side, border, padding
		,	o = $E.offset()
		;

		$.each("Left,Right,Top,Bottom".split(","), function () {
			e = str(this);
			b = x["border" +e] = _borderWidth($E, e);
			p = x["padding"+e] = cssNum($E, "padding"+e);
			i[e] = b + p; // total offset of content from outer side
			// if BOX MODEL, then 'position' = PADDING (ignore borderWidth)
			if ($E == $Container)
				d["inset"+ e] = ($.boxModel ? p : 0); 
		});

		d.innerWidth	= d.outerWidth  = $E.outerWidth();
		d.offsetWidth	= $E.innerWidth(true); // true=include Padding
		d.innerHeight	= d.outerHeight = $E.outerHeight();
		d.offsetHeight	= $E.innerHeight(true);
		if ($.boxModel) {
			d.innerWidth  -= (i.Left + i.Right);
			d.innerHeight -= (i.Top  + i.Bottom);
		}

		d.offsetLeft	= o.left;
		d.offsetTop		= o.top;

		// TESTING
		x.width  = $E.width();
		x.height = $E.height();

		return d;
	};


	var setTimer = function (pane, action, fn, ms) {
		var
			Layout = window.layout = window.layout || {}
		,	Timers = Layout.timers = Layout.timers || {}
		,	name = "layout_"+ state.id +"_"+ pane +"_"+ action // UNIQUE NAME for every layout-pane-action
		;
		if (Timers[name]) return; // timer already set!
		else Timers[name] = setTimeout(fn, ms);
	};

	var clearTimer = function (pane, action) {
		var
			Layout = window.layout = window.layout || {}
		,	Timers = Layout.timers = Layout.timers || {}
		,	name = "layout_"+ state.id +"_"+ pane +"_"+ action // UNIQUE NAME for every layout-pane-action
		;
		if (Timers[name]) {
			clearTimeout( Timers[name] );
			delete Timers[name];
			return true;
		}
		else
			return false;
	};


	var getHoverClasses = function (el, allStates) {
		var
			$El		= $(el)
		,	classes	= ""
		,	_hover	= "-hover " // Note trailing space
		,	_open	= "-open"
		,	_closed	= "-closed"
		,	_slide	= "-sliding"
		,	_pane
		,	_state
		,	_alt
		,	root
		,	type
		;
		if		($El.attr("pane"))		{ type = "pane";	root = o.paneClass;		}
		else if	($El.attr("resizer"))	{ type = "resizer";	root = o.resizerClass;	}
		else if	($El.attr("toggler"))	{ type = "toggler";	root = o.togglerClass;	}
		else							return "";

		_pane	= "-"+ $El.attr(type); // eg: "-west"
		_state	= $El.hasClass(root +_closed) ? _closed : _open;
		_alt	= _state == _closed ? _open : _closed;

		classes = (root+_hover) + (root+_pane+_hover) + (root+_state+_hover) + (root+_pane+_state+_hover);

		if (allStates) // when 'removing' classes, remove BOTH 'states'
			classes += (root+_alt+_hover) + (root+_pane+_alt+_hover)

		if (type=="resizer" && $El.hasClass(root+_slide))
			classes += (root+_slide+_hover) + (root+_pane+_slide+_hover);

		return $.trim(classes);
	};
	var addHover	= function (evt, el) { $(el || this).addClass( getHoverClasses(el || this) ); };
	var removeHover	= function (evt, el) { $(el || this).removeClass( getHoverClasses(el || this, true) ); };

/*
 * ###########################
 *   INITIALIZATION METHODS
 * ###########################
 */

	/**
	 * create
	 *
	 * Initialize the layout - called automatically whenever an instance of layout is created
	 *
	 * @callers  NEVER explicity called
	 * @returns  An object pointer to the instance created
	 */
	var create = function () {
		// initialize config/options
		initOptions();
		var o = options;
		if (o.autoStateManagement && o.cookie.autoLoad)
			_loadState();

		// initialize all objects
		initContainer();	// set CSS as needed and init state.container dimensions
		initPanes();		// size & position all panes
		//initHandles();		// create and position all resize bars & togglers buttons
		initResizable();	// activate resizing on all panes where resizable=true
		sizeContent("all");	// AFTER panes & handles have been initialized, size 'content' divs

		if (o.scrollToBookmarkOnLoad)
			with (self.location) if (hash) replace( hash ); // scrollTo Bookmark

		// search for and bind custom-buttons
		if (o.autoBindCustomButtons) initButtons();

		// bind hotkey function - keyDown - if required
		initHotkeys();
		// track mouse position so we can use it anytime we need it
		initMouseTracking();

		// bind resizeAll() for 'this layout instance' to window.resize event
		if (o.resizeWithWindow && !$Container.attr("isLayoutPane")) // skip if 'nested' inside a pane
			$(window).bind("resize."+c.namespace, windowResize);

		// bind saveState to window.onunload
		if (o.autoStateManagement)
			$(window).bind("unload."+c.namespace, function(){
				// check in case options were changed after init
				var o = options;
				if (o.autoStateManagement && o.cookie.autoSave) saveState();
			});
	};

	var windowResize = function () {
		// resizing use a delay because the resize event fires repeatly
		var ID = "timerLayout_"+state.id;
		if (window[ID]) clearTimeout(window[ID]);
		window[ID] = setTimeout(resizeAll, Number(options.resizeWithWindowDelay));
		//self.location = '#count='+ (window.counter ? ++window.counter : (window.counter=1)); // DEBUG
	};


	/**
	 *	initMouseTracking / trackMouse / isMouseOver
	 *
	 *	Bound to document.mousemove - updates window.mouseCoords.X/Y
	 */
	var initMouseTracking = function () {
			if (!window.mouseCoords) { // only need 1 mouse tracker!
				window.mouseCoords = { X: 0, Y: 0 } // init
				$(document).bind("mousemove."+c.namespace, trackMouse);
			}
		};
	var trackMouse = function (evt) {
			var m = window.mouseCoords;
			m.X = evt.pageX;
			m.Y = evt.pageY;
		};
	var isMouseOver = function (el) {
			var $E	= (typeof(el == "string") ? $Ps[el] : $(el));
			if (!$E.length) return false;
			var
				_	= this
			,	d	= $E.offset()
			,	T	= d.top
			,	L	= d.left
			,	R	= L + $E.outerWidth()
			,	B	= T + $E.outerHeight()
			,	m	= window.mouseCoords
			;
			return ((m.X >= L && m.X <= R) && (m.Y >= T && m.Y <= B));
		};

	/**
	 *	swapPanes
	 *
	 *	Move a pane from source-side (eg, west) to target-side (eg, east)
	 *	If pane exists on target-side, move that to source-side, ie, 'swap' the panes
	 */
	var swapPanes = function (pane1, pane2) {
		var
			oPane1	= copy( pane1 )
		,	oPane2	= copy( pane2 )
		,	sizes	= {}
		;
		sizes[pane1] = oPane1 ? oPane1.state.size : 0;
		sizes[pane2] = oPane2 ? oPane2.state.size : 0;

		// clear pointers & state
		$Ps[pane1] = false;
		$Ps[pane2] = false;
		state[pane1] = {};
		state[pane2] = {};
		
		// transfer element pointers and data to NEW Layout keys
		move( oPane1, pane2 );
		move( oPane2, pane1 );

		if (!$Ps[pane1] && $Rs[pane1]) {
			$Rs[pane1].remove();
			$Rs[pane1] = false;
			$Ts[pane1] = false;
		}

		if (!$Ps[pane2] && $Rs[pane2]) {
			$Rs[pane2].remove();
			$Rs[pane2] = false;
			$Ts[pane2] = false;
		}

		// resize layout - will move all elements as needed
		resizeAll();
		return;

		function copy (n) { // n = pane
			var
				$P	= $Ps[n]
			,	$C	= $Cs[n]
			;
			return !$P ? false : {
				pane:		n
			,	P:			$P ? $P[0] : false
			,	C:			$C ? $C[0] : false
			,	state:		$.extend({}, state[n])
			,	options:	$.extend({}, options[n])
			}
		};

		function move (oPane, pane) {
			if (!oPane) return;
			var
				P		= oPane.P
			,	C		= oPane.C
			,	oldPane = oPane.pane
			,	cP		= c[pane]
			,	side	= cP.side.toLowerCase()
			,	inset	= "inset"+ cP.side
			//	save pane-options that should be retained
			,	s		= $.extend({}, state[pane])
			,	o		= options[pane]
			,	retainOptions = {
					resizerCursor:		o.resizerCursor
				,	fxName:				o.fxName
				,	fxSpeed:			o.fxSpeed
				,	fxSettings:			o.fxSettings
				,	fxName_open:		o.fxName
				,	fxSpeed_open:		o.fxSpeed
				,	fxSettings_open:	o.fxSettings
				,	fxName_close:		o.fxName
				,	fxSpeed_close:		o.fxSpeed
				,	fxSettings_close:	o.fxSettings
				}
			,	re, size, pos
			;
			// set object pointers and update attributes
			$Ps[pane] = $(P).attr("pane", pane).css(cP.cssReq);
			$Cs[pane] = C ? $(C) : false;

			// set options and state
			options[pane]	= $.extend({}, oPane.options, retainOptions);
			state[pane]		= $.extend({}, oPane.state);

			// change classNames on the pane, eg: ui-layout-pane-east ==> ui-layout-pane-west
			re = new RegExp("pane-"+ oldPane, "g");
			P.className = P.className.replace(re, "pane-"+ pane);

			if (!$Rs[pane]) {
				initHandles(pane); // create the required resizer & toggler
				initResizable(pane);
			}

			// if moving to different orientation, then keep 'target' pane size
			if (cP.dir != c[oldPane].dir) {
				size = sizes[pane] || 0;
				setSizeLimits(pane); // update pane-state
				size = max(size, state[pane].minSize);
				sizePane(pane, size, true); // true = skipCallback
			}
			else // move the resizer here
				$Rs[pane].css(side, sC[inset] + (state[pane].isVisible ? getPaneSize(pane) : 0));

			// ADD CLASSNAMES & SLIDE-BINDINGS
			if (oPane.state.isVisible && !s.isVisible)
				setAsOpen(pane, true); // true = onInit to disable callbacks
			else {
				setAsClosed(pane, true); // true = onInit to disable callbacks
				bindStartSlidingEvent(pane, true); // will enable events IF option is set
			}

			// DESTROY the object
			oPane = null;
		};
	};


	/**
	 *	destroy
	 *
	 *	Destroy this layout and reset all elements
	 */
	var destroy = function () {
		// UNBIND layout events
		$(window).unbind("."+c.namespace);
		$(document).unbind("."+c.namespace);

		var
			isFullPage	= (sC.tagName == "BODY")
		//	create list of ALL pane-classes that need to be removed
		,	root	= o.paneClass // default="ui-layout-pane"
		,	_open	= "-open"
		,	_sliding= "-sliding"
		,	_closed	= "-closed"
		,	generic = [ root, root+_open, root+_closed, root+_sliding ] // generic classes
		,	$P, pane, pRoot, pClasses // loop vars
		;
		// loop all panes to remove layout classes, attributes and bindings
		$.each(c.allPanes.split(","), function() {
			pane	= str(this);
			$P		= $Ps[pane];
			if (!$P) return true; // no pane - SKIP

			// REMOVE pane's resizer and toggler elements
			if (pane != "center") {
				$Ts[pane].remove();
				$Rs[pane].remove();
			}

			pRoot = root+"-"+pane; // eg: "ui-layout-pane-west"
			pClasses = []; // reset
			pClasses.push( pRoot );
			pClasses.push( pRoot+_open );
			pClasses.push( pRoot+_closed );
			pClasses.push( pRoot+_sliding );

			$.merge(pClasses, generic); // ADD generic classes
			$.merge(pClasses, getHoverClasses($P, true)); // ADD hover-classes

			$P
				.removeClass( pClasses.join(" ") ) // remove ALL pane-classes
				.removeAttr( "pane" ) // remove layout attribute
				.unbind( "."+ c.namespace ) // remove ALL Layout events
			;

			// do NOT reset CSS if this pane is STILL the container of a nested layout!
			// the nested layout will reset its 'container' when/if it is destroyed
			if (!$P.attr("isLayoutContainer"))
				$P.css( $P.data("preLayoutCSS") );
		});

		// reset layout-container
		$Container
			.removeAttr("isLayoutContainer")
			.removeAttr("layout") // is this used?
		;
		// do NOT reset container CSS if is a 'pane' in an outer-layout - ie, THIS layout is 'nested'
		if (!$Container.attr("pane"))
			$Container.css( $Container.data("preLayoutCSS") ); // RESET CSS
		// for full-page layouts, must also reset the <HTML> CSS
		if (isFullPage)
			$("html").css( $("html").data("preLayoutCSS") ); // RESET CSS

		var n = options.name; // layout-name
		if (n && window[n]) window[n] = null; // clear window object, if exists
	};

	/**
	 * initContainer
	 *
	 * Validate and initialize container CSS and events
	 *
	 * @callers  create()
	 */
	var initContainer = function () {
		sC.tagName	= $Container.attr("tagName");
		var isFullPage = (sC.tagName == "BODY");

		$Container.attr("isLayoutContainer", 1);

		// SAVE original container CSS for use in destroy()
		if (!$Container.data("preLayoutCSS")) {
			var
				style	= $Container[0].style
			,	props	= "position,margin,padding,border"
			,	CSS		= {}
			;
			// handle props like overflow different for BODY & HTML - has 'system default' values
			if (isFullPage) {
				CSS = {
					height:		$Container.css("height")
				,	overflow:	$Container.css("overflow")
				,	overflowX:	$Container.css("overflowX")
				,	overflowY:	$Container.css("overflowY")
				};
				// ALSO SAVE <HTML> CSS
				var $HTML = $("html");
				$HTML.data("preLayoutCSS", {
					height:		"auto" // FF would return a fixed px-size!
				,	overflow:	$HTML.css("overflow")
				,	overflowX:	$HTML.css("overflowX")
				,	overflowY:	$HTML.css("overflowY")
				});
			}
			else // handle props normally for non-body elements
				props += ",height,overflow,overflowX,overflowY";

			// loop and save properties
			$.each(props.split(","),function(i, prop){
				if ("border,padding,margin".indexOf(prop) >= 0)
					$.each("Top,Bottom,Left,Right".split(","),function(ii,side){
						CSS[prop+side] = style[prop+side];
					});
				else
					CSS[prop] = style[prop];
			});
			$Container.data("preLayoutCSS", CSS);
		}

		try { // format html/body if this is a full page layout
			if (isFullPage) {
				$("html").css({
					height:		"100%"
				,	overflow:	"hidden"
				,	overflowX:	"hidden"
				,	overflowY:	"hidden"
				});
				$("body").css({
					position:	"relative"
				,	height:		"100%"
				,	overflow:	"hidden"
				,	overflowX:	"hidden"
				,	overflowY:	"hidden"
				,	margin:		0
				,	padding:	0		// TODO: test whether body-padding could be handled?
				,	border:		"none"	// a body-border creates problems because it cannot be measured!
				});
			}
			else { // set required CSS for overflow and position
				var
					CSS	= { overflow: "hidden" } // make sure container will not 'scroll'
				,	p	= $Container.css("position")
				,	h	= $Container.css("height")
				;
				// if this is a NESTED layout, then container/outer-pane ALREADY has position and height
				if (!$Container.attr("pane")) {
					if (!p || "fixed,absolute,relative".indexOf(p) < 0)
						CSS.position = "relative"; // container MUST have a 'position'
					if (!h || h=="auto")
						CSS.height = "100%"; // container MUST have a 'height'
				}
				$Container.css( CSS );
				if ($Container.is(":visible") && $Container.innerHeight() < 2)
					alert( 'UI.Layout Initialization Error\n\nThe layout-container "'+ $Container[0].tagName + ($Container.selector || '') +'" has no height!' );
			}
		} catch (ex) {}

		// set current layout-container dimensions
		$.extend( state.container, getElemDims( $Container ), true );
	};

	/**
	 * initHotkeys
	 *
	 * Bind layout hotkeys - if options enabled
	 *
	 * @callers  create()
	 */
	var initHotkeys = function () {
		// bind keyDown to capture hotkeys, if option enabled for ANY pane
		$.each(c.borderPanes.split(","), function (i,pane) {
			var o = options[pane];
			if (o.enableCursorHotkey || o.customHotkey) {
				$(document).bind("keydown."+c.namespace, keyDown); // only need to bind this ONCE
				return false; // BREAK - binding was done
			}
		});
	};

	/**
	 * initOptions
	 *
	 * Build final CONFIG and OPTIONS data
	 *
	 * @callers  create()
	 */
	var initOptions = function () {
		// simplify logic by making sure passed 'opts' var has basic keys
		opts = _transformData( opts );

		// update default effects, if case user passed key
		if (opts.effects) {
			$.extend( effects, opts.effects );
			delete opts.effects;
		}
		$.extend( options.cookie, opts.cookie );

		// see if any 'global options' were specified
		var globals = "name,scrollToBookmarkOnLoad,resizeWithWindow,resizeWithWindowDelay,"+
			"onresizeall,onresizeall_start,onresizeall_end,autoBindCustomButtons,autoStateManagement";
		$.each(globals.split(","), function (idx,key) {
			if (opts[key] !== undefined)
				options[key] = opts[key];
			else if (opts.defaults[key] !== undefined) {
				options[key] = opts.defaults[key];
				delete opts.defaults[key];
			}
		});

		// remove any 'defaults' that MUST be set 'per-pane'
		$.each("paneSelector,resizerCursor,customHotkey".split(","),
			function (idx,key) { delete opts.defaults[key]; } // is OK if key does not exist
		);

		// now update options.defaults
		$.extend( true, options.defaults, opts.defaults );

		// merge all config & options for the 'center' pane
		c.center = $.extend( true, {}, c.defaults, c.center );
		$.extend( options.center, opts.center );
		// Most 'default options' do not apply to 'center', so add only those that DO
		var o_Center = $.extend( true, {}, options.defaults, opts.defaults, options.center ); // TEMP data
		$.each("paneClass,contentSelector,contentIgnoreSelector,applyDefaultStyles,showOverflowOnHover,triggerEventsOnLoad".split(","),
			function (idx,key) { options.center[key] = o_Center[key]; }
		);

		var defs = options.defaults;

		// create a COMPLETE set of options for EACH border-pane
		$.each(c.borderPanes.split(","), function(i,pane) {
			// apply 'pane-defaults' to CONFIG.PANE
			c[pane] = $.extend( true, {}, c.defaults, c[pane] );
			// apply 'pane-defaults' +  user-options to OPTIONS.PANE
			o = options[pane] = $.extend( true, {}, options.defaults, options[pane], opts.defaults, opts[pane] );

			// make sure we have base-classes
			if (!o.paneClass)		o.paneClass		= prefix +"pane";
			if (!o.resizerClass)	o.resizerClass	= prefix +"resizer";
			if (!o.togglerClass)	o.togglerClass	= prefix +"toggler";

			// create FINAL fx options for each pane, ie: options.PANE.fxName/fxSpeed/fxSettings[_open|_close]
			$.each(["_open","_close",""], function (i,n) { 
				var
					sName		= "fxName"+n
				,	sSpeed		= "fxSpeed"+n
				,	sSettings	= "fxSettings"+n
				;
				// recalculate fxName according to specificity rules
				o[sName] =
					opts[pane][sName]		// opts.west.fxName_open
				||	opts[pane].fxName		// opts.west.fxName
				||	opts.defaults[sName]	// opts.defaults.fxName_open
				||	opts.defaults.fxName	// opts.defaults.fxName
				||	o[sName]				// options.west.fxName_open
				||	o.fxName				// options.west.fxName
				||	defs[sName]				// options.defaults.fxName_open
				||	defs.fxName				// options.defaults.fxName
				||	"none"
				;
				// validate fxName to be sure is a valid effect
				var fxName = o[sName];
				if (fxName == "none" || !$.effects || !$.effects[fxName] || (!effects[fxName] && !o[sSettings] && !o.fxSettings))
					fxName = o[sName] = "none"; // effect not loaded, OR undefined FX AND fxSettings not passed
				// set vars for effects subkeys to simplify logic
				var
					fx = effects[fxName]	|| {} // effects.slide
				,	fx_all	= fx.all		|| {} // effects.slide.all
				,	fx_pane	= fx[pane]		|| {} // effects.slide.west
				;
				// RECREATE the fxSettings[_open|_close] keys using specificity rules
				o[sSettings] = $.extend(
					{}
				,	fx_all						// effects.slide.all
				,	fx_pane						// effects.slide.west
				,	defs.fxSettings || {}		// options.defaults.fxSettings
				,	defs[sSettings] || {}		// options.defaults.fxSettings_open
				,	o.fxSettings				// options.west.fxSettings
				,	o[sSettings]				// options.west.fxSettings_open
				,	opts.defaults.fxSettings	// opts.defaults.fxSettings
				,	opts.defaults[sSettings] || {} // opts.defaults.fxSettings_open
				,	opts[pane].fxSettings		// opts.west.fxSettings
				,	opts[pane][sSettings] || {}	// opts.west.fxSettings_open
				);
				// recalculate fxSpeed according to specificity rules
				o[sSpeed] =
					opts[pane][sSpeed]		// opts.west.fxSpeed_open
				||	opts[pane].fxSpeed		// opts.west.fxSpeed (pane-default)
				||	opts.defaults[sSpeed]	// opts.defaults.fxSpeed_open
				||	opts.defaults.fxSpeed	// opts.defaults.fxSpeed
				||	o[sSpeed]				// options.west.fxSpeed_open
				||	o[sSettings].duration	// options.west.fxSettings_open.duration
				||	o.fxSpeed				// options.west.fxSpeed
				||	o.fxSettings.duration	// options.west.fxSettings.duration
				||	defs.fxSpeed			// options.defaults.fxSpeed
				||	defs.fxSettings.duration// options.defaults.fxSettings.duration
				||	fx_pane.duration		// effects.slide.west.duration
				||	fx_all.duration			// effects.slide.all.duration
				||	"normal"				// DEFAULT
				;
			});
		});
	};

	/**
	 * initPanes
	 *
	 * Initialize module objects, styling, size and position for all panes
	 *
	 * @callers  create()
	 */
	var initPanes = function () {
		// NOTE: do north & south FIRST so we can measure their height - do center LAST
		$.each(c.allPanes.split(","), function() {
			var
				pane	= str(this)
			,	o		= options[pane]
			,	s		= state[pane]
			,	cP		= config[pane]
			,	fx		= s.fx
			,	dir		= cP.dir
			,	sel		= o.paneSelector
			,	spacing	= o.spacing_open || 0
			,	isCenter = (pane == "center")
			,	isIE6	= ($.browser.msie && $.browser.version < 7)
			,	CSS		= {}
			,	$P, $C
			,	size, minSize, maxSize
			;
			$Cs[pane] = false; // init

			if (sel.substr(0,1)==="#") // ID selector
				// NOTE: elements selected 'by ID' DO NOT have to be 'children'
				$P = $Ps[pane] = $Container.find(sel+":first");
			else { // class or other selector
				$P = $Ps[pane] = $Container.children(sel+":first");
				// look for the pane nested inside a 'form' element
				if (!$P.length) $P = $Ps[pane] = $Container.children("form:first").children(sel+":first");
			}

			if (!$P.length) {
				$Ps[pane] = false; // logic
				return true; // SKIP to next
			}

			// SAVE original Pane CSS
			if (!$P.data("preLayoutCSS")) {
				var
					pCSS	= {}
				,	style	= $P[0].style
				,	props	= "position,top,left,bottom,right,overflow,zIndex,display,width,height,padding,margin,backgroundColor,border"
				;
				$.each(props.split(","),function(i, prop){
					if ("border,padding,margin".indexOf(prop) >= 0)
						$.each("Top,Bottom,Left,Right".split(","),function(ii,side){
							if (prop == "border")
								$.each("Color,Style,Width".split(","),function(iii,attr){
									pCSS[prop+side+attr] = style[prop+side+attr];
								});
							else
								pCSS[prop+side] = style[prop+side];
						});
					else
						pCSS[prop] = style[prop];
				});
				$P.data( "preLayoutCSS", pCSS );
			}

			// add basic classes & attributes
			$P
				.attr("isLayoutPane", 1)
				.attr("pane", pane)		// pane-identifier
				.css(c.defaults.cssReq)	// pane-default styles
				.css(cP.cssReq)	// pane-specifid styles
				.css(o.applyDefaultStyles ? cP.cssDef : {}) // demo styles
				.addClass( o.paneClass +" "+ o.paneClass+"-"+pane ) // default = "ui-layout-pane ui-layout-pane-west" - may be a dupe of 'paneSelector'
				.bind("mouseenter."+c.namespace, addHover ) // namespace used by destroy()
				.bind("mouseleave."+c.namespace, removeHover )
			;

			if (!isCenter) {
				// call parseSize AFTER applying pane classes & styles - but before making visible (if hidden)
				// if o.size is auto or not valid, then MEASURE the pane and use that as it's 'size'
				size	= s.size = parseSize(pane,o.size);
				minSize	= parseSize(pane,o.minSize) || 1;
				maxSize	= parseSize(pane,o.maxSize) || 100000;
			}

			// init pane-logic vars
				s.tagName	= $P.attr("tagName");
				s.noRoom	= false; // true = pane 'automatically' hidden due to insufficient room - will unhide automatically
				s.isVisible	= true;  // false = pane is invisible - closed OR hidden - simplify logic
			if (!isCenter) {
				s.isClosed  = false; // true = pane is closed
				s.isSliding = false; // true = pane is currently open by 'sliding' over adjacent panes
				s.isResizing= false; // true = pane is in process of being resized
				s.isHidden	= false; // true = pane is hidden - no spacing, resizer or toggler is visible!
				// create special keys for internal use
				cP.pins = [];   // used to track and sync 'pin-buttons' for border-panes
			}

			// set css-position to account for container borders & padding
			switch (pane) {
				case "north": 	CSS.top 	= sC.insetTop;
								CSS.left 	= sC.insetLeft;
								CSS.right	= sC.insetRight;
								break;
				case "south": 	CSS.bottom	= sC.insetBottom;
								CSS.left 	= sC.insetLeft;
								CSS.right 	= sC.insetRight;
								break;
				case "west": 	CSS.left 	= sC.insetLeft; // top, bottom & height set by sizeMidPanes()
								break;
				case "east": 	CSS.right 	= sC.insetRight; // ditto
								break;
				case "center":	// top, left, width & height set by sizeMidPanes()
			}

			if (dir == "horz") // north or south pane
				CSS.height = max(1, cssH(pane, size));
				//if (isIE6) CSS.width = cssW($P, sC.innerWidth); // handle IE6
			else if (dir == "vert") // east or west pane
				CSS.width = max(1, cssW(pane, size));
			//else if (isCenter) {}

			$P.css(CSS); // apply size -- top, bottom & height will be set by sizeMidPanes
			if (dir != "horz") sizeMidPanes(pane, null, true); // true = onInit

			// NOW make the pane visible - in case was initially hidden
			$P.css({ visibility: "visible", display: "block" });

			// close or hide the pane if specified in settings
			if (o.initClosed && o.closable)
				close(pane, true, true, true); // true = onInit
			else if (o.initHidden || o.initClosed)
				hide(pane, true); // will be completely invisible - no resizer or spacing
			// ELSE setAsOpen() - called later by initHandles()

			// check option for auto-handling of pop-ups & drop-downs
			if (o.showOverflowOnHover)
				$P.hover( allowOverflow, resetOverflow );

			/*
			 *	see if this pane has a 'content element' that we need to auto-size
			 */
			if (o.contentSelector) {
				$C = $Cs[pane] = $P.children(o.contentSelector+":first"); // match 1-element only
				if (!$C.length)
					$Cs[pane] = false;
				else {
					$C.css( c.content.cssReq );
					if (o.applyDefaultStyles) $C.css( c.content.cssDef ); // cosmetic defaults
					// NO PANE-SCROLLING when there is a content-div
					$P.css("overflow","hidden");
					// sizeContent() is called later
				}
			}
		});

		/*
		 *	init the pane-handles NOW in case we have to hide or close the pane below
		 */
		initHandles();

		// make sure there is enough space available for each pane pane
		$.each(c.borderPanes.split(","), function (idx, pane) {
			if ($Ps[pane] && state[pane].isVisible) { // pane is OPEN
				setSizeLimits(pane);
				makePaneFit(pane); // pane may be Closed, Hidden or Resized by makePaneFit()
			}
		});

		// size center-pane AGAIN in case we 'closed' a border-pane in loop above
		sizeMidPanes("center", null, true); // true = onInit

		// border-pane callbacks are triggered in setAsOpen, but need to handle center-pane here
		var o = options.center;
		if (state.center.isVisible && o.triggerEventsOnLoad)
			_execCallback('center', o.onresize_end || o.onresize); // call onresize
	};

	/**
	 * initHandles
	 *
	 * Initialize module objects, styling, size and position for all resize bars and toggler buttons
	 *
	 * @callers  create()
	 */
	var initHandles = function (panes) {
		if (!panes || panes == "all") panes = c.borderPanes;

		// create toggler DIVs for each pane, and set object pointers for them, eg: $R.north = north toggler DIV
		$.each(panes.split(","), function() {
			var
				pane	= str(this)
			,	$P		= $Ps[pane]
			;
			$Rs[pane] = false; // INIT
			$Ts[pane] = false;
			if (!$P) return; // pane does not exist - skip

			var 
				o		= options[pane]
			,	s		= state[pane]
			,	cP		= config[pane]
			,	rClass	= o.resizerClass
			,	tClass	= o.togglerClass
			,	side	= cP.side.toLowerCase()
			,	spacing	= (s.isVisible ? o.spacing_open : o.spacing_closed)
			,	_pane	= "-"+ pane // used for classNames
			,	_state	= (s.isVisible ? "-open" : "-closed") // used for classNames
				// INIT RESIZER BAR
			,	$R		= $Rs[pane] = $("<div></div>")
				// INIT TOGGLER BUTTON
			,	$T		= (o.closable ? $Ts[pane] = $("<div></div>") : false)
			;
			if (!cP.pins) cP.pins = [];

			if (s.isVisible && o.resizable)
				; // handled by initResizable
			else if (!s.isVisible && o.slidable)
				$R.attr("title", o.sliderTip).css("cursor", o.sliderCursor);

			$R
				// if paneSelector is an ID, then create a matching ID for the resizer, eg: "#paneLeft" => "paneLeft-resizer"
				.attr("id", (o.paneSelector.substr(0,1)=="#" ? o.paneSelector.substr(1) + "-resizer" : ""))
				.attr("resizer", pane) // so we can read this from the resizer
				.css(c.resizers.cssReq) // add base/required styles
				.css(o.applyDefaultStyles ? c.resizers.cssDef : {}) // add demo styles
				.addClass(rClass +" "+ rClass+_pane)
				.appendTo($Container) // append DIV to container
				.mouseenter( addHover )
				.mouseleave( removeHover )
			;

			if ($T) {
				$T
					// if paneSelector is an ID, then create a matching ID for the resizer, eg: "#paneLeft" => "#paneLeft-toggler"
					.attr("id", (o.paneSelector.substr(0,1)=="#" ? o.paneSelector.substr(1) + "-toggler" : ""))
					.attr("toggler", pane) // so we can read this from the toggler
					.css(c.togglers.cssReq) // add base/required styles
					.css(o.applyDefaultStyles ? c.togglers.cssDef : {}) // add demo styles
					.addClass(tClass +" "+ tClass+_pane)
					.appendTo($R) // append SPAN to resizer DIV
					.click(function(evt){ toggle(pane); evt.stopPropagation(); })
					.mouseenter( addHover )
					.mouseleave( removeHover )
				;
				// ADD INNER-SPANS TO TOGGLER
				if (o.togglerContent_open) // ui-layout-open
					$("<span>"+ o.togglerContent_open +"</span>")
						.addClass("content content-open")
						.css("display","none")
						.appendTo( $T )
						.mouseenter( addHover )
						.mouseleave( removeHover )
					;
				if (o.togglerContent_closed) // ui-layout-closed
					$("<span>"+ o.togglerContent_closed +"</span>")
						.addClass("content content-closed")
						.css("display","none")
						.appendTo( $T )
						.mouseenter( addHover )
						.mouseleave( removeHover )
					;
			}

			// ADD CLASSNAMES & SLIDE-BINDINGS - eg: class="resizer resizer-west resizer-open"
			if (s.isVisible)
				setAsOpen(pane, true); // true = onInit
			else {
				setAsClosed(pane, true); // true = onInit
				bindStartSlidingEvent(pane, true); // will enable events IF option is set
			}

		});

		// SET ALL HANDLE DIMENSIONS
		sizeHandles("all", true); // true = onInit
	};

	var initButtons = function () {
		var pre	= prefix +"button-", name;
		$.each("toggle,open,close,pin".split(","), function (i, action) {
			$.each(c.borderPanes.split(","), function (ii, pane) {
				$("."+pre+action+"-"+pane).each(function(){
					// if button was previously 'bound', a "layout" attribute was added by getBtn()
					name = $(this).attr("layout") || $(this).attr("rel");
					if (name == options.name || name == undefined)
						bindButton(this, action, pane);
				});
			});
		});
	};

	/**
	 * initResizable
	 *
	 * Add resize-bars to all panes that specify it in options
	 *
	 * @dependancies  $.fn.resizable - will abort if not found
	 * @callers  create()
	 */
	var initResizable = function (panes) {
		var
			draggingAvailable = (typeof $.fn.draggable == "function")
		,	$Frames, side // set in start()
		;
		if (!panes || panes == "all") panes = c.borderPanes;

		$.each(panes.split(","), function() {
			var 
				pane	= str(this)
			,	o		= options[pane]
			,	s		= state[pane]
			,	cP		= config[pane]
			,	side	= (cP.dir=="horz" ? "top" : "left")
			,	r, live // set in start because may change
			;
			if (!draggingAvailable || !$Ps[pane] || !o.resizable) {
				o.resizable = false;
				return true; // skip to next
			}

			var 
				$P 		= $Ps[pane]
			,	$R		= $Rs[pane]
			,	base	= o.resizerClass
			//	'drag' classes are applied to the ORIGINAL resizer-bar while dragging is in process
			,	resizerClass		= base+"-drag"				// resizer-drag
			,	resizerPaneClass	= base+"-"+pane+"-drag"		// resizer-north-drag
			//	'helper' class is applied to the CLONED resizer-bar while it is being dragged
			,	helperClass			= base+"-dragging"			// resizer-dragging
			,	helperPaneClass		= base+"-"+pane+"-dragging" // resizer-north-dragging
			,	helperLimitClass	= base+"-dragging-limit"	// resizer-drag
			,	helperClassesSet	= false 					// logic var
			;

			if (!s.isClosed)
				$R
					.attr("title", o.resizerTip)
					.css("cursor", o.resizerCursor) // n-resize, s-resize, etc
				;

			$R.draggable({
				containment:	$Container[0] // limit resizing to layout container
			,	axis:			(cP.dir=="horz" ? "y" : "x") // limit resizing to horz or vert axis
			,	delay:			100
			,	distance:		1
			//	basic format for helper - style it using class: .ui-draggable-dragging
			,	helper:			"clone"
			,	opacity:		o.resizerDragOpacity
			,	addClasses:		false // avoid ui-state-disabled class when disabled
			//,	iframeFix:		o.draggableIframeFix // TODO: consider using when bug is fixed
			,	zIndex:			c.zIndex.resizer_drag

			,	start: function (e, ui) {
					// REFRESH options & state pointers in case we used swapPanes
					o = options[pane];
					s = state[pane];
					// re-read options
					live = o.resizeWhileDragging;

					// onresize_start callback - will CANCEL hide if returns false
					// TODO: CONFIRM that dragging can be cancelled like this???
					if (false === _execCallback(pane, o.onresize_start)) return false;

					s.isResizing = true; // prevent pane from closing while resizing
					clearTimer(pane, "closeSlider"); // just in case already triggered

					// SET RESIZER LIMITS - used in drag()
					setSizeLimits(pane); // update pane/resizer state
					r = s.resizerPosition;

					$R.addClass( resizerClass +" "+ resizerPaneClass ); // add drag classes
					helperClassesSet = false; // reset logic var - see drag()

					// MASK PANES WITH IFRAMES OR OTHER TROUBLESOME ELEMENTS
					$Frames = $(o.maskIframesOnResize === true ? "iframe" : o.maskIframesOnResize).filter(":visible");
					var id, i=0; // ID incrementer - used when 'resizing' masks during dynamic resizing
					$Frames.each(function() {					
						id = "ui-layout-mask-"+ (++i);
						$(this).data("maskID", id); // tag iframe with corresponding maskID
						$('<div id="'+ id +'" class="ui-layout-mask ui-layout-mask-'+ pane +'"/>')
							.css({
								background:	"#fff"
							,	opacity:	"0.001"
							,	zIndex:		c.zIndex.iframe_mask
							,	position:	"absolute"
							,	width:		this.offsetWidth+"px"
							,	height:		this.offsetHeight+"px"
							})
							.css($(this).position()) // top & left -- changed from offset()
							.appendTo(this.parentNode) // put mask-div INSIDE pane to avoid zIndex issues
						;
					});
				}

			,	drag: function (e, ui) {
					if (!helperClassesSet) { // can only add classes after clone has been added to the DOM
						//$(".ui-draggable-dragging")
						ui.helper
							.addClass( helperClass +" "+ helperPaneClass ) // add helper classes
							.children().css("visibility","hidden") // hide toggler inside dragged resizer-bar
						;
						helperClassesSet = true;
						// draggable bug!? RE-SET zIndex to prevent E/W resize-bar showing through N/S pane!
						if (s.isSliding) $Ps[pane].css("zIndex", c.zIndex.pane_sliding);
					}
					// CONTAIN RESIZER-BAR TO RESIZING LIMITS
					var limit = 0;
					if (ui.position[side] < r.min) {
						ui.position[side] = r.min;
						limit = -1;
					}
					else if (ui.position[side] > r.max) {
						ui.position[side] = r.max;
						limit = 1;
					}
					// ADD/REMOVE dragging-limit CLASS
					if (limit) {
						ui.helper.addClass( helperLimitClass ) // at dragging-limit
						window.defaultStatus = "Panel has reached its "+ (limit>0 ? "maximum" : "minimum") +" size";
					}
					else {
						ui.helper.removeClass( helperLimitClass ) // not at dragging-limit
						window.defaultStatus = "";
					}
					// DYNAMICALLY RESIZE PANES IF OPTION ENABLED
					if (live) resizePanes(e, ui, pane);
				}

			,	stop: function (e, ui) {
					window.defaultStatus = ""; // clear 'resizing limit' message from statusbar
					$R.removeClass( resizerClass +" "+ resizerPaneClass +" "+ helperLimitClass ); // remove drag classes from Resizer
					resizePanes(e, ui, pane, true); // true = resizingDone
					s.isResizing = false;
				}

			});

			/**
			 * resizePanes
			 *
			 * Sub-routine called from stop() and optionally drag()
			 */
			var resizePanes = function (e, ui, pane, resizingDone) {
				var 
					dragPos	= ui.position
				,	cP		= config[pane]
				,	resizerPos, newSize
				,	i = 0 // ID incrementer
				;
				switch (pane) {
					case "north":	resizerPos = dragPos.top; break;
					case "west":	resizerPos = dragPos.left; break;
					case "south":	resizerPos = sC.offsetHeight - dragPos.top  - o.spacing_open; break;
					case "east":	resizerPos = sC.offsetWidth  - dragPos.left - o.spacing_open; break;
				};

				// remove container margin from resizer position to get the pane size
				newSize = resizerPos - sC["inset"+ cP.side];
				sizePane(pane, newSize);

				if (resizingDone) {
					// after 'manually resizing', turn off autoResize
					o.autoResize = false;
					// Remove OR Resize MASK(S) created in drag.start
					$("div.ui-layout-mask").each(function() { this.parentNode.removeChild(this); });
					//$("div.ui-layout-mask").remove(); // TODO: Is this less efficient?
				}
				else
					$Frames.each(function() {
						$("#"+ $(this).data("maskID")) // get corresponding mask by ID
							.css($(this).position()) // update top & left
							.css({ // update width & height
								width:	this.offsetWidth +"px"
							,	height:	this.offsetHeight+"px"
							})
						;
					});
			}
		});
	};


/*
 * ###########################
 *       ACTION METHODS
 * ###########################
 */

	/**
	 * hide / show
	 *
	 * Completely 'hides' a pane, including its spacing - as if it does not exist
	 * The pane is not actually 'removed' from the source, so can use 'show' to un-hide it
	 *
	 * @param String  pane   The pane being hidden, ie: north, south, east, or west
	 */
	var hide = function (pane, onInit, noAnimation) {
		var
			o	= options[pane]
		,	s	= state[pane]
		,	$P	= $Ps[pane]
		,	$R	= $Rs[pane]
		;
		if (!$P || s.isHidden) return; // pane does not exist OR is already hidden

		// onhide_start callback - will CANCEL hide if returns false
		if (!onInit && false === _execCallback(pane, o.onhide_start)) return;

		s.isSliding = false; // just in case

		// now hide the elements
		if ($R) $R.hide(); // hide resizer-bar
		if (onInit || s.isClosed) {
			s.isClosed = true; // to trigger open-animation on show()
			s.isHidden  = true;
			s.isVisible = false;
			$P.hide(); // no animation when loading page
			sizeMidPanes(c[pane].dir == "horz" ? "all" : "center");
			if (!onInit || o.triggerEventsOnLoad)
				_execCallback(pane, o.onhide_end || o.onhide);
		}
		else {
			s.isHiding = true; // used by onclose
			close(pane, false, noAnimation); // adjust all panes to fit
		}
	};

	var show = function (pane, openPane, noAnimation, noAlert) {
		var
			o	= options[pane]
		,	s	= state[pane]
		,	$P	= $Ps[pane]
		,	$R	= $Rs[pane]
		;
		if (!$P || !s.isHidden) return; // pane does not exist OR is not hidden

		// onshow_start callback - will CANCEL show if returns false
		if (false === _execCallback(pane, o.onshow_start)) return;

		s.isSliding = false; // just in case
		s.isShowing = true; // used by onopen/onclose
		//s.isHidden  = false; - will be set by open/close - if not cancelled

		// now show the elements
		//if ($R) $R.show(); - will be shown by open/close
		if (openPane === false)
			close(pane, true); // true = force
		else
			open(pane, false, noAnimation, noAlert); // adjust all panes to fit
	};


	/**
	 * toggle
	 *
	 * Toggles a pane open/closed by calling either open or close
	 *
	 * @param String  pane   The pane being toggled, ie: north, south, east, or west
	 */
	var toggle = function (pane) {
		if (typeof pane !="string") pane = $(this).attr("resizer"); // bound to $R.dblclick
		var s = state[pane];
		if (s.isHidden)
			show(pane); // will call 'open' after unhiding it
		else if (s.isClosed)
			open(pane);
		else
			close(pane);
	};

	/**
	 * close
	 *
	 * Close the specified pane (animation optional), and resize all other panes as needed
	 *
	 * @param String  pane   The pane being closed, ie: north, south, east, or west
	 */
	var close = function (pane, force, noAnimation, onInit) {
		var
			$P		= $Ps[pane]
		,	$R		= $Rs[pane]
		,	$T		= $Ts[pane]
		,	o		= options[pane]
		,	s		= state[pane]
		,	doFX	= !noAnimation && !s.isClosed && (o.fxName_close != "none")
		// 	transfer logic vars to temp vars
		,	isShowing	= s.isShowing
		,	isHiding	= s.isHiding
		,	wasSliding	= s.isSliding
		;
		// now clear the logic vars
		delete s.isShowing;
		delete s.isHiding;

		if (!$P || (!o.resizable && !o.closable)) return; // invalid request
		else if (!force && s.isClosed && !isShowing) return; // already closed

		if (c.isLayoutBusy) { // layout is 'busy' - probably with an animation
			_queue("close", pane, force); // set a callback for this action, if possible
			return; // ABORT 
		}

		// onclose_start callback - will CANCEL hide if returns false
		// SKIP if just 'showing' a hidden pane as 'closed'
		if (!onInit && !isShowing && false === _execCallback(pane, o.onclose_start)) return;

		// SET flow-control flags
		c[pane].isMoving = true;
		c.isLayoutBusy = true;

		s.isClosed = true;
		s.isVisible = false;
		// update isHidden BEFORE sizing panes
		if (isHiding) s.isHidden = true;
		else if (isShowing) s.isHidden = false;

		if (s.isSliding) // pane is being closed, so UNBIND trigger events
			bindStopSlidingEvents(pane, false); // will set isSliding=false
		else if (!onInit) // resize panes adjacent to this one
			sizeMidPanes(c[pane].dir == "horz" ? "all" : "center");

		// if this pane has a resizer bar, move it NOW - before animation
		if (!onInit) setAsClosed(pane); // onInit, this will be called LATER by initHandles

		// ANIMATE 'CLOSE' - if no animation, then was ALREADY shown above
		if (doFX) {
			lockPaneForFX(pane, true); // need to set left/top so animation will work
			$P.hide( o.fxName_close, o.fxSettings_close, o.fxSpeed_close, function () {
				lockPaneForFX(pane, false); // undo
				close_2();
			});
		}
		else {
			$P.hide(); // just hide pane NOW
			close_2();
		};

		// SUBROUTINE
		function close_2 () {
			if (s.isClosed) { // make sure pane was not 'reopened' before animation finished!

				bindStartSlidingEvent(pane, true); // will enable if state.PANE.isSliding = true

				// if opposite-pane was autoClosed, see if it can be autoOpened now
				var altPane = altSide[pane];
				if (state[ altPane ].noRoom) {
					setSizeLimits( altPane );
					makePaneFit( altPane );
				}

				if (!onInit || o.triggerEventsOnLoad) {
					// onclose callback - UNLESS just 'showing' a hidden pane as 'closed'
					if (!isShowing && !wasSliding) _execCallback(pane, o.onclose_end || o.onclose);
					// onhide OR onshow callback
					if (isShowing)	_execCallback(pane, o.onshow_end || o.onshow);
					if (isHiding)	_execCallback(pane, o.onhide_end || o.onhide);
				}
			}
			// execute internal flow-control callback
			_dequeue(pane);
		}
	};

	var setAsClosed = function (pane, onInit) {
		var
			$P		= $Ps[pane]
		,	$R		= $Rs[pane]
		,	$T		= $Ts[pane]
		,	o		= options[pane]
		,	s		= state[pane]
		,	side	= c[pane].side.toLowerCase()
		,	inset	= "inset"+ c[pane].side
		,	rClass	= o.resizerClass
		,	tClass	= o.togglerClass
		,	_pane	= "-"+ pane // used for classNames
		,	_open	= "-open"
		,	_sliding= "-sliding"
		,	_closed	= "-closed"
		;
		$R
			.css(side, sC[inset]) // move the resizer
			.removeClass( rClass+_open +" "+ rClass+_pane+_open )
			.removeClass( rClass+_sliding +" "+ rClass+_pane+_sliding )
			.addClass( rClass+_closed +" "+ rClass+_pane+_closed )
			.unbind("dblclick."+c.namespace)
		;
		// DISABLE 'resizing' when closed - do this BEFORE bindStartSlidingEvent?
		if (o.resizable)
			$R
				.draggable("disable")
				.removeClass("ui-state-disabled") // do NOT apply disabled styling - not suitable here
				.css("cursor", "default")
				.attr("title","")
			;

		// if pane has a toggler button, adjust that too
		if ($T) $T
			.removeClass( tClass+_open +" "+ tClass+_pane+_open )
			.addClass( tClass+_closed +" "+ tClass+_pane+_closed )
			.attr("title", o.togglerTip_closed) // may be blank
		;

		// sync any 'pin buttons'
		syncPinBtns(pane, false);

		if (!onInit) {
			// resize 'length' and position togglers for adjacent panes
			sizeHandles("all");
		}
	};

	/**
	 * open
	 *
	 * Open the specified pane (animation optional), and resize all other panes as needed
	 *
	 * @param String  pane   The pane being opened, ie: north, south, east, or west
	 */
	var open = function (pane, slide, noAnimation, noAlert) {
		var 
			$P		= $Ps[pane]
		,	$R		= $Rs[pane]
		,	$T		= $Ts[pane]
		,	o		= options[pane]
		,	s		= state[pane]
		,	doFX	= !noAnimation && s.isClosed && (o.fxName_open != "none")
		// 	transfer logic var to temp var
		,	isShowing = s.isShowing
		;
		// now clear the logic var
		delete s.isShowing;

		if (!$P || (!o.resizable && !o.closable)) return; // invalid request
		else if (s.isVisible && !s.isSliding) return; // already open

		// pane can ALSO be unhidden by just calling show(), so handle this scenario
		if (s.isHidden && !isShowing) {
			show(pane, true);
			return;
		}

		if (c.isLayoutBusy) { // layout is 'busy' - probably with an animation
			_queue("open", pane, slide); // set a callback for this action, if possible
			return; // ABORT
		}

		// onopen_start callback - will CANCEL hide if returns false
		if (false === _execCallback(pane, o.onopen_start)) return;

		// make sure there is enough space available to open the pane
		setSizeLimits(pane, slide); // update pane-state
		if (s.minSize > s.maxSize) { // INSUFFICIENT ROOM FOR PANE TO OPEN!
			syncPinBtns(pane, false); // make sure pin-buttons are reset
			if (!noAlert && o.noRoomToOpenTip) alert(o.noRoomToOpenTip);
			return; // ABORT
		}

		// SET flow-control flags
		c[pane].isMoving = true;
		c.isLayoutBusy = true;

		if (slide) // START Sliding - will set isSliding=true
			bindStopSlidingEvents(pane, true); // BIND trigger events to close sliding-pane
		else if (s.isSliding) // PIN PANE (stop sliding) - open pane 'normally' instead
			bindStopSlidingEvents(pane, false); // UNBIND trigger events - will set isSliding=false

		s.noRoom = false; // will be reset by makePaneFit if 'noRoom'
		makePaneFit(pane);

		s.isVisible = true;
		s.isClosed	= false;
		// update isHidden BEFORE sizing panes - WHY??? Old?
		if (isShowing) s.isHidden = false;

		bindStartSlidingEvent(pane, false); // remove trigger event from resizer-bar

		if (doFX) { // ANIMATE
			lockPaneForFX(pane, true); // need to set left/top so animation will work
			$P.show( o.fxName_open, o.fxSettings_open, o.fxSpeed_open, function() {
				lockPaneForFX(pane, false); // undo
				open_2(); // continue
			});
		}
		else {// no animation
			$P.show();	// just show pane and...
			open_2();	// continue
		};

		// SUBROUTINE
		function open_2 () {
			if (s.isVisible) { // make sure pane was not closed or hidden before animation finished!

				// cure iframe display issues in IE & other browsers
				if (!$.browser.mozilla) { // skip FireFox - it auto-refreshes iframes onShow
					if (s.tagName == "IFRAME") $P.css(c.hidden).css(c.visible); 
					$P.find('IFRAME').css(c.hidden).css(c.visible); // ditto for interior iframes
				}

				// NOTE: if isSliding, then other panes are NOT 'resized'
				if (!s.isSliding) // resize all panes adjacent to this one
					sizeMidPanes(c[pane].dir=="vert" ? "center" : "all");
				else if (isMouseOver(pane)) // handle Chrome browser glitch...
					clearTimer(pane, "closeSlider"); // prevent premature close
				else
					slideClosed(pane);

				// set classes, position handles and execute callbacks...
				setAsOpen(pane);
			}

			// internal flow-control callback
			_dequeue(pane);
		};
	
	};

	var setAsOpen = function (pane, onInit) {
		var 
			$P		= $Ps[pane]
		,	$R		= $Rs[pane]
		,	$T		= $Ts[pane]
		,	o		= options[pane]
		,	s		= state[pane]
		,	side	= c[pane].side.toLowerCase()
		,	inset	= "inset"+ c[pane].side
		,	rClass	= o.resizerClass
		,	tClass	= o.togglerClass
		,	_pane	= "-"+ pane // used for classNames
		,	_open	= "-open"
		,	_closed	= "-closed"
		,	_sliding= "-sliding"
		;
		$R
			.css(side, sC[inset] + getPaneSize(pane)) // move the resizer
			.removeClass( rClass+_closed +" "+ rClass+_pane+_closed )
			.addClass( rClass+_open +" "+ rClass+_pane+_open )
			.addClass( !s.isSliding ? "" : rClass+_sliding +" "+ rClass+_pane+_sliding )
			.bind("dblclick."+c.namespace, toggle )
		;
		removeHover( 0, $R ); // remove hover classes
		if (o.resizable)
			$R
				.draggable("enable")
				.css("cursor", o.resizerCursor)
				.attr("title", o.resizerTip)
			;
		else
			$R.css("cursor", "default"); // n-resize, s-resize, etc

		// if pane also has a toggler button, adjust that too
		if ($T) {
			$T
				.removeClass( tClass+_closed +" "+ tClass+_pane+_closed )
				.addClass( tClass+_open +" "+ tClass+_pane+_open )
				.attr("title", o.togglerTip_open) // may be blank
			;
			removeHover( 0, $T ); // remove hover classes
		}

		// sync any 'pin buttons'
		syncPinBtns(pane, !s.isSliding);

		if (!onInit) {
			// resize resizer & toggler sizes for all panes
			sizeHandles("all");
			// resize content every time pane opens - to be sure
			sizeContent(pane);
		}

		if (!onInit || o.triggerEventsOnLoad) {
			// onopen callback
			_execCallback(pane, o.onopen_end || o.onopen);
			// ALSO call onresize because layout-size *may* have changed while pane was closed
			_execCallback(pane, o.onresize_end || o.onresize); // if (!onInit) 
			// onshow callback - TODO: should this be here?
			if (s.isShowing) _execCallback(pane, o.onshow_end || o.onshow);
		}

		// update pane-state dimensions
		$.extend(s, getElemDims($P), true);
	};


	/**
	 * lockPaneForFX
	 *
	 * Must set left/top on East/South panes so animation will work properly
	 *
	 * @param String  pane  The pane to lock, 'east' or 'south' - any other is ignored!
	 * @param Boolean  doLock  true = set left/top, false = remove
	 */
	var lockPaneForFX = function (pane, doLock) {
		var $P = $Ps[pane];
		if (doLock) {
			$P.css({ zIndex: c.zIndex.pane_animate }); // overlay all elements during animation
			if (pane=="south")
				$P.css({ top: sC.insetTop + sC.innerHeight - $P.outerHeight() });
			else if (pane=="east")
				$P.css({ left: sC.insetLeft + sC.innerWidth - $P.outerWidth() });
		}
		else {
			$P.css({ zIndex: (state[pane].isSliding ? c.zIndex.pane_sliding : c.zIndex.pane_normal) });
			if (pane=="south")
				$P.css({ top: "auto" });
			else if (pane=="east")
				$P.css({ left: "auto" });
		}
	};


	/**
	 * bindStartSlidingEvent
	 *
	 * Toggle sliding functionality of a specific pane on/off by adding removing 'slide open' trigger
	 *
	 * @callers  open(), close()
	 * @param String  pane  The pane to enable/disable, 'north', 'south', etc.
	 * @param Boolean  enable  Enable or Disable sliding?
	 */
	var bindStartSlidingEvent = function (pane, enable) {
		var 
			o		= options[pane]
		,	$R		= $Rs[pane]
		,	trigger	= o.slideTrigger_open
		;
		if (!$R || !o.slidable) return;
		// make sure we have a valid event
		if (trigger != "click" && trigger != "dblclick" && trigger != "mouseover")
			trigger = o.slideTrigger_open = "click";
		$R
			// add or remove trigger event
			[enable ? "bind" : "unbind"](trigger, slideOpen)
			// set the appropriate cursor & title/tip
			.css("cursor", (enable ? o.sliderCursor: "default"))
			.attr("title", (enable ? o.sliderTip : ""))
		;
	};

	/**
	 * bindStopSlidingEvents
	 *
	 * Add or remove 'mouseout' events to 'slide close' when pane is 'sliding' open or closed
	 * Also increases zIndex when pane is sliding open
	 * See bindStartSlidingEvent for code to control 'slide open'
	 *
	 * @callers  slideOpen(), slideClosed()
	 * @param String  pane  The pane to process, 'north', 'south', etc.
	 * @param Boolean  enable  Enable or Disable events?
	 */
	var bindStopSlidingEvents = function (pane, enable) {
		var 
			o		= options[pane]
		,	s		= state[pane]
		,	trigger	= o.slideTrigger_close
		,	action	= (enable ? "bind" : "unbind") // can't make 'unbind' work! - see disabled code below
		,	$P		= $Ps[pane]
		,	$R		= $Rs[pane]
		;

		s.isSliding = enable; // logic
		clearTimer(pane, "closeSlider"); // just in case

		// raise z-index when sliding
		$P.css({ zIndex: (enable ? c.zIndex.pane_sliding : c.zIndex.pane_normal) });
		$R.css({ zIndex: (enable ? c.zIndex.pane_sliding : c.zIndex.resizer_normal) });

		// make sure we have a valid event
		if (trigger != "mouseout" && trigger != "click")
			trigger = o.slideTrigger_close = "mouseout";

		// when trigger is 'mouseout', must cancel timer when mouse moves between 'pane' and 'resizer'
		if (enable) { // BIND trigger events
			$R.bind(trigger+"."+c.namespace, slideClosed ); // click OR mouseout - 'click' on resizer will close
			if (trigger == "mouseout") {
				$P.bind("mouseout."+c.namespace, slideClosed );
				$R.bind("dblclick."+c.namespace, close ); // ALWAYS close with dbl-click on Slider-bar
				// must cancel timer when mouse moves between 'pane' and 'resizer'
				$P.bind("mouseover."+c.namespace, cancelMouseOut );
				$R.bind("mouseover."+c.namespace, cancelMouseOut );
			}
		}
		else { // UNBIND trigger events
			// TODO: why does unbind of a 'single function' not work reliably?
			//$P[action](trigger, slideClosed );
			$R.unbind(trigger+"."+c.namespace);
			if (trigger == "mouseout") {
				$P.unbind("mouseout."+c.namespace);
				$R.unbind("dblclick."+c.namespace);
				$P.unbind("mouseover."+c.namespace);
				$R.unbind("mouseover."+c.namespace);
				clearTimer(pane, "closeSlider");
			}
		}

		// SUBROUTINE for mouseout timer clearing
		function cancelMouseOut (evt) {
			clearTimer(pane, "closeSlider");
			evt.stopPropagation();
		}
	};

	var slideOpen = function (evt_or_pane) {
		var pane = (typeof evt_or_pane == "string" ? evt_or_pane : $(this).attr("resizer"));
		if (state[pane].isClosed)
			open(pane, true); // true = slide - ie, called from here!
		else // skip 'open' if already open!
			bindStopSlidingEvents(pane, true); // BIND trigger events to close sliding-pane
	};

	var slideClosed = function (evt_or_pane) {
		var
			$E	= (typeof evt_or_pane == "string" ? $Ps[evt_or_pane] : $(this))
		,	pane= $E.attr("pane") || $E.attr("resizer")
		,	o	= options[pane]
		,	s	= state[pane]
		,	$P	= $Ps[pane]
		;
		if (s.isClosed || s.isResizing)
			return; // skip if already closed OR in process of resizing
		else if (o.slideTrigger_close == "click")
			close_NOW(); // close immediately onClick
		//else if (isMouseOver(pane))
		//	clearTimer(pane, "closeSlider"); // browser glitch - mouse is REALLY 'over' the pane
		else // trigger = mouseout - use a delay
			setTimer(pane, "closeSlider", close_NOW, 300); // .3 sec delay

		// SUBROUTINE for timed close
		function close_NOW (e) {
			if (s.isClosed) // skip 'close' if already closed!
				bindStopSlidingEvents(pane, false); // UNBIND trigger events
			else
				close(pane); // close will handle unbinding
		}
	};


	/**
	 * makePaneFit
	 *
	 * Hides/closes a pane if there is insufficient room - reverses this when there is room again
	 * MUST have already called setSizeLimits() before calling this method
	 */
	var makePaneFit = function (pane, isOpening) {
		var
			o	= options[pane]
		,	s	= state[pane]
		;
		// see if there is enough room to fit the pane
		if (s.minSize <= s.maxSize) { // pane CAN fit
			if (s.size > s.maxSize) // pane is too big - shrink it
				sizePane(pane, s.maxSize);
			else if (s.size < s.minSize) // pane is too small - enlarge it
				sizePane(pane, s.minSize);
			// if was previously hidden due to noRoom, then RESET because NOW there is room
			if (s.noRoom) {
				// s.noRoom state will be set by open or show
				if (s.wasOpen && o.closable) {
					if (o.autoReopen)
						open(pane, false, true, true); // true = noAnimation, true = noAlert
					else // leave the pane closed, so just update state
						s.noRoom = false;
				}
				else
					show(pane, s.wasOpen, true, true); // true = noAnimation, true = noAlert
			}
		}
		else if (!s.noRoom) { // pane CANNOT fit
			s.noRoom = true; // update state
			s.wasOpen = !s.isClosed && !s.isSliding;
			if (o.closable)
				close(pane, true, true); // true = noAnimation
			else
				hide(pane, false, true); // true = noAnimation
		}
	};


	/**
	 * sizePane
	 *
	 * @param String  pane   The pane being resized - usually west or east, but potentially north or south
	 * @param Integer  newSize  The new size for this pane - will be validated
	 */
	var sizePane = function (pane, size, skipCallback) {
		var 
			o		= options[pane]
		,	s		= state[pane]
		,	$P		= $Ps[pane]
		,	$R		= $Rs[pane]
		,	side	= c[pane].side.toLowerCase()
		,	inset	= "inset"+ c[pane].side
		,	dir		= c[pane].dir
		,	oldSize
		;
		// calculate 'current' min/max sizes
		setSizeLimits(pane); // update pane-state
		oldSize = s.size;

		size = parseSize(pane, size); // handle percentages & auto
		size = max(size, parseSize(pane, o.minSize));
		size = min(size, s.maxSize);
		if (size < s.minSize) { // not enough room for pane!
			makePaneFit(pane);	// will hide or close pane
			return;
		}
		s.size = size;

		// resize the pane and move the resizer
		$P.css( c[pane].sizeType.toLowerCase(), max(1, cssSize(pane, size)) );
		// update pane-state dimensions
		$.extend(s, getElemDims($P), true);
		// reposition the resizer-bar
		if ($R && $P.is(":visible")) $R.css( side, size + sC[inset] );

		// resize all the adjacent panes, and adjust their toggler buttons
		if (!s.isSliding) sizeMidPanes(dir=="horz" ? "all" : "center");
		sizeHandles("all");
		sizeContent(pane);

		if (!skipCallback && s.isVisible) {
			_execCallback(pane, o.onresize_end || o.onresize);
		}

		// if opposite-pane was autoClosed, see if it can be autoOpened now
		var altPane = altSide[pane];
		if (size < oldSize && state[ altPane ].noRoom) {
			setSizeLimits( altPane );
			makePaneFit( altPane );
		}
	};

	/**
	 * sizeMidPanes
	 *
	 * @callers  initPanes(), sizePane(), resizeAll(), open(), close(), hide()
	 */
	var sizeMidPanes = function (panes, overrideDims, onInit, skipCallback) {
		if (!panes || panes == "all") panes = "east,west,center";

		var d = $.extend( calcNewCenterPaneDims(), overrideDims || {} );

		$.each(panes.split(","), function() {
			if (!$Ps[this]) return; // NO PANE - skip
			var 
				pane	= str(this)
			,	o		= options[pane]
			,	s		= state[pane]
			,	$P		= $Ps[pane]
			,	$R		= $Rs[pane]
			,	isCenter= (pane=="center")
			,	hasRoom	= true
			,	CSS		= {}
			;

			if (pane == "center") {
				// RECALC center-dims because may have just 'unhidden' East or West pane after a 'resize'
				d = calcNewCenterPaneDims();
				CSS = $.extend( {}, d ); // COPY ALL of the paneDims
				CSS.width  = max(1, cssW(pane, CSS.width));
				CSS.height = max(1, cssH(pane, CSS.height));
				hasRoom = (CSS.width > 1 && CSS.height > 1);
				/*
				 * Extra CSS for IE6 or IE7 in Quirks-mode - add 'width' to NORTH/SOUTH panes
				 * Normally these panes have only 'left' & 'right' positions so pane auto-sizes
				 * ALSO required when an IFRAME is a pane because will NOT default to 'full width'
				 */
				if (s.tagName=="IFRAME" || ($.browser.msie && (!$.boxModel || $.browser.version < 7))) {
					if ($Ps.north) $Ps.north.css({ width: cssW($Ps.north, sC.innerWidth) });
					if ($Ps.south) $Ps.south.css({ width: cssW($Ps.south, sC.innerWidth) });
				}
			}
			else { // for east and west, set only the height, which is same as center height
				CSS.top = d.top;
				CSS.bottom = d.bottom;
				CSS.height = max(1, cssH(pane, d.height));
				hasRoom = (CSS.height > 1);
			}

			if (hasRoom) {
				$P.css(CSS);
				if (pane == "center") $.extend(s, getElemDims($P));
				if (s.noRoom) makePaneFit(pane); // will reopen pane
				if (!onInit) sizeContent(pane);
			}
			else if (!s.noRoom && s.isVisible) { // no room for pane
				makePaneFit(pane); // will hide or close pane
			}

			// resizeAll passes skipCallback because it triggers callbacks after ALL panes are resized
			if (!onInit && !skipCallback && s.isVisible)
				_execCallback(pane, o.onresize_end || o.onresize);
		});
	};


	/**
	 * resizeAll
	 *
	 * @callers  window.onresize(), callbacks or custom code
	 */
	var resizeAll = function () {
		var
			oldW	= sC.innerWidth
		,	oldH	= sC.innerHeight
		;
		$.extend( state.container, getElemDims( $Container ) ); // UPDATE container dimensions
		if (!sC.outerHeight) return; // cannot size layout when 'container' is hidden or collapsed

		// onresizeall_start will CANCEL resizing if returns false
		// state.container has already been set, so user can access this info for calcuations
		if (false === _execCallback(null, (options.onresizeall_start))) return false;

		var
			// see if container is now 'smaller' than before
			shrunkH	= (sC.innerHeight < oldH)
		,	shrunkW	= (sC.innerWidth < oldW)
		,	o, s, dir
		;
		// NOTE special order for sizing: S-N-E-W
		$.each(["south","north","east","west"], function (i,pane) {
			if (!$Ps[pane]) return; // no pane - SKIP
			s	= state[pane];
			o	= options[pane];
			dir	= c[pane].dir;
			if (o.autoResize) // resize pane using original 'auto' or percent option
				sizePane(pane, o.size);
			else {
				setSizeLimits(pane);
				makePaneFit(pane);
			}
		});

		sizeMidPanes("all", false, false, true); // true - skipCallback
		sizeHandles("all"); // reposition the toggler elements

		// trigger all individual pane callbacks AFTER layout has finished resizing
		o = options; // reuse alias
		$.each(c.allPanes.split(","), function(i,pane) {
			if (state[pane].isVisible) // undefined for non-existent panes
				_execCallback(null, o[pane].onresize_end || o[pane].onresize); // callback - if exists
		});
		_execCallback(null, o.onresizeall_end || o.onresizeall); // onresizeall callback, if exists
	};


	/**
	 * sizeContent
	 *
	 * IF pane has a content-div, then resize all elements inside pane to fit pane-height
	 */
	var sizeContent = function (panes, recalc) {
		if (!panes || panes == "all") panes = c.allPanes;

		$.each(panes.split(","), function() {
			if (!$Cs[this]) return; // NO CONTENT - skip
			var 
				pane	= str(this)
			,	$P		= $Ps[pane]
			,	$C		= $Cs[pane]
			,	Sizes	= $C.data('Sizes')
			,	pHeight	= cssH($P)	// pane.innerHeight
			,	above	= 0
			,	below	= 0
			;
			if (!$P.is(":visible"))
				return true; // NOT VISIBLE - skip
			else if (Sizes && !recalc) {
				above = Sizes.above;
				below = Sizes.below;
			}
			else {
				var
					CSS		= _showInvisibly( $C )
				,	above	= $C.position().top
				,	below	= 0 // init
				,	cHeight	= $C.outerHeight() // EXCLUDING margins
				,	cBottom	= above + cHeight
				,	ignore	= options[pane].contentIgnoreSelector
				,	$Es		= $P.children()
				;
				var $E, below = 0, top = bot = 0;
				
				for (var i = $Es.length-1; i >= 0; i--) {
					if ($Es[i] == $C[0]) break; // Content elem - NOTHING AFTER IT
					var $E = $( $Es[i] );
					if ((!ignore || !$E.is(ignore)) && $E.css("display") !="none" && !$E.hasClass('ui-layout-mask')) {
						var // IMPORTANT - must measure outerHeight() BEFORE position() or else top=0 - some kind of bug!
							eHt  = $E.outerHeight()
						,	eTop = $E.position().top
						,	eBot = eTop + eHt + cssNum($E,'marginBottom')
						;
						below = eBot - cBottom;
						// set meta-data to use next time, UNLESS sizeContent called with recalc=true
						$C.data('Sizes', { above: above, below: below });
						break;
					}
					// restore original visiblity
					$C.css( CSS );
				}
			}
			// resize the Content element to fit -  will autoHide if not enough room
			setOuterHeight($C, (pHeight - above - below), true); // true=autoHide
		}); // END $.each(panes)
	};


	/**
	 * sizeHandles
	 *
	 * Called every time a pane is opened, closed, or resized to slide the togglers to 'center' and adjust their length if necessary
	 *
	 * @callers  initHandles(), open(), close(), resizeAll()
	 */
	var sizeHandles = function (panes, onInit) {
		if (!panes || panes == "all") panes = c.borderPanes;

		$.each(panes.split(","), function() {
			var 
				pane	= str(this)
			,	o		= options[pane]
			,	s		= state[pane]
			,	$P		= $Ps[pane]
			,	$R		= $Rs[pane]
			,	$T		= $Ts[pane]
			;
			if (!$P || !$R) return;

			var 
				dir			= c[pane].dir
			,	_state		= (s.isClosed ? "_closed" : "_open")
			,	spacing		= o["spacing"+ _state]
			,	togAlign	= o["togglerAlign"+ _state]
			,	togLen		= o["togglerLength"+ _state]
			,	paneLen
			,	offset
			,	CSS = {}
			;
			if (spacing == 0) {
				$R.hide();
				return;
			}
			else if (!s.noRoom && !s.isHidden) // skip if resizer was hidden for any reason
				$R.show(); // in case was previously hidden

			// Resizer Bar is ALWAYS same width/height of pane it is attached to
			if (dir == "horz") { // north/south
				paneLen = $P.outerWidth();
				$R.css({
					width:	max(1, cssW($R, paneLen)) // account for borders & padding
				,	height:	max(0, cssH($R, spacing)) // ditto
				,	left:	cssNum($P, "left")
				});
			}
			else { // east/west
				paneLen = $P.outerHeight();
				$R.css({
					height:	max(1, cssH($R, paneLen)) // account for borders & padding
				,	width:	max(0, cssW($R, spacing)) // ditto
				,	top:	sC.insetTop + getPaneSize("north", true)
				//,	top:	cssNum($Ps["center"], "top")
				});
			}

			// remove hover classes
			removeHover( o, $R );

			if ($T) {
				if (togLen == 0 || (s.isSliding && o.hideTogglerOnSlide)) {
					$T.hide(); // always HIDE the toggler when 'sliding'
					return;
				}
				else
					$T.show(); // in case was previously hidden

				if (!(togLen > 0) || togLen == "100%" || togLen > paneLen) {
					togLen = paneLen;
					offset = 0;
				}
				else { // calculate 'offset' based on options.PANE.togglerAlign_open/closed
					if (typeof togAlign == "string") {
						switch (togAlign) {
							case "top":
							case "left":	offset = 0;
											break;
							case "bottom":
							case "right":	offset = paneLen - togLen;
											break;
							case "middle":
							case "center":
							default:		offset = floor((paneLen - togLen) / 2); // 'default' catches typos
						}
					}
					else { // togAlign = number
						var x = parseInt(togAlign); //
						if (togAlign >= 0) offset = x;
						else offset = paneLen - togLen + x; // NOTE: x is negative!
					}
				}

				var
					$TC_o = (o.togglerContent_open   ? $T.children(".content-open") : false)
				,	$TC_c = (o.togglerContent_closed ? $T.children(".content-closed")   : false)
				,	$TC   = (s.isClosed ? $TC_c : $TC_o)
				;
				if ($TC_o) $TC_o.css("display", s.isClosed ? "none" : "block");
				if ($TC_c) $TC_c.css("display", s.isClosed ? "block" : "none");

				if (dir == "horz") { // north/south
					var width = cssW($T, togLen);
					$T.css({
						width:	max(0, width)  // account for borders & padding
					,	height:	max(1, cssH($T, spacing)) // ditto
					,	left:	offset // TODO: VERIFY that toggler  positions correctly for ALL values
					,	top:	0
					});
					if ($TC) // CENTER the toggler content SPAN
						$TC.css("marginLeft", floor((width-$TC.outerWidth())/2)); // could be negative
				}
				else { // east/west
					var height = cssH($T, togLen);
					$T.css({
						height:	max(0, height)  // account for borders & padding
					,	width:	max(1, cssW($T, spacing)) // ditto
					,	top:	offset // POSITION the toggler
					,	left:	0
					});
					if ($TC) // CENTER the toggler content SPAN
						$TC.css("marginTop", floor((height-$TC.outerHeight())/2)); // could be negative
				}

				// remove ALL hover classes
				removeHover( 0, $T );
			}

			// DONE measuring and sizing this resizer/toggler, so can be 'hidden' now
			if (onInit && o.initHidden) {
				$R.hide();
				if ($T) $T.hide();
			}
		});
	};


	/**
	 * keyDown
	 *
	 * Capture keys when enableCursorHotkey - toggle pane if hotkey pressed
	 *
	 * @callers  document.keydown()
	 */
	function keyDown (evt) {
		if (!evt) return true;
		var code = evt.keyCode;
		if (code < 33) return true; // ignore special keys: ENTER, TAB, etc

		var
			PANE = {
				38: "north" // Up Cursor
			,	40: "south" // Down Cursor
			,	37: "west"  // Left Cursor
			,	39: "east"  // Right Cursor
			}
		,	isCursorKey = (code >= 37 && code <= 40)
		,	ALT = evt.altKey // no worky!
		,	SHIFT = evt.shiftKey
		,	CTRL = evt.ctrlKey
		,	pane = false
		,	s, o, k, m, el
		;

		if (!CTRL && !SHIFT)
			return true; // no modifier key - abort
		else if (isCursorKey && options[PANE[code]].enableCursorHotkey) // valid cursor-hotkey
			pane = PANE[code];
		else // check to see if this matches a custom-hotkey
			$.each(c.borderPanes.split(","), function(i,p) { // loop each pane to check its hotkey
				o = options[p];
				k = o.customHotkey;
				m = o.customHotkeyModifier; // if missing or invalid, treated as "CTRL+SHIFT"
				if ((SHIFT && m=="SHIFT") || (CTRL && m=="CTRL") || (CTRL && SHIFT)) { // Modifier matches
					if (k && code == (isNaN(k) || k <= 9 ? k.toUpperCase().charCodeAt(0) : k)) { // Key matches
						pane = p;
						return false; // BREAK
					}
				}
			});

		if (!pane) return true; // no hotkey - abort

		// validate pane
		o = options[pane]; // get pane options
		s = state[pane]; // get pane options
		if (!o.enableCursorHotkey || s.isHidden || !$Ps[pane]) return true;

		// see if user is in a 'form field' because may be 'selecting text'!
		el = evt.target || evt.srcElement;
		if (el && SHIFT && isCursorKey && (el.tagName=="TEXTAREA" || (el.tagName=="INPUT" && (code==37 || code==39))))
			return true; // allow text-selection

		// SYNTAX NOTES
		// use "returnValue=false" to abort keystroke but NOT abort function - can run another command afterwards
		// use "return false" to abort keystroke AND abort function
		toggle(pane);
		evt.stopPropagation();
		evt.returnValue = false; // CANCEL key
		return false;
	};


/*
 * ###########################
 *      UTILITY METHODS
 *   called externally only
 * ###########################
 */

	/**
	* allowOverflow / resetOverflow
	*
	* Change/reset a pane's overflow setting & zIndex to allow popups/drop-downs to work
	*
	* @param element   elem 	Optional - can also be 'bound' to a click, mouseOver, or other event
	*/
	function allowOverflow (elem) {
		if (this && this.tagName) elem = this; // BOUND to element
		var $P;
		if (typeof elem=="string")
			$P = $Ps[elem];
		else {
			if ($(elem).attr("pane")) $P = $(elem);
			else $P = $(elem).parents("div[pane]:first");
		}
		if (!$P.length) return; // INVALID

		var
			pane	= $P.attr("pane")
		,	s		= state[pane]
		;

		// if pane is already raised, then reset it before doing it again!
		// this would happen if allowOverflow is attached to BOTH the pane and an element 
		if (s.cssSaved)
			resetOverflow(pane); // reset previous CSS before continuing

		// if pane is raised by sliding or resizing, or it's closed, then abort
		if (s.isSliding || s.isResizing || s.isClosed) {
			s.cssSaved = false;
			return;
		}

		var
			newCSS	= { zIndex: (c.zIndex.pane_normal + 1) }
		,	curCSS	= {}
		,	of		= $P.css("overflow")
		,	ofX		= $P.css("overflowX")
		,	ofY		= $P.css("overflowY")
		;
		// determine which, if any, overflow settings need to be changed
		if (of != "visible") {
			curCSS.overflow = of;
			newCSS.overflow = "visible";
		}
		if (ofX && ofX != "visible" && ofX != "auto") {
			curCSS.overflowX = ofX;
			newCSS.overflowX = "visible";
		}
		if (ofY && ofY != "visible" && ofY != "auto") {
			curCSS.overflowY = ofX;
			newCSS.overflowY = "visible";
		}

		// save the current overflow settings - even if blank!
		s.cssSaved = curCSS;

		// apply new CSS to raise zIndex and, if necessary, make overflow 'visible'
		$P.css( newCSS );

		// make sure the zIndex of all other panes is normal
		$.each(c.allPanes.split(","), function(i, p) {
			if (p != pane) resetOverflow(p);
		});

	};

	function resetOverflow (elem) {
		if (this && this.tagName) elem = this; // BOUND to element
		var $P;
		if (typeof elem=="string")
			$P = $Ps[elem];
		else {
			if ($(elem).hasClass("ui-layout-pane")) $P = $(elem);
			else $P = $(elem).parents("div[pane]:first");
		}
		if (!$P.length) return; // INVALID

		var
			pane	= $P.attr("pane")
		,	s		= state[pane]
		,	CSS		= s.cssSaved || {}
		;
		// reset the zIndex
		if (!s.isSliding && !s.isResizing)
			$P.css("zIndex", c.zIndex.pane_normal);

		// reset Overflow - if necessary
		$P.css( CSS );

		// clear var
		s.cssSaved = false;
	};


	/**
	* getBtn
	*
	* Helper function to validate params received by addButton utilities
	*
	* Two classes are added to the element, based on the buttonClass...
	* The type of button is appended to create the 2nd className:
	*  - ui-layout-button-pin
	*  - ui-layout-pane-button-toggle
	*  - ui-layout-pane-button-open
	*  - ui-layout-pane-button-close
	*
	* @param String   selector 	jQuery selector for button, eg: ".ui-layout-north .toggle-button"
	* @param String   pane 		Name of the pane the button is for: 'north', 'south', etc.
	* @returns  If both params valid, the element matching 'selector' in a jQuery wrapper - otherwise 'false'
	*/
	function getBtn(selector, pane, action) {
		var
			$E	= $(selector)
		,	err = "Error Adding Button \n\nInvalid "
		;
		if (!$E.length) // element not found
			alert(err+"selector: "+ selector);
		else if (c.borderPanes.indexOf(pane) == -1) // invalid 'pane' sepecified
			alert(err+"pane: "+ pane);
		else { // VALID
			var btn = options[pane].buttonClass +"-"+ action;
			$E
				.addClass( btn +" "+ btn +"-"+ pane )
				.attr("layout", options.name) // add layout identifier - even if blank!
			;
			return $E;
		}
		return false;  // INVALID
	};


	function bindButton (selector, action, pane) {
		switch (action.toLowerCase()) {
			case "toggle":	addToggleBtn(selector, pane);	break;	
			case "open":	addOpenBtn(selector, pane);		break;
			case "close":	addCloseBtn(selector, pane);	break;
			case "pin":		addPinBtn(selector, pane);
		}
	};


	/**
	* addToggleBtn
	*
	* Add a custom Toggler button for a pane
	*
	* @param String   selector 	jQuery selector for button, eg: ".ui-layout-north .toggle-button"
	* @param String   pane 		Name of the pane the button is for: 'north', 'south', etc.
	*/
	function addToggleBtn (selector, pane) {
		var $E = getBtn(selector, pane, "toggle");
		if ($E)
			$E
				.attr("title", state[pane].isClosed ? lang.Open : lang.Close)
				.click(function (evt) {
					toggle(pane);
					evt.stopPropagation();
				})
			;
	};

	/**
	* addOpenBtn
	*
	* Add a custom Open button for a pane
	*
	* @param String   selector 	jQuery selector for button, eg: ".ui-layout-north .open-button"
	* @param String   pane 		Name of the pane the button is for: 'north', 'south', etc.
	*/
	function addOpenBtn (selector, pane) {
		var $E = getBtn(selector, pane, "open");
		if ($E)
			$E
				.attr("title", lang.Open)
				.click(function (evt) {
					open(pane);
					evt.stopPropagation();
				})
			;
	};

	/**
	* addCloseBtn
	*
	* Add a custom Close button for a pane
	*
	* @param String   selector 	jQuery selector for button, eg: ".ui-layout-north .close-button"
	* @param String   pane 		Name of the pane the button is for: 'north', 'south', etc.
	*/
	function addCloseBtn (selector, pane) {
		var $E = getBtn(selector, pane, "close");
		if ($E)
			$E
				.attr("title", lang.Close)
				.click(function (evt) {
					close(pane);
					evt.stopPropagation();
				})
			;
	};

	/**
	* addPinBtn
	*
	* Add a custom Pin button for a pane
	*
	* Four classes are added to the element, based on the paneClass for the associated pane...
	* Assuming the default paneClass and the pin is 'up', these classes are added for a west-pane pin:
	*  - ui-layout-pane-pin
	*  - ui-layout-pane-west-pin
	*  - ui-layout-pane-pin-up
	*  - ui-layout-pane-west-pin-up
	*
	* @param String   selector 	jQuery selector for button, eg: ".ui-layout-north .ui-layout-pin"
	* @param String   pane 		Name of the pane the pin is for: 'north', 'south', etc.
	*/
	function addPinBtn (selector, pane) {
		var $E = getBtn(selector, pane, "pin");
		if ($E) {
			var s = state[pane];
			$E.click(function (evt) {
				setPinState($(this), pane, (s.isSliding || s.isClosed));
				if (s.isSliding || s.isClosed) open( pane ); // change from sliding to open
				else close( pane ); // slide-closed
				evt.stopPropagation();
			});
			// add up/down pin attributes and classes
			setPinState ($E, pane, (!s.isClosed && !s.isSliding));
			// add this pin to the pane data so we can 'sync it' automatically
			// PANE.pins key is an array so we can store multiple pins for each pane
			c[pane].pins.push( selector ); // just save the selector string
		}
	};

	/**
	* syncPinBtns
	*
	* INTERNAL function to sync 'pin buttons' when pane is opened or closed
	* Unpinned means the pane is 'sliding' - ie, over-top of the adjacent panes
	*
	* @callers  open(), close()
	* @params  pane   These are the params returned to callbacks by layout()
	* @params  doPin  True means set the pin 'down', False means 'up'
	*/
	function syncPinBtns (pane, doPin) {
		$.each(c[pane].pins, function (i, selector) {
			setPinState($(selector), pane, doPin);
		});
	};

	/**
	* setPinState
	*
	* Change the class of the pin button to make it look 'up' or 'down'
	*
	* @callers  addPinBtn(), syncPinBtns()
	* @param Element  $Pin		The pin-span element in a jQuery wrapper
	* @param Boolean  doPin		True = set the pin 'down', False = set it 'up'
	* @param String   pinClass	The root classname for pins - will add '-up' or '-down' suffix
	*/
	function setPinState ($Pin, pane, doPin) {
		var updown = $Pin.attr("pin");
		if (updown && doPin == (updown=="down")) return; // already in correct state
		var
			pin		= options[pane].buttonClass +"-pin"
		,	side	= pin +"-"+ pane
		,	UP		= pin +"-up "+	side +"-up"
		,	DN		= pin +"-down "+side +"-down"
		;
		$Pin
			.attr("pin", doPin ? "down" : "up") // logic
			.attr("title", doPin ? lang.Unpin : lang.Pin)
			.removeClass( doPin ? UP : DN ) 
			.addClass( doPin ? DN : UP ) 
		;
	};


	/*
	 *	LAYOUT STATE MANAGEMENT
	 *
	 *	@example .layout({ cookie: { name: "myLayout", keys: "west.isClosed,east.isClosed" } })
	 *	@example .layout({ cookie__name: "myLayout", cookie__keys: "west.isClosed,east.isClosed" })
	 *	@example myLayout.getState( "west.isClosed,north.size,south.isHidden" );
	 *	@example myLayout.saveState( "west.isClosed,north.size,south.isHidden", {expires: 7} );
	 *	@example myLayout.clearState();
	 *	@example myLayout.getCookie();
	 *	@example var hSaved = myLayout.state.cookie;
	 */

	/*
	 * _hashify
	 *
	 * Accepts a stringified hash (cookie-data) and converts it back to JSON
	 */
	function _hashify (str) {
		if (str.length < 7) return {}; // min = {"a":9}
		var
			// strip outer-brackets: {..str..}} - then split each subkey (pane data)
			Panes = str.substr(1, str.length-3).split("},")
		,	Pair, Keys, pane, k, v, i, ii // loop vars
		,	data = {}
		;
		for (i in Panes) {
			Pair = Panes[i].split(":{");	// separate pane-data
			pane = unQuote( Pair[0] );		// name of the pane, eg: north
			data[pane] = {};				// create the pane subkey
			Keys = Pair[1].split(",");		// pane keys, eg: ("size":150,"initHidden":true).split
			for (ii in Keys) {	// separate each key-pair, and parse key=value
				Pair = Keys[ii].split(":");
				k = unQuote( Pair[0] );
				v = convert( Pair[1] );
				data[pane][k] = v;			// eg: data.north.size = 50
			}
		}
		return data;

		function unQuote (s) { // strip quotation marks
			return s.substr(0,1) == '"' ? s.substr(1, s.length-2) : s;
		};

		function convert (v) {
			if (v == "true") return true;
			if (v == "false") return false;
			if (Number(v) == v) return Number(v);
			return unQuote( v );
		};
	};

	/*
	 * _stringify
	 *
	 * Accepts a hash and stringifies it to save in a cookie
	 */
	function _stringify (hash) {
		return parse( hash );

		function parse (data) {
			var D=[], i=0, key, k, v, t;
			for (key in data) {
				k = '"'+ key +'":';
				v = data[key];
				t = typeof v;
				if (t == 'string')		// STRING
					D[i++] = k +'"'+ v +'"';
				else if (t != 'object')	// NUMBER or BOOLEAN
					D[i++] = k + v;
				else					// SUB-KEY - recurse into it
					D[i++] = k + parse(v); 
			}
			return "{"+ D.join(",") +"}";
		};
	};

	function isCookiesEnabled () {
		// TODO: is the cookieEnabled property common enough to be useful???
		return (navigator.cookieEnabled != 0);
	};
	
	/*
	 * getCookie
	 *
	 * Get data from the cookie and return a hash of it
	 */
	function getCookie () {
		var
			name = options.cookie.name || options.name || "Layout"
		,	data = {}
		,	c = document.cookie
		,	cs, pair, i // loop vars
		;
		if (c && c != '') {
			cs = c.split(';');
			for (i = 0; i < cs.length; i++) {
				c = $.trim(cs[i]);
				pair = c.split('='); // name=value pair
				if (pair[0] == name) { // this is the layout cookie
					data = _hashify( decodeURIComponent(pair[1]) ); // convert value to a hash
					break; // DONE
				}
			}
		}
		return data;
	};

	/*
	 * clearState
	 *
	 * Remove the state cookie
	 */
	function clearState () {
		saveState('', { expires: -1 });
	};

	/*
	 * _loadState
	 *
	 * Update layout options from the cookie, if one exists
	 */
	function _loadState () {
		state.cookie = getCookie(); // update state.cookie
		$.extend( true, options, state.cookie ); // update layout options
	};

	/*
	 * getState
	 *
	 * Get the *current layout state* and return it as a hash
	 */
	function getState (keys) {
		var
			data	= {}
		,	alt		= { isClosed: 'initClosed', isHidden: 'initHidden' }
		,	pair, pane, key, val
		;
		if (!keys) keys = options.cookie.keys; // if called by user
		if ($.isArray(keys)) keys = keys.join(",");
		// convert keys to an array and change delimiters from '__' to '.'
		keys = keys.replace(/__/g, ".").split(',');
		// loop keys and create a data hash
		for (var i=0,n=keys.length; i < n; i++) {
			pair = keys[i].split(".");
			pane = pair[0];
			key  = pair[1];
			if (c.allPanes.indexOf(pane) < 0) continue; // bad pane!
			val = state[ pane ][ key ];
			if (val == undefined) continue;
			if (key=="isClosed" && state[pane]["isSliding"])
				val = true; // if sliding, then *really* isClosed
			( data[pane] || (data[pane]={}) )[ alt[key] ? alt[key] : key ] = val;
		}
		return data;
	};

	/*
	 * saveState
	 *
	 * Get the current layout state and save it to a cookie
	 */
	function saveState (keys, opts) {
		var
			o		= $.extend( {}, options.cookie, opts || {} )
		,	name	= o.name || options.name || "Layout"
		,	params	= ''
		,	date	= ''
		,	clear	= false
		;
		if (o.expires.toUTCString)
			date = o.expires;
		else if (typeof o.expires == 'number') {
			date = new Date();
			if (o.expires > 0)
				date.setDate(date.getDate() + o.expires);
			else {
				date.setYear(1970);
				clear = true;
			}
		}
		if (date)		params += ';expires='+ date.toUTCString();
		if (o.path)		params += ';path='+ o.path;
		if (o.domain)	params += ';domain='+ o.domain;
		if (o.secure)	params += ';secure';

		if (clear) {
			state.cookie = {}; // clear data
			document.cookie = name +'='+ params; // expire the cookie
		}
		else {
			state.cookie = getState(keys || o.keys); // read current panes-state
			document.cookie = name +'='+ encodeURIComponent( _stringify( state.cookie) ) + params; // write cookie
		}

		return $.extend( {}, state.cookie ); // return a COPY of the state
	};


/*
 * #####################
 * CREATE/RETURN LAYOUT
 * #####################
 */

	// validate the container
	if (!$(this).length) {
		alert('ERROR:  Cannot create a UI/Layout.\n\nThe specified layout-container does not exist.');
		return {};
	};

	// init global vars
	var 
		$Container = $(this) // Container elem
	,	$Ps	= {} // Panes x5	- set in initPanes()
	,	$Cs	= {} // Content x5	- set in initPanes()
	,	$Rs	= {} // Resizers x4	- set in initHandles()
	,	$Ts	= {} // Togglers x4	- set in initHandles()
	//	object aliases
	,	c	= config // alias for config hash
	,	sC	= state.container // alias for easy access to 'container dimensions'
	;

	// create the border layout NOW
	create();

	// return object pointers to expose data & option Properties, and primary action Methods
	return {
		options:		options			// property - options hash
	,	state:			state			// property - dimensions hash
	,	container:		$Container		// property - object pointers for layout container
	,	panes:			$Ps				// property - object pointers for ALL panes: panes.north, panes.center
	,	toggle:			toggle			// method - pass a 'pane' ("north", "west", etc)
	,	open:			open			// method - ditto
	,	close:			close			// method - ditto
	,	hide:			hide			// method - ditto
	,	show:			show			// method - ditto
	,	resizeContent:	sizeContent		// method - ditto - DEPRICATED - "resize" is inconsistent
	,	sizeContent:	sizeContent		// method - pass a 'pane'
	,	sizePane:		sizePane		// method - pass a 'pane' AND an 'outer-size' in pixels or percent, or 'auto'
	,	swapPanes:		swapPanes		// method - pass TWO 'panes' - will swap them
	,	resizeAll:		resizeAll		// method - no parameters
	,	destroy:		destroy			// method - no parameters
	,	setSizeLimits:	setSizeLimits	// method - pass a 'pane' - update state min/max data
	,	bindButton:		bindButton		// utility - pass element selector, 'action' and 'pane' (E, "toggle", "west")
	,	addToggleBtn:	addToggleBtn	// utility - pass element selector and 'pane' (E, "west")
	,	addOpenBtn:		addOpenBtn		// utility - ditto
	,	addCloseBtn:	addCloseBtn		// utility - ditto
	,	addPinBtn:		addPinBtn		// utility - ditto
	,	allowOverflow:	allowOverflow	// utility - pass calling element (this)
	,	resetOverflow:	resetOverflow	// utility - ditto
	,	getCookie:		getCookie		// method - returns hash of new cookie data
	,	getState:		getState		// method - returns hash of saved cookie data
	,	saveState:		saveState		// method - optionally pass keys-list and cookie-options (hash)
	,	clearState:		clearState		// method
	,	cssWidth:		cssW			// utility - pass element and target outerWidth
	,	cssHeight:		cssH			// utility - ditto
	,	isMouseOver:	isMouseOver		// utility - pass any element OR 'pane' - returns true or false
	};

}
})( jQuery );