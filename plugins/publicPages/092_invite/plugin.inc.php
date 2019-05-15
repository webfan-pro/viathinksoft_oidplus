<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class OIDplusPagePublicInvite extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public function priority() {
		return 92;
	}

	public function action(&$handled) {
		if ($_POST["action"] == "invite_ra") {
			$handled = true;
			$email = $_POST['email'];

			if (!oidplus_valid_email($email)) {
				die('Invalid email address');
			}

			if (RECAPTCHA_ENABLED) {
				$secret=RECAPTCHA_PRIVATE;
				$response=$_POST["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					die('Captcha wrong');
				}
			}

			$timestamp = time();
			$activate_url = OIDplus::system_url() . '?goto='.urlencode('oidplus:activate_ra$'.$email.'$'.$timestamp.'$'.OIDplus::authUtils()::makeAuthKey('activate_ra;'.$email.';'.$timestamp));

			$message = OIDplus::gui()::getInvitationText($_POST['email']);
			$message = str_replace('{{ACTIVATE_URL}}', $activate_url, $message);

			my_mail($email, OIDplus::config()->systemTitle().' - Invitation', $message, OIDplus::config()->globalCC());

			die("OK");
		}

		if ($_POST["action"] == "activate_ra") {
			$handled = true;

			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];
			$email = $_POST['email'];
			$auth = $_POST['auth'];
			$timestamp = $_POST['timestamp'];

			if (!OIDplus::authUtils()::validateAuthKey('activate_ra;'.$email.';'.$timestamp, $auth)) {
				die('Invalid auth key');
			}

			if ((OIDplus::config()->getValue('max_ra_invite_time') > 0) && (time()-$timestamp > OIDplus::config()->getValue('max_ra_invite_time'))) {
				die('Invitation expired!');
			}

			if ($password1 !== $password2) {
				die('Passwords are not equal');
			}

			if (strlen($password1) < OIDplus::config()->minRaPasswordLength()) {
				die('Password is too short. Minimum password length: '.OIDplus::config()->minRaPasswordLength());
			}

			$ra = new OIDplusRA($email);
			$ra->register_ra($password1);

			die('OK');
		}
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('max_ra_invite_time', 'Max RA invite time in seconds (0 = infinite)', '0', 0, 1);
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'max_ra_invite_time') {
			if (!is_numeric($value) || ($value < 0)) {
				throw new Exception("Please enter a valid value.");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:invite_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$origin = explode('$',$id)[2];

			$out['title'] = 'Invite a Registration Authority';
			$out['icon'] = 'plugins/publicPages/'.basename(__DIR__).'/invite_ra_big.png';

			try {
				$cont = self::getInvitationText($email);

				$out['text'] .= '<p>You have chosen to invite <b>'.$email.'</b> as an Registration Authority. If you click "Send", the following email will be sent to '.$email.':</p><p><i>'.nl2br(htmlentities($cont)).'</i></p>
				  <form id="inviteForm" onsubmit="return inviteFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="origin" value="'.htmlentities($origin).'"/>'.
				 (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
				                   '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '').
				' <br>
				    <input type="submit" value="Send invitation">
				  </form>';

			} catch (Exception $e) {

				$out['icon'] = 'img/error_big.png';
				$out['text'] = "Error: ".$e->getMessage();

			}
		} else if (explode('$',$id)[0] == 'oidplus:activate_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = 'Register as Registration Authority';
			$out['icon'] = 'plugins/publicPages/'.basename(__DIR__).'/activate_ra_big.png';

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$out['text'] = 'This RA is already registered and does not need to be invited.';
			} else {
				if (!OIDplus::authUtils()::validateAuthKey('activate_ra;'.$email.';'.$timestamp, $auth)) {
					$out['icon'] = 'img/error_big.png';
					$out['text'] = 'Invalid authorization. Is the URL OK?';
				} else {
					// TODO: like in the FreeOID plugin, we could ask here at least for a name for the RA
					$out['text'] = '<p>E-Mail-Adress: <b>'.$email.'</b></p>

					  <form id="activateRaForm" onsubmit="return activateRaFormOnSubmit();">
					    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
					    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
					    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
					    <label class="padding_label">New password:</label><input type="password" id="password1" value=""/><br><br>
					    <label class="padding_label">Again:</label><input type="password" id="password2" value=""/><br><br>
					    <input type="submit" value="Register">
					  </form>';
				}
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false) {
		return false;
	}

	private function getInvitationText($email) {
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) > 0) {
			throw new Exception("This RA is already registered and does not need to be invited.");
		}

		if (!OIDplus::authUtils()::isAdminLoggedIn()) {
			// Check if the RA may invite the user (i.e. the they are the parent of an OID of that person)
			$ok = false;
			$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where ra_email = '".OIDplus::db()->real_escape_string($email)."'");
			while ($row = OIDplus::db()->fetch_array($res)) {
				$objParent = OIDplusObject::parse($row['parent']);
				if (is_null($objParent)) throw new Exception("Type of ".$row['parent']." unknown");
				if ($objParent->userHasWriteRights()) {
					$ok = true;
				}
			}
			if (!$ok) {
				throw new Exception('You may not invite this RA. Maybe you need to log in again.');
			}
		}

		$list_of_oids = array();
		$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where ra_email = '".OIDplus::db()->real_escape_string($email)."'");
		while ($row = OIDplus::db()->fetch_array($res)) {
			$list_of_oids[] = $row['id'];
		}

		$message = file_get_contents(__DIR__ . '/../invite_msg.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
		$message = str_replace('{{OID_LIST}}', implode("\n", $list_of_oids), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDplus::config()->getValue('admin_email'), $message);
		$message = str_replace('{{PARTY}}', OIDplus::authUtils()::isAdminLoggedIn() ? 'the system administrator' : 'a superior Registration Authority', $message);

		// {{ACTIVATE_URL}} will be resolved in action.php

		return $message;
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicInvite());
