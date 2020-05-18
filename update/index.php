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

declare(ticks=1);

set_time_limit(0);

require_once __DIR__ . '/../includes/oidplus.inc.php';

// Note: we don't want to use OIDplus::init() in this updater (it should be independent as much as possible)
OIDplus::baseConfig(); // This call will redirect to setup if userdata/baseconfig/config.inc.php is missing

define('OIDPLUS_REPO', 'https://svn.viathinksoft.com/svn/oidplus');

?><!DOCTYPE html>
<html lang="en">

<head>
	<title>OIDplus Update</title>
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../setup/setup.css">
	<?php
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
	?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<?php
	}
	?>
</head>

<body>

<h1>Update OIDplus</h1>

<?php

if (isset($_REQUEST['update_now'])) {
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
		$secret = OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
		$response = $_POST["g-recaptcha-response"];
		$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
		$captcha_success = json_decode($verify);
	}
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) && ($captcha_success->success==false)) {
		echo '<p><font color="red"><b>CAPTCHA not sucessfully verified</b></font></p>';
		echo '<p><a href="index.php">Try again</a></p>';
	} else {
		if (!OIDplusAuthUtils::adminCheckPassword($_REQUEST['admin_password'])) {
			echo '<p><font color="red"><b>Wrong password</b></font></p>';
			echo '<p><a href="index.php">Try again</a></p>';
		} else {
			$svn = new phpsvnclient(OIDPLUS_REPO);
			$svn->versionFile = 'oidplus_version.txt';
			echo '<h2>Updating ...</h2>';

			ob_start();
			$svn->updateWorkingCopy(dirname(__DIR__).'/oidplus_version.txt', '/trunk', dirname(__DIR__), false);

			// START DIRTY HACK: Manually update the external repos, since we cannot handle external repos at the moment (TODO)
			@mkdir(__DIR__ . '/../3p/vts_vnag');
			foreach (array('vnag_framework.inc.php') as $file) {
				@file_put_contents(__DIR__ . '/../3p/vts_vnag/'.$file, file_get_contents('https://svn.viathinksoft.com/svn/vnag/trunk/framework/'.$file));
			}
			@mkdir(__DIR__ . '/../3p/vts_fileformats');
			foreach (array('VtsFileTypeDetect.class.php', 'filetypes.conf', 'mimetype_lookup.inc.php') as $file) {
				@file_put_contents(__DIR__ . '/../3p/vts_fileformats/'.$file, file_get_contents('https://svn.viathinksoft.com/svn/fileformats/trunk/'.$file));
			}
			// END DIRTY HACK

			$cont = ob_get_contents();
			$cont = str_replace(realpath(dirname(__DIR__)), '...', $cont);
			ob_end_clean();

			echo '<pre>'.$cont.'</pre>';

			echo '<p><a href="index.php">Back to update page</a></p>';
			echo '<hr>';
		}
	}

} else {

	class VNagMonitorDummy extends VNag {
		private $status;
		private $content;

		public function __construct($status, $content) {
			parent::__construct();
			$this->status = $status;
			$this->content = $content;
		}

		protected function cbRun($optional_args=array()) {
			$this->setStatus($this->status);
			$this->setHeadline($this->content);
		}
	}

	?>

	<p><u>There are two possibilities how to keep OIDplus up-to-date:</u></p>

	<p><b>Method A</b>: Install OIDplus using the subversion tool in your SSH/Linux shell using the command <code>svn co <?php echo htmlentities(OIDPLUS_REPO); ?>/trunk</code>
	and update it regularly with the command <code>svn update</code> . This will automatically download the latest version and also check for
	conflicts. Highly recommended if you have a Shell/SSH access to your webspace!</p>

	<p><b>Method B:</b> Install OIDplus by downloading a ZIP file from www.viathinksoft.com, which contains a SVN snapshot, and extract it to your webspace.
	The ZIP file contains a file named "oidplus_version.txt" which contains the SVN revision of the snapshot. This update-tool will then try to update your files
	on-the-fly by downloading them from the ViaThinkSoft SVN repository directly into your webspace directory.
	A change conflict detection is NOT implemented.
	It is required that the files on your webspace have
	create/write/delete permissions. Only recommended if you have no access to the SSH/Linux shell.</p>

	<hr>

	<?php

	$svn_wc_exists = is_dir(OIDplus::basePath().'/.svn');
	$snapshot_exists = file_exists(OIDplus::basePath().'/oidplus_version.txt');

	if ($svn_wc_exists && $snapshot_exists) {
		echo '<font color="red">ERROR: Both, oidplus_version.txt and .svn directory exist! Therefore, the version is ambigous!</font>';
		$job = new VNagMonitorDummy(VNag::STATUS_CRITICAL, "ERROR: Both, oidplus_version.txt and .svn directory exist! Therefore, the version is ambigous!");
		$job->http_visual_output = VNag::OUTPUT_NEVER;
		$job->run();
		unset($job);
	} else if (!$svn_wc_exists && !$snapshot_exists) {
		echo '<font color="red">ERROR: Neither oidplus_version.txt, nor .svn directory exist! Therefore, the version cannot be determined and the update needs to be applied manually!</font>';
		$job = new VNagMonitorDummy(VNag::STATUS_CRITICAL, "Neither oidplus_version.txt, nor .svn directory exist! Therefore, the version cannot be determined and the update needs to be applied manually!");
		$job->http_visual_output = VNag::OUTPUT_NEVER;
		$job->run();
		unset($job);
	} else if ($svn_wc_exists) {
		echo '<p>You are using <b>method A</b> (SVN working copy).</p>';

		$local_installation = OIDplus::getVersion();
		$svn = new phpsvnclient(OIDPLUS_REPO);
		$newest_version = 'svn-'.$svn->getVersion();
		
		echo 'Local installation: ' . ($local_installation ? $local_installation : 'unknown') . '<br>';
		echo 'Latest published version: ' . ($newest_version ? $newest_version : 'unknown') . '<br>';

		if (!$local_installation) {
			echo '<p><font color="red">The system could not determine the version of your system. (Required: svnupdate shell access or SQLite3). Please update your system manually via the SVN "update" command regularly.</font></p>';

			$job = new VNagMonitorDummy(VNag::STATUS_WARNING, 'The system could not determine the version of your system. (Required: svnupdate shell access or SQLite3). Please update your system manually via the SVN "update" command regularly.');
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		} else if ($local_installation == $newest_version) {
			echo '<p><font color="green">You are already using the latest version of OIDplus.</font></p>';

			$job = new VNagMonitorDummy(VNag::STATUS_OK, "You are using the latest version of OIDplus ($local_installation local / $newest_version remote)");
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		} else {
			echo '<p><font color="blue">Please enter <code>svn update</code> into the SSH shell to update OIDplus to the latest version.</font></p>';

			echo '<h2>Preview of update '.$local_installation.' &rarr; '.$newest_version.'</h2>';
			$svn = new phpsvnclient(OIDPLUS_REPO);

			ob_start();
			$svn->updateWorkingCopy(str_replace('svn-', '', $local_installation), '/trunk', dirname(__DIR__), true);
			$cont = ob_get_contents();
			$cont = str_replace(realpath(dirname(__DIR__)), '...', $cont);
			ob_end_clean();

			echo '<pre>'.$cont.'</pre>';

			$job = new VNagMonitorDummy(VNag::STATUS_WARNING, "OIDplus is outdated. ($local_installation local / $newest_version remote)");
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		}
	} else if ($snapshot_exists) {
		echo '<p>You are using <b>method B</b> (Snapshot ZIP file with oidplus_version.txt file).</p>';

		$local_installation = OIDplus::getVersion();
		$svn = new phpsvnclient(OIDPLUS_REPO);
		$newest_version = 'svn-'.$svn->getVersion();

		echo 'Local installation: ' . $local_installation.'<br>';
		echo 'Latest published version: ' . $newest_version.'<br>';

		if ($local_installation == $newest_version) {
			echo '<p><font color="green">You are already using the latest version of OIDplus.</font></p>';

			$job = new VNagMonitorDummy(VNag::STATUS_OK, "You are using the latest version of OIDplus ($local_installation local / $newest_version remote)");
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		} else {
			echo '<p><font color="blue">To update your OIDplus installation, please enter your password and click the button "Update NOW".</font></p>';
			echo '<p><font color="red">WARNING: Please make a backup of your files before updating. In case of an error, the OIDplus installation (including this update-assistant) might become unavailable. Also, since the web-update does not contain collission-detection, changes you have applied (like adding, removing or modified files) might get reverted/lost!</font></p>';
			echo '<form method="POST" action="index.php">';

			if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
				echo '<noscript>';
				echo '<p><font color="red">You need to enable JavaScript to solve the CAPTCHA.</font></p>';
				echo '</noscript>';
				echo '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>';
				echo '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>';
			}

			echo '<input type="hidden" name="update_now" value="1">';
			echo '<input type="password" name="admin_password">';
			echo '<input type="submit" value="Update NOW">';
			echo '</form>';

			echo '<h2>Preview of update '.$local_installation.' &rarr; '.$newest_version.'</h2>';
			$svn = new phpsvnclient(OIDPLUS_REPO);

			ob_start();
			$svn->updateWorkingCopy(dirname(__DIR__).'/oidplus_version.txt', '/trunk', dirname(__DIR__), true);
			$cont = ob_get_contents();
			$cont = str_replace(realpath(dirname(__DIR__)), '...', $cont);
			ob_end_clean();

			echo '<pre>'.$cont.'</pre>';

			$job = new VNagMonitorDummy(VNag::STATUS_WARNING, "OIDplus is outdated. ($local_installation local / $newest_version remote)");
			$job->http_visual_output = VNag::OUTPUT_NEVER;
			$job->run();
			unset($job);
		}
	}

	echo '<hr>';

	echo '<p><input type="button" onclick="document.location=\'../\'" value="Go back to OIDplus"></p>';

	echo '<br><h2>File Completeness Check</h2>';

	echo '<p>With this optional tool, you can check if your OIDplus installation is complete and no files are missing.</p>';

	echo '<form method="POST" action="check.php">';
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
		echo '<noscript>';
		echo '<p><font color="red">You need to enable JavaScript to solve the CAPTCHA.</font></p>';
		echo '</noscript>';
		echo '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>';
		echo '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>';
	}
	if (!isset($local_installation)) $local_installation = 'svn-';
	echo '<input type="hidden" name="svn_version" value="'.(substr($local_installation,strlen('svn-'))).'">';
	echo '<input type="password" name="admin_password">';
	echo '<input type="submit" value="Check">';
	echo '<p>Attention: This will take some time!</p>';
	echo '</form>';

	echo '<h2>VNag integration</h2>';

	echo '<p>Did you know that this page contains an invisible VNag tag? You can watch this page using the "webreader" plugin of VNag, and then monitor it with any Nagios compatible software! <a href="https://www.viathinksoft.com/projects/vnag">More information</a>.</p>';
}

?>

</body>
</html>
