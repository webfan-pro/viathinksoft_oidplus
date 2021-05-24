<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusAuthContentStoreSession extends OIDplusAuthContentStore {

	protected static function getSessionHandler() {
		static $sesHandler = null;
		if (is_null($sesHandler)) {
			$sesHandler = new OIDplusSessionHandler();
		}
		return $sesHandler;
	}

	// Override abstract functions
	# TODO: shouldn't we just include OIDplusSessionHandler in this class?

	public function getValue($name, $default = NULL) {
		return self::getSessionHandler()->getValue($name, $default);
	}

	public function setValue($name, $value) {
		return self::getSessionHandler()->setValue($name, $value);
	}

	public function exists($name) {
		return self::getSessionHandler()->exists($name);
	}

	public function delete($name) {
		return self::getSessionHandler()->delete($name);
	}

	public function destroySession() {
		return self::getSessionHandler()->destroySession();
	}

	public static function getActiveProvider() {
		static $contentProvider = null;

		if (!$contentProvider) {
			if (self::getSessionHandler()->isActive()) {
				$contentProvider = new OIDplusAuthContentStoreSession();
			}
		}

		return $contentProvider;
	}

	public function raLoginEx($email, &$loginfo) {
		$this->raLogin($email);
		if (is_null(self::getActiveProvider())) {
			$loginfo = 'into new PHP session';
		} else {
			$loginfo = 'into existing PHP session';
		}
	}

	public function adminLoginEx(&$loginfo) {
		$this->adminLogin();
		if (is_null(self::getActiveProvider())) {
			$loginfo = 'into new PHP session';
		} else {
			$loginfo = 'into existing PHP session';
		}
	}

	public function raLogoutEx($email, &$loginfo) {
		$this->raLogout($email);
		$loginfo = 'from PHP session';
	}

	public function adminLogoutEx(&$loginfo) {
		$this->adminLogout();
		$loginfo = 'from PHP session';
	}

	public function activate() {
		# Sessions automatically activate during setValue()
		return;
	}

}