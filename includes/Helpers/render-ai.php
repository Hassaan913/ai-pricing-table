<?php
/**
 * AI pricing table renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render AI-generated pricing table.
 *
 * @param array  $data Normalized AI data.
 * @param string $template Template key.
 * @return string
 */
function ai_pricing_render_ai_table( $data, $template = 'basic_blue' ) {
    $data = ai_pricing_normalize_ai_data( $data );

    if ( null === $data ) {
        return 'No pricing data found.';
    }

    $layout      = ai_pricing_get_template_layout( $template );
    $recommended = $data['recommended_tier'] ?? '';
    $wrapper_css = ai_pricing_get_wrapper_classes( 'ai', $template, $layout );
    $partial     = AI_PRICING_TABLE_PATH . 'includes/Layouts/ai-' . $layout . '.php';

    if ( ! file_exists( $partial ) ) {
        $layout      = 'cards';
        $wrapper_css = ai_pricing_get_wrapper_classes( 'ai', $template, $layout );
        $partial     = AI_PRICING_TABLE_PATH . 'includes/Layouts/ai-cards.php';
    }

    ob_start();
    include $partial;

    return ob_get_clean();
}
