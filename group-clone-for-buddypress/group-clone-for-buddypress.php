<?php
/*
Plugin Name: Group Clone for BuddyPress
Plugin URI: https://github.com/EmmanuelProuveze/group-clone-for-buddypress
Description: Adds functionality to clone BuddyPress groups, including a button in the admin interface and group management area.
Version: 1.1.0
Author: Emmanuel Prouvèze
Author URI: https://www.prouveze.fr/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: group-clone-for-buddypress
Domain Path: /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('GROUP_CLONE_FOR_BP_VERSION', '1.1.0');
define('GROUP_CLONE_FOR_BP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GROUP_CLONE_FOR_BP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main Group_Clone_For_BP class
require_once GROUP_CLONE_FOR_BP_PLUGIN_DIR . 'includes/class-group-clone-for-bp.php';

// Check if BuddyPress is active
function group_clone_for_bp_check_buddypress() {
    if (!class_exists('BuddyPress')) {
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
        $group_clone_for_bp = new Group_Clone_For_BP();
        $group_clone_for_bp->run();
        
        // Initialize Group_Clone_For_BP_Functions
        new Group_Clone_For_BP_Functions();
    }
}
add_action('plugins_loaded', 'group_clone_for_bp_init');
