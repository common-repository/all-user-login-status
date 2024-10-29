<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://ravisinghit.wordpress.com/
 * @since      1.0.0
 *
 * @package    All_User_Login_Status
 * @subpackage All_User_Login_Status/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    All_User_Login_Status
 * @subpackage All_User_Login_Status/includes
 * @author     Ravi Singh <ravisinghit@gmail.com>
 */
class All_User_Login_Status_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'all-user-login-status',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
