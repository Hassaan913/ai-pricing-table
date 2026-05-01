<?php
/**
 * Helper functions for AI Pricing Table plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/Helpers/sanitize.php';
require_once __DIR__ . '/Helpers/normalize-ai.php';
require_once __DIR__ . '/Helpers/normalize-manual.php';
require_once __DIR__ . '/Helpers/presentation.php';
require_once __DIR__ . '/Helpers/render-ai.php';
require_once __DIR__ . '/Helpers/render-manual.php';

/**
 * Basic Pro entitlement check.
 *
 * This plugin doesn't ship with a licensing system yet, so we provide a simple
 * mechanism that can be controlled via:
 * - a constant: define('AI_PRICING_TABLE_PRO', true);
 * - an option: update_option('ai_pricing_pro_enabled', '1');
 * - a filter: add_filter('ai_pricing_table_is_pro', fn() => true);
 *
 * @return bool
 */
function ai_pricing_table_is_pro() {
    $via_constant = defined( 'AI_PRICING_TABLE_PRO' ) && AI_PRICING_TABLE_PRO;
    $via_option   = '1' === (string) get_option( 'ai_pricing_pro_enabled', '0' );

    /**
     * Filter: allow theme/Pro addon to enable Pro.
     *
     * @param bool $is_pro Whether Pro is enabled.
     */
    return (bool) apply_filters( 'ai_pricing_table_is_pro', ( $via_constant || $via_option ) );
}
