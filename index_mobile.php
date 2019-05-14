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

require_once __DIR__ . '/includes/oidplus.inc.php';

ob_start(); // allow cookie headers to be sent

header('Content-Type:text/html; charset=UTF-8');

OIDplus::init(true);

OIDplus::db()->set_charset("UTF8");
OIDplus::db()->query("SET NAMES 'utf8'");

$static_node_id = isset($_REQUEST['goto']) ? $_REQUEST['goto'] : 'oidplus:system';
$static = OIDplus::gui()::generateContentPage($static_node_id);
$static_title = $static['title'];
$static_icon = $static['icon'];
$static_content = $static['text'];

function combine_systemtitle_and_pagetitle($systemtitle, $pagetitle) {
	if ($systemtitle == $pagetitle) {
		return $systemtitle;
	} else {
		return $systemtitle . ' - ' . $pagetitle;
	}
}

$sysid_oid = OIDplus::system_id(true);
if (!$sysid_oid) $sysid_oid = 'unknown';
header('X-OIDplus-SystemID:'.$sysid_oid);

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="x-oidplus-system-id" content="<?php echo $sysid_oid; ?>">
	<meta name="x-oidplus-system-url" content="<?php echo OIDplus::system_url(); ?>">
	<title><?php echo combine_systemtitle_and_pagetitle(OIDplus::config()->systemTitle(), $static_title); ?></title>
	<link rel="stylesheet" href="3p/jstree/themes/default/style.min.css">

	<!-- We are using jQuery 2.2.1, because 3.3.1 seems to be incompatible with jsTree (HTML content will not be loaded into jsTree!) TODO: File bug report -->
	<script src="3p/jquery/jquery-2.2.1.min.js"></script>
	<script src="3p/bootstrap/js/bootstrap.min.js"></script>
	<script src="3p/jstree/jstree.min.js"></script>
	<script src='3p/tinymce/tinymce.min.js'></script>
	<script src="3p/jquery-ui/jquery-ui.js"></script>
	<script src="3p/layout/jquery.layout.min.js"></script>
	<script src="3p/spamspan/spamspan.js"></script>
	<script src='https://www.google.com/recaptcha/api.js'></script>

	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"><!-- for menu button. TODO: download into 3p/ -->

	<script src="oidplus.js"></script>
	<?php
	$ary = glob(__DIR__ . '/plugins/publicPages/'.'*'.'/script.js');
	sort($ary);
	foreach ($ary as $a) {
		echo '<script src="'.str_replace(__DIR__ . '/', '', $a).'"></script>';
	}
	$ary = glob(__DIR__ . '/plugins/adminPages/'.'*'.'/script.js');
	sort($ary);
	foreach ($ary as $a) {
		echo '<script src="'.str_replace(__DIR__ . '/', '', $a).'"></script>';
	}
	$ary = glob(__DIR__ . '/plugins/raPages/'.'*'.'/script.js');
	sort($ary);
	foreach ($ary as $a) {
		echo '<script src="'.str_replace(__DIR__ . '/', '', $a).'"></script>';
	}
	?>

	<link rel="stylesheet" href="oidplus.css">
	<?php
	$ary = glob(__DIR__ . '/plugins/publicPages/'.'*'.'/style.css');
	sort($ary);
	foreach ($ary as $a) {
		echo '<link rel="stylesheet" href="'.str_replace(__DIR__ . '/', '', $a).'">';
	}
	$ary = glob(__DIR__ . '/plugins/adminPages/'.'*'.'/style.css');
	sort($ary);
	foreach ($ary as $a) {
		echo '<link rel="stylesheet" href="'.str_replace(__DIR__ . '/', '', $a).'">';
	}
	$ary = glob(__DIR__ . '/plugins/raPages/'.'*'.'/style.css');
	sort($ary);
	foreach ($ary as $a) {
		echo '<link rel="stylesheet" href="'.str_replace(__DIR__ . '/', '', $a).'">';
	}
	?>
	<link rel="stylesheet" href="3p/bootstrap/css/bootstrap.min.css">

	<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">

	<script>
	system_title = <?php echo js_escape(OIDplus::config()->systemTitle()); ?>; // TODO: Is that timing OK or is that a race condition?
	</script>

<!-- https://cookieconsent.insites.com -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.js"></script>
<script>
window.addEventListener("load", function(){
window.cookieconsent.initialise({
	"palette": {
		"popup": {
			"background": "#edeff5",
			"text": "#838391"
		},
		"button": {
			"background": "#4b81e8"
		}
	},
	"position": "bottom-right"
})});
</script>

<script>
function mobileNavButtonClick() {
	var x = document.getElementById("oidtree");
	if (x.style.display === "block") {
		x.style.display = "none";
		document.getElementById("system_title_menu").classList.remove("active");
	} else {
		x.style.display = "block";
		document.getElementById("system_title_menu").classList.add("active");
	}
}
</script>

</head>

<body class="mobile">

<div id="system_title_bar">
	<a href="?goto=oidplus:system">
		<span id="system_title_1" class="mobile">ViaThinkSoft OIDplus 2.0</span><br>
		<span id="system_title_2" class="mobile"><?php echo htmlentities(OIDplus::config()->systemTitle()); ?></span>
	</a>
	<a href="javascript:void(0);" id="system_title_menu" onclick="mobileNavButtonClick()"><i class="fa fa-bars"></i></a>
</div>
<br>

<div class="wrap">
	<!-- TODO: add ellipses at the end? https://stackoverflow.com/questions/37158758/add-a-gap-between-jstree-nodes-text-and-scrollbars -->

	<div id="oidtree" class="mobile"><!-- TODO: jstree should be inside a scrollable container. don't let the page be broader than the screen. -->
		<noscript>
			<p><b>Please enable JavaScript to use all features</b></p>
		</noscript>
		<?php OIDplusTree::nonjs_menu($static_node_id); ?>
	</div>
</div>

<div id="content_window" class="mobile">
	<?php
	$static_content = preg_replace_callback(
		'|<a\s([^>]*)href="mailto:([^"]+)"([^>]*)>([^<]*)</a>|ismU',
		function ($treffer) {
			$email = $treffer[2];
			$text = $treffer[4];
			return secure_email($email, $text, 1); // AntiSpam
		}, $static_content);

	echo '<h1 id="real_title">';
	if ($static_icon != '') echo '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt="'.htmlentities($static_title).'"> ';
	echo htmlentities($static_title).'</h1>';
	echo '<div id="real_content">'.$static_content.'</div>';
	echo '<br><p><a href="?goto='.htmlentities($static_node_id).'" id="static_link"><img src="img/share.png" width="15" height="15" alt="Share"> Static link to this page</a></p>';
	echo '<br>';
	?>
</div>

</body>
</html>
<?php

$cont = ob_get_contents();
ob_end_clean();

echo $cont;