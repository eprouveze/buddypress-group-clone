<?php
/**
 * Plugin Name: BuddyPress Group Clone
 * Plugin URI: https://github.com/buddypress/bp-group-clone
 * Description: Allows admins to clone BuddyPress groups.
 * Version: 1.0.2
 * Author: BuddyPress, Your Name
 * Author URI: https://buddypress.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: buddypress-group-clone
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

define('BP_GROUP_CLONE_PLUGIN_PATH', plugin_dir_path(__FILE__));

function bp_group_clone_check_buddypress() {
    if (!class_exists('BuddyPress')) {
        add_action('admin_notices', 'bp_group_clone_buddypress_notice');
        return false;
    }
    return true;
}

function bp_group_clone_buddypress_notice() {
    echo '<div class="error"><p>BuddyPress Group Clone requires BuddyPress to be installed and activated.</p></div>';
}

function bp_group_clone_init() {
    if (bp_group_clone_check_buddypress()) {
        require_once BP_GROUP_CLONE_PLUGIN_PATH . 'includes/bp-group-clone-functions.php';
        if (is_admin()) {
            add_action('admin_init', 'bp_group_clone_process');
            add_action('admin_footer', 'bp_group_clone_add_admin_button');
        }
    }
}
add_action('bp_include', 'bp_group_clone_init');
