<?php
/*
Plugin Name: BuddyPress Group Clone
Plugin URI: https://example.com/buddypress-group-clone
Description: Adds a button to clone a group in BuddyPress (only the settings, not the group contents)
Version: 1.0
Author: Your Name
Author URI: https://example.com
License: GPL2
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
    }
}
add_action('plugins_loaded', 'bp_group_clone_init');
