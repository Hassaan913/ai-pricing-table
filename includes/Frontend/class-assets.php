<?php
namespace AI_Pricing_Table\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Assets {

    private bool $should_enqueue_public_assets = false;

    public function hooks() {
        add_action( 'wp', [ $this, 'detect_public_asset_need' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function detect_public_asset_need() {
        if ( is_admin() ) {
            return;
        }

        global $wp_query;

        foreach ( (array) ( $wp_query->posts ?? [] ) as $post ) {
            if ( $post instanceof \WP_Post && has_shortcode( (string) $post->post_content, 'ai_pricing_table' ) ) {
                $this->should_enqueue_public_assets = true;
                break;
            }
        }
    }

    public function enqueue_public_assets() {
        if ( ! $this->should_enqueue_public_assets ) {
            return;
        }

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
    }

    public function enqueue_admin_assets( $hook ) {
        wp_enqueue_style(
            'ai-admin-style',
            AI_PRICING_TABLE_URL . 'admin/css/admin.css',
            [],
            AI_PRICING_TABLE_VERSION
        );

        if ( false === strpos( (string) $hook, 'ai_pricing' ) ) {
            return;
        }

        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

        if ( 'ai_pricing_add_new' !== $current_page ) {
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
            'ai-builder-ns',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/namespace.js',
            [ 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-utils',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/utils.js',
            [ 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-state',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/state.js',
            [ 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-plans-manager',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/managers/plans.js',
            [ 'ai-builder-state', 'ai-builder-utils', 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-features-manager',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/managers/features.js',
            [ 'ai-builder-state', 'ai-builder-utils', 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-matrix-manager',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/managers/matrix.js',
            [ 'ai-builder-state', 'ai-builder-utils', 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-operations-manager',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/managers/operations.js',
            [
                'ai-builder-plans-manager',
                'ai-builder-features-manager',
                'ai-builder-matrix-manager',
                'ai-builder-state',
                'ai-builder-utils',
                'ai-builder-ns',
                'jquery',
            ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-persistence',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/persistence.js',
            [
                'ai-builder-operations-manager',
                'ai-builder-plans-manager',
                'ai-builder-features-manager',
                'ai-builder-matrix-manager',
                'ai-builder-state',
                'ai-builder-utils',
                'ai-builder-ns',
                'jquery',
            ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-rendering',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/rendering.js',
            [ 'ai-builder-persistence', 'ai-builder-utils', 'ai-builder-ns', 'jquery', 'jquery-ui-sortable' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-events',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/events.js',
            [ 'ai-builder-rendering', 'ai-builder-persistence', 'ai-builder-utils', 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-index',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder/index.js',
            [ 'ai-builder-events', 'ai-builder-rendering', 'ai-builder-persistence', 'ai-builder-utils', 'ai-builder-state', 'ai-builder-ns', 'jquery' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-builder-js',
            AI_PRICING_TABLE_URL . 'assets/js/admin-builder.js',
            [ 'jquery', 'jquery-ui-sortable', 'ai-builder-index' ],
            AI_PRICING_TABLE_VERSION,
            true
        );

        wp_localize_script(
            'ai-admin-js',
            'aiPricingAdmin',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ai_pricing_generate' ),
                'isPro'   => function_exists( 'ai_pricing_table_is_pro' ) ? (bool) ai_pricing_table_is_pro() : false,
            ]
        );
    }
}

