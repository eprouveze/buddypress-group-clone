<?php
/**
 * Plugin Name: BuddyPress Group Clone
 * Plugin URI: https://github.com/yourusername/buddypress-group-clone
 * Description: A WordPress plugin that extends BuddyPress functionality by adding the ability to clone groups.
 * Version: 1.0.3
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: buddypress-group-clone
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('BP_GROUP_CLONE_VERSION', '1.0.3');
define('BP_GROUP_CLONE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BP_GROUP_CLONE_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/class-bp-group-clone.php';
        $plugin = new BP_Group_Clone();
        $plugin->run();
    }
}
add_action('bp_include', 'bp_group_clone_init');
