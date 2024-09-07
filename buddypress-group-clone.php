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

// Add "Clone Group" button to group admin area
function bp_group_clone_add_admin_button() {
    if (bp_is_group_admin_page() && bp_group_is_admin()) {
        $group_id = bp_get_current_group_id();
        $clone_url = wp_nonce_url(add_query_arg('action', 'clone_group', bp_get_group_permalink(groups_get_current_group())), 'clone_group');
        echo '<a href="' . esc_url($clone_url) . '" class="button">Clone Group</a>';
    }
}
add_action('bp_group_admin_pagination', 'bp_group_clone_add_admin_button');

// Handle group cloning
function bp_group_clone_process() {
    if (isset($_GET['action']) && $_GET['action'] == 'clone_group') {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'clone_group')) {
            wp_die('Security check failed');
        }

        $original_group_id = bp_get_current_group_id();
        $original_group = groups_get_group($original_group_id);

        // Create new group
        $new_group_id = groups_create_group(array(
            'creator_id' => get_current_user_id(),
            'name' => $original_group->name . ' (Clone)',
            'description' => $original_group->description,
            'slug' => groups_check_slug(sanitize_title($original_group->name . '-clone')),
            'status' => $original_group->status,
            'enable_forum' => $original_group->enable_forum,
            'date_created' => bp_core_current_time()
        ));

        if ($new_group_id) {
            // Clone group meta
            $group_meta = groups_get_groupmeta($original_group_id);
            foreach ($group_meta as $meta_key => $meta_value) {
                groups_update_groupmeta($new_group_id, $meta_key, $meta_value);
            }

            // Redirect to the new group's admin area
            wp_redirect(bp_get_group_permalink(groups_get_group($new_group_id)) . 'admin/');
            exit;
        } else {
            wp_die('Failed to clone group');
        }
    }
}
add_action('bp_actions', 'bp_group_clone_process');
