<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------*
| Filename: includes/downloads_setup.php
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
if (isset($_POST['uninstall'])) {
	$result = dbquery("DROP TABLE IF EXISTS ".$db_prefix."download_cats");
	$result = dbquery("DROP TABLE IF EXISTS ".$db_prefix."downloads");
} else {
	$result = dbquery("DROP TABLE IF EXISTS ".$db_prefix."download_cats");
	$result = dbquery("DROP TABLE IF EXISTS ".$db_prefix."downloads");
	if (!db_exists($db_prefix."download_cats")) {
		$result = dbquery("CREATE TABLE ".$db_prefix."download_cats (
					download_cat_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
					download_cat_name VARCHAR(100) NOT NULL DEFAULT '',
					download_cat_description TEXT NOT NULL,
					download_cat_sorting VARCHAR(50) NOT NULL DEFAULT 'download_title ASC',
					download_cat_access TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
					download_cat_language VARCHAR(50) NOT NULL DEFAULT '".$_POST['localeset']."',
					PRIMARY KEY (download_cat_id)
					) ENGINE=MyISAM DEFAULT CHARSET=UTF8 COLLATE=utf8_unicode_ci");
		if (!$result) {
			$fail = TRUE;
		}
	} else {
		$fail = TRUE;
	}
	if (!db_exists($db_prefix."downloads")) {
		$result = dbquery("CREATE TABLE ".$db_prefix."downloads (
				download_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
				download_user MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
				download_homepage VARCHAR(100) NOT NULL DEFAULT '',
				download_title VARCHAR(100) NOT NULL DEFAULT '',
				download_description_short VARCHAR(255) NOT NULL,
				download_description TEXT NOT NULL,
				download_keywords VARCHAR(250) NOT NULL DEFAULT '',
				download_image VARCHAR(100) NOT NULL DEFAULT '',
				download_image_thumb VARCHAR(100) NOT NULL DEFAULT '',
				download_url VARCHAR(200) NOT NULL DEFAULT '',
				download_file VARCHAR(100) NOT NULL DEFAULT '',
				download_cat MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
				download_license VARCHAR(50) NOT NULL DEFAULT '',
				download_copyright VARCHAR(250) NOT NULL DEFAULT '',
				download_os VARCHAR(50) NOT NULL DEFAULT '',
				download_version VARCHAR(20) NOT NULL DEFAULT '',
				download_filesize VARCHAR(20) NOT NULL DEFAULT '',
				download_datestamp INT(10) UNSIGNED NOT NULL DEFAULT '0',
				download_count INT(10) UNSIGNED NOT NULL DEFAULT '0',
				download_allow_comments TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				download_allow_ratings TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (download_id),
				KEY download_datestamp (download_datestamp)
				) ENGINE=MyISAM DEFAULT CHARSET=UTF8 COLLATE=utf8_unicode_ci");
		if (!$result) {
			$fail = TRUE;
		}
	} else {
		$fail = TRUE;
	}
}


?>