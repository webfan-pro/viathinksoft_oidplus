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

class OIDplusPageAdminSoftwareUpdate extends OIDplusPagePluginAdmin {

	public function init($html=true) {
	}

	public function action($actionID, $params) {
		if ($actionID == 'update_now') {
			@set_time_limit(0);

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')));
			}

			if (OIDplus::getInstallType() === 'git-wc') {
				$cmd = 'git pull -s recursive -X theirs 2>&1';

				$ec = -1;
				$out = array();
				exec($cmd, $out, $ec);

				$res = _L('Execute command:').' '.$cmd."\n\n".trim(implode("\n",$out));
				if ($ec === 0) {
					$rev = 'HEAD'; // do not translate
					return array("status" => 0, "content" => $res, "rev" => $rev);
				} else {
					return array("status" => -1, "error" => $res, "content" => "");
				}
			}
			else if (OIDplus::getInstallType() === 'svn-wc') {
				$cmd = 'svn update --accept theirs-full 2>&1';

				$ec = -1;
				$out = array();
				exec($cmd, $out, $ec);

				$res = _L('Execute command:').' '.$cmd."\n\n".trim(implode("\n",$out));
				if ($ec === 0) {
					$rev = 'HEAD'; // do not translate
					return array("status" => 0, "content" => $res, "rev" => $rev);
				} else {
					return array("status" => -1, "error" => $res, "content" => "");
				}
			}
			else if (OIDplus::getInstallType() === 'svn-snapshot') {

				$rev = $params['rev'];

				// Download and unzip

				if (function_exists('gzdecode')) {
					$url = sprintf(parse_ini_file(__DIR__.'/consts.ini')['update_package_gz'], $rev-1, $rev);
					$cont = url_get_contents($url);
					if ($cont !== false) $cont = @gzdecode($cont);
				} else {
					$url = sprintf(parse_ini_file(__DIR__.'/consts.ini')['update_package'], $rev-1, $rev);
					$cont = url_get_contents($url);
				}

				if ($cont === false) throw new OIDplusException(_L("Update %1 could not be downloaded from ViaThinkSoft server. Please try again later.",$rev));

				// Check signature...

				if (function_exists('openssl_verify')) {

					$m = array();
					if (!preg_match('@<\?php /\* <ViaThinkSoftSignature>(.+)</ViaThinkSoftSignature> \*/ \?>\n@ismU', $cont, $m)) {
						throw new OIDplusException(_L("Update package file of revision %1 not digitally signed",$rev));
					}
					$signature = base64_decode($m[1]);

					$naked = preg_replace('@<\?php /\* <ViaThinkSoftSignature>(.+)</ViaThinkSoftSignature> \*/ \?>\n@ismU', '', $cont);
					$hash = hash("sha256", $naked."update_".($rev-1)."_to_".($rev).".txt");

					$public_key = file_get_contents(__DIR__.'/public.pem');
					if (!openssl_verify($hash, $signature, $public_key, OPENSSL_ALGO_SHA256)) {
						throw new OIDplusException(_L("Update package file of revision %1: Signature invalid",$rev));
					}

				}

				// All OK! Now write file

				$tmp_filename = 'update_'.generateRandomString(10).'.tmp.php';
				$local_file = OIDplus::localpath().$tmp_filename;
				$web_file = OIDplus::webpath().$tmp_filename;

				@file_put_contents($local_file, $cont);

				if (!file_exists($local_file) || (@file_get_contents($local_file) !== $cont)) {
					throw new OIDplusException(_L('Update file could not written. Probably there are no write-permissions to the root folder.'));
				}

				// Now call the written file
				// Note: we may not use eval($cont) because script uses die()

				$res = url_get_contents($web_file);
				if ($res === false) {
					throw new OIDplusException(_L('Communication with ViaThinkSoft server failed'));
				}

				return array("status" => 0, "content" => $res, "rev" => $rev);
			}
			else {
				throw new OIDplusException(_L('Multiple version files/directories (oidplus_version.txt, .version.php, .git and .svn) are existing! Therefore, the version is ambiguous!'));
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		$parts = explode('.',$id,2);
		if (!isset($parts[1])) $parts[1] = '';
		if ($parts[0] == 'oidplus:software_update') {
			@set_time_limit(0);

			$handled = true;
			$out['title'] = _L('Software update');
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')).'</p>';
				return;
			}

			$out['text'] .= '<div id="update_versioninfo">';

			$out['text'] .= '<p><u>'._L('There are three possibilities how to keep OIDplus up-to-date').':</u></p>';

			$out['text'] .= '<p><b>'._L('Method A').'</b>: '._L('Install OIDplus using the subversion tool in your SSH/Linux shell using the command <code>svn co %1</code> and update it regularly with the command <code>svn update</code> . This will automatically download the latest version and check for conflicts. Highly recommended if you have a Shell/SSH access to your webspace!',htmlentities(parse_ini_file(__DIR__.'/consts.ini')['svn']).'/trunk').'</p>';

			$out['text'] .= '<p><b>'._L('Method B').'</b>: '._L('Install OIDplus using the Git client in your SSH/Linux shell using the command <code>git clone %1</code> and update it regularly with the command <code>git pull</code> . This will automatically download the latest version and check for conflicts. Highly recommended if you have a Shell/SSH access to your webspace!','https://github.com/danielmarschall/oidplus.git').'</p>';

			$out['text'] .= '<p><b>'._L('Method C').'</b>: '._L('Install OIDplus by downloading a TAR.GZ file from www.viathinksoft.com, which contains an SVN snapshot, and extract it to your webspace. The TAR.GZ file contains a file named ".version.php" which contains the SVN revision of the snapshot. This update-tool will then try to update your files on-the-fly by downloading them from the ViaThinkSoft SVN repository directly into your webspace directory. A change conflict detection is NOT implemented. It is required that the files on your webspace have create/write/delete permissions. Only recommended if you have no access to the SSH/Linux shell.').'</p>';

			$out['text'] .= '<hr>';

			$installType = OIDplus::getInstallType();

			if ($installType === 'ambigous') {
				$out['text'] .= '<font color="red">'.strtoupper(_L('Error')).': '._L('Multiple version files/directories (oidplus_version.txt, .version.php, .git and .svn) are existing! Therefore, the version is ambiguous!').'</font>';
				$out['text'] .= '</div>';
			} else if ($installType === 'unknown') {
				$out['text'] .= '<font color="red">'.strtoupper(_L('Error')).': '._L('The version cannot be determined, and the update needs to be applied manually!').'</font>';
				$out['text'] .= '</div>';
			} else if (($installType === 'svn-wc') || ($installType === 'git-wc') || ($installType === 'svn-snapshot')) {
				if ($installType === 'svn-wc') {
					$out['text'] .= '<p>'._L('You are using <b>method A</b> (SVN working copy).').'</p>';
					$requireInfo = _L('shell access with svn/svnversion tool, or PDO/SQLite3 PHP extension');
					$updateCommand = 'svn update';
				} else if ($installType === 'git-wc') {
					$out['text'] .= '<p>'._L('You are using <b>method B</b> (Git working copy).').'</p>';
					$requireInfo = _L('shell access with Git client');
					$updateCommand = 'git pull';
				} else if ($installType === 'svn-snapshot') {
					$out['text'] .= '<p>'._L('You are using <b>method C</b> (Snapshot TAR.GZ file with .version.php file).').'</p>';
					$requireInfo = ''; // unused
					$updateCommand = ''; // unused
				}

				$local_installation = OIDplus::getVersion();
				$newest_version = $this->getLatestRevision();

				$out['text'] .= _L('Local installation: %1',($local_installation ? $local_installation : _L('unknown'))).'<br>';
				$out['text'] .= _L('Latest published version: %1',($newest_version ? $newest_version : _L('unknown'))).'<br><br>';

				if (!$newest_version) {
					$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine the latest version. Probably the ViaThinkSoft server could not be reached.').'</font></p>';
					$out['text'] .= '</div>';
				} else if (!$local_installation) {
					if ($installType === 'svn-snapshot') {
						$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine its version.').'</font></p>';
					} else {
						$out['text'] .= '<p><font color="red">'._L('OIDplus could not determine its version. (Required: %1). Please update your system manually via the "%2" command regularly.',$requireInfo,$updateCommand).'</font></p>';
					}
					$out['text'] .= '</div>';
				} else if (substr($local_installation,4) >= substr($newest_version,4)) {
					$out['text'] .= '<p><font color="green">'._L('You are already using the latest version of OIDplus.').'</font></p>';
					$out['text'] .= '</div>';
				} else {
					if (($installType === 'svn-wc') || ($installType === 'git-wc')) {
						$out['text'] .= '<p><font color="blue">'._L('Please enter %1 into the SSH shell to update OIDplus to the latest version.','<code>'.$updateCommand.'</code>').'</font></p>';
						$out['text'] .= '<p>'._L('Alternatively, click this button to execute the command through the web-interface (command execution and write permissions required).').'</p>';
					}

					$out['text'] .= '<p><input type="button" onclick="OIDplusPageAdminSoftwareUpdate.doUpdateOIDplus('.((int)substr($local_installation,4)+1).', '.substr($newest_version,4).')" value="'._L('Update NOW').'"></p>';

					// TODO: Open "system_file_check" without page reload.
					// TODO: Only show link if the plugin is installed
					$out['text'] .= '<p><font color="red">'.strtoupper(_L('Warning')).': '._L('Please make a backup of your files before updating. In case of an error, the OIDplus system (including this update-assistant) might become unavailable. Also, since the web-update does not contain collision-detection, changes you have applied (like adding, removing or modified files) might get reverted/lost! (<a href="%1">Click here to check which files have been modified</a>) In case the update fails, you can download and extract the complete <a href="https://www.viathinksoft.com/projects/oidplus">SVN-Snapshot TAR.GZ file</a> again. Since all your data should lay inside the folder "userdata" and "userdata_pub", this should be safe.','?goto='.urlencode('oidplus:system_file_check')).'</font></p>';

					$out['text'] .= '</div>';

					$out['text'] .= $this->showPreview($local_installation, $newest_version);
				}
			}
		} else {
			$handled = false;
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:software_update',
			'icon' => $tree_icon,
			'text' => _L('Software update')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}

	private $releases_ser = null;

	private function showChangelog($local_ver) {

		try {
			if (is_null($this->releases_ser)) {
				$url = parse_ini_file(__DIR__.'/consts.ini')['revisionlog'];
				$cont = url_get_contents($url);
				if ($cont === false) return false;
				$this->releases_ser = $cont;
			} else {
				$cont = $this->releases_ser;
			}
			$content = '';
			$ary = @unserialize($cont);
			if ($ary === false) return false;
			krsort($ary);
			foreach ($ary as $rev => $data) {
				if ($rev <= substr($local_ver,4)) continue;
				$comment = empty($data['msg']) ? _L('No comment') : $data['msg'];
				$tex = _L("New revision %1 by %2",$rev,$data['author'])." (".$data['date'].") ";
				$content .= trim($tex . str_replace("\n", "\n".str_repeat(' ', strlen($tex)), $comment));
				$content .= "\n";
			}
			return $content;
		} catch (Exception $e) {
			return false;
		}

	}

	private function getLatestRevision() {
		try {
			if (is_null($this->releases_ser)) {
				$url = parse_ini_file(__DIR__.'/consts.ini')['revisionlog'];
				$cont = url_get_contents($url);
				if ($cont === false) return false;
				$this->releases_ser = $cont;
			} else {
				$cont = $this->releases_ser;
			}
			$ary = @unserialize($cont);
			if ($ary === false) return false;
			krsort($ary);
			$max_rev = array_keys($ary)[0];
			$newest_version = 'svn-' . $max_rev;
			return $newest_version;
		} catch (Exception $e) {
			return false;
		}
	}

	private function showPreview($local_installation, $newest_version) {
		$out = '<h2 id="update_header">'._L('Preview of update %1 &rarr; %2',$local_installation,$newest_version).'</h2>';

		ob_start();
		try {
			$cont = $this->showChangelog($local_installation);
		} catch (Exception $e) {
			$cont = _L('Error: %1',$e->getMessage());
		}
		ob_end_clean();

		$cont = preg_replace('@!!!(.+)\\n@', '<font color="red">!!!\\1</font>'."\n", $cont);

		$out .= '<pre id="update_infobox">'.$cont.'</pre>';

		return $out;
	}
}