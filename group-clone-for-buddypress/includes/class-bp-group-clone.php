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
        // These hooks are now handled by BP_Group_Clone_Functions
    }

    private function define_public_hooks() {
        // These hooks are now handled by BP_Group_Clone_Functions
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
