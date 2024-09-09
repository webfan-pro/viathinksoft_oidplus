<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\URN;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusUrn extends OIDplusObject {
	/**
	 * @var string
	 */
	private $urn;

	/**
	 * @param string $urn
	 */
	public function __construct(string $urn) {
		// No syntax checks
		$this->urn = $urn;
	}

	/**
	 * @param string $node_id
	 * @return OIDplusUrn|null
	 */
	public static function parse(string $node_id): ?OIDplusUrn {
		@list($namespace, $urn) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return null;
		return new self($urn);
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitle(): string {
		return _L('Uniform Resource Name (URN)');
	}

	/**
	 * @return string
	 */
	public static function objectTypeTitleShort(): string {
		return _L('URN');
	}

	/**
	 * @return string
	 */
	public static function ns(): string {
		return 'urn';
	}

	/**
	 * @return string
	 */
	public static function root(): string {
		return self::ns().':';
	}

	/**
	 * @return bool
	 */
	public function isRoot(): bool {
		return $this->urn == '';
	}

	/**
	 * @param bool $with_ns
	 * @return string
	 */
	public function nodeId(bool $with_ns=true): string {
		return $with_ns ? self::root().$this->urn : $this->urn;
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function addString(string $str): string {
		if ($this->isRoot()) {
			return self::root() . $str;
		} else {
			return $this->nodeId() . ':' . $str;
		}
	}

	/**
	 * @param OIDplusObject $parent
	 * @return string
	 */
	public function crudShowId(OIDplusObject $parent): string {
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	/**
	 * @return string
	 */
	public function crudInsertPrefix(): string {
		if ($this->isRoot()) {
			return 'urn:';
		} else {
			return '';
		}
	}

	/**
	 * @param OIDplusObject|null $parent
	 * @return string
	 */
	public function jsTreeNodeName(?OIDplusObject $parent=null): string {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent->isRoot()) {
			return 'urn:'.substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	/**
	 * @return string
	 */
	public function defaultTitle(): string {
		return 'urn:'.$this->urn;
	}

	/**
	 * @return bool
	 */
	public function isLeafNode(): bool {
		return false;
	}

	/**
	 * @param string $title
	 * @param string $content
	 * @param string $icon
	 * @return void
	 * @throws OIDplusException
	 */
	public function getContentPage(string &$title, string &$content, string &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusUrn::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  = '<p>'._L('Please select an object in the tree view at the left to show its contents.').'</p>';
			} else {
				$content  = '<p>'._L('Currently, no custom URN are registered in the system.').'</p>';
			}

			$content .= '<h2>'._L('Built-in URN types').'</h2>';
			$content .= '<ul>';
			$objTypesChildren = array();
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				if ($ot::ns() == 'urn') continue;
				$urn_nss = $ot::urnNs();
				if (count($urn_nss)==0) $urn_nss[] = 'x-oidplus:'.$ot::ns();
				foreach ($urn_nss as $urn_ns) {
					$content .= '<li><a '.OIDplus::gui()->link($ot::root()).'>urn:'.htmlentities($urn_ns).':</a> = <b>'.htmlentities($ot::objectTypeTitle()).'</b></li>';
				}
			}
			$content .= '</ul>';

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage custom URN').'</h2>';
				} else {
					$content .= '<h2>'._L('Custom URN types').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$content = '';

			if ($this->userHasParentalWriteRights()) {
				$content .= '<h2>'._L('Superior RA Allocation Info').'</h2>%%SUPRA%%';
			}

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type
			$content .= '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subordinate objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subordinate objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	/**
	 * @return OIDplusUrn|null
	 */
	public function one_up(): ?OIDplusUrn {
		$oid = $this->urn;

		$p = strrpos($oid, ':');
		if ($p === false) return self::parse($oid);
		if ($p == 0) return self::parse(':');

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	/**
	 * @param OIDplusObject|string $to
	 * @return int|null
	 */
	public function distance($to): ?int {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!$to) return null;
		if (!($to instanceof $this)) return null;

		$a = $to->urn;
		$b = $this->urn;

		if (substr($a,0,1) == ':') $a = substr($a,1);
		if (substr($b,0,1) == ':') $b = substr($b,1);

		$ary = explode(':', $a);
		$bry = explode(':', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return null;
		}

		return count($ary) - count($bry);
	}

	/**
	 * @return string
	 */
	public function getDirectoryName(): string {
		if ($this->isRoot()) return $this->ns();
		return $this->ns().'_'.md5($this->nodeId(false));
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	public static function treeIconFilename(string $mode): string {
		return 'img/'.$mode.'_icon16.png';
	}
}
