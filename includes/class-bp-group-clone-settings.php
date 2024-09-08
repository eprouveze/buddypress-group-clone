<?php
/**
 * BP Group Clone Settings
 *
 * @package BuddyPressGroupClone
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP_Group_Clone_Settings Class
 */
class BP_Group_Clone_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Group Clone Settings', 'buddypress-group-clone' ),
            __( 'Group Clone', 'buddypress-group-clone' ),
            'manage_options',
            'bp-group-clone-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'bp_group_clone_settings',
            'bp_group_clone_fields_to_clone',
            array(
                'sanitize_callback' => array( $this, 'sanitize_fields_to_clone' ),
                'default'           => array(),
            )
        );

        add_settings_section(
            'bp_group_clone_main_section',
            __( 'Fields to Clone', 'buddypress-group-clone' ),
            null,
            'bp-group-clone-settings'
        );

        add_settings_field(
            'bp_group_clone_fields_to_clone',
            __( 'Select Fields to Clone', 'buddypress-group-clone' ),
            array( $this, 'render_components_field' ),
            'bp-group-clone-settings',
            'bp_group_clone_main_section'
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Check user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'buddypress-group-clone' ) );
        }

        // Check if the form is submitted.
        if ( isset( $_POST['submit'] ) ) {
            // Verify nonce.
            $nonce = isset( $_POST['bp_group_clone_settings_nonce'] ) ? wp_unslash( $_POST['bp_group_clone_settings_nonce'] ) : '';
            if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'bp_group_clone_settings_action' ) ) {
                wp_die( esc_html__( 'Security check failed. Please try again.', 'buddypress-group-clone' ) );
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'bp_group_clone_settings' );
                do_settings_sections( 'bp-group-clone-settings' );
                wp_nonce_field( 'bp_group_clone_settings_action', 'bp_group_clone_settings_nonce' );
                submit_button( __( 'Save Settings', 'buddypress-group-clone' ) );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the components field
     */
    public function render_components_field() {
        $options = (array) get_option( 'bp_group_clone_fields_to_clone', array() );
        $fields  = array( 'name', 'type', 'status', 'members', 'forums', 'activity', 'media' );

        foreach ( $fields as $field ) {
            ?>
            <label>
                <input type="checkbox" name="bp_group_clone_fields_to_clone[]" 
                       value="<?php echo esc_attr( $field ); ?>" 
                       <?php checked( in_array( $field, $options, true ) ); ?>>
                <?php echo esc_html( ucfirst( $field ) ); ?>
            </label><br>
            <?php
        }
    }

    /**
     * Sanitize the fields to clone
     *
     * @param array $input The input array to sanitize.
     * @return array The sanitized input array.
     */
    public function sanitize_fields_to_clone( $input ) {
        $valid_fields = array( 'name', 'type', 'status', 'members', 'forums', 'activity', 'media' );
        return array_intersect( $input, $valid_fields );
    }
}

/**
 * Initialize the settings class
 */
function bp_group_clone_init_settings() {
    new BP_Group_Clone_Settings();
}
add_action( 'plugins_loaded', 'bp_group_clone_init_settings' );
