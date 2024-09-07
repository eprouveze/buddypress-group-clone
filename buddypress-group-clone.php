<?php
/*
Plugin Name: BuddyPress Group Clone
Plugin URI: https://github.com/EmmanuelProuveze/buddypress-group-clone
Description: Adds functionality to clone BuddyPress groups, including a button in the admin interface and group management area.
Version: 1.0.3
Author: Emmanuel Prouvèze
Author URI: https://www.prouveze.fr/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: buddypress-group-clone
Domain Path: /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('BP_GROUP_CLONE_VERSION', '1.0.3');
define('BP_GROUP_CLONE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BP_GROUP_CLONE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main BP_Group_Clone class
require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/class-bp-group-clone.php';

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

// Initialize the plugin
function bp_group_clone_init() {
    if (bp_group_clone_check_buddypress()) {
        $bp_group_clone = new BP_Group_Clone();
        $bp_group_clone->run();
        
        // Initialize BP_Group_Clone_Functions
        new BP_Group_Clone_Functions();
    }
}
add_action('plugins_loaded', 'bp_group_clone_init');
