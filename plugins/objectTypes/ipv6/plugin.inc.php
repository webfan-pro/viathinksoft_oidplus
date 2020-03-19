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

if (!defined('IN_OIDPLUS')) die();

class OIDplusIpv6 extends OIDplusObject {
	private $ipv6;
	private $bare;
	private $cidr;

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'IPv6';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function __construct($ipv6) {
		$this->ipv6 = $ipv6;

		if (!empty($ipv6)) {
			if (strpos($ipv6, '/') === false) $ipv6 .= '/128';
			list($bare, $cidr) = explode('/', $ipv6);
			$this->bare = $bare;
			$this->cidr = $cidr;
			if (!ipv6_valid($bare)) throw new Exception("Invalid IPv6");
			if (!is_numeric($cidr)) throw new Exception("Invalid IPv6");
			if ($cidr < 0) throw new Exception("Invalid IPv6");
			if ($cidr > 128) throw new Exception("Invalid IPv6");
			$this->bare = ipv6_normalize($this->bare);
		}
	}

	public static function parse($node_id) {
		@list($namespace, $ipv6) = explode(':', $node_id, 2);
		if ($namespace !== 'ipv6') return false;
		return new self($ipv6);
	}

	public static function objectTypeTitle() {
		return "IPv6 Network Blocks";
	}

	public static function objectTypeTitleShort() {
		return "IPv6";
	}

	public static function ns() {
		return 'ipv6';
	}

	public static function root() {
		return 'ipv6:';
	}

	public function isRoot() {
		return $this->ipv6 == '';
	}

	public function nodeId() {
		return 'ipv6:'.$this->ipv6;
	}

	public function addString($str) {
		if (strpos($str, '/') === false) $str .= "/128";

		if (!$this->isRoot()) {
			if (!ipv6_in_cidr($this->bare.'/'.$this->cidr, $str)) {
				throw new Exception("Cannot add this address, because it must be inside the address range of the superior range.");
			}
		}

		list($ipv6, $cidr) = explode('/', $str);
		if ($cidr < 0) throw new Exception("Invalid IPv6 address '$str'");
		if ($cidr > 128) throw new Exception("Invalid IPv6 address '$str'");
		$ipv6_normalized = ipv6_normalize($ipv6);
		if (!$ipv6_normalized) throw new Exception("Invalid IPv6 address '$str'");
		return 'ipv6:'.$ipv6_normalized.'/'.$cidr; // overwrite; no hierarchical tree
	}

	public function crudShowId(OIDplusObject $parent) {
		return $this->ipv6;
	}

	public function crudInsertPrefix() {
		return '';
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->ipv6;
	}

	public function defaultTitle() {
		return $this->ipv6;
	}

	public function isLeafNode() {
		return $this->cidr >= 128;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusIpv6::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where parent = ?", array(self::root()));
			if (OIDplus::db()->num_rows($res) > 0) {
				$content  = 'Please select a network block in the tree view at the left to show its contents.';
			} else {
				$content  = 'Currently, no network blocks are registered in the system.';
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>Manage root objects</h2>';
				} else {
					$content .= '<h2>Available objects</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$content = '<h2>Technical information</h2>';

			$content .= '<p>IPv6/CIDR: <code>' . ipv6_normalize($this->bare) . '/' . $this->cidr . '</code><br>';
			if ($this->cidr < 128) {
				$content .= 'First address: <code>' . ipv6_cidr_min_ip($this->bare . '/' . $this->cidr) . '</code><br>';
				$content .= 'Last address: <code>' . ipv6_cidr_max_ip($this->bare . '/' . $this->cidr) . '</code></p>';
			} else {
				$content .= 'Single host address</p>';
			}

			$content .= '<h2>Description</h2>%%DESC%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>Create or change subsequent objects</h2>';
				} else {
					$content .= '<h2>Subsequent objects</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	public function one_up() {
		$cidr = $this->cidr - 1;
		if ($cidr < 0) return false; // cannot go further up

		$tmp = ipv6_normalize_range($this->bare . '/' . $cidr);
		return self::parse($this->ns() . ':' . $tmp);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;
		return ipv6_distance($to->ipv6, $this->ipv6);
	}
}

OIDplus::registerObjectType('OIDplusIpv6');