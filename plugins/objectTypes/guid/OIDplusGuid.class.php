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

class OIDplusGuid extends OIDplusObject {
	private $guid;

	public function __construct($guid) {
		// No syntax checks, since we also allow categories.
		$this->guid = $guid;
	}

	public static function parse($node_id) {
		@list($namespace, $guid) = explode(':', $node_id, 2);
		if ($namespace !== 'guid') return false;
		return new self($guid);
	}

	public static function objectTypeTitle() {
		return "Globally Unique Identifier (GUID)";
	}

	public static function objectTypeTitleShort() {
		return "GUID";
	}

	public static function ns() {
		return 'guid';
	}

	public static function root() {
		return 'guid:';
	}

	public function isRoot() {
		return $this->guid == '';
	}

	public function nodeId() {
		return 'guid:'.$this->guid;
	}

	public function addString($str) {
		// TODO! es soll m�glich sein, zwei kategorien zu haben mit dem selben namen (filedialog/filedialog/guid)
		return 'guid:'.$str;
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->guid;
	}

	public function crudInsertPrefix() {
		return '';
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->guid;
	}

	public function defaultTitle() {
		return $this->guid;
	}

	public function isLeafNode() {
		return uuid_valid($this->guid);
	}

	public function getContentPage(&$title, &$content) {
		if ($this->isRoot()) {
			$title = OIDplusGuid::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = '".OIDplus::db()->real_escape_string(self::root())."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$content  = 'Please select a GUID in the tree view at the left to show its contents.';
			} else {
				$content  = 'Currently, no GUID is registered in the system.';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>Manage root objects / categories</h2>';
				} else {
					$content .= '<h2>Available objects / categories</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			if ($this->isLeafNode()) {
				ob_start();
				uuid_info($this->guid);
				$info = ob_get_contents();
				ob_end_clean();
				$info = preg_replace('@:\s*(.+)\n@ismU', ": <code>\\1</code><br>", $info);

				$content = "<h2>Technical information</h2><p>UUID: <code>" . uuid_canonize($this->guid) . "</code><br>" .
				       "OID: <code>" . uuid_to_oid($this->guid) . "</code><br>" .
				       "C++ notation: <code>" . uuid_c_syntax($this->guid) . "</code><br>" .
				       "$info";
				//      "<a href=\"https://misc.daniel-marschall.de/tools/uuid_mac_decoder/interprete_uuid.php?uuid=".urlencode($this->guid)."\">More technical information</a></p>";
			} else {
				$content = '';
			}

			$content .= '<h2>Description</h2>%%DESC%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>Create or change subsequent objects / categories</h2>';
				} else {
					$content .= '<h2>Subsequent objects / categories</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}
}

OIDplusObject::$registeredObjectTypes[] = 'OIDplusGuid';

