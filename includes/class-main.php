<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Class
 */
class Main {

    private static $instance = null;
    private $settings = null;
    private $ai = null;

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
        $this->init_hooks();
        $this->load_dependencies();
        $this->register_cpt();
        $this->register_admin_menu();
    }

    /**
     * Hooks
     */
    private function init_hooks() {
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        add_action( 'admin_init', [ $this, 'handle_admin_requests' ] );

        add_action( 'wp_enqueue_scripts', function() {
            wp_enqueue_style(
                'ai-pricing-style',
                AI_PRICING_TABLE_URL . 'public/css/pricing-table.css',
                [],
                AI_PRICING_TABLE_VERSION
            );

            wp_enqueue_script(
                'ai-pricing-js',
                AI_PRICING_TABLE_URL . 'public/js/pricing-table.js',
                [],
                AI_PRICING_TABLE_VERSION,
                true
            );
        } );

        add_action( 'admin_enqueue_scripts', function( $hook ) {
            wp_enqueue_style(
                'ai-admin-style',
                AI_PRICING_TABLE_URL . 'admin/css/admin.css',
                [],
                AI_PRICING_TABLE_VERSION
            );

            if ( false === strpos( $hook, 'ai_pricing' ) ) {
                return;
            }

            wp_enqueue_style(
                'ai-pricing-preview-style',
                AI_PRICING_TABLE_URL . 'public/css/pricing-table.css',
                [],
                AI_PRICING_TABLE_VERSION
            );

            wp_enqueue_script(
                'ai-admin-js',
                AI_PRICING_TABLE_URL . 'assets/js/admin-ai.js',
                [ 'jquery' ],
                AI_PRICING_TABLE_VERSION,
                true
            );

            wp_enqueue_script(
                'ai-builder-js',
                AI_PRICING_TABLE_URL . 'assets/js/admin-builder.js',
                [ 'jquery', 'jquery-ui-sortable' ],
                AI_PRICING_TABLE_VERSION,
                true
            );

            wp_localize_script(
                'ai-admin-js',
                'aiPricingAdmin',
                [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'ai_pricing_generate' ),
                ]
            );
        } );
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
     * CPT Registration
     */
    private function register_cpt() {
        add_action( 'init', function () {
            $labels = [
                'name'          => 'Pricing Tables',
                'singular_name' => 'Pricing Table',
            ];

            register_post_type( 'ai_pricing_table', [
                'labels'              => $labels,
                'public'              => false,
                'show_ui'             => false,
                'show_in_menu'        => false,
                'show_in_admin_bar'   => false,
                'show_in_nav_menus'   => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'supports'            => [ 'title' ],
                'has_archive'         => false,
                'rewrite'             => false,
            ] );
        } );
    }

    /**
     * Admin Notice
     */
    public function admin_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( empty( $_GET['page'] ) || false === strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'ai_pricing' ) ) {
            return;
        }

        $message_code = isset( $_GET['ai_notice'] ) ? sanitize_key( wp_unslash( $_GET['ai_notice'] ) ) : '';
        $messages = [
            'saved'    => [ 'success', 'Pricing table saved.' ],
            'deleted'  => [ 'success', 'Pricing table deleted.' ],
            'imported' => [ 'success', 'Pricing tables imported successfully.' ],
            'partial'  => [ 'warning', 'Import completed with some skipped tables.' ],
            'invalid'  => [ 'error', 'Invalid pricing table request.' ],
        ];

        if ( empty( $message_code ) || empty( $messages[ $message_code ] ) ) {
            return;
        }

        [ $type, $message ] = $messages[ $message_code ];

        if ( in_array( $message_code, [ 'imported', 'partial' ], true ) ) {
            $imported = isset( $_GET['imported'] ) ? absint( $_GET['imported'] ) : 0;
            $skipped  = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
            $message .= ' Imported: ' . $imported . '.';

            if ( $skipped > 0 ) {
                $message .= ' Skipped: ' . $skipped . '.';
            }
        }

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr( $type ),
            esc_html( $message )
        );
    }

    /**
     * Admin Menu
     */
    private function register_admin_menu() {
        add_action( 'admin_menu', function () {
            add_menu_page(
                'AI Pricing',
                'AI Pricing',
                'manage_options',
                'ai_pricing_main',
                [ $this, 'render_overview_page' ],
                'dashicons-chart-bar',
                26
            );

            add_submenu_page(
                'ai_pricing_main',
                'Overview',
                'Overview',
                'manage_options',
                'ai_pricing_main',
                [ $this, 'render_overview_page' ]
            );

            add_submenu_page(
                'ai_pricing_main',
                'Add New Table',
                'Add New Table',
                'manage_options',
                'ai_pricing_add_new',
                [ $this, 'render_add_new_page' ]
            );

            add_submenu_page(
                'ai_pricing_main',
                'Show All Tables',
                'Show All Tables',
                'manage_options',
                'ai_pricing_tables',
                [ $this, 'render_tables_page' ]
            );

            add_submenu_page(
                'ai_pricing_main',
                'Settings',
                'Settings',
                'manage_options',
                'ai_pricing_settings',
                [ $this, 'render_settings_page' ]
            );

            add_submenu_page(
                'ai_pricing_main',
                'Import / Export',
                'Import / Export',
                'manage_options',
                'ai_pricing_import_export',
                [ $this, 'render_import_export_page' ]
            );
        } );
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_requests() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['ai_pricing_action'] ) && 'save_table' === sanitize_key( wp_unslash( $_POST['ai_pricing_action'] ) ) ) {
            $this->save_table_from_request();
            return;
        }

        if ( isset( $_POST['ai_pricing_action'] ) && 'export_tables' === sanitize_key( wp_unslash( $_POST['ai_pricing_action'] ) ) ) {
            $this->export_tables_from_request();
            return;
        }

        if ( isset( $_POST['ai_pricing_action'] ) && 'import_tables' === sanitize_key( wp_unslash( $_POST['ai_pricing_action'] ) ) ) {
            $this->import_tables_from_request();
            return;
        }

        if ( isset( $_GET['ai_pricing_action'] ) && 'delete_table' === sanitize_key( wp_unslash( $_GET['ai_pricing_action'] ) ) ) {
            $this->delete_table_from_request();
            return;
        }

        if ( isset( $_GET['ai_pricing_action'] ) && 'duplicate_table' === sanitize_key( wp_unslash( $_GET['ai_pricing_action'] ) ) ) {
            $this->duplicate_table_from_request();
        }
    }

    /**
     * Save table request
     */
    private function save_table_from_request() {
        check_admin_referer( 'ai_pricing_save_table', 'ai_pricing_nonce' );

        $table_id      = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : 0;
        $title         = isset( $_POST['table_title'] ) ? sanitize_text_field( wp_unslash( $_POST['table_title'] ) ) : '';
        $pricing_mode  = isset( $_POST['ai_pricing_mode'] ) ? sanitize_key( wp_unslash( $_POST['ai_pricing_mode'] ) ) : 'ai';
        $business_name = isset( $_POST['ai_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_business_name'] ) ) : '';
        $audience      = isset( $_POST['ai_audience'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_audience'] ) ) : '';
        $features      = isset( $_POST['ai_features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ai_features'] ) ) : '';
        $manual_data   = isset( $_POST['ai_manual_data'] ) ? wp_unslash( $_POST['ai_manual_data'] ) : '';
        $ai_json_raw   = isset( $_POST['ai_pricing_json'] ) ? wp_unslash( $_POST['ai_pricing_json'] ) : '';
        $template      = isset( $_POST['ai_template'] ) ? sanitize_key( wp_unslash( $_POST['ai_template'] ) ) : 'basic_blue';
        $normalized_ai = \ai_pricing_normalize_ai_data( $ai_json_raw );
        $normalized_manual = \ai_pricing_normalize_manual_data( $manual_data );

        if ( ! in_array( $pricing_mode, [ 'ai', 'manual' ], true ) ) {
            $pricing_mode = 'ai';
        }

        if ( 'manual' === $pricing_mode && null === $normalized_manual && null !== $normalized_ai ) {
            $pricing_mode = 'ai';
        } elseif ( 'ai' === $pricing_mode && null === $normalized_ai && null !== $normalized_manual ) {
            $pricing_mode = 'manual';
        }

        if ( empty( $title ) ) {
            $title = ! empty( $business_name ) ? $business_name . ' Pricing' : 'Untitled Pricing Table';
        }

        $post_data = [
            'post_type'   => 'ai_pricing_table',
            'post_title'  => $title,
            'post_status' => 'publish',
        ];

        if ( $table_id > 0 ) {
            $post_data['ID'] = $table_id;
            $saved_id = wp_update_post( $post_data, true );
        } else {
            $saved_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $saved_id ) ) {
            wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_tables', [ 'ai_notice' => 'invalid' ] ) );
            exit;
        }

        update_post_meta( $saved_id, '_ai_business_name', $business_name );
        update_post_meta( $saved_id, '_ai_audience', $audience );
        update_post_meta( $saved_id, '_ai_features', $features );
        update_post_meta( $saved_id, '_ai_template', $template );
        update_post_meta( $saved_id, '_ai_pricing_mode', $pricing_mode );

        if ( null !== $normalized_manual ) {
            update_post_meta( $saved_id, '_ai_pricing_data', wp_json_encode( $normalized_manual ) );
        } else {
            delete_post_meta( $saved_id, '_ai_pricing_data' );
        }

        if ( null !== $normalized_ai ) {
            update_post_meta( $saved_id, '_ai_pricing_json', wp_json_encode( $normalized_ai ) );
        } else {
            delete_post_meta( $saved_id, '_ai_pricing_json' );
        }

        wp_safe_redirect(
            $this->get_admin_page_url(
                'ai_pricing_add_new',
                [
                    'table_id'   => $saved_id,
                    'ai_notice'  => 'saved',
                ]
            )
        );
        exit;
    }

    /**
     * Delete table request
     */
    private function delete_table_from_request() {
        $table_id = isset( $_GET['table_id'] ) ? absint( $_GET['table_id'] ) : 0;
        $nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $table_id < 1 || ! wp_verify_nonce( $nonce, 'ai_pricing_delete_' . $table_id ) ) {
            wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_tables', [ 'ai_notice' => 'invalid' ] ) );
            exit;
        }

        wp_delete_post( $table_id, true );

        wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_tables', [ 'ai_notice' => 'deleted' ] ) );
        exit;
    }

    /**
     * Duplicate table request.
     */
    private function duplicate_table_from_request() {
        $table_id = isset( $_GET['table_id'] ) ? absint( $_GET['table_id'] ) : 0;
        $nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $table_id < 1 || ! wp_verify_nonce( $nonce, 'ai_pricing_duplicate_' . $table_id ) ) {
            wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_tables', [ 'ai_notice' => 'invalid' ] ) );
            exit;
        }

        $original = get_post( $table_id );

        if ( ! $original || 'ai_pricing_table' !== $original->post_type ) {
            wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_tables', [ 'ai_notice' => 'invalid' ] ) );
            exit;
        }

        $new_id = wp_insert_post( [
            'post_type'   => 'ai_pricing_table',
            'post_status' => 'publish',
            'post_title'  => $original->post_title . ' (Copy)',
        ], true );

        if ( is_wp_error( $new_id ) ) {
            wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_tables', [ 'ai_notice' => 'invalid' ] ) );
            exit;
        }

        $meta_keys = [
            '_ai_business_name',
            '_ai_audience',
            '_ai_features',
            '_ai_template',
            '_ai_pricing_mode',
            '_ai_pricing_data',
            '_ai_pricing_json',
        ];

        foreach ( $meta_keys as $meta_key ) {
            $value = get_post_meta( $table_id, $meta_key, true );

            if ( '' === $value || null === $value ) {
                continue;
            }

            update_post_meta( $new_id, $meta_key, $value );
        }

        wp_safe_redirect(
            $this->get_admin_page_url(
                'ai_pricing_add_new',
                [
                    'table_id'  => $new_id,
                    'ai_notice' => 'saved',
                ]
            )
        );
        exit;
    }

    /**
     * Export tables as JSON.
     */
    private function export_tables_from_request() {
        check_admin_referer( 'ai_pricing_export_tables', 'ai_pricing_import_export_nonce' );

        $tables = get_posts( [
            'post_type'      => 'ai_pricing_table',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        $payload = [
            'plugin'         => 'ai-pricing-table',
            'plugin_version' => AI_PRICING_TABLE_VERSION,
            'schema_version' => 2,
            'exported_at'    => gmdate( DATE_ATOM ),
            'site_url'       => home_url( '/' ),
            'tables'         => [],
        ];

        foreach ( $tables as $table ) {
            $payload['tables'][] = $this->prepare_table_export_payload( $table->ID );
        }

        $filename = 'ai-pricing-tables-' . gmdate( 'Y-m-d-His' ) . '.json';

        nocache_headers();
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }

    /**
     * Import tables from JSON.
     */
    private function import_tables_from_request() {
        check_admin_referer( 'ai_pricing_import_tables', 'ai_pricing_import_export_nonce' );

        $raw_import = isset( $_POST['ai_pricing_import_json'] ) ? wp_unslash( $_POST['ai_pricing_import_json'] ) : '';
        $decoded    = json_decode( $raw_import, true );

        if ( ! is_array( $decoded ) || empty( $decoded['tables'] ) || ! is_array( $decoded['tables'] ) ) {
            wp_safe_redirect( $this->get_admin_page_url( 'ai_pricing_import_export', [ 'ai_notice' => 'invalid' ] ) );
            exit;
        }

        $imported = 0;
        $skipped  = 0;

        foreach ( $decoded['tables'] as $table_payload ) {
            if ( ! is_array( $table_payload ) ) {
                $skipped++;
                continue;
            }

            $saved_id = $this->import_single_table( $table_payload );

            if ( $saved_id > 0 ) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        $notice = $skipped > 0 ? 'partial' : 'imported';

        wp_safe_redirect(
            $this->get_admin_page_url(
                'ai_pricing_import_export',
                [
                    'ai_notice' => $notice,
                    'imported'  => $imported,
                    'skipped'   => $skipped,
                ]
            )
        );
        exit;
    }

    /**
     * Build export payload for one table.
     *
     * @param int $table_id Table post ID.
     * @return array
     */
    private function prepare_table_export_payload( $table_id ) {
        $manual_data = get_post_meta( $table_id, '_ai_pricing_data', true );
        $ai_data     = get_post_meta( $table_id, '_ai_pricing_json', true );

        return [
            'title'         => get_the_title( $table_id ),
            'business_name' => get_post_meta( $table_id, '_ai_business_name', true ),
            'audience'      => get_post_meta( $table_id, '_ai_audience', true ),
            'features'      => get_post_meta( $table_id, '_ai_features', true ),
            'template'      => get_post_meta( $table_id, '_ai_template', true ),
            'pricing_mode'  => get_post_meta( $table_id, '_ai_pricing_mode', true ),
            'manual_data'   => \ai_pricing_normalize_manual_data( $manual_data ),
            'ai_data'       => \ai_pricing_normalize_ai_data( $ai_data ),
        ];
    }

    /**
     * Import a single table payload.
     *
     * @param array $table_payload Exported table payload.
     * @return int
     */
    private function import_single_table( $table_payload ) {
        $title         = sanitize_text_field( $table_payload['title'] ?? '' );
        $business_name = sanitize_text_field( $table_payload['business_name'] ?? '' );
        $audience      = sanitize_text_field( $table_payload['audience'] ?? '' );
        $features      = sanitize_textarea_field( $table_payload['features'] ?? '' );
        $template      = sanitize_key( $table_payload['template'] ?? 'basic_blue' );
        $pricing_mode  = sanitize_key( $table_payload['pricing_mode'] ?? 'manual' );
        $manual_data   = \ai_pricing_normalize_manual_data( $table_payload['manual_data'] ?? null );
        $ai_data       = \ai_pricing_normalize_ai_data( $table_payload['ai_data'] ?? null );

        if ( ! in_array( $pricing_mode, [ 'ai', 'manual' ], true ) ) {
            $pricing_mode = null !== $manual_data ? 'manual' : 'ai';
        }

        if ( null === $manual_data && null === $ai_data ) {
            return 0;
        }

        if ( '' === $title ) {
            $title = '' !== $business_name ? $business_name . ' Pricing' : 'Imported Pricing Table';
        }

        $saved_id = wp_insert_post( [
            'post_type'   => 'ai_pricing_table',
            'post_title'  => $title,
            'post_status' => 'publish',
        ], true );

        if ( is_wp_error( $saved_id ) ) {
            return 0;
        }

        update_post_meta( $saved_id, '_ai_business_name', $business_name );
        update_post_meta( $saved_id, '_ai_audience', $audience );
        update_post_meta( $saved_id, '_ai_features', $features );
        update_post_meta( $saved_id, '_ai_template', $template );
        update_post_meta( $saved_id, '_ai_pricing_mode', $pricing_mode );

        if ( null !== $manual_data ) {
            update_post_meta( $saved_id, '_ai_pricing_data', wp_json_encode( $manual_data ) );
        }

        if ( null !== $ai_data ) {
            update_post_meta( $saved_id, '_ai_pricing_json', wp_json_encode( $ai_data ) );
        }

        return (int) $saved_id;
    }

    /**
     * Build plugin page URL
     *
     * @param string $page Page slug.
     * @param array  $args Query args.
     * @return string
     */
    private function get_admin_page_url( $page, $args = [] ) {
        return add_query_arg( $args, admin_url( 'admin.php?page=' . $page ) );
    }

    /**
     * Get current table from request
     *
     * @return \WP_Post|null
     */
    private function get_current_table() {
        $table_id = isset( $_GET['table_id'] ) ? absint( $_GET['table_id'] ) : 0;

        if ( $table_id < 1 ) {
            return null;
        }

        $table = get_post( $table_id );

        if ( ! $table || 'ai_pricing_table' !== $table->post_type ) {
            return null;
        }

        return $table;
    }

    /**
     * Render add or edit page
     */
    public function render_add_new_page() {
        $table = $this->get_current_table();
        include AI_PRICING_TABLE_PATH . 'admin/templates/add-new-table.php';
    }

    /**
     * Render overview page
     */
    public function render_overview_page() {
        include AI_PRICING_TABLE_PATH . 'admin/templates/overview.php';
    }

    /**
     * Render list page
     */
    public function render_tables_page() {
        $tables = get_posts( [
            'post_type'      => 'ai_pricing_table',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        include AI_PRICING_TABLE_PATH . 'admin/templates/tables.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( $this->settings ) {
            $this->settings->settings_page();
            return;
        }

        include AI_PRICING_TABLE_PATH . 'admin/templates/settings.php';
    }

    /**
     * Render import/export page
     */
    public function render_import_export_page() {
        include AI_PRICING_TABLE_PATH . 'admin/templates/import-export.php';
    }
}
