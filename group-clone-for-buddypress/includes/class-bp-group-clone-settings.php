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
            'bp_group_clone_default_components',
            __('Default Components to Clone', 'buddypress-group-clone'),
            array($this, 'render_components_field'),
            'bp-group-clone-settings',
            'bp_group_clone_main_section'
        );
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
        $options = (array) get_option('bp_group_clone_default_options', array());
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
