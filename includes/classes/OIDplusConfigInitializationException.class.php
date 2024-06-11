<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusConfigInitializationException extends OIDplusHtmlException {

	/**
	 * @param string $message
	 */
	public function __construct(string $message) {

		try {
			$title = _L('OIDplus initialization error');

			$message = '<p>' . $message . '</p>';
			$message .= '<p>' . _L('Please check the file %1', '<b>'.OIDplus::getUserDataDir("baseconfig").'config.inc.php</b>');
			if (is_dir(__DIR__ . '/../../setup')) {
				$message .= ' ' . _L('or run <a href="%1">setup</a> again', OIDplus::webpath(null, OIDplus::PATH_RELATIVE) . 'setup/');
			}
			$message .= '</p>';
		} catch (\Throwable $e) {
			// In case something fails very hard (i.e. the translation), then we still must show the Exception somehow!
			// We intentionally catch Exception and Error
			$title = 'OIDplus initialization error';
			$message = '<p>'.$message.'</p><p>Please check the file <b>'.OIDplus::getUserDataDir("baseconfig").'config.inc.php</b> or run <b>setup/</b> again</p>';
		}

		parent::__construct($message, $title, 500);
	}

}