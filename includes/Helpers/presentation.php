<?php
/**
 * Presentation helpers shared by renderers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render pricing table wrapper classes.
 *
 * @param string $mode Pricing mode.
 * @param string $template Template key.
 * @return string
 */
function ai_pricing_get_wrapper_classes( $mode, $template ) {
    $classes = [
        'ai-pricing-wrapper',
        'ai-pricing-mode-' . sanitize_html_class( $mode ),
        'ai-pricing-template-' . sanitize_html_class( $template ),
    ];

    return implode( ' ', $classes );
}

/**
 * Currency symbol helper.
 *
 * @param string $currency Currency code.
 * @return string
 */
function ai_pricing_get_currency_symbol( $currency ) {
    $symbols = [
        'USD' => '$',
        'EUR' => 'EUR ',
        'GBP' => 'GBP ',
        'PKR' => 'PKR ',
    ];

    $currency = strtoupper( sanitize_text_field( $currency ) );

    return $symbols[ $currency ] ?? ( $currency ? $currency . ' ' : '$' );
}
