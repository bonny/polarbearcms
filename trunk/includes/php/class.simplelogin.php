<?php
/**
 * Enkelt inloggningssystem
 * inmatade uppgifter sparas i session eller cookie? cookie för att fungera längre?
 *
 * $login = new Simplelogin($login, $password);
 * $login->ok(); // true om man är inloggad
 * $login->getForm(); // ger loginform
 * $login->getLogoutLink(); // ger logga ut-länk
 *
 */
class Simplelogin
{

    protected $username, $password, $ok, $okmsg, $errmsg;
    public $logoutURL;

    /**
     * Konstruktor
     * @return
     * @param object $username användarnamn för att logga in
     * @param object $password lösenord för att logga in
     */
    function Simplelogin($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

		// kontrollera om man har befintlig cookie
		if ($_COOKIE["simplelogin"]) {
			$this->ok = true;
		}

        // sköt inloggning etc automatiskt. för det e enkelt..
        if ($_POST["simplelogin_action"] == "simplelogin-dologin")
        {
            if ($this->check($_POST["simplelogin_username"], $_POST["simplelogin_password"])) {
				// inloggning lyckades. check sätter cookie och this->ok
            } else {
				// inloggning lyckades inte
				$this->errmsg = "Inloggningen misslyckades. Kontrollera att du fyllt i uppgifterna korrekt.";
            }
        } else if ($_GET["simplelogin_action"]=="simplelogin-logout") {
			// dit för utloggning
			$_COOKIE["simplelogin"] = false;
			$this->ok = false;
			#$qs = new Query_String;
			#unset($qs->action);
			#echo $qs;
			// todo: ta fram hela sökvägen till aktuell fil så vi kan göra en korrent header(location
			// ..eller: man vill nog inte tillbaka till sidan man var på, som kan t.ex. vara /intranet
			// bättre är att gå till en av utvecklaren bestämd sida
			// eller..skit samma: det är upp till sidmallen att fixa detta. heter ju simplelogin av en anledning: enkelt, inte så förändringsbart
        } else {
        }


    }

	/**
	 * Kollar om man är inloggad
	 * @return bool
	 */
    function ok()
    {
        return $this->ok;
    }

    /**
     * Kollar att angivna uppgifter är ok
     * @return bool ok
     * @param object $username
     * @param object $password
     */
    function check($username, $password)
    {
        if (($username == $this->username) && ($password = $this->password))
        {
            $this->ok = true;
            setcookie("simplelogin", true, time()+60*60*24*30, "/");
            return true;
        } else
        {
            $this->ok = false;
            setcookie("simplelogin", false, time()+60*60*24*30, "/");
            return false;
        }
    }

	/**
	 * Ger länk för utloggnings
	 * @return string länk
	 */
	function getLogoutLink() {
		// länk = aktuellt sida med action=simplelogin-logout
		$qs = new Query_String;
		$qs->simplelogin_action = "simplelogin-logout";
		return $qs;
	}

    /**
	 * Skapar formulär för att ange inloggningsuppgifter
     * @return string formulär
     */
    function getForm()
    {
        $out = "<form method='post'>";
        if ($this->errmsg) {
			$out .= "<p class='errmsg'><strong>$this->errmsg</strong></p>";
        }
        if (! empty($this->username))
        {
            $out .= "<p><label for='simplelogin_username'>Användarnamn</label><input id='simplelogin_username' name='simplelogin_username' type='text' /></p>";
        }
        if (! empty($this->password))
        {
            $out .= "<p><label for='simplelogin_password'>Lösenord</label><input id='simplelogin_password' name='simplelogin_password' type='password' /></p>";
        }
        $out .= "<p><input type='submit' value='Ok' /></p>";
        $out .= "<input type='hidden' name='simplelogin_action' value='simplelogin-dologin' />";
        $out .= "</form>";

        return $out;
    }

}
