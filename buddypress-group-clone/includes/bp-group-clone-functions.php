<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Add "Clone Group" form to group admin area
function bp_group_clone_add_admin_form() {
    if (bp_is_group_admin_page() && bp_group_is_admin()) {
        $group_id = bp_get_current_group_id();
        $form_action = bp_get_group_permalink(groups_get_current_group()) . 'admin/';
        ?>
        <h3>Clone Group</h3>
        <form action="<?php echo esc_url($form_action); ?>" method="post">
            <label for="new_group_name">New Group Name:</label>
            <input type="text" id="new_group_name" name="new_group_name" required>
            <?php wp_nonce_field('clone_group', 'clone_group_nonce'); ?>
            <input type="submit" name="clone_group_submit" value="Clone Group" class="button">
        </form>
        <?php
    }
}
add_action('bp_group_admin_pagination', 'bp_group_clone_add_admin_form');

// Handle group cloning
function bp_group_clone_process() {
    if (isset($_POST['clone_group_submit']) && wp_verify_nonce($_POST['clone_group_nonce'], 'clone_group')) {
        $original_group_id = bp_get_current_group_id();
        $original_group = groups_get_group($original_group_id);
        $new_group_name = sanitize_text_field($_POST['new_group_name']);

        if (empty($new_group_name)) {
            bp_core_add_message('New group name cannot be empty.', 'error');
            return;
        }

        // Create new group
        $new_group_id = groups_create_group(array(
            'creator_id' => get_current_user_id(),
            'name' => $new_group_name,
            'description' => $original_group->description,
            'slug' => groups_check_slug(sanitize_title($new_group_name)),
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

            bp_core_add_message('Group cloned successfully.');
            // Redirect to the new group's admin area
            wp_redirect(bp_get_group_permalink(groups_get_group($new_group_id)) . 'admin/');
            exit;
        } else {
            bp_core_add_message('Failed to clone group', 'error');
        }
    }
}
add_action('bp_actions', 'bp_group_clone_process');
