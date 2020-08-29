<?php

/*
 * OIDplus 2.0
 * Copyright 2019-2020 Daniel Marschall, ViaThinkSoft
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

header('Content-Type:text/html; charset=UTF-8');

require_once __DIR__ . '/includes/oidplus.inc.php';

set_exception_handler(array('OIDplusGui', 'html_exception_handler'));

ob_start(); // allow cookie headers to be sent

OIDplus::init(true);

$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';
$static = OIDplus::gui()::generateContentPage($static_node_id);
$static_title = $static['title'];
$static_icon = $static['icon'];
$static_content = $static['text'];

OIDplus::handleLangArgument();

function combine_systemtitle_and_pagetitle($systemtitle, $pagetitle) {
	// Please also change the function in oidplus_base.js
	if ($systemtitle == $pagetitle) {
		return $systemtitle;
	} else {
		return $pagetitle . ' - ' . $systemtitle;
	}
}

$sysid_oid = OIDplus::getSystemId(true);
if (!$sysid_oid) $sysid_oid = 'unknown'; // do not translate
header('X-OIDplus-SystemID:'.$sysid_oid);

$sys_url = OIDplus::getSystemUrl();
header('X-OIDplus-SystemURL:'.$sys_url);

$sys_ver = OIDplus::getVersion();
if (!$sys_ver) $sys_ver = 'unknown'; // do not translate
header('X-OIDplus-SystemVersion:'.$sys_ver);

$sys_install_type = OIDplus::getInstallType();
header('X-OIDplus-SystemInstallType:'.$sys_install_type);

$sys_title = OIDplus::config()->getValue('system_title');
header('X-OIDplus-SystemTitle:'.$sys_title);

if (class_exists('OIDplusPageAdminColors')) {
	$add_css_args = '?invert='.(OIDplus::config()->getValue('color_invert')).'&h_shift='.(OIDplus::config()->getValue('color_hue_shift')/360).'&s_shift='.(OIDplus::config()->getValue('color_sat_shift')/100).'&v_shift='.(OIDplus::config()->getValue('color_val_shift')/100);
} else {
	$add_css_args = '';
}

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="OIDplus-SystemID" content="<?php echo htmlentities($sysid_oid); ?>">
	<meta name="OIDplus-SystemURL" content="<?php echo htmlentities($sys_url); ?>">
	<meta name="OIDplus-SystemVersion" content="<?php echo htmlentities($sys_ver); ?>">
	<meta name="OIDplus-SystemInstallType" content="<?php echo htmlentities($sys_install_type); ?>">
	<meta name="OIDplus-SystemTitle" content="<?php echo htmlentities($sys_title); /* Do not remove. This meta tag is acessed by oidplus_base.js */ ?>">
	<meta name="theme-color" content="#A9DCF0">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo combine_systemtitle_and_pagetitle(OIDplus::config()->getValue('system_title'), $static_title); ?></title>

	<script src="polyfill.min.js.php"></script>
	<?php
	if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) == true) {
		echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
	}
	?>
	<script src="oidplus.min.js.php"></script>

	<link rel="stylesheet" href="oidplus.min.css.php<?php echo htmlentities($add_css_args); ?>">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico.php">
</head>

<body>

<div id="frames">
	<div id="content_window" class="borderbox">
		<?php
		$static_content = preg_replace_callback(
			'|<a\s([^>]*)href="mailto:([^"]+)"([^>]*)>([^<]*)</a>|ismU',
			function ($treffer) {
				$email = $treffer[2];
				$text = $treffer[4];
				return OIDplus::mailUtils()->secureEmailAddress($email, $text, 1); // AntiSpam
			}, $static_content);

		echo '<h1 id="real_title">';
		if ($static_icon != '') echo '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		echo htmlentities($static_title).'</h1>';
		echo '<div id="real_content">'.$static_content.'</div>';
		if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] == 'GET')) {
			echo '<br><p><img src="img/share.png" width="15" height="15" alt="'._L('Share').'"> <a href="?goto='.htmlentities($static_node_id).'" id="static_link" class="gray_footer_font">'._L('Static link to this page').'</a>';
			echo '</p>';
		}
		echo '<br>';
		?>
	</div>

	<div id="system_title_bar">
		<?php
		echo '<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">';
		echo '	<div id="bar1"></div>';
		echo '	<div id="bar2"></div>';
		echo '	<div id="bar3"></div>';
		echo '</div>';
		echo '';
		echo '<div id="system_title_text">';
		echo '	<a '.OIDplus::gui()->link('oidplus:system').'>';
		echo '		<span id="system_title_1">'._L('ViaThinkSoft OIDplus 2.0').'</span><br>';
		echo '		<span id="system_title_2">'.htmlentities(OIDplus::config()->getValue('system_title')).'</span>';
		echo '	</a>';
		echo '</div>';
		?>
	</div>

	<?php
	echo OIDplusGui::getLanguageBox($static_node_id, true);
	?>

	<div id="gotobox">
		<?php
		echo '<input type="text" name="goto" id="gotoedit" value="'.htmlentities($static_node_id).'">';
		echo '<input type="button" value="'._L('Go').'" onclick="gotoButtonClicked()" id="gotobutton">';
		?>
	</div>

	<div id="oidtree" class="borderbox">
		<?php
		//echo '<noscript>';
		//echo '<p><b>'._L('Please enable JavaScript to use all features').'</b></p>';
		//echo '</noscript>';
		OIDplus::menuUtils()->nonjs_menu();
		?>
	</div>
</div>

</body>
</html>
<?php

$cont = ob_get_contents();
ob_end_clean();

echo $cont;
