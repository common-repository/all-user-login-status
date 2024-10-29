<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ravisinghit.wordpress.com/
 * @since             1.0.0
 * @package           All_User_Login_Status
 *
 * @wordpress-plugin
 * Plugin Name:       All User Login Status
 * Plugin URI:        https://www.topinfosoft.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Ravi Singh
 * Author URI:        https://ravisinghit.wordpress.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       all-user-login-status
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ALL_USER_LOGIN_STATUS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-all-user-login-status-activator.php
 */
function activate_all_user_login_status() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-all-user-login-status-activator.php';
	All_User_Login_Status_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-all-user-login-status-deactivator.php
 */
function deactivate_all_user_login_status() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-all-user-login-status-deactivator.php';
	All_User_Login_Status_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_all_user_login_status' );
register_deactivation_hook( __FILE__, 'deactivate_all_user_login_status' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-all-user-login-status.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_all_user_login_status() {

	$plugin = new All_User_Login_Status();
	$plugin->run();

//Active Users Metabox
add_action('wp_dashboard_setup', 'auls_activeusers_metabox');
function auls_activeusers_metabox(){
    global $wp_meta_boxes;
    wp_add_dashboard_widget('auls_activeusers', 'Active Users', 'dashboard_auls_activeusers');
}


function dashboard_auls_activeusers(){
        $user_count = count_users();
        $users_plural = ( $user_count['total_users'] == 1 )? 'User' : 'Users'; //Determine singular/plural tense
        $useradminurl = admin_url( 'users.php', 'https' );
        echo '<div><a href="'.esc_html($useradminurl).'">' . esc_html($user_count['total_users']) . ' ' . esc_html($users_plural) . '</a> <small>(' . auls_online_users('count') . ' currently active)</small></div>';
}

//Get a count of online users, or an array of online user IDs.
//Pass 'count' (or nothing) as the parameter to simply return a count, otherwise it will return an array of online user data.
function auls_online_users($return='count'){
    $logged_in_users = get_transient('users_status');
    
    //If no users are online
    if ( empty($logged_in_users) ){
        return ( $return == 'count' )? 0 : false; //If requesting a count return 0, if requesting user data return false.
    }
    
    $user_online_count = 0;
    $online_users = array();
    foreach ( $logged_in_users as $user ){
        if ( !empty($user['username']) && isset($user['last']) && $user['last'] > time()-900 ){ //If the user has been online in the last 900 seconds, add them to the array and increase the online count.
            $online_users[] = $user;
            $user_online_count++;
        }
    }

    return ( $return == 'count' )? $user_online_count : $online_users; //Return either an integer count, or an array of all online user data.
}


//Update user online status
add_action('init', 'auls_users_status_init');
add_action('admin_init', 'auls_users_status_init');
function auls_users_status_init(){
    $logged_in_users = get_transient('users_status'); //Get the active users from the transient.
    $user = wp_get_current_user(); //Get the current user's data

    //Update the user if they are not on the list, or if they have not been online in the last 900 seconds (15 minutes)
    if ( !isset($logged_in_users[$user->ID]['last']) || $logged_in_users[$user->ID]['last'] <= time()-9 ){
        $logged_in_users[$user->ID] = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'last' => time(),
        );
        set_transient('users_status', $logged_in_users, 900); //Set this transient to expire 15 minutes after it is created.
    }
}

//Check if a user has been online in the last 15 minutes
function auls_is_user_online($id){  
    $logged_in_users = get_transient('users_status'); //Get the active users from the transient.
    
    return isset($logged_in_users[$id]['last']) && $logged_in_users[$id]['last'] > time()-9; //Return boolean if the user has been online in the last 900 seconds (15 minutes).
}

//Check when a user was last online.
function auls_user_last_online($id){
    $logged_in_users = get_transient('users_status'); //Get the active users from the transient.
    
    //Determine if the user has ever been logged in (and return their last active date if so).
    if ( isset($logged_in_users[$id]['last']) ){
        return $logged_in_users[$id]['last'];
    } else {
        return false;
    }
}



 //Add columns to user listings
add_filter('manage_users_columns', 'auls_user_columns_head');
function auls_user_columns_head($defaults){
    $defaults['status'] = 'Status';
    return $defaults;
}


add_action('manage_users_custom_column', 'auls_user_columns_content', 15, 3);
function auls_user_columns_content($value='', $column_name, $id){
    if ( $column_name == 'status' ){
        if ( auls_is_user_online($id) ){



            return '<strong style="color: green;">Online Now</strong>';
        } else {
            return ( auls_user_last_online($id) )? '<small>Last Seen: <br /><em>' . date('M j, Y @ g:ia', auls_user_last_online($id)) . '</em></small>' : ''; //Return the user's "Last Seen" date, or return empty if that user has never logged in.
        }
    }
}


}
run_all_user_login_status();
