<?php
/**
 * vänstermenyn med trädet
 */
?>
<div style="background-color: #E2E2E2;">
	<a href="<?php polarbear_webpath() ?>?treepage=gui/overview.php"><img width="136" height="66" src="<?php polarbear_webpath() ?>images/polarbear/polarbear-logo.gif" alt="Polarbear CMS logotype" /></a>
</div>

<div class="fg-toolbar ui-widget-header ui-corner-all ui-helper-clearfix">

	<a class="fg-button ui-state-default ui-state-disabled fg-button-icon-left ui-corner-all" id="button-article-new" href="#" title="Add new article">
		<span class="ui-icon ui-icon-circle-plus"></span>New article
	</a>

	<?php
	/*
	<!--
	<a class="fg-button ui-state-default ui-state-disabled fg-button-icon-left ui-corner-all" id="button-article-rename" href="#" title="Rename selected article">
		<span class="ui-icon ui-icon-pencil"></span>Edit
	</a>

	<a class="fg-button ui-state-default ui-state-disabled fg-button-icon-left ui-corner-all" id="button-article-delete" href="#" title="Delete selected article">
		<span class="ui-icon ui-icon-trash"></span>Delete
	</a>
	-->
	*/
	?>

</div>
<div id="tree-articles" class="ui-layout-content"></div>

<div class='pb-leftframe-meta'>
	<div>
		<p class="nav-userinfo"><?php echo $polarbear_u ?></p>
		<p class="nav-userinfo-logout"><a href="login.php?logout">Log out</a></p>
		<p class="nav-userinfo-visitWebsite"><a href="/">Go to <?php echo POLARBEAR_DOMAIN ?></a></p>
	</div>
</div>

<div id="polarbear-article-edit-dialog-loading" style="display: none" class="ui-layout-ignore">
	Loading article...
</div>