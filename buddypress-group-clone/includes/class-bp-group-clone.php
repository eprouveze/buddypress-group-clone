<?php

class BP_Group_Clone {

    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/bp-group-clone-functions.php';
    }

    private function define_admin_hooks() {
        add_action('admin_init', 'bp_group_clone_process');
        add_action('admin_footer', 'bp_group_clone_add_admin_button');
    }

    private function define_public_hooks() {
        add_action('bp_setup_nav', 'bp_group_clone_add_admin_nav_item');
    }

    public function run() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'buddypress-group-clone',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
