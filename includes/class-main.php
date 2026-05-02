<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once AI_PRICING_TABLE_PATH . 'includes/Frontend/class-assets.php';
require_once AI_PRICING_TABLE_PATH . 'includes/Admin/class-admin.php';
require_once AI_PRICING_TABLE_PATH . 'includes/class-post-type.php';

/**
 * Main Plugin Class
 */
class Main {

    private static $instance = null;
    private $settings = null;
    private $ai = null;
    private $assets = null;
    private $admin = null;
    private $post_type = null;

    /**
     * Singleton Instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        $includes_path = AI_PRICING_TABLE_PATH . 'includes/';

        if ( file_exists( $includes_path . 'helpers.php' ) ) {
            require_once $includes_path . 'helpers.php';
        }

        if ( file_exists( $includes_path . 'class-ai.php' ) ) {
            require_once $includes_path . 'class-ai.php';
            $this->ai = new AI();
        }

        if ( file_exists( $includes_path . 'class-settings.php' ) ) {
            require_once $includes_path . 'class-settings.php';
            $this->settings = new Settings();
        }

        require_once $includes_path . 'class-shortcode.php';

        if ( class_exists( 'AI_Pricing_Table\Shortcode' ) ) {
            new Shortcode();
        }

        if ( file_exists( $includes_path . 'class-cpt.php' ) ) {
            require_once $includes_path . 'class-cpt.php';
            new CPT();
        }

        require_once $includes_path . 'class-templates.php';
    }

    /**
     * Initialize modular subsystems.
     */
    private function init_modules() {
        $this->assets = new \AI_Pricing_Table\Frontend\Assets();
        $this->assets->hooks();

        $this->post_type = new Post_Type();
        $this->post_type->hooks();

        $this->admin = new \AI_Pricing_Table\Admin\Admin( $this->settings, $this->ai );
        $this->admin->hooks();
    }
}
