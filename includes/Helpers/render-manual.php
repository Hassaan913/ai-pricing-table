<?php
/**
 * Manual pricing table renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render manual pricing table.
 *
 * @param array  $data Normalized manual data.
 * @param string $template Template key.
 * @return string
 */
function ai_pricing_render_manual_table( $data, $template = 'basic_blue' ) {
    $data = ai_pricing_normalize_manual_data( $data );

    if ( null === $data ) {
        return 'Invalid manual data.';
    }

    $layout      = ai_pricing_get_template_layout( $template );
    $wrapper_css = ai_pricing_get_wrapper_classes( 'manual', $template, $layout );
    $partial     = AI_PRICING_TABLE_PATH . 'includes/Layouts/manual-' . $layout . '.php';

    if ( ! file_exists( $partial ) ) {
        $layout      = 'cards';
        $wrapper_css = ai_pricing_get_wrapper_classes( 'manual', $template, $layout );
        $partial     = AI_PRICING_TABLE_PATH . 'includes/Layouts/manual-cards.php';
    }

    ob_start();
    include $partial;

    return ob_get_clean();
}
