<?php

class BP_Group_Clone_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_options_page(
            __('Group Clone Settings', 'buddypress-group-clone'),
            __('Group Clone', 'buddypress-group-clone'),
            'manage_options',
            'bp-group-clone-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('bp_group_clone_settings', 'bp_group_clone_default_options');

        add_settings_section(
            'bp_group_clone_main_section',
            __('Default Cloning Options', 'buddypress-group-clone'),
            null,
            'bp-group-clone-settings'
        );

        add_settings_field(
            'bp_group_clone_default_name',
            __('Default Group Name', 'buddypress-group-clone'),
            array($this, 'render_name_field'),
            'bp-group-clone-settings',
            'bp_group_clone_main_section'
        );

        add_settings_field(
            'bp_group_clone_default_type',
            __('Default Group Type', 'buddypress-group-clone'),
            array($this, 'render_type_field'),
            'bp-group-clone-settings',
            'bp_group_clone_main_section'
        );

        add_settings_field(
            'bp_group_clone_default_status',
            __('Default Group Status', 'buddypress-group-clone'),
            array($this, 'render_status_field'),
            'bp-group-clone-settings',
            'bp_group_clone_main_section'
        );

        add_settings_field(
            'bp_group_clone_default_components',
            __('Default Components to Clone', 'buddypress-group-clone'),
            array($this, 'render_components_field'),
            'bp-group-clone-settings',
            'bp_group_clone_main_section'
        );
    }

    public function render_name_field() {
        $options = get_option('bp_group_clone_default_options', array());
        $name = isset($options['name']) ? $options['name'] : '';
        ?>
        <input type="text" name="bp_group_clone_default_options[name]" value="<?php echo esc_attr($name); ?>">
        <?php
    }

    public function render_type_field() {
        $options = get_option('bp_group_clone_default_options', array());
        $type = isset($options['type']) ? $options['type'] : 'public';
        ?>
        <select name="bp_group_clone_default_options[type]">
            <option value="public" <?php selected($type, 'public'); ?>><?php esc_html_e('Public', 'buddypress-group-clone'); ?></option>
            <option value="private" <?php selected($type, 'private'); ?>><?php esc_html_e('Private', 'buddypress-group-clone'); ?></option>
            <option value="hidden" <?php selected($type, 'hidden'); ?>><?php esc_html_e('Hidden', 'buddypress-group-clone'); ?></option>
        </select>
        <?php
    }

    public function render_status_field() {
        $options = get_option('bp_group_clone_default_options', array());
        $status = isset($options['status']) ? $options['status'] : 'active';
        ?>
        <select name="bp_group_clone_default_options[status]">
            <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'buddypress-group-clone'); ?></option>
            <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inactive', 'buddypress-group-clone'); ?></option>
        </select>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Group Clone Settings', 'buddypress-group-clone'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('bp_group_clone_settings');
                do_settings_sections('bp-group-clone-settings');
                submit_button(__('Save Settings', 'buddypress-group-clone'));
                ?>
            </form>
        </div>
        <?php
    }

    public function render_components_field() {
        $options = get_option('bp_group_clone_default_options', array());
        $components = array('members', 'forums', 'activity', 'media');
        foreach ($components as $component) {
            ?>
            <label>
                <input type="checkbox" name="bp_group_clone_default_options[]" value="<?php echo esc_attr($component); ?>" <?php checked(in_array($component, $options)); ?>>
                <?php echo esc_html(ucfirst($component)); ?>
            </label><br>
            <?php
        }
    }
}

new BP_Group_Clone_Settings();
