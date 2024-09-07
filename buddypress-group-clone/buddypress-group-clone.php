<?php
/*
Plugin Name: BuddyPress Group Clone
Plugin URI: https://github.com/yourusername/buddypress-group-clone
Description: Adds functionality to clone BuddyPress groups, including a button in the admin interface and group management area.
Version: 1.0.0
Author: Your Name
Author URI: https://yourwebsite.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: buddypress-group-clone
Domain Path: /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin path
define('BP_GROUP_CLONE_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Check if BuddyPress is active
function bp_group_clone_check_buddypress() {
    if (!class_exists('BuddyPress')) {
        add_action('admin_notices', 'bp_group_clone_buddypress_notice');
        return false;
    }
    return true;
}

// Admin notice if BuddyPress is not active
function bp_group_clone_buddypress_notice() {
    echo '<div class="error"><p>BuddyPress Group Clone requires BuddyPress to be installed and active.</p></div>';
}

// Load plugin functionality
function bp_group_clone_init() {
    if (bp_group_clone_check_buddypress()) {
        require_once BP_GROUP_CLONE_PLUGIN_PATH . 'includes/bp-group-clone-functions.php';
        if (is_admin()) {
            add_action('admin_init', 'bp_group_clone_process');
            add_action('admin_footer', 'bp_group_clone_add_admin_button');
        } else {
            add_action('bp_setup_nav', 'bp_group_clone_add_admin_nav_item');
        }
    }
}
add_action('plugins_loaded', 'bp_group_clone_init');
