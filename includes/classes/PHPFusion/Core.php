<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: Core.php
| Author: Takács Ákos (Rimelek)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
namespace PHPFusion;


class Core {
	private static $currentUserData = array();
	private static $defaultLanguage = 'English';

	/**
	 * Change the default language
	 *
	 * @param $language
	 */
	public static function setDefaultLanguage($language) {
		self::$defaultLanguage = $language;
	}

	/**
	 * Get the selected language
	 */
	public static function getLanguage() {
		$userdata = Core::getCurrentUserData();
		$defaultLocale = \fusion_get_settings('locale');
		// Main language detection procedure
		$language = $userdata['user_language'];

		// $defaultLocale is null if the database connection is not available yet
		if ($defaultLocale && !$language) {
			$language = \dbresult(\dbquery("SELECT user_language FROM ".DB_LANGUAGE_SESSIONS." WHERE user_ip='".USER_IP."'"), 0) ? : $defaultLocale;
		}

		return \valid_language($language) ? $language : self::$defaultLanguage;
	}

	/**
	 * Run the default authentication process
	 *
	 * @param array $options
	 * 	<ul>
	 * 		<li><strong>login_button_name</strong>: The name of the submit button on login form.
	 * 			It can be empty the button has no name. Default: 'login'</li>
	 * 		<li><strong>login_destination</strong>: Destination URL where the user should be redirected to after login.
	 * 			Current URL by default.</li>
	 * 		<li><strong>logout_destination</strong>: Destination URL where the user should be redirected to after logout</li>
	 * 		<li><strong>logout_query_name</strong>: The name of the variable in query string to fire logout event</li>
	 * 		<li><strong>logout_query_value</strong>: The value of logout_query_name to fire logout event</li>
	 * 		<li><strong>password_input_name</strong>: Input name for password. Default: 'user_pass'</li>
	 * 		<li><strong>user_input_name</strong>: Input name for username. Default: 'user_name'</li>
	 * 	</ul>
	 */
	public static function authenticate(array $options = array()) {
		$defaults = array(
			'login_button_name' => 'login',
			'user_input_name' => 'user_name',
			'password_input_name' => 'user_pass',
			'login_destination' => FUSION_REQUEST,
			'logout_destination' => BASEDIR.'index.php',
			'remember_me_input_name' => 'remember_me',
			'logout_query_name' => 'logout',
			'logout_query_value' => 'yes'
		);
		$options += $defaults;

		$lbn = $options['login_button_name'];
		$uin = $options['user_input_name'];
		$pin = $options['password_input_name'];
		$rin = $options['remember_me_input_name'];
		// Autenticate user
		if (($lbn || isset($_POST[$lbn])) && isset($_POST[$uin]) && isset($_POST[$pin])) {
			$auth = new Authenticate($_POST[$uin], $_POST[$pin], isset($_POST[$rin]));
			self::$currentUserData = $auth->getUserData();
			unset($auth, $_POST[$uin], $_POST[$pin]);
			if ($options['login_destination']) {
				redirect($options['login_destination']);
			}
		} elseif (isset($_GET[$options['logout_query_name']]) && $_GET[$options['logout_query_name']] === $options['logout_query_value']) {
			Authenticate::logOut();
			self::$currentUserData = Authenticate::getEmptyUserData();
			if ($options['logout_destination']) {
				redirect($options['logout_destination']);
			}
		}

		self::$currentUserData = Authenticate::validateAuthUser();
	}

	/**
	 * Get the data of the current authenticated or guest user
	 *
	 * @see Authenticate::getEmptyUserData()
	 *
	 * @return array
	 */
	public static function getCurrentUserData() {
		return self::$currentUserData ? : Authenticate::getEmptyUserData();
	}
}