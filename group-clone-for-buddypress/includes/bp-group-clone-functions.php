<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

class BP_Group_Clone_Functions {

    public function __construct() {
        add_action('bp_setup_nav', array($this, 'add_admin_nav_item'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_bp_group_clone', array($this, 'process_clone'));
        add_action('wp_ajax_bp_group_clone_log_error', array($this, 'log_ajax_error'));
        add_action('admin_footer', array($this, 'add_clone_button_to_admin'));
    }

    public function enqueue_admin_scripts($hook) {
        error_log('BP Group Clone: enqueue_admin_scripts() called with hook: ' . $hook);
        
        if ('toplevel_page_bp-groups' !== $hook) {
            error_log('BP Group Clone: Not on BP Groups admin page, scripts not enqueued');
            return;
        }
        
        error_log('BP Group Clone: Enqueuing scripts for BP Groups admin page');

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('bp-group-clone-common', BP_GROUP_CLONE_PLUGIN_URL . 'assets/js/common.js', array('jquery', 'jquery-ui-dialog'), BP_GROUP_CLONE_VERSION, true);
        
        wp_localize_script('bp-group-clone-common', 'bpGroupCloneL10n', array(
            'cloneText' => __('Clone', 'buddypress-group-clone'),
            'cloneGroupTitle' => __('Clone Group', 'buddypress-group-clone'),
            'groupStatusText' => __('Group Status: ', 'buddypress-group-clone'),
            'groupTypeText' => __('Group Type: ', 'buddypress-group-clone'),
            'enterNameText' => __('Enter a name for the cloned group:', 'buddypress-group-clone'),
            'optionalComponentsText' => __('Optional: Select additional components to clone:', 'buddypress-group-clone'),
            'membersText' => __('Members', 'buddypress-group-clone'),
            'forumsText' => __('Forums', 'buddypress-group-clone'),
            'activityText' => __('Activity', 'buddypress-group-clone'),
            'mediaText' => __('Media', 'buddypress-group-clone'),
            'cloneButtonText' => __('Clone', 'buddypress-group-clone'),
            'cancelButtonText' => __('Cancel', 'buddypress-group-clone'),
            'emptyGroupNameError' => __('Group name cannot be empty.', 'buddypress-group-clone'),
            'cloneSuccessMessage' => __('Group cloned successfully!', 'buddypress-group-clone'),
            'genericErrorMessage' => __('An error occurred while cloning the group.', 'buddypress-group-clone'),
            'ajaxErrorMessage' => __('A network error occurred. Please try again.', 'buddypress-group-clone')
        ));

        wp_localize_script('bp-group-clone-common', 'bpGroupCloneNonce', wp_create_nonce('bp_group_clone'));
    }

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

    public function display_clone_page() {
        add_action('bp_template_content', array($this, 'clone_page_content'));
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'groups/single/plugins'));
    }

    public function clone_page_content() {
        echo '<h2>' . esc_html__('Clone This Group', 'buddypress-group-clone') . '</h2>';
        // Add your form HTML here
    }

    public function add_clone_button_to_admin() {
        $screen = get_current_screen();
        error_log('BP Group Clone: Current screen ID: ' . $screen->id);
        
        if ($screen->id === 'toplevel_page_bp-groups') {
            error_log('BP Group Clone: add_clone_button_to_admin() called');
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('BP Group Clone: jQuery ready function executed');
                $('.row-actions').each(function() {
                    var $this = $(this);
                    var groupId = $this.closest('tr').attr('id').replace('group-', '');
                    console.log('BP Group Clone: Processing group with ID: ' + groupId);
                    if ($this.find('.bp-group-clone').length === 0) {
                        console.log('BP Group Clone: Adding clone button to group ' + groupId);
                        $this.prepend('<span class="clone"><a href="#" class="bp-group-clone" data-group-id="' + groupId + '"><?php echo esc_js(__('Clone', 'buddypress-group-clone')); ?></a> | </span>');
                    }
                });
            });
            </script>
            <?php
        } else {
            error_log('BP Group Clone: Not on BP Groups admin page');
        }
    }

    public function process_clone() {
        // Verify nonce
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'bp_group_clone')) {
            wp_send_json_error(['message' => __('Security check failed.', 'buddypress-group-clone')]);
            return;
        }

        // Sanitize and validate input
        $original_group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $new_group_name = isset($_POST['new_group_name']) ? sanitize_text_field(wp_unslash($_POST['new_group_name'])) : '';
        $clone_components = isset($_POST['clone_components']) ? array_map('sanitize_text_field', wp_unslash($_POST['clone_components'])) : array();

        // Validate group ID
        $original_group = groups_get_group($original_group_id);
        if (!$original_group) {
            wp_send_json_error(['message' => __('Invalid original group ID.', 'buddypress-group-clone')]);
            return;
        }

        // Validate new group name
        if (empty($new_group_name)) {
            wp_send_json_error(['message' => __('New group name cannot be empty.', 'buddypress-group-clone')]);
            return;
        }

        // Validate clone components
        $valid_components = ['members', 'forums', 'activity', 'media'];
        $clone_components = array_intersect($clone_components, $valid_components);

        // Get group types of the original group
        $group_types = bp_groups_get_group_type($original_group_id, false);

        // Create new group
        $new_group_args = array(
            'creator_id' => get_current_user_id(),
            'name' => $new_group_name,
            /* translators: %s: Original group name */
            'description' => sprintf(__('This is a clone of the group "%s"', 'buddypress-group-clone'), esc_html($original_group->name)),
            'slug' => groups_check_slug(sanitize_title($new_group_name)),
            'status' => $original_group->status,
            'enable_forum' => $original_group->enable_forum,
            'date_created' => bp_core_current_time()
        );

        $new_group_id = groups_create_group($new_group_args);

        if (!$new_group_id) {
            error_log('BP Group Clone: Failed to create new group. Args: ' . print_r($new_group_args, true));
            wp_send_json_error(['message' => __('Failed to create new group.', 'buddypress-group-clone')]);
            return;
        }

        // Set group types for the new group
        if (!empty($group_types)) {
            foreach ($group_types as $group_type) {
                bp_groups_set_group_type($new_group_id, $group_type);
            }
        }

        // Clone selected components
        foreach ($clone_components as $component) {
            switch ($component) {
                case 'members':
                    $this->clone_members($original_group_id, $new_group_id);
                    break;
                case 'forums':
                    $this->clone_forums($original_group_id, $new_group_id);
                    break;
                case 'activity':
                    $this->clone_activity($original_group_id, $new_group_id);
                    break;
                case 'media':
                    $this->clone_media($original_group_id, $new_group_id);
                    break;
            }
        }

        // Clone group meta
        $group_meta = groups_get_groupmeta($original_group_id);
        foreach ($group_meta as $meta_key => $meta_value) {
            groups_update_groupmeta($new_group_id, $meta_key, $meta_value);
        }

        // Update the last activity time for the new group
        $current_time = bp_core_current_time();
        groups_update_last_activity($new_group_id, $current_time);

        error_log('BP Group Clone: New group created. ID: ' . $new_group_id . ', Name: ' . $new_group_name . ', Types: ' . print_r($group_types, true));

        wp_send_json_success([
            'message' => __('Group cloned successfully.', 'buddypress-group-clone'),
            'redirect_url' => admin_url('admin.php?page=bp-groups')
        ]);
    }

    private function clone_members($original_group_id, $new_group_id) {
        $members = groups_get_group_members([
            'group_id' => $original_group_id,
            'per_page' => 9999  // Adjust this value based on your needs
        ]);
        foreach ($members['members'] as $member) {
            groups_join_group($new_group_id, $member->ID);
        }
    }

    private function clone_forums($original_group_id, $new_group_id) {
        if (function_exists('bbp_get_group_forum_ids')) {
            $forum_ids = bbp_get_group_forum_ids($original_group_id);
            foreach ($forum_ids as $forum_id) {
                $new_forum_id = bbp_insert_forum([
                    'post_parent' => $new_group_id,
                    'post_title' => get_the_title($forum_id),
                    'post_content' => get_post_field('post_content', $forum_id),
                ]);
                
                if ($new_forum_id) {
                    $this->clone_topics($forum_id, $new_forum_id);
                }
            }
        }
    }

    private function clone_topics($old_forum_id, $new_forum_id) {
        $topics = get_posts([
            'post_type' => 'topic',
            'post_parent' => $old_forum_id,
            'numberposts' => -1
        ]);
        foreach ($topics as $topic) {
            $new_topic_id = bbp_insert_topic(
                [
                    'post_parent' => $new_forum_id,
                    'post_title' => $topic->post_title,
                    'post_content' => $topic->post_content,
                ],
                ['forum_id' => $new_forum_id]
            );

            if ($new_topic_id) {
                $this->clone_replies($topic->ID, $new_topic_id, $new_forum_id);
            }
        }
    }

    private function clone_replies($old_topic_id, $new_topic_id, $new_forum_id) {
        $replies = get_posts([
            'post_type' => 'reply',
            'post_parent' => $old_topic_id,
            'numberposts' => -1
        ]);
        foreach ($replies as $reply) {
            bbp_insert_reply(
                [
                    'post_parent' => $new_topic_id,
                    'post_content' => $reply->post_content,
                ],
                ['forum_id' => $new_forum_id, 'topic_id' => $new_topic_id]
            );
        }
    }

    private function clone_activity($original_group_id, $new_group_id) {
        $activities = bp_activity_get([
            'filter' => [
                'object' => 'groups',
                'primary_id' => $original_group_id
            ],
            'per_page' => 9999 // Adjust based on your needs
        ]);

        foreach ($activities['activities'] as $activity) {
            bp_activity_add([
                'user_id' => $activity->user_id,
                'action' => $activity->action,
                'content' => $activity->content,
                'primary_link' => $activity->primary_link,
                'component' => 'groups',
                'type' => $activity->type,
                'item_id' => $new_group_id,
                'secondary_item_id' => $activity->secondary_item_id,
                'date_recorded' => $activity->date_recorded,
            ]);
        }
    }

    private function clone_media($original_group_id, $new_group_id) {
        if (function_exists('bp_media_get')) {
            $media_items = bp_media_get([
                'group_id' => $original_group_id,
                'per_page' => 9999 // Adjust based on your needs
            ]);

            foreach ($media_items['media'] as $media) {
                $file_path = bp_media_upload_path() . '/' . $media->attachment_data['file'];
                $new_file_path = bp_media_upload_path() . '/' . $new_group_id . '_' . basename($file_path);

                if (copy($file_path, $new_file_path)) {
                    bp_media_add([
                        'group_id' => $new_group_id,
                        'user_id' => $media->user_id,
                        'title' => $media->title,
                        'description' => $media->description,
                        'attachment' => [
                            'file' => $new_file_path,
                            'type' => $media->attachment_data['type']
                        ]
                    ]);
                }
            }
        }
    }

    public function log_ajax_error() {
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'bp_group_clone_log_error')) {
            return;
        }

        if (!isset($_POST['error_details']) || !is_array($_POST['error_details'])) {
            return;
        }

        $error_details = wp_unslash($_POST['error_details']);
        $error_message = sprintf(
            "AJAX Error in BP Group Clone:\nStatus: %s\nError: %s\nResponse: %s",
            isset($error_details['textStatus']) ? sanitize_text_field($error_details['textStatus']) : '',
            isset($error_details['errorThrown']) ? sanitize_text_field($error_details['errorThrown']) : '',
            isset($error_details['responseText']) ? sanitize_textarea_field($error_details['responseText']) : ''
        );

        error_log($error_message);
    }
}
