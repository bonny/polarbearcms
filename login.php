<?php
require("polarbear-boot.php");

$okmsg = null;
$errmsg = null;

/**
 * hide polarbear tab completely
 */
if (isset($_GET["removeTab"])) {
	$returnto = $_SERVER["HTTP_REFERER"];
	setcookie("pb_been_logged_in", "0", time()+60*60*24*30, "/");
	header("Location: $returnto");
	exit;
}

/**
 * log out user
 */
if (isset($_GET["logout"]) && $polarbear_u) {
	$polarbear_u->logout();
	$okmsg = "You have been logged out.";
	if (isset($_GET["returnto"])) {
		$returnto = $_GET["returnto"];
		if ($returnto == "referer") {
			$_SESSION["pb_logged_out"] = "1";
			$_SESSION["pb_show_site_edit_tab"] = "1";
			$returnto = $_SERVER["HTTP_REFERER"];
		}
		header("Location: $returnto");
		exit;
	}
}


if (isset($_POST["action"]) && $_POST["action"] == "setNewPassword") {

	$email = $polarbear_db->escape(trim($_POST["email"]));
	$code = $polarbear_db->escape(trim($_POST["code"]));
	
	// kontrollera att koden är rätt
	$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_users WHERE email = '$email' AND passwordResetCode = '$code'";
	if ($userID = $polarbear_db->get_var($sql)) {
		// koden är ok, kolla att det är två lika lösenord vi skickat
		$password = $_POST["newPassword"];
		$passwordRepeat = $_POST["newPasswordRepeat"];
		if (($password == $passwordRepeat) && ($password!="")) {
			// ok, byt!
			$u = new PolarBear_user($userID);
			$u->changePassword($password);
			$okmsg = "Your password has been changed.";
		} else {
			// fel...
			$_GET["resetPassword"] = "1";
			$_GET["email"] = $email;
			$_GET["code"] = $code;
			if ($password != $passwordRepeat) {
				$errmsg = "Both password must be the same. Please try again.";
			} else {
				$errmsg = "Password can not be empty. Please try again.";
			}
		}
	} else {
		$errmsg = "Sorry, the information you submitted is not valid.";
	}

}

if (isset($_POST["login"])) {

	$wrong_login = false;
	$email = $polarbear_db->escape($_POST["login-email"]);

	if (POLARBEAR_PASSWORD_HASHTYPE == "MD5") {
		$password = $polarbear_db->escape(md5($_POST["login-password"].POLARBEAR_SALT));
	} else {
		$password = $polarbear_db->escape(sha1($_POST["login-password"].POLARBEAR_SALT));
	}
	
	$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_users WHERE email = '$email' and password = '$password'";
	$userID = $polarbear_db->get_var($sql);

	$options = pb_event_fire("login_start", array("userID" => $userID, "passwordHashed" => $password, "passwordCleartext" => $_POST["login-password"], "email" => $_POST["login-email"]));
	$userID = $options["userID"];
	if ($userID) {
			
		// rätt login, sätt cookie and da shit
		if (isset($_POST["login-remember-me"]) && $_POST["login-remember-me"]) {
			$persistant = true;
		} else {
			$persistant = false;
		}
		$u = new PolarBear_User($userID);
		$u->login($persistant);

		// enable site edit icons, if user is an admin
		if ($u->isAdmin()) {
			setcookie("pb_site_edit_icons_enabled", "1", time()+60*60*24*30, "/");
			setcookie("pb_been_logged_in", "1", time()+60*60*24*30, "/");
		}

		// inloggad. välkommen in i polarbear! ;)
		if (isset($_POST["returnto"])) {
			$returnto = $_POST["returnto"];
			if ($returnto == "referer") { $returnto = $_SERVER["HTTP_REFERER"]; }
			if ($u->isAdmin()) {
				$_SESSION["pb_show_site_edit_tab"] = "1";
			}
			$_SESSION["pb_ok_login"] = "1";
			header("Location: $returnto");
		} else {
			header("Location: " . POLARBEAR_WEBPATH);
		}
		exit;
		
	} else {
		// login failed
		$u = new PolarBear_User();
		$u->logout();
		$wrong_login = true;
		if ($returnto == "referer") { $returnto = $_SERVER["HTTP_REFERER"]; }
		if (isset($_POST["returnto"])) {
			$returnto = $_POST["returnto"];
			if ($returnto == "referer") { $returnto = $_SERVER["HTTP_REFERER"]; }
			#$_SESSION["pb_show_site_edit_tab"] = "1";
			$_SESSION["pb_wrong_login"] = "1";
			$_SESSION["pb_show_site_edit_tab"] = "1";
			header("Location: $returnto");
			exit;
		} else {
			$errmsg = "Wrong email or password. Please try again.";
		}
	}
}

// glömt lösenord
if (isset($_POST["forgot-password"])) {

	$email = trim($_POST["login-email"]);
	$email = $polarbear_db->escape($email);
	
	// om denna epost finns i systemet så skickar vi info till den
	$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_users WHERE email = '$email' AND email <> ''";
	if ($r = $polarbear_db->get_var($sql)) {
		$code = md5(uniqid(rand(), true));
		$sql = "UPDATE " . POLARBEAR_DB_PREFIX . "_users SET passwordResetCode = '$code' WHERE email = '$email'";
		$polarbear_db->query($sql);
		$subject = "Password reset instructions from " . POLARBEAR_DOMAIN;
		$link = "http://" . POLARBEAR_DOMAIN . POLARBEAR_WEBPATH . "login.php?resetPassword&email={$email}&code=$code";
		$body = "Please click on the following link to reset the password for your account on domain " . POLARBEAR_DOMAIN . ":";
		$body .= "\n$link";
		$body .= "\n\nIf you have not requested this information you can safely delete this message.";
		$from = "no-reply@" . POLARBEAR_DOMAIN;
		mail($email, $subject, $body, "From: $from");
		#echo $body;
		$okmsg = "An email with further instructions has been sent to your email.";
	} else {
		$errmsg = "Sorry, we couldn't find any user with that email.";
		$_GET["forgotPassword"] = "1";
	}
	
	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title>Log in | PolarBear CMS</title>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js"></script>
		<meta name="robots" content="noindex, nofollow" />
		<style type="text/css" media="all">
			@import url(<?php polarbear_webpath() ?>includes/css/reset.css);
			@import url(<?php polarbear_webpath() ?>includes/css/styles.css);
			@import url(http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/base/jquery-ui.css);
						
			#login-wrapper {
				width: 400px;
				margin: 0 auto 0 auto;
				padding: 1em;

			}
			#login-wrapper h1 {
				margin: 1em 0 1em 0;
			}
			#login-wrapper label {
				display: block;
				margin: 0 0 0 0;
				font-weight: bold;
			}
			#login-wrapper input.text, 
			#login-wrapper input.password,
			#login-wrapper input.submit
			{
				width: 97%;
				font-size: 1.5em;
				margin: .25ex 0 0em 0;
			}
			
			#login-wrapper input.submit {
				width: auto;
			}
			
			#login-wrapper label.checkbox {
				display: inline;
			}
					
			p.forgot {
				border-top: 1px solid #aaa;
				padding-top: 1em;
				margin-top: 1em;
			}
			
			#login-form {
				margin-bottom: 2em;
			}
			#login-form p {
				margin-bottom: 1.5em;
			}
			
		</style>
		<script type="text/javascript">
			var wrongLogin = <?php echo (int) $wrong_login ?>;
			$(function() {
				
				$(".pb-login-webbrowser-requirements").hide();
				
				$(".fg-button").hover(function(){$(this).toggleClass("ui-state-hover");}, function(){$(this).toggleClass("ui-state-hover");});
				
				$("#login-email,#login-password").focus(function() {
					$(this).parent("p").addClass("focused");
				});
				$("#login-email,#login-password").blur(function() {
					$(this).parent("p").removeClass("focused");
				});
				$("#login-email").focus();
				
				if (wrongLogin) {
					$("#login-form").effect("shake");
				}
				
				var version = (parseFloat($.browser.version));
				// lame browser check
				var browserOk = true;
				if ($.browser.mozilla && version >= 1.9) {
					// ok
				} else if ($.browser.safari && version >= 1.9) {
				} else if ($.browser.msie && version >= 8) {
				} else {
					browserOk = false;
				}
				
				if (browserOk == false) {
					$(".pb-login-webbrowser-requirements").show();
				}

			});
		</script>
	</head>
<body>

	<div id="login-wrapper">

		<h1>
			<img src="<?php polarbear_webpath() ?>images/polarbear/polarbear-logo2.gif" alt="PolarBear CMS logotype" />
		</h1>

		<?php
		polarbear_infomsg($okmsg, $errmsg);
		
		if (isset($_GET["forgotPassword"])) {
			?>
			<form id="login-form" method="post" action="login.php">
				<p>Forgot your password? No problem!</p>
				<p>Enter your email below and we will send you further instructions.</p>
				<p>
					<label for="login-email">Email</label>
					<input id="login-email" name="login-email" type="text" size="30" class="text ui-widget-content ui-corner-all" />
				</p>
				<p>
					<input type="submit" value="Ok" class="submit fg-button ui-state-default ui-priority-primary ui-corner-all" name="forgot-password" />
					or
					<a href="login.php">cancel</a>
				</p>
			</form>
			<?php
		} elseif (isset($_GET["resetPassword"])) {
			// om man klickat på byt-lösenord-länk
			// http://localhost/maj/login.php?resetPassword&email=par@marsapril.se&code=821f7d11429da6546dfd24da704e35dd
			$email = $polarbear_db->escape(trim($_GET["email"]));
			$code = $polarbear_db->escape(trim($_GET["code"]));
			// kontrollera att koden är rätt
			$sql = "SELECT id FROM " . POLARBEAR_DB_PREFIX . "_users WHERE email = '$email' AND passwordResetCode = '$code'";
			if ($r = $polarbear_db->get_var($sql)) {
				?>
				<form method="post" action="login.php">
					<p>
						Please enter a new password for <?php echo $email ?>:
					</p>
					<p>
						<label for="newPassword">New password</label>
						<input type="password" id="newPassword" name="newPassword" value="" />
					</p>
					<p>
						<label for="newPasswordRepeat">Repeat new password</label>
						<input type="password" id="newPasswordRepeat" name="newPasswordRepeat" value="" />
					</p>
					<p>
						<input type="hidden" name="action" value="setNewPassword" />
						<input type="hidden" name="email" value="<?php echo $email ?>" />
						<input type="hidden" name="code" value="<?php echo $code ?>" />
						<input type="submit" value="Set password" name="setNewPassword" />
					</p>
				</form>
				<?php
			} else {
				$errmsg = "Sorry, the information you submitted is not valid.";
				polarbear_infomsg("", $errmsg);
			}
			

		} else {
			?>

				<div class="pb-login-webbrowser-requirements">
				<p>
					PolarBear requires a modern webbrowser.
				</p>
				<p>
					Recommended browsers:
				</p>
				<ul>
					<li><a href="http://www.mozilla.com/firefox/ ">Firefox</a> version 3.5 or higher</li>
					<li><a href="http://www.apple.com/safari/ ">Safari</a> 3 or higher</li>
					<li><a href="http://www.microsoft.com/windows/internet-explorer/">Internet Explorer</a> 8 or higher</li>
				</ul>
			</div>


			<form id="login-form" method="post" action="login.php">
				<p>
					<label for="login-email">Email</label>
					<input id="login-email" name="login-email" type="text" size="30" class="text ui-widget-content ui-corner-all" />
				</p>
				<p>
					<label for="login-password">Password</label>
					<input id="login-password" name="login-password" type="password" size="30" class="password text ui-widget-content ui-corner-all" />
				</p>
				<!--
				<p>
					<label>Go to PolarBear CMS</label>
					<input type="radio" name="goTo" />
					<label>Go to <?php polarbear_domain() ?></label>
					<input type="radio" name="goTo" />				
				</p>
				-->
				<p>
					<input type="checkbox" class="checkbox" value="1" name="login-remember-me" id="login-remember-me" checked="checked" />
					<label class="checkbox" for="login-remember-me">Remember me on this computer</label>
				</p>

				<p>
					<input type="submit" value="Log in" class="submit fg-button ui-state-default ui-priority-primary ui-corner-all" name="login" />
					or
					<a href="http://<?php polarbear_domain() ?>">return to <?php polarbear_domain() ?></a>
				</p>
			</form>

			<p class="forgot">
				<a href="login.php?forgotPassword">Forgot your password?</a>
			</p>

			<?php
		}
		?>
	
	</div>

</body>
</html>