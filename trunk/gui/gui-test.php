<?php
#require("includes/admin-header.php");
?>

<h1>GUI/Komponenter</h1>

<h2>jquery.ui-baserade komponenter</h2>

			<div class="ui-widget">
				<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em; margin-top: 20px;">
					<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>
					<strong>Hey!</strong> Sample ui-state-highlight style.</p>

				</div>
			</div>
			<br>
			<div class="ui-widget">
				<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;">
					<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span>
					<strong>Alert:</strong> Sample ui-state-error style.</p>
				</div>
			</div>



<h2>Knappar</h2>

<div class="buttons">

	<a href="#" class="button">
		<img src="images/silkicons/user_add.png" alt="" />
	</a>
	<a href="#" class="button">
		<img src="images/silkicons/group_add.png" alt="" />
	</a>

	<a href="#" class="button">
		<img src="images/silkicons/user_add.png" alt="" />
		Ny användare
	</a>
	
	<a href="#" class="button">
		<img src="images/silkicons/group_add.png" alt="" />
		Ny grupp
	</a>
	
	<a href="#" class="button button-disabled">
		<img src="images/silkicons/group_add.png" alt="" />
		Inaktiv knapp
	</a>

</div>

<h2>Formulär</h2>



<div class="fields">

	<!-- förnamn + efternamn, 50% i var -->
	<div class="group">

		<label>Personal information</label>
		
		<div class="col left">
			<label for="firstname">First name</label>
			<input type="text" id="firstname" class="text ui-corner-all ui-widget-content" />
		</div>
		<div class="col right">
			<label for="lastname">Last name</label>
			<input type="text" id="lastname" class="text ui-corner-all ui-widget-content" />
		</div>
		
		<div class="full">
			<label for="address">Address</label>
			<input id="address" type="text" class="text ui-corner-all ui-widget-content" />
		</div>
		
	</div>
	
	<div class="group">
		<label for="yyyy">Personnummer</label>
		<div class="col">
			<label for="yyyy">YYYY</label>
			<input type="text" id="yyyy" size="4" class="text ui-corner-all ui-widget-content" />
		</div>
		<div class="col">
			<label for="mm">MM</label>
			<input id="mm" type="text" size="2" class="text ui-corner-all ui-widget-content" />
		</div>
		<div class="col">
			<label for="dd">DD</label>
			<input id="dd" type="text" size="2" class="text ui-corner-all ui-widget-content" /><span> - </span>
		</div>
		<div class="col">
			<label for="xxxx">xxxx</label>
			<input id="xxxx" type="text" size="4" class="text ui-corner-all ui-widget-content" />
		</div>
		<div class="clearer"></div>
	</div>
		
	<div class="group">
		<label>Can log in</label>
		<div class="checkboxes">
			<input type="radio" name="canLogIn" id="canLogIn1" value="1" /> <label for="canLogIn1">Yes</label>
			<input type="radio" name="canLogIn" id="canLogIn0" value="0" /> <label for="canLogIn0">No</label>
		</div>
	</div>

	<div class="group">
		<label>Password</label>
		<p><a href="#">Change password</a></p>
	</div>

	<div class="actions ui-helper-clearfix">
		<a title="Add new article" href="#" id="button-article-new" class="fg-button ui-state-default fg-button-icon-left ui-corner-all">
			<span class="ui-icon ui-icon-circle-plus"></span>
			Button
		</a>
		<span class="polarbear-afterbuttons">
			or
			<a href="#" class="users-user-edit-cancel">Cancel editing</a>
		</span>
	</div>
	
</div>

<?php
#require("includes/admin-footer.php");
?>
