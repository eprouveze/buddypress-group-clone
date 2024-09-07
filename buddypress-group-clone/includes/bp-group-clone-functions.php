<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

class BP_Group_Clone_Functions {

    public function __construct() {
        add_action('bp_setup_nav', array($this, 'add_admin_nav_item'));
        add_action('admin_init', array($this, 'process_clone'));
        add_action('admin_enqueue_scripts', array($this, 'add_admin_button'));
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
        if (isset($_POST['clone_group_submit']) && isset($_POST['clone_group_nonce']) && wp_verify_nonce($_POST['clone_group_nonce'], 'clone_group')) {
            $original_group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $original_group = groups_get_group($original_group_id);
            $new_group_name = isset($_POST['new_group_name']) ? sanitize_text_field($_POST['new_group_name']) : '';
            $clone_components = isset($_POST['clone_components']) ? $_POST['clone_components'] : array();

            if (empty($new_group_name)) {
                bp_core_add_message(__('New group name cannot be empty.', 'buddypress-group-clone'), 'error');
                return;
            }

            if (empty($clone_components)) {
                bp_core_add_message(__('Please select at least one component to clone.', 'buddypress-group-clone'), 'error');
                return;
            }

            // Create new group
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

                bp_core_add_message(__('Group cloned successfully.', 'buddypress-group-clone'));
                // Redirect to the groups admin page
                wp_safe_redirect(admin_url('admin.php?page=bp-groups'));
                exit;
            } else {
                bp_core_add_message(__('Failed to clone group', 'buddypress-group-clone'), 'error');
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
        global $wpdb;
        $activity_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bp_activity WHERE component = 'groups' AND item_id = %d",
            $original_group_id
        ));

        foreach ($activity_ids as $activity_id) {
            $activity = bp_activity_get_specific(array('activity_ids' => $activity_id));
            if (!empty($activity['activities'])) {
                $activity_data = $activity['activities'][0];
                
                // Check for dependencies
                $has_dependencies = false;
                if ($activity_data->type === 'bbp_reply_create' || $activity_data->type === 'bbp_topic_create') {
                    $has_dependencies = true;
                }

                if (!$has_dependencies) {
                    bp_activity_add(array(
                        'user_id' => $activity_data->user_id,
                        'action' => $activity_data->action,
                        'content' => $activity_data->content,
                        'primary_link' => $activity_data->primary_link,
                        'component' => 'groups',
                        'type' => $activity_data->type,
                        'item_id' => $new_group_id,
                        'secondary_item_id' => $activity_data->secondary_item_id,
                        'date_recorded' => $activity_data->date_recorded,
                    ));
                }
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
        if ($pagenow !== 'admin.php' || !isset($_GET['page']) || $_GET['page'] !== 'bp-groups') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        add_action('admin_footer', function() {
            ?>
            <script type="text/javascript">
            /* <![CDATA[ */
        jQuery(document).ready(function($) {
            $('.row-actions').each(function() {
                var $this = $(this);
                var groupId = $this.closest('tr').attr('id').replace('group-', '');
                if ($this.find('.bp-group-clone').length === 0) {
                    $this.prepend('<span class="clone"><a href="#" class="bp-group-clone" data-group-id="' + groupId + '"><?php echo esc_html__('Clone', 'buddypress-group-clone'); ?></a> | </span>');
                }
            });

            $('body').on('click', '.bp-group-clone', function(e) {
                e.preventDefault();
                var groupId = $(this).data('group-id');
                var groupName = $(this).closest('tr').find('.column-title strong').text();
                
                var cloneDialog = $('<div title="' + <?php echo wp_json_encode(__('Clone Group', 'buddypress-group-clone')); ?> + '">' +
                    '<p>' + <?php echo wp_json_encode(__('Enter a name for the cloned group:', 'buddypress-group-clone')); ?> + '</p>' +
                    '<input type="text" id="new-group-name" value="' + groupName + '">' +
                    '<p>' + <?php echo wp_json_encode(__('Select components to clone:', 'buddypress-group-clone')); ?> + '</p>' +
                    '<label><input type="checkbox" name="clone_components[]" value="members"> ' + <?php echo wp_json_encode(__('Members', 'buddypress-group-clone')); ?> + '</label><br>' +
                    '<label><input type="checkbox" name="clone_components[]" value="forums"> ' + <?php echo wp_json_encode(__('Forums', 'buddypress-group-clone')); ?> + '</label><br>' +
                    '<label><input type="checkbox" name="clone_components[]" value="activity"> ' + <?php echo wp_json_encode(__('Activity', 'buddypress-group-clone')); ?> + '</label><br>' +
                    '<label><input type="checkbox" name="clone_components[]" value="media"> ' + <?php echo wp_json_encode(__('Media', 'buddypress-group-clone')); ?> + '</label><br>' +
                    '</div>');

                cloneDialog.dialog({
                    modal: true,
                    buttons: {
                        <?php echo wp_json_encode(__('Clone', 'buddypress-group-clone')); ?>: function() {
                            var newGroupName = $('#new-group-name').val();
                            var selectedComponents = [];
                            $('input[name="clone_components[]"]:checked').each(function() {
                                selectedComponents.push($(this).val());
                            });

                            if (newGroupName && selectedComponents.length > 0) {
                                var form = $('<form action="" method="post">' +
                                    '<input type="hidden" name="clone_group_submit" value="1">' +
                                    '<input type="hidden" name="clone_group_nonce" value="' + '<?php echo esc_attr(wp_create_nonce("clone_group")); ?>' + '">' +
                                    '<input type="hidden" name="group_id" value="' + groupId + '">' +
                                    '<input type="hidden" name="new_group_name" value="' + newGroupName + '">' +
                                    '</form>');
                                
                                $.each(selectedComponents, function(index, value) {
                                    form.append('<input type="hidden" name="clone_components[]" value="' + value + '">');
                                });

                                $('body').append(form);
                                form.submit();
                            } else {
                                alert('Please enter a group name and select at least one component to clone.');
                            }
                        },
                        "Cancel": function() {
                            $(this).dialog("close");
                        }
                    }
                });
            });
        });
        </script>
        <?php
        });
    }
}

// Initialize the BP_Group_Clone_Functions class
new BP_Group_Clone_Functions();
