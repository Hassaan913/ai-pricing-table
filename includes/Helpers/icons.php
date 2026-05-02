<?php
/**
 * Curated Heroicon-style feature icons for manual pricing tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return supported manual feature icons.
 *
 * @return array<string,array<string,string>>
 */
function ai_pricing_get_manual_feature_icons() {
    static $icons = null;

    if ( null !== $icons ) {
        return $icons;
    }

    $icons = [
        'check' => [
            'label' => 'Check',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12.75 10.5 18 19 6.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
        'bolt' => [
            'label' => 'Bolt',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2 4.75 13h5.5L9 22l8.25-11h-5.5L13 2Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
        'rocket-launch' => [
            'label' => 'Rocket Launch',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15.59 4.41a2.25 2.25 0 0 1 3.182 0l.818.818a2.25 2.25 0 0 1 0 3.182l-7.523 7.523a4.5 4.5 0 0 1-1.897 1.13l-3.19.912.912-3.19a4.5 4.5 0 0 1 1.13-1.897l7.523-7.523Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="M12 8.25h.008v.008H12V8.25ZM4.5 19.5l3-3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
        'shield-check' => [
            'label' => 'Shield Check',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.75c2.284 1.53 4.965 2.25 7.5 2.25v5.625c0 4.65-3.021 8.872-7.5 10.125C7.521 20.497 4.5 16.275 4.5 11.625V6c2.535 0 5.216-.72 7.5-2.25Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="m9.75 12 1.5 1.5 3-3.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
        'sparkles' => [
            'label' => 'Sparkles',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9.813 15.904 9 18l-.813-2.096L6 15l2.187-.904L9 12l.813 2.096L12 15l-2.187.904ZM18.259 8.715 18 10l-.259-1.285L16.5 8l1.241-.715L18 6l.259 1.285L19.5 8l-1.241.715ZM6.75 7.5 6 10.5l-.75-3L2.25 6l3-.75.75-3 .75 3 3 .75-3 .75ZM16.5 15.75 15 21l-1.5-5.25L8.25 14.25l5.25-1.5L15 7.5l1.5 5.25 5.25 1.5-5.25 1.5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"/></svg>',
        ],
        'chart-bar' => [
            'label' => 'Chart Bar',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.75 3v18h16.5M7.5 16.5v-6M12 16.5V9m4.5 7.5V6.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
        'users' => [
            'label' => 'Users',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15.75 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4.5 19.125a7.5 7.5 0 0 1 15 0M18.75 9.75a2.25 2.25 0 1 0 0-4.5M20.25 19.125a6.74 6.74 0 0 0-2.015-4.826M5.25 9.75a2.25 2.25 0 1 1 0-4.5M3.75 19.125a6.74 6.74 0 0 1 2.015-4.826" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
        'globe-alt' => [
            'label' => 'Globe',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM3.6 9h16.8M3.6 15h16.8M12 3a15.3 15.3 0 0 1 3.6 9A15.3 15.3 0 0 1 12 21 15.3 15.3 0 0 1 8.4 12 15.3 15.3 0 0 1 12 3Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ],
    ];

    return $icons;
}

/**
 * Sanitize a manual feature icon slug.
 *
 * @param mixed $value Raw icon value.
 * @return string
 */
function ai_pricing_sanitize_manual_icon( $value ) {
    $slug = sanitize_key( (string) $value );

    return isset( ai_pricing_get_manual_feature_icons()[ $slug ] ) ? $slug : '';
}

/**
 * Return feature icon data for JS.
 *
 * @return array<string,array<string,string>>
 */
function ai_pricing_get_manual_feature_icons_for_js() {
    return ai_pricing_get_manual_feature_icons();
}

/**
 * Return the raw SVG for a curated feature icon.
 *
 * @param string $slug Icon slug.
 * @return string
 */
function ai_pricing_get_manual_feature_icon_svg( $slug ) {
    $slug = ai_pricing_sanitize_manual_icon( $slug );

    if ( '' === $slug ) {
        return '';
    }

    return ai_pricing_get_manual_feature_icons()[ $slug ]['svg'];
}
