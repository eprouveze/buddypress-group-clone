<?php

class BP_Group_Clone {

    private $group_clone_functions;

    public function __construct() {
        $this->load_dependencies();
        $this->initialize_functions();
        $this->define_hooks();
    }

    private function load_dependencies() {
        require_once BP_GROUP_CLONE_PLUGIN_DIR . 'includes/bp-group-clone-functions.php';
    }

    private function initialize_functions() {
        $this->group_clone_functions = new BP_Group_Clone_Functions();
    }

    private function define_hooks() {
        add_action('bp_include', array($this, 'setup_group_clone_functions'));
        add_action('bp_init', array($this, 'init_group_clone_functions'));
        add_action('admin_enqueue_scripts', array($this->group_clone_functions, 'enqueue_admin_scripts'));
        add_action('admin_footer', array($this->group_clone_functions, 'add_clone_button_to_admin'));
    }

    public function run() {
        $this->load_plugin_textdomain();
        $this->setup_group_clone_functions();
        $this->init_group_clone_functions();
    }

    public function setup_group_clone_functions() {
        if (method_exists($this->group_clone_functions, 'setup_actions')) {
            $this->group_clone_functions->setup_actions();
        }
    }

    public function init_group_clone_functions() {
        if (method_exists($this->group_clone_functions, 'init')) {
            $this->group_clone_functions->init();
        }
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'buddypress-group-clone',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}