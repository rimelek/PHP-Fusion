<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------*
| Filename: install/index.php
| Author: PHP-Fusion Development Team
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
define("IN_FUSION", TRUE);
use PHPFusion\Database\DatabaseFactory;
use PHPFusion\Core;

ini_set('display_errors', 1);
define('BASEDIR', '../');
require_once 'setup_includes.php';
define("FUSION_SELF", basename($_SERVER['PHP_SELF']));
if (!defined('DYNAMICS')) {
	define('DYNAMICS', INCLUDES."dynamics/");
}


$localeset = filter_input(INPUT_GET, 'localeset') ? : filter_input(INPUT_POST, 'localeset');

if ($localeset && file_exists(LOCALE.$localeset) && is_dir(LOCALE.$localeset)) {
	Core::setDefaultLanguage($localeset);
}
$_GET['localeset'] = Core::getLanguage();
require LOCALE.Core::getLanguage().'/setup.php';


require_once INCLUDES."defender.inc.php";
include INCLUDES."output_handling_include.php";
$defender = new defender();

if (isset($_POST['step']) && $_POST['step'] == "8") {
	if (file_exists(BASEDIR.'config_temp.php')) {
		@rename(BASEDIR.'config_temp.php', BASEDIR.'config.php');
		@chmod(BASEDIR.'config.php', 0644);
	}
	redirect(BASEDIR.'index.php');
}

// Determine the chosen database functions
$pdo_enabled = filter_input(INPUT_POST, 'pdo_enabled', FILTER_VALIDATE_BOOLEAN);
$db_host = 'localhost';
$db_user = NULL;
$db_pass = NULL;
$db_name = NULL;
$db_prefix = NULL;

if (file_exists(BASEDIR.'config.php')) {
	include BASEDIR.'config.php';
} elseif (file_exists(BASEDIR.'config_temp.php')) {
	include BASEDIR.'config_temp.php';
}

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
	$pdo_enabled = (bool)intval($pdo_enabled);
	$db_host = (string) (stripinput(trim(filter_input(INPUT_POST, 'db_host'))) ? : $db_host);
	$db_user = (string) (stripinput(trim(filter_input(INPUT_POST, 'db_user'))) ? : $db_user);
	$db_pass = (string) (stripinput(filter_input(INPUT_POST, 'db_pass')) ? : $db_pass);
	$db_name = (string) (stripinput(trim(filter_input(INPUT_POST, 'db_name'))) ? : $db_name);
	$db_prefix = (string) (stripinput(trim(filter_input(INPUT_POST, 'db_prefix'))) ? : $db_prefix);
}



$locale_files = makefilelist("../locale/", ".svn|.|..", TRUE, "folders");
include_once INCLUDES."dynamics/dynamics.inc.php";

DatabaseFactory::setDefaultDriver(intval($pdo_enabled) === 1 ? DatabaseFactory::DRIVER_PDO_MYSQL : DatabaseFactory::DRIVER_MYSQL);

define('LANGUAGE', Core::getLanguage());
define('LOCALESET', LANGUAGE.'/');

require_once INCLUDES."db_handlers/all_functions_include.php";
require_once LOCALE.LOCALESET.'global.php';

$dynamics = new dynamics();
$dynamics->boot();
$system_apps = array(
	'articles' => $locale['articles']['title'],
	'blog' => $locale['blog']['title'],
	'downloads' => $locale['downloads']['title'],
	'eshop' => $locale['eshop']['title'],
	'faqs' => $locale['faqs']['title'],
	'forums' => $locale['forums']['title'],
	'news' => $locale['news']['title'],
	'photos' => $locale['photos']['title'],
	'polls' => $locale['polls']['title'],
	'weblinks' => $locale['weblinks']['title']);
	
$buttons = array('next' => array('next', $locale['setup_0121']),
	'finish' => array('next', $locale['setup_0123']),
	'done' => array('done', $locale['setup_0120']),
	'refresh' => array('next', $locale['setup_1105']),
	'tryagain' => array('next', $locale['setup_0122']),
	'back' => array('back', $locale['setup_0122']));

$buttonMode = NULL;
$nextStep = 1;
$content = "";

switch (filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT) ? : 1) {
	// Introduction
	case 1:
	default:
		// create htaccess file.
		if (isset($_POST['htaccess'])) {
			dbconnect($db_host, $db_user, $db_pass, $db_name, FALSE);
			/*
			 * We need to include it to create DB_SETTINGS 
			 * for fusion_get_settings()
			 * 
			 * TODO: Find better way
			 */
			require_once INCLUDES.'multisite_include.php';
			$site_path = fusion_get_settings('site_path');
			write_htaccess($site_path);
			redirect(FUSION_SELF."?localeset=".LANGUAGE);
		}
		// ALWAYS reset config to config_temp.php
		if (file_exists(BASEDIR.'config.php')) {
			@rename(BASEDIR.'config.php', BASEDIR.'config_temp.php');
			@chmod(BASEDIR.'config_temp.php', 0755);
		}
		// Must always include a temp file.
		/* 1. To enter Recovery. CONFIG TEMP file must have dbprefix and have value in dbprefix. */
		if (isset($db_prefix) && $db_prefix) {
			dbconnect($db_host, $db_user, $db_pass, $db_name, FALSE);
			if (isset($_POST['uninstall'])) {
				include_once 'includes/core_setup.php'; // why does it still produce flash of error message?, Locale ?
				@unlink(BASEDIR.'config_temp.php');
				@unlink(BASEDIR.'config.php');
				redirect(BASEDIR."install/index.php", 1); // temp fix.
			}
			$content .= "<h4 class='strong'>".$locale['setup_1002']."</h4>\n";
			$content .= "<span class='display-block m-t-20 m-b-10'>".$locale['setup_1003']."</span>\n";
			$content .= "<div class='well'>\n";
			$content .= "<span class='strong display-inline-block m-b-10'>".$locale['setup_1017']."</span><br/><p>".$locale['setup_1018']."</p>";
			$content .= form_button('step', $locale['setup_1019'], '8', array('class' => 'btn-success btn-sm m-t-10'));
			$content .= "</div>\n";
			$content .= "<div class='well'>\n";
			$content .= "<span class='strong display-inline-block m-b-10'>".$locale['setup_1004']."</span><br/><p>".$locale['setup_1005']." <span class='strong'>".$locale['setup_1006']."</span></p>";
			$content .= form_button('uninstall', $locale['setup_1007'], 'uninstall', array('class' => 'btn-danger btn-sm m-t-10'));
			$content .= "</div>\n";
			$content .= "<div class='well'>\n";
			$content .= "<span class='strong display-inline-block m-b-10'>".$locale['setup_1008']."</span>\n<br/><p>".$locale['setup_1009']."</p>";
			$content .= form_button('step', $locale['setup_1010'], '5', array('class' => 'btn-primary btn-sm m-r-10'));
			$content .= "</div>\n";
			$content .= "<div class='well'>\n";
			$content .= "<span class='strong display-inline-block m-b-10'>".$locale['setup_1011']."</span>\n<br/><p>".$locale['setup_1012']."</p>";
			$content .= form_button('step', $locale['setup_1013'], '6', array('class' => 'btn-primary btn-sm m-r-10'));
			$content .= "</div>\n";
			$content .= "<input type='hidden' name='localeset' value='".stripinput(LANGUAGE)."' />\n";
			if (isset($db_prefix)) {
				$content .= "<div class='well'>\n";
				$content .= "<span class='strong display-inline-block m-b-10'>".$locale['setup_1014']."</span>\n<br/><p>".$locale['setup_1015']."</p>";
				$content .= form_button('htaccess', $locale['setup_1016'], 'htaccess', array('class' => 'btn-primary btn-sm m-r-10'));
				$content .= "</div>\n";
			}
		} /* Without click uninstall this is the opening page of installer - just for safety. if not, an else suffices */ elseif (!isset($_POST['uninstall'])) {
			// no db_prefix
			$locale_list = makefileopts($locale_files, Core::getLanguage());
			$content .= "<h4 class='strong'>".$locale['setup_0002']."</h4>\n";
			if (isset($_GET['error']) && $_GET['error'] == 'license') {
				$content .= "<div class='alert alert-danger'>".$locale['setup_5000']."</div>\n";
			} else {
				$content .= "<span>".$locale['setup_0003']."</span>\n";
			}
			$content .= "<span class='display-block m-t-20 m-b-10 strong'>".$locale['setup_1000']."</span>\n";
			$content .= form_select('localeset', '', array_combine($locale_files, $locale_files), LANGUAGE, array('placeholder' => $locale['choose']));
			$content .= "<script>\n";
			$content .= "$('#localeset').bind('change', function() {
				var value = $(this).val();
				document.location.href='".FUSION_SELF."?localeset='+value;
			});";
			$content .= "</script>\n";
			$content .= "<div>".$locale['setup_1001']."</div>\n";
			$content .= "<hr>\n";
			$content .= form_checkbox('license', $locale['setup_0005'], '');
			$content .= "<hr>\n";
			$nextStep = 2;
			$buttonMode = 'next';
		}
		break;
	// Step 2 - File and Folder Permissions
	case 2:
		if (!isset($_POST['license'])) redirect(FUSION_SELF."?error=license&localeset=".LANGUAGE);
		// Create a blank config temp by now if not exist.
		if (!file_exists(BASEDIR."config_temp.php")) {
			if (file_exists(BASEDIR."_config.php") && function_exists("rename")) {
				@rename(BASEDIR."_config.php", BASEDIR."config_temp.php");
			} else {
				touch(BASEDIR."config_temp.php");
			}
		}
		$check_arr = array("administration/db_backups" => FALSE,
			"ftp_upload" => FALSE,
			"images" => FALSE,
			"images/imagelist.js" => FALSE,
			"images/avatars" => FALSE,
			"config_temp.php" => FALSE,
			"robots.txt" => FALSE);
		$write_check = TRUE;
		$check_display = "";
		foreach ($check_arr as $key => $value) {
			$check_arr[$key] = (file_exists(BASEDIR.$key) && is_writable(BASEDIR.$key)) or (file_exists(BASEDIR.$key) && function_exists("chmod") && @chmod(BASEDIR.$key, 0777) && is_writable(BASEDIR.$key));
			if (!$check_arr[$key]) {
				$write_check = FALSE;
			}
			$check_display .= "<tr>\n<td class='tbl1'>".$key."</td>\n";
			$check_display .= "<td class='tbl1' style='text-align:right'>".($check_arr[$key] == TRUE ? "<label class='label label-success'>".$locale['setup_1100']."</label>" : "<label class='label label-warning'>".$locale['setup_1101']."</label>")."</td>\n</tr>\n";
		}
		$content .= "<div class='m-b-20'><h4>".$locale['setup_1106']."</h4> ".$locale['setup_1102']."</div>\n";
		$content .= "<table class='table table-responsive'>\n".$check_display."\n</table><br /><br />\n";
		// can proceed
		if ($write_check) {
			$content .= "<p><strong>".$locale['setup_1103']."</strong></p>\n";
			$nextStep = 3;
			$buttonMode = 'next';
		} else {
			$content .= "<p><strong>".$locale['setup_1104']."</strong></p>\n";
			$content .= form_hidden('', 'license', 'license', '1');
			$buttonMode = 'refresh';
			$nextStep = 2;
		}
		break;
	// Step 3 - Database Settings
	case 3:
		if (!$db_prefix) {
			$db_prefix = "fusion".createRandomPrefix()."_";
		}
		$cookie_prefix = "fusion".createRandomPrefix()."_";
		$email = (isset($_POST['email']) ? stripinput(trim($_POST['email'])) : "");
		$username = (isset($_POST['username']) ? stripinput(trim($_POST['username'])) : "");
		$db_error = (isset($_POST['db_error']) && isnum($_POST['db_error']) ? $_POST['db_error'] : "0");
		$field_class = array("", "", "", "", "");
		if ($db_error > "0") {
			$field_class[2] = " tbl-error";
			if ($db_error == 1) {
				$field_class[1] = " tbl-error";
				$field_class[2] = " tbl-error";
			} elseif ($db_error == 2) {
				$field_class[3] = " tbl-error";
			} elseif ($db_error == 3) {
				$field_class[4] = " tbl-error";
			} elseif ($db_error == 7) {
				if ($db_host == "") {
					$field_class[0] = " tbl-error";
				}
				if ($db_user == "") {
					$field_class[1] = " tbl-error";
				}
				if ($db_name == "") {
					$field_class[3] = " tbl-error";
				}
				if ($db_prefix == "") {
					$field_class[4] = " tbl-error";
				}
			}
		}
		$content .= "<div class='m-b-20'><h4>".$locale['setup_1200']."</h4> ".$locale['setup_1201']."</div>\n";
		$content .= "<table class='table table-responsive'>\n<tr>\n";
		$content .= "<td class='tbl1' style='text-align:left'>".$locale['setup_1202']."</td>\n";
		$content .= "<td class='tbl1'><input type='text' value='".$db_host."' name='db_host' class='form-control input-sm textbox".$field_class[0]."' style='width:200px' /></td>\n</tr>\n";
		$content .= "<tr>\n<td class='tbl1' style='text-align:left'>".$locale['setup_1203']."</td>\n";
		$content .= "<td class='tbl1'><input type='text' value='".$db_user."' name='db_user' class='form-control input-sm textbox".$field_class[1]."' style='width:200px' /></td>\n</tr>\n";
		$content .= "<tr>\n<td class='tbl1' style='text-align:left'>".$locale['setup_1204']."</td>\n";
		$content .= "<td class='tbl1'><input type='password' value='' name='db_pass' class='form-control input-sm textbox".$field_class[2]."' style='width:200px' /></td>\n</tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1205']."</td>\n";
		$content .= "<td class='tbl1'><input type='text' value='".$db_name."' name='db_name' class='form-control input-sm textbox".$field_class[3]."' style='width:200px' /></td>\n</tr>\n";
		$content .= "<tr>\n<td class='tbl1' style='text-align:left'>".$locale['setup_1208']."</td>\n";
		// enable PDO
		$content .= "<td class='tbl1'>\n";
		if (!defined('PDO::ATTR_DRIVER_NAME')) {
			$content .= $locale['setup_1209'];
		} else {
			$content .= "<select name='pdo_enabled' class='form-control input-sm textbox' style='width:200px'>\n";
			$content .= "<option value='0' selected='selected'>".$locale['setup_1210']."</option>\n";
			$content .= "<option value='1'>".$locale['setup_1211']."</option>\n";
			$content .= "</select>\n";
		}
		$content .= "</td>\n</tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1213']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='text' name='username' value='".$username."' maxlength='255' class='form-control input-sm textbox' style='width:200px' /></td></tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1509']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='text' name='email' value='".$email."' maxlength='100' class='form-control input-sm textbox' style='width:200px' /></td></tr>\n";
		$content .= "<tr><td class='tbl1'>".$locale['setup_1212']."</td>\n";
		$content .= "<td class='tbl1'>\n";
		for ($i = 0; $i < count($locale_files); $i++) {
			if (file_exists(BASEDIR.'locale/'.$locale_files[$i].'/setup.php')) {
				$content .= "<input type='checkbox' value='".$locale_files[$i]."' name='enabled_languages[]' class='m-r-10 textbox' ".($locale_files[$i] == $_POST['localeset'] ? "checked='checked'" : "")."> ".$locale_files[$i]."<br />\n";
			}
		}
		$content .= "</td></tr>\n";
		$content .= "<tr>\n<td class='tbl1' style='text-align:left'>".$locale['setup_1206']."</td>\n";
		$content .= "<td class='tbl1'><input type='text' value='".$db_prefix."' name='db_prefix' class='form-control input-sm textbox".$field_class[4]."' style='width:200px' /></td>\n</tr>\n";
		$content .= "<tr>\n<td class='tbl1' style='text-align:left'>".$locale['setup_1207']."</td>\n";
		$content .= "<td class='tbl1'><input type='text' value='".$cookie_prefix."' name='cookie_prefix' class='form-control input-sm textbox' style='width:200px' /></td>\n</tr>\n";
		$content .= "</table>\n";
		$nextStep = 4;
		$buttonMode = 'next';
		break;
	// Step 4 - Config / Database Setup
	case 4:
		// Generate All Core Tables - this includes settings and all its injections
		$db_host = (isset($_POST['db_host']) ? stripinput(trim($_POST['db_host'])) : "");
		$db_user = (isset($_POST['db_user']) ? stripinput(trim($_POST['db_user'])) : "");
		$db_pass = (isset($_POST['db_pass']) ? stripinput(trim($_POST['db_pass'])) : "");
		$db_name = (isset($_POST['db_name']) ? stripinput(trim($_POST['db_name'])) : "");
		$db_prefix = (isset($_POST['db_prefix']) ? stripinput(trim($_POST['db_prefix'])) : "fusion_");
		$cookie_prefix = (isset($_POST['cookie_prefix']) ? stripinput(trim($_POST['cookie_prefix'])) : "fusion_");
		$email = (isset($_POST['email']) ? stripinput(trim($_POST['email'])) : "");
		$username = (isset($_POST['username']) ? stripinput(trim($_POST['username'])) : "");
		$enabled_languages = '';
		if (!empty($_POST['enabled_languages'])) {
			for ($i = 0; $i < count($_POST['enabled_languages']); $i++) {
				$enabled_languages .= $_POST['enabled_languages'][$i].".";
			}
			$enabled_languages = substr($enabled_languages, 0, (strlen($enabled_languages)-1));
		} else {
			$enabled_languages = stripinput($_POST['localeset']);
		}
		if ($db_prefix != "") {
			$db_prefix_last = $db_prefix[strlen($db_prefix)-1];
			if ($db_prefix_last != "_") {
				$db_prefix = $db_prefix."_";
			}
		}
		if ($cookie_prefix != "") {
			$cookie_prefix_last = $cookie_prefix[strlen($cookie_prefix)-1];
			if ($cookie_prefix_last != "_") {
				$cookie_prefix = $cookie_prefix."_";
			}
		}
		$selected_langs = '';
		$secret_key = createRandomPrefix(32);
		$secret_key_salt = createRandomPrefix(32);
		if ($db_host != "" && $db_user != "" && $db_name != "" && $db_prefix != "") {
			$connection_info = dbconnect($db_host, $db_user, $db_pass, $db_name, FALSE);
			$db_connect = $connection_info['connection_success'];
			$db_select = $connection_info['dbselection_success'];
			if ($db_connect) {
				if ($db_select) {
					$countRows = dbrows(dbquery("SHOW TABLES LIKE '".str_replace("_", "\_", $db_prefix)."%'"));
					if (!$countRows) {
						$table_name = uniqid($db_prefix, FALSE);
						$can_write = TRUE;
						$result = dbquery("CREATE TABLE ".$table_name." (test_field VARCHAR(10) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=UTF8 COLLATE=utf8_unicode_ci");
						if (!$result) {
							$can_write = FALSE;
						}
						$result = dbquery("DROP TABLE ".$table_name);
						if (!$result) {
							$can_write = FALSE;
						}
						if ($can_write) {
							// Write a Temporary Config File.
							$config = "<?php\n";
							$config .= "// database settings\n";
							$config .= "\$db_host = '".$db_host."';\n";
							$config .= "\$db_user = '".$db_user."';\n";
							$config .= "\$db_pass = '".$db_pass."';\n";
							$config .= "\$db_name = '".$db_name."';\n";
							$config .= "\$db_prefix = '".$db_prefix."';\n";
							$config .= "\$pdo_enabled = ".intval($pdo_enabled).";\n";
							$config .= "define(\"DB_PREFIX\", \"".$db_prefix."\");\n";
							$config .= "define(\"COOKIE_PREFIX\", \"".$cookie_prefix."\");\n";
							$config .= "define(\"SECRET_KEY\", \"".$secret_key."\");\n";
							$config .= "define(\"SECRET_KEY_SALT\", \"".$secret_key_salt."\");\n";
							$config .= "?>";
							if (fusion_file_put_contents(BASEDIR.'config_temp.php', $config)) {
								/*
								 * We need to include them to create DB_SETTINGS 
								 * for fusion_get_settings() in write_htaccess()
								 * 
								 * TODO: Find better way
								 */
								require BASEDIR.'config_temp.php';
								require_once INCLUDES.'multisite_include.php';
								$fail = FALSE;
								if (!$result) {
									$fail = TRUE;
								}
								// install core tables fully injected.
								include 'includes/core_setup.php';
								if (!$fail) {
									$content .= "<i class='entypo check'></i> ".$locale['setup_1300']."<br /><br />\n<i class='entypo check'></i> ";
									$content .= $locale['setup_1301']."<br /><br />\n<i class='entypo check'></i> ";
									$content .= $locale['setup_1302']."<br /><br />\n";
									$success = TRUE;
									$db_error = 6;
								} else {
									$content .= "<br />\n<i class='entypo check'></i> ".$locale['setup_1300']."<br /><br />\n<i class='entypo check'></i> ";
									$content .= $locale['setup_1301']."<br /><br />\n<i class='entypo icancel'></i> ";
									$content .= "<strong>".$locale['setup_1303']."</strong> ".$locale['setup_1308']."<br /><br />\n";
									$success = FALSE;
									$db_error = 0;
								}
							} else {
								$content .= "<br />\n".$locale['setup_1300']."<br /><br />\n";
								$content .= "<strong>".$locale['setup_1303']."</strong> ".$locale['setup_1306']."<br />\n";
								$content .= "<span class='small'>".$locale['setup_1307']."</span><br /><br />\n";
								$success = FALSE;
								$db_error = 5;
							}
							write_htaccess(fusion_get_settings('site_path'));
						} else {
							$content .= "<div class='alert alert-danger'>\n";
							$content .= $locale['setup_1300']."<br /><br />\n";
							$content .= "<strong>".$locale['setup_1303']."</strong> ".$locale['setup_1314']."<br />\n";
							$content .= "<span class='small'>".$locale['setup_1315']."</span><br /><br />\n";
							$content .= "</div>\n";
							$success = FALSE;
							$db_error = 4;
						}
					} else {
						$content .= "<div class='alert alert-danger'>\n";
						$content .= "<strong>".$locale['setup_1303']."<strong> ".$locale['setup_1312']."<br />\n";
						$content .= "<span class='small'>".$locale['setup_1313']."</span><br /><br />\n";
						$content .= "</div>\n";
						$success = FALSE;
						$db_error = 3;
					}
				} else {
					$content .= "<div class='alert alert-danger'>\n";
					$content .= "<br />\n<strong>".$locale['setup_1303']."<strong> ".$locale['setup_1310']."<br />\n";
					$content .= "<span class='small'>".$locale['setup_1311']."</span><br /><br />\n";
					$content .= "</div>\n";
					$success = FALSE;
					$db_error = 2;
				}
			} else {
				$content .= "<div class='alert alert-danger'>\n";
				$content .= "<strong>".$locale['setup_1303']."<strong> ".$locale['setup_1304']."<br />\n";
				$content .= "<span class='small'>".$locale['setup_1305']."</span><br /><br />\n";
				$content .= "</div>\n";
				$success = FALSE;
				$db_error = 1;
			}
		} else {
			$content .= "<div class='alert alert-danger'>\n";
			$content .= "<strong>".$locale['setup_1303']."<strong> ".$locale['setup_1316']."<br />\n";
			$content .= "".$locale['setup_1317']."<br /><br />\n";
			$content .= "</div>\n";
			$success = FALSE;
			$db_error = 7;
		}
		$content .= "<input type='hidden' name='enabled_languages' value='".$selected_langs."' />\n";
		if ($success) {
			$nextStep = 5;
			$buttonMode = 'next';
		} else {
			$content .= "<input type='hidden' name='db_host' value='".$db_host."' />\n";
			$content .= "<input type='hidden' name='db_user' value='".$db_user."' />\n";
			$content .= "<input type='hidden' name='db_name' value='".$db_name."' />\n";
			$content .= "<input type='hidden' name='db_prefix' value='".$db_prefix."' />\n";
			$content .= "<input type='hidden' name='db_error' value='".$db_error."' />\n";
			$nextStep = 3;
			$buttonMode = 'tryagain';
		}
		break;
	// Step 5 - Configure Core System - $settings accessible - Requires Config_temp.php (Shut down site when upgrading).
	case 5:
		include LOCALE.LANGUAGE."/admin/infusions.php";
//		if (!isset($_POST['done'])) {
			// Load Config and SQL handler.
			if (file_exists(BASEDIR.'config_temp.php')) {
				/*
				 * We need to include it to create DB_SETTINGS 
				 * for fusion_get_settings()
				 * 
				 * TODO: Find better way
				 */
				require_once INCLUDES.'multisite_include.php';
				dbconnect($db_host, $db_user, $db_pass, $db_name, FALSE);
				if (!fusion_get_settings()) {
					redirect(FUSION_SELF);
				}
			} else {
				redirect(FUSION_SELF); // start all over again if you tampered config_temp here.
			}
			$fail = FALSE;
			$message = "";
			$inf_title = "";
			$inf_description = "";
			$inf_version = "";
			$inf_developer = "";
			$inf_email = "";
			$inf_weburl = "";
			$inf_folder = "";
			$inf_newtable = "";
			$inf_insertdbrow = "";
			$inf_droptable = "";
			$inf_altertable = "";
			$inf_deldbrow = "";
			$inf_sitelink = "";

if (isset($_POST['infuse']) && isset($_POST['infusion'])) {
	$error = "";
	$infusion = stripinput($_POST['infusion']);
	if (file_exists(INFUSIONS.$infusion."/infusion.php")) {
		include INFUSIONS.$infusion."/infusion.php";
		$result = dbquery("SELECT inf_id, inf_version FROM ".DB_INFUSIONS." WHERE inf_folder='".$inf_folder."'");
		if (dbrows($result)) {
			$data = dbarray($result);
			if ($inf_version > $data['inf_version']) {
				if (isset($inf_altertable) && is_array($inf_altertable)) {
					foreach ($inf_altertable as $item) {
						$result = dbquery("ALTER TABLE ".$item);
					}
				}
				$result2 = dbquery("UPDATE ".DB_INFUSIONS." SET inf_version='".$inf_version."' WHERE inf_id='".$data['inf_id']."'");
			}
		} else {
			if (isset($inf_adminpanel) && is_array($inf_adminpanel)) {
				$error = 0;
				foreach ($inf_adminpanel as $item) {
					$inf_admin_image = ($item['image'] ? : "infusion_panel.gif");
					if (!dbcount("(admin_id)", DB_ADMIN, "admin_rights='".$item['rights']."'")) {
						dbquery("INSERT INTO ".DB_ADMIN." (admin_rights, admin_image, admin_title, admin_link, admin_page) VALUES ('".$item['rights']."', '".$inf_admin_image."', '".$item['title']."', '".INFUSIONS.$inf_folder."/".$item['panel']."', '5')");
						$result = dbquery("SELECT user_id, user_rights FROM ".DB_USERS." WHERE user_level=".USER_LEVEL_SUPER_ADMIN);
						while ($data = dbarray($result)) {
							dbquery("UPDATE ".DB_USERS." SET user_rights='".$data['user_rights'].".".$item['rights']."' WHERE user_id='".$data['user_id']."'");
						}
					} else {
						$error = 1;
					}
				}
			}
			if (!$error) {
				if (isset($inf_sitelink) && is_array($inf_sitelink)) {
					foreach ($inf_sitelink as $item) {
						$link_order = dbresult(dbquery("SELECT MAX(link_order) FROM ".DB_SITE_LINKS), 0)+1;
						dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_icon, link_visibility, link_position, link_window,link_language, link_order) VALUES ('".$item['title']."', '".str_replace("../", "", INFUSIONS).$inf_folder."/".$item['url']."', '".$item['icon']."', '".$item['visibility']."', '".$item['position']."', '0', '".LANGUAGE."', '".$link_order."')");
					}
				}
				//Multilang rights
				if (isset($inf_mlt) && is_array($inf_mlt)) {
					foreach ($inf_mlt as $item) {
						dbquery("INSERT INTO ".DB_LANGUAGE_TABLES." (mlt_rights, mlt_title, mlt_status) VALUES ('".$item['rights']."', '".$item['title']."', '1')");
					}
				}
				if (isset($inf_newtable) && is_array($inf_newtable)) {
					foreach ($inf_newtable as $item) {
						dbquery("CREATE TABLE ".$item);
					}
				}
				if (isset($inf_insertdbrow) && is_array($inf_insertdbrow)) {
					foreach ($inf_insertdbrow as $item) {
						dbquery("INSERT INTO ".$item);
					}
				}
				dbquery("INSERT INTO ".DB_INFUSIONS." (inf_title, inf_folder, inf_version) VALUES ('".$inf_title."', '".$inf_folder."', '".$inf_version."')");
			}
		}
	}
	//redirect(FUSION_SELF);
}
if (isset($_POST['defuse']) && isset($_POST['infusion'])) {
	$infusion = form_sanitizer($_POST['infusion'], '');
	$result = dbquery("SELECT inf_folder FROM ".DB_INFUSIONS." WHERE inf_folder='".$infusion."'");
	$data = dbarray($result);
	include INFUSIONS.$data['inf_folder']."/infusion.php";
	if (isset($inf_adminpanel) && is_array($inf_adminpanel)) {
		foreach ($inf_adminpanel as $item) {
			dbquery("DELETE FROM ".DB_ADMIN." WHERE admin_rights='".($item['rights'] ? : "IP")."' AND admin_link='".INFUSIONS.$inf_folder."/".$item['panel']."' AND admin_page='5'");
			$result = dbquery("SELECT user_id, user_rights FROM ".DB_USERS." WHERE user_level<=".USER_LEVEL_ADMIN);
			while ($data = dbarray($result)) {
				$user_rights = explode(".", $data['user_rights']);
				if (in_array($item['rights'], $user_rights)) {
					$key = array_search($item['rights'], $user_rights);
					unset($user_rights[$key]);
				}
				dbquery("UPDATE ".DB_USERS." SET user_rights='".implode(".", $user_rights)."' WHERE user_id='".$data['user_id']."'");
			}
		}
	}
	if (isset($inf_mlt) && is_array($inf_mlt)) {
		foreach ($inf_mlt as $item) {
			dbquery("DELETE FROM ".DB_LANGUAGE_TABLES." WHERE mlt_rights='".$item['rights']."'");
		}
	}
	if (isset($inf_sitelink) && is_array($inf_sitelink)) {
		foreach ($inf_sitelink as $item) {
			$result2 = dbquery("SELECT link_id, link_order FROM ".DB_SITE_LINKS." WHERE link_url='".str_replace("../", "", INFUSIONS).$inf_folder."/".$item['url']."'");
			if (dbrows($result2)) {
				$data2 = dbarray($result2);
				dbquery("UPDATE ".DB_SITE_LINKS." SET link_order=link_order-1 WHERE link_order>'".$data2['link_order']."'");
				dbquery("DELETE FROM ".DB_SITE_LINKS." WHERE link_id='".$data2['link_id']."'");
			}
		}
	}
	if (isset($inf_droptable) && is_array($inf_droptable)) {
		foreach ($inf_droptable as $item) {
			dbquery("DROP TABLE ".$item);
		}
	}
	if (isset($inf_deldbrow) && is_array($inf_deldbrow)) {
		foreach ($inf_deldbrow as $item) {
			dbquery("DELETE FROM ".$item);
		}
	}
	dbquery("DELETE FROM ".DB_INFUSIONS." WHERE inf_folder='".$_POST['infusion']."'");
	//redirect(FUSION_SELF);
}

add_to_jquery("
	$('.defuse').bind('click', function() {return confirm('".$locale['412']."');});
    ");


$temp = opendir(INFUSIONS);
$inf = array();
while ($folder = readdir($temp)) {
	if (!in_array($folder, array("..", "."))) {
		if (is_dir(INFUSIONS.$folder) && file_exists(INFUSIONS.$folder."/infusion.php")) {
			include INFUSIONS.$folder."/infusion.php";
			$result = dbquery("SELECT inf_version FROM ".DB_INFUSIONS." WHERE inf_folder='".$inf_folder."'");
			if (dbrows($result)) {
				$data = dbarray($result);
				if (version_compare($inf_version, $data['inf_version'], ">")) {
					$inf[] = array('inf_name' => str_replace('_', ' ', $inf_title), 'inf_folder' => $folder, 'inf_description' => isset($inf_description) && $inf_description ? $inf_description : '', 'inf_version' => isset($inf_version) && $inf_version ? $inf_version : 'beta', 'inf_developer' => isset($inf_developer) && $inf_developer ? $inf_developer : 'PHP-Fusion', 'inf_url' => isset($inf_weburl) && $inf_weburl ? $inf_weburl : '', 'inf_email' => isset($inf_email) && $inf_email ? $inf_email : '', 'inf_status' => 2);
				} else {
					$inf[] = array('inf_name' => str_replace('_', ' ', $inf_title), 'inf_folder' => $folder, 'inf_description' => isset($inf_description) && $inf_description ? $inf_description : '', 'inf_version' => isset($inf_version) && $inf_version ? $inf_version : 'beta', 'inf_developer' => isset($inf_developer) && $inf_developer ? $inf_developer : 'PHP-Fusion', 'inf_url' => isset($inf_weburl) && $inf_weburl ? $inf_weburl : '', 'inf_email' => isset($inf_email) && $inf_email ? $inf_email : '', 'inf_status' => 1);
				}
			} else {
				$inf[] = array('inf_name' => str_replace('_', ' ', $inf_title), 'inf_folder' => $folder, 'inf_description' => isset($inf_description) && $inf_description ? $inf_description : '', 'inf_version' => isset($inf_version) && $inf_version ? $inf_version : 'beta', 'inf_developer' => isset($inf_developer) && $inf_developer ? $inf_developer : 'PHP-Fusion', 'inf_url' => isset($inf_weburl) && $inf_weburl ? $inf_weburl : '', 'inf_email' => isset($inf_email) && $inf_email ? $inf_email : '', 'inf_status' => 0);
			}
			$inf_title = "";
			$inf_description = "";
			$inf_version = "";
			$inf_developer = "";
			$inf_email = "";
			$inf_weburl = "";
			$inf_folder = "";
			$inf_newtable = "";
			$inf_insertdbrow = "";
			$inf_droptable = "";
			$inf_altertable = "";
			$inf_deldbrow = "";
			$inf_sitelink = "";
		}
	}
}
closedir($temp);
sort($inf);
$content .= "<div>\n";
if ($inf) {
	$content .= "<div class='list-group'>\n";
	if ($inf) {
		$content .= "<div class='list-group-item hidden-xs'>\n";
		$content .= "<div class='row'>\n";
		$content .= "<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>\n<strong>".$locale['419']."</strong></div>\n";
		$content .= "<div class='col-xs-5 col-sm-5 col-md-4 col-lg-4'>\n<strong>".$locale['400']."</strong></div>\n";
		$content .= "<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>\n<strong>".$locale['418']."</strong></div>\n";
		$content .= "<div class='hidden-xs hidden-sm col-md-2 col-lg-1'>\n<strong>".$locale['420']."</strong></div>\n";
		$content .= "<div class='hidden-xs hidden-sm hidden-md col-lg-3 col-lg-offset-0'>\n<strong>".$locale['421']."</strong></div>\n";
		$content .= "</div>\n</div>\n";
		$formaction = FUSION_SELF;
		foreach ($inf as $i => $item) {
			$content .= openform('infuseform', 'post', $formaction, array('max_tokens' => 1));
			$content .= form_hidden('step', 'step', '5', '');
			$content .= "<input type='hidden' name='step' value='5' />\n";
			$content .= "<div class='list-group-item'>\n";
			$content .= "<div class='row'>\n";
			$content .= "<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>\n";
			$content .= form_hidden('', 'infusion', 'infusion', $item['inf_folder']);
			if ($item['inf_status'] > 0) {
				if ($item['inf_status'] > 1) {
					$content .= form_button('infuse', $locale['401'], "infuse-$i", array('class' => 'btn-info m-t-5 infuse', 'icon' => 'entypo magnet'));
				} else {
					$content .= form_button('defuse', $locale['411'], "defuse-$i", array('class' => 'btn-default btn-sm m-t-5 defuse', 'icon' => 'entypo trash'));
				}
			} else {
				$content .= form_button('infuse', $locale['401'], "infuse-$i", array('class' => 'btn-primary btn-sm m-t-5 infuse', 'icon' => 'entypo install'));
			}
			$content .= "</div>\n";
			$content .= "<div class='col-xs-6 col-sm-6 col-md-4 col-lg-4'><strong>".$item['inf_name']."</strong><br/>".trimlink($item['inf_description'], 30)."</div>\n";
			$content .= "<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>".($item['inf_status'] > 0 ? "<h5 class='m-0'><label class='label label-success'>".$locale['415']."</label></h5>" : "<h5 class='m-0'><label class='label label-default'>".$locale['414']."</label></h5>")."</div>\n";
			$content .= "<div class='hidden-xs hidden-sm col-md-2 col-lg-1'>".($item['inf_version'] ? $item['inf_version'] : '')."</div>\n";
			$content .= "<div class='col-xs-10 col-xs-offset-2 col-sm-10 col-sm-offset-2 col-md-10 col-md-offset-1 col-lg-3 col-lg-offset-0'>".($item['inf_url'] ? "<a href='".$item['inf_url']."' target='_blank'>" : "")." ".($item['inf_developer'] ? $item['inf_developer'] : $locale['410'])." ".($item['inf_url'] ? "</a>" : "")." <br/>".($item['inf_email'] ? "<a href='mailto:".$item['inf_email']."'>".$locale['409']."</a>" : '')."</div>\n";
			$content .= "</div>\n</div>\n";
			$content .= closeform();
		}
	}
} else {
	$content .= "<br /><p class='text-center'>".$locale['417']."</p>\n";
}
$content .=  "</div>\n</div>\n";
$content .= "<div class='well text-center m-t-10'>\n";
$content .= "<a class='btn btn-block btn-primary' href='https://www.php-fusion.co.uk/infusions/addondb/directory.php' title='".$locale['422']."' target='_blank'>".$locale['422']."</a>\n";
$content .= "</div>\n";
$nextStep = 6;
$buttonMode = 'next';
		break;
		// Step 6 - Primary Admin Details
	case 6:
		$iOWNER = 0;
		$username = (isset($_POST['username']) ? stripinput(trim($_POST['username'])) : "");
		$email = (isset($_POST['email']) ? stripinput(trim($_POST['email'])) : "");
		$error_pass = (isset($_POST['error_pass']) && isnum($_POST['error_pass']) ? $_POST['error_pass'] : "0");
		$error_name = (isset($_POST['error_name']) && isnum($_POST['error_name']) ? $_POST['error_name'] : "0");
		$error_mail = (isset($_POST['error_mail']) && isnum($_POST['error_mail']) ? $_POST['error_mail'] : "0");
		$field_class = array("", "", "", "", "", "");
		if ($error_pass == "1" || $error_name == "1" || $error_mail == "1") {
			$field_class = array("", " tbl-error", " tbl-error", " tbl-error", " tbl-error", "");
			if ($error_name == 1) {
				$field_class[0] = " tbl-error";
			}
			if ($error_mail == 1) {
				$field_class[5] = " tbl-error";
			}
		}
		// to scan whether User Acccount exists.
		if (file_exists(BASEDIR.'config.php') || file_exists(BASEDIR.'config_temp.php')) {
			/*
			 * We need to include it to create DB_SETTINGS 
			 * for fusion_get_settings()
			 * 
			 * TODO: Find better way
			 */
			require_once INCLUDES.'multisite_include.php';
			dbconnect($db_host, $db_user, $db_pass, $db_name, FALSE);
			$iOWNER = dbcount("('user_id')", $db_prefix."users", "user_id='1'");
		} else {
			redirect(FUSION_SELF);
		}
		if ($iOWNER) {
			$content .= "<div class='m-b-20'><h4>".$locale['setup_1502']."</h4> ".$locale['setup_1503']."</div>\n";
			$content .= "<input type='hidden' name='transfer' value='1'>\n";
			// load authentication during post.
			// in development.
		} else {
			$content .= "<div class='m-b-20'><h4>".$locale['setup_1500']."</h4> ".$locale['setup_1501']."</div>\n";
		}
		$content .= "<table class='table table-responsive'>\n<tr>\n";
		$content .= "<td class='tbl1'>".$locale['setup_1504']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='text' name='username' value='".$username."' maxlength='30' class='form-control input-sm textbox".$field_class[0]."' style='width:200px' /></td></tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1509']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='text' name='email' value='".$email."' maxlength='100' class='form-control input-sm textbox' style='width:200px' /></td></tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1505']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='password' name='password1' maxlength='64' class='form-control input-sm textbox".$field_class[1]."' style='width:200px' /></td></tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1506']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='password' name='password2' maxlength='64' class='form-control input-sm textbox".$field_class[2]."' style='width:200px' /></td></tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1507']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='password' name='admin_password1' maxlength='64' class='form-control input-sm textbox".$field_class[3]."' style='width:200px' /></td></tr>\n";
		$content .= "<tr>\n<td class='tbl1'>".$locale['setup_1508']."</td>\n";
		$content .= "<td class='tbl1' style='text-align:right'><input type='password' name='admin_password2' maxlength='64' class='form-control input-sm textbox".$field_class[4]."' style='width:200px' /></td></tr>\n";
		$content .= "</table>\n";
		$content .= "<input type='hidden' name='enabled_languages' value='".fusion_get_settings('enabled_languages')."' />\n";
		$nextStep = 7;
		$buttonMode = 'next';
		break;
	// Step 7 - Final Settings
	case 7:
		if (!file_exists(BASEDIR.'config_temp.php')) {
			redirect(FUSION_SELF);
		}
		dbconnect($db_host, $db_user, $db_pass, $db_name, FALSE);
		$error = "";
		$error_pass = "0";
		$error_name = "0";
		$error_mail = "0";
		$password_algorithm = "sha256";
		$username = (isset($_POST['username']) ? stripinput(trim($_POST['username'])) : "");
		if ($username == "") {
			$error .= $locale['setup_5011']."<br /><br />\n";
			$error_name = "1";
		} elseif (!preg_match("/^[-0-9A-Z_@\s]+$/i", $username)) {
			$error .= $locale['setup_5010']."<br /><br />\n";
			$error_name = "1";
		}
		$userPassword = "";
		$adminPassword = "";
		$userPass = new \PHPFusion\PasswordAuth($password_algorithm);
		$userPass->inputNewPassword = (isset($_POST['password1']) ? stripinput(trim($_POST['password1'])) : "");
		$userPass->inputNewPassword2 = (isset($_POST['password2']) ? stripinput(trim($_POST['password2'])) : "");
		$returnValue = $userPass->isValidNewPassword();
		if ($returnValue == 0) {
			$userPassword = $userPass->getNewHash();
			$userSalt = $userPass->getNewSalt();
		} elseif ($returnValue == 2) {
			$error .= $locale['setup_5012']."<br /><br />\n";
			$error_pass = "1";
		} elseif ($returnValue == 3) {
			$error .= $locale['setup_5013']."<br /><br />\n";
		}
		
		$adminPass = new \PHPFusion\PasswordAuth($password_algorithm);
		$adminPass->inputNewPassword = (isset($_POST['admin_password1']) ? stripinput(trim($_POST['admin_password1'])) : "");
		$adminPass->inputNewPassword2 = (isset($_POST['admin_password2']) ? stripinput(trim($_POST['admin_password2'])) : "");
		$returnValue = $adminPass->isValidNewPassword();

		if ($returnValue == 0) {
			$adminPassword = $adminPass->getNewHash();
			$adminSalt = $adminPass->getNewSalt();
		} elseif ($returnValue == 2) {
			$error .= $locale['setup_5015']."<br /><br />\n";
			$error_pass = "1";
		} elseif ($returnValue == 3) {
			$error .= $locale['setup_5017']."<br /><br />\n";
		}
		if ($userPass->inputNewPassword == $adminPass->inputNewPassword) {
			$error .= $locale['setup_5016']."<br /><br />\n";
			$error_pass = "1";
		}
		$email = (isset($_POST['email']) ? stripinput(trim($_POST['email'])) : "");
		if ($email == "") {
			$error .= $locale['setup_5020']."<br /><br />\n";
			$error_mail = "1";
		} elseif (!preg_match("/^[-0-9A-Z_\.]{1,50}@([-0-9A-Z_\.]+\.){1,50}([0-9A-Z]){2,4}$/i", $email)) {
			$error .= $locale['setup_5019']."<br /><br />\n";
			$error_mail = "1";
		}
		$rows = dbrows(dbquery("SELECT user_id FROM ".$db_prefix."users"));
		if ($error == "") {
			if ($rows == 0) {
				// Create Super Admin with Full Modular Rights - We don't need to update Super Admin later.
				if (isset($_POST['transfer']) && $_POST['transfer'] == 1) {
					$result = dbquery("UPDATE ".$db_prefix."users user_name='".$username."', user_salt='".$userSalt."', user_password='".$userPassword."', user_admin_salt='".$adminSalt."', user_admin_password='".$adminPassword."'
					user_email='".$email."', user_theme='Default', user_timezone='Europe/London' WHERE user_id='1'");
				} else {
					$result = dbquery("INSERT INTO ".$db_prefix."users (
					user_name, user_algo, user_salt, user_password, user_admin_algo, user_admin_salt, user_admin_password, user_email, user_hide_email, user_timezone,
					user_avatar, user_posts, user_threads, user_joined, user_lastvisit, user_ip, user_rights,
					user_groups, user_level, user_status, user_theme, user_location, user_birthdate, user_aim,
					user_icq, user_yahoo, user_web, user_sig
					) VALUES (
					'".$username."', 'sha256', '".$userSalt."', '".$userPassword."', 'sha256', '".$adminSalt."', '".$adminPassword."',
					'".$email."', '1', 'Europe/London', '',  '0', '0', '".time()."', '0', '0.0.0.0',
					'A.AC.AD.APWR.B.BB.BLOG.BLC.C.CP.DB.DC.D.ERRO.FQ.F.FR.IM.I.IP.M.MI.MAIL.N.NC.P.PH.PI.PL.PO.ROB.SL.S1.S2.S3.S4.S5.S6.S7.S8.S9.S10.S11.S12.S13.SB.SM.SU.UF.UFC.UG.UL.U.TS.W.WC.MAIL.LANG.ESHP',
					'', '-103', '0', 'Default', '', '0000-00-00', '', '',  '', '', ''
					)");
				}
			}
			$content .= "<div class='m-b-20'><h4>".$locale['setup_1600']."</h4> ".$locale['setup_1601']."</div>\n";
			$content .= "<div class='m-b-10'>".$locale['setup_1602']."</div>\n";
			$content .= "<div class='m-b-10'>".$locale['setup_1603']."</div>\n";
			$nextStep = 8;
			$buttonMode = 'finish';
		} elseif ($rows == 0) {
			$content .= "<br />\n".$locale['setup_5021']."<br /><br />\n".$error;
			$content .= "<input type='hidden' name='error_pass' value='".$error_pass."' />\n";
			$content .= "<input type='hidden' name='error_name' value='".$error_name."' />\n";
			$content .= "<input type='hidden' name='error_mail' value='".$error_mail."' />\n";
			$content .= "<input type='hidden' name='username' value='".$username."' />\n";
			$content .= "<input type='hidden' name='email' value='".$email."' />\n";
			$nextStep = 6;
			$buttonMode = 'back';
		} else {
			$content .= "<div class='m-b-20'><h4>".$locale['setup_1600']."</h4> ".$locale['setup_1601']."</div>\n";
			$content .= "<div class='m-b-10'>".$locale['setup_1602']."</div>\n";
			$content .= "<div class='m-b-10'>".$locale['setup_1603']."</div>\n";
			$nextStep = 8;
			$buttonMode = 'finish';
		}
		break;
}

ob_start();
opensetup();
echo $content;

if ($localeset) {
	echo "<input type='hidden' name='localeset' value='".stripinput($localeset)."' />\n";
}

if ($buttonMode) {
	echo '<input type="hidden" name="step" value="'.$nextStep.'" />';
	renderButton($buttons[$buttonMode][0], $buttons[$buttonMode][1], $buttonMode);
}
closesetup();
