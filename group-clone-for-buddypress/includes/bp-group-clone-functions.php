<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

class BP_Group_Clone_Functions {

    public function __construct() {
        add_action('bp_setup_nav', array($this, 'add_admin_nav_item'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'add_admin_button'));
        add_action('wp_ajax_bp_group_clone', array($this, 'process_clone'));
        add_action('wp_ajax_bp_group_clone', array($this, 'process_clone'));
        add_action('wp_ajax_bp_group_clone_log_error', array($this, 'log_ajax_error'));
    }

    // Log AJAX errors
    public function log_ajax_error() {
        if (isset($_POST['error_details'])) {
            $error_details = wp_unslash($_POST['error_details']);
            error_log('AJAX Error Details: ' . print_r($error_details, true));
        }
        wp_die(); // Required to terminate immediately and return a proper response
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('bp-group-clone-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'jquery-ui-dialog'), null, true);
    }

    // Add "Clone Group" form to group admin area
    public function add_admin_form() {
        if (bp_is_group_admin_page() && bp_group_is_admin()) {
            $group_id = bp_get_current_group_id();
            $form_action = bp_get_group_permalink(groups_get_current_group()) . 'admin/clone/';
            ?>
            <div class="bp-group-clone-admin-form">
                <h3><?php esc_html_e('Clone Group', 'buddypress-group-clone'); ?></h3>
                <form action="<?php echo esc_url($form_action); ?>" method="post">
                    <div class="bp-group-clone-form-field">
                        <label for="new_group_name"><?php esc_html_e('New Group Name:', 'buddypress-group-clone'); ?></label>
                        <input type="text" id="new_group_name" name="new_group_name" required>
                    </div>
                    <div class="bp-group-clone-form-field">
                        <h4><?php esc_html_e('Select Components to Clone:', 'buddypress-group-clone'); ?></h4>
                        <label><input type="checkbox" name="clone_components[]" value="members" checked> <?php esc_html_e('Members', 'buddypress-group-clone'); ?></label><br>
                        <label><input type="checkbox" name="clone_components[]" value="forums" checked> <?php esc_html_e('Forums', 'buddypress-group-clone'); ?></label><br>
                        <label><input type="checkbox" name="clone_components[]" value="activity" checked> <?php esc_html_e('Activity', 'buddypress-group-clone'); ?></label><br>
                        <label><input type="checkbox" name="clone_components[]" value="media" checked> <?php esc_html_e('Media', 'buddypress-group-clone'); ?></label><br>
                    </div>
                    <div class="bp-group-clone-form-field">
                        <button type="button" id="select-all"><?php esc_html_e('Select All', 'buddypress-group-clone'); ?></button>
                        <button type="button" id="deselect-all"><?php esc_html_e('Deselect All', 'buddypress-group-clone'); ?></button>
                    </div>
                    <?php wp_nonce_field('clone_group', 'clone_group_nonce'); ?>
                    <div class="bp-group-clone-submit">
                        <input type="submit" name="clone_group_submit" value="<?php esc_attr_e('Clone Group', 'buddypress-group-clone'); ?>" class="button">
                    </div>
                </form>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('#select-all').click(function() {
                        $('input[name="clone_components[]"]').prop('checked', true);
                    });
                    $('#deselect-all').click(function() {
                        $('input[name="clone_components[]"]').prop('checked', false);
                    });
                });
            </script>
            <?php
        }
    }

    // Add "Clone" to group admin navigation
    public function add_admin_nav_item() {
        if (bp_is_group() && bp_group_is_admin()) {
            bp_core_new_subnav_item(array(
                'name' => __('Clone', 'buddypress-group-clone'),
                'slug' => 'clone',
                'parent_url' => bp_get_group_permalink(groups_get_current_group()) . 'admin/',
                'parent_slug' => 'admin',
                'screen_function' => array($this, 'display_clone_page'),
                'position' => 65,
                'user_has_access' => bp_is_item_admin()
            ));
        }
    }

    // Display clone page
    public function display_clone_page() {
        add_action('bp_template_content', array($this, 'add_admin_form'));
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'groups/single/plugins'));
    }

    // Handle group cloning
    public function process_clone() {
        if (isset($_POST['clone_group_submit']) && isset($_POST['clone_group_nonce']) && wp_verify_nonce(wp_unslash($_POST['clone_group_nonce']), 'clone_group')) {
            $original_group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $original_group = groups_get_group($original_group_id);
            if (!$original_group) {
                error_log('Invalid original group ID: ' . $original_group_id);
                wp_send_json_error(__('Invalid original group ID', 'buddypress-group-clone'));
                return;
            }

            $new_group_name = isset($_POST['new_group_name']) ? sanitize_text_field(wp_unslash($_POST['new_group_name'])) : '';
            $clone_components = isset($_POST['clone_components']) ? array_map('sanitize_text_field', wp_unslash($_POST['clone_components'])) : array();

            if (empty($new_group_name)) {
                bp_core_add_message(__('New group name cannot be empty.', 'buddypress-group-clone'), 'error');
                return;
            }

            // Create new group
            $group_status = !empty($original_group->status) ? $original_group->status : 'public'; // Default to 'public' if status is not set

            $new_group_id = groups_create_group(array(
                'creator_id' => get_current_user_id(),
                'name' => $new_group_name,
                /* translators: %s: Original group name */
                'description' => sprintf(__('This is a clone of the group "%s"', 'buddypress-group-clone'), $original_group->name),
                'slug' => groups_check_slug(sanitize_title($new_group_name)),
                'status' => $original_group->status,
                'enable_forum' => $original_group->enable_forum,
                'date_created' => bp_core_current_time()
            ));

            if ($new_group_id) {
                // Clone selected components
                if (in_array('members', $clone_components)) {
                    $this->clone_members($original_group_id, $new_group_id);
                } else {
                    // Ensure the creator is added as a member if no members are cloned
                    groups_join_group($new_group_id, get_current_user_id());
                }
                if (in_array('forums', $clone_components)) {
                    $this->clone_forums($original_group_id, $new_group_id);
                }
                if (in_array('activity', $clone_components)) {
                    $this->clone_activity($original_group_id, $new_group_id);
                }
                if (in_array('media', $clone_components)) {
                    $this->clone_media($original_group_id, $new_group_id);
                }

                // Clone group meta
                $group_meta = groups_get_groupmeta($original_group_id);
                foreach ($group_meta as $meta_key => $meta_value) {
                    groups_update_groupmeta($new_group_id, $meta_key, $meta_value);
                }

                wp_send_json_success(array(
                    'message' => __('Group cloned successfully.', 'buddypress-group-clone'),
                    'redirect_url' => admin_url('admin.php?page=bp-groups')
                ));
            } else {
                error_log('Failed to clone group: ' . print_r($_POST, true)); // Debugging line
                wp_send_json_error(__('Failed to clone group', 'buddypress-group-clone'));
            }
        }
    }

    private function clone_members($original_group_id, $new_group_id) {
        $members = groups_get_group_members(array('group_id' => $original_group_id));
        foreach ($members['members'] as $member) {
            groups_join_group($new_group_id, $member->ID);
        }
    }

    private function clone_forums($original_group_id, $new_group_id) {
        if (function_exists('bbp_get_group_forum_ids')) {
            $forum_ids = bbp_get_group_forum_ids($original_group_id);
            foreach ($forum_ids as $forum_id) {
                $new_forum_id = bbp_insert_forum(array(
                    'post_parent' => $new_group_id,
                    'post_title' => get_the_title($forum_id),
                    'post_content' => get_post_field('post_content', $forum_id),
                ));
                
                // Clone topics and replies
                $topics = get_posts(array('post_type' => 'topic', 'post_parent' => $forum_id));
                foreach ($topics as $topic) {
                    $new_topic_id = bbp_insert_topic(array(
                        'post_parent' => $new_forum_id,
                        'post_title' => $topic->post_title,
                        'post_content' => $topic->post_content,
                    ), array('forum_id' => $new_forum_id));

                    $replies = get_posts(array('post_type' => 'reply', 'post_parent' => $topic->ID));
                    foreach ($replies as $reply) {
                        bbp_insert_reply(array(
                            'post_parent' => $new_topic_id,
                            'post_content' => $reply->post_content,
                        ), array('forum_id' => $new_forum_id, 'topic_id' => $new_topic_id));
                    }
                }
            }
        }
    }

    private function clone_activity($original_group_id, $new_group_id) {
        $cache_key = 'bp_group_clone_activity_' . $original_group_id;
        $activity_ids = wp_cache_get($cache_key);

        if (false === $activity_ids) {
            $activity_ids = BP_Activity_Activity::get_activity_ids(array(
                'component' => 'groups',
                'item_id' => $original_group_id,
            ));
            wp_cache_set($cache_key, $activity_ids, 'bp_group_clone', 3600); // Cache for 1 hour
        }

        foreach ($activity_ids as $activity_id) {
            $activity = new BP_Activity_Activity($activity_id);
            
            // Check for dependencies
            $has_dependencies = in_array($activity->type, array('bbp_reply_create', 'bbp_topic_create'), true);

            if (!$has_dependencies) {
                bp_activity_add(array(
                    'user_id' => $activity->user_id,
                    'action' => $activity->action,
                    'content' => $activity->content,
                    'primary_link' => $activity->primary_link,
                    'component' => 'groups',
                    'type' => $activity->type,
                    'item_id' => $new_group_id,
                    'secondary_item_id' => $activity->secondary_item_id,
                    'date_recorded' => $activity->date_recorded,
                ));
            }
        }
    }

    private function clone_media($original_group_id, $new_group_id) {
        // Clone cover image
        $original_cover_image = groups_get_groupmeta($original_group_id, 'cover_image');
        if ($original_cover_image) {
            $upload_dir = wp_upload_dir();
            $new_cover_image_path = $upload_dir['path'] . '/' . basename($original_cover_image);
            
            if (wp_copy_file($original_cover_image, $new_cover_image_path)) {
                groups_update_groupmeta($new_group_id, 'cover_image', $new_cover_image_path);
            } else {
                bp_core_add_message(__('Failed to copy the group cover image.', 'buddypress-group-clone'), 'error');
            }
        }

        // Clone other media assets (if applicable)
        // Add code here to clone other media assets associated with the group
    }

    // Add clone button to admin groups list
    public function add_admin_button() {
        global $pagenow;
        error_log('BP Group Clone: add_admin_button called');
        error_log('Current page: ' . $pagenow);
        error_log('$_GET[\'page\']: ' . (isset($_GET['page']) ? $_GET['page'] : 'not set'));

        if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'bp-groups') {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
            wp_enqueue_script('bp-group-clone-dialog', plugins_url('assets/js/clone-dialog.js', __FILE__), array('jquery', 'jquery-ui-dialog'), null, true);
            wp_localize_script('bp-group-clone-dialog', 'bpGroupCloneNonce', array('nonce' => wp_create_nonce('bp_group_clone')));
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('bp-group-clone-dialog', plugins_url('assets/js/clone-dialog.js', __FILE__), array('jquery', 'jquery-ui-dialog'), null, true);
        wp_localize_script('bp-group-clone-dialog', 'bpGroupCloneNonce', array('nonce' => wp_create_nonce('bp_group_clone')));
    }
}

add_action('wp_ajax_bp_group_clone_log_error', array('BP_Group_Clone_Functions', 'log_ajax_error'));

// Initialize the BP_Group_Clone_Functions class
new BP_Group_Clone_Functions();
