<?php
/*
Plugin Name: Group Clone for BuddyPress
Plugin URI: https://github.com/eprouveze/group-clone-for-buddypress
Description: Adds functionality to clone BuddyPress groups, including a button in the admin interface and group management area.
Version: 1.1.1
Author: Emmanuel Prouvèze
Author URI: https://www.prouveze.fr/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: group-clone-for-buddypress
Domain Path: /languages
*/

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define plugin constants
define('BP_GROUP_CLONE_VERSION', '1.1.0');
define('BP_GROUP_CLONE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BP_GROUP_CLONE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main BP_Group_Clone class and functions
if (file_exists(BP_GROUP_CLONE_PLUGIN_DIR . 'includes/class-bp-group-clone.php')) {
    require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/class-bp-group-clone.php';
}
if (file_exists(BP_GROUP_CLONE_PLUGIN_DIR . 'includes/bp-group-clone-functions.php')) {
    require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/bp-group-clone-functions.php';
}

require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/class-bp-group-clone-settings.php';

// Check if BuddyPress is active
function group_clone_for_bp_check_buddypress() {
    if (!function_exists('buddypress')) {
        add_action('admin_notices', 'group_clone_for_bp_buddypress_notice');
        return false;
    }
    return true;
}

// Admin notice if BuddyPress is not active
function group_clone_for_bp_buddypress_notice() {
    echo '<div class="error"><p>Group Clone for BuddyPress requires BuddyPress to be installed and active.</p></div>';
}

// Initialize the plugin
function group_clone_for_bp_init() {
    if (group_clone_for_bp_check_buddypress()) {
        if (class_exists('BP_Group_Clone')) {
            $group_clone_for_bp = new BP_Group_Clone();
            add_action('bp_init', array($group_clone_for_bp, 'run'));
        }
    }
}
add_action('bp_include', 'group_clone_for_bp_init');
