<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Add "Clone Group" form to group admin area
function bp_group_clone_add_admin_form() {
    if (bp_is_group_admin_page() && bp_group_is_admin()) {
        $group_id = bp_get_current_group_id();
        $form_action = bp_get_group_permalink(groups_get_current_group()) . 'admin/clone/';
        ?>
        <div class="bp-group-clone-admin-form">
            <h3><?php _e('Clone Group', 'buddypress-group-clone'); ?></h3>
            <form action="<?php echo esc_url($form_action); ?>" method="post">
                <div class="bp-group-clone-form-field">
                    <label for="new_group_name"><?php _e('New Group Name:', 'buddypress-group-clone'); ?></label>
                    <input type="text" id="new_group_name" name="new_group_name" required>
                </div>
                <?php wp_nonce_field('clone_group', 'clone_group_nonce'); ?>
                <div class="bp-group-clone-submit">
                    <input type="submit" name="clone_group_submit" value="<?php esc_attr_e('Clone Group', 'buddypress-group-clone'); ?>" class="button">
                </div>
            </form>
        </div>
        <?php
    }
}

function bp_group_clone_add_admin_nav_item() {
    if (bp_is_group() && bp_group_is_admin()) {
        $group = groups_get_current_group();
        bp_core_new_subnav_item(array(
            'name' => __('Clone', 'buddypress-group-clone'),
            'slug' => 'clone',
            'parent_url' => bp_get_group_permalink($group),
            'parent_slug' => $group->slug,
            'screen_function' => 'bp_group_clone_admin_screen',
            'position' => 100,
            'user_has_access' => bp_group_is_admin()
        ));
    }
}
add_action('bp_setup_nav', 'bp_group_clone_add_admin_nav_item');

function bp_group_clone_admin_screen() {
    add_action('bp_template_content', 'bp_group_clone_admin_screen_content');
    bp_core_load_template(apply_filters('bp_core_template_plugin', 'groups/single/plugins'));
}

function bp_group_clone_admin_screen_content() {
    bp_group_clone_add_admin_form();
}

// Handle group cloning
function bp_group_clone_process() {
    if (isset($_POST['clone_group_submit']) && isset($_POST['clone_group_nonce']) && wp_verify_nonce($_POST['clone_group_nonce'], 'clone_group')) {
        $original_group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $original_group = groups_get_group($original_group_id);
        $new_group_name = isset($_POST['new_group_name']) ? sanitize_text_field($_POST['new_group_name']) : '';

        if (empty($new_group_name)) {
            bp_core_add_message(__('New group name cannot be empty.', 'buddypress-group-clone'), 'error');
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

            bp_core_add_message(__('Group cloned successfully.', 'buddypress-group-clone'));
            // Redirect to the groups admin page
            wp_safe_redirect(admin_url('admin.php?page=bp-groups'));
            exit;
        } else {
            bp_core_add_message(__('Failed to clone group', 'buddypress-group-clone'), 'error');
        }
    }
}
add_action('admin_init', 'bp_group_clone_process');

// Add clone button to admin groups list
function bp_group_clone_add_admin_button() {
    $screen = get_current_screen();
    if ($screen->id !== 'buddypress_page_bp-groups') {
        return;
    }

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.row-actions').each(function() {
            var $this = $(this);
            var groupId = $this.closest('tr').attr('id').replace('group-', '');
            $this.append('<span class="clone"> | <a href="#" class="bp-group-clone" data-group-id="' + groupId + '">Clone</a></span>');
        });

        $(document).on('click', '.bp-group-clone', function(e) {
            e.preventDefault();
            var groupId = $(this).data('group-id');
            var groupName = $(this).closest('tr').find('.column-title strong').text();
            var newGroupName = prompt('Enter a name for the cloned group:', 'Copy of ' + groupName);
            
            if (newGroupName) {
                var form = $('<form action="" method="post">' +
                    '<input type="hidden" name="clone_group_submit" value="1">' +
                    '<input type="hidden" name="clone_group_nonce" value="' + '<?php echo wp_create_nonce("clone_group"); ?>' + '">' +
                    '<input type="hidden" name="group_id" value="' + groupId + '">' +
                    '<input type="hidden" name="new_group_name" value="' + newGroupName + '">' +
                    '</form>');
                $('body').append(form);
                form.submit();
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'bp_group_clone_add_admin_button');
