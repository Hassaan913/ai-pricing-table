<?php
/**
 * Normalize AI pricing payloads into a safe, predictable shape.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize AI pricing data.
 *
 * @param mixed $data Raw AI payload.
 * @return array|null
 */
function ai_pricing_normalize_ai_data( $data ) {
    if ( is_string( $data ) ) {
        $data = json_decode( $data, true );
    }

    if ( ! is_array( $data ) || empty( $data['tiers'] ) || ! is_array( $data['tiers'] ) ) {
        return null;
    }

    $normalized = [
        'tiers'            => [],
        'recommended_tier' => sanitize_text_field( $data['recommended_tier'] ?? '' ),
        'currency'         => sanitize_text_field( $data['currency'] ?? 'USD' ),
    ];

    foreach ( $data['tiers'] as $tier ) {
        if ( ! is_array( $tier ) ) {
            continue;
        }

        $features = [];

        foreach ( $tier['features'] ?? [] as $feature ) {
            $feature = sanitize_text_field( $feature );

            if ( '' === $feature ) {
                continue;
            }

            $features[] = $feature;
        }

        $name = sanitize_text_field( $tier['name'] ?? '' );

        if ( '' === $name ) {
            continue;
        }

        $normalized['tiers'][] = [
            'name'          => $name,
            'price_monthly' => is_numeric( $tier['price_monthly'] ?? null ) ? (string) $tier['price_monthly'] : sanitize_text_field( $tier['price_monthly'] ?? '' ),
            'price_yearly'  => is_numeric( $tier['price_yearly'] ?? null ) ? (string) $tier['price_yearly'] : sanitize_text_field( $tier['price_yearly'] ?? '' ),
            'billing_text'  => sanitize_text_field( $tier['billing_text'] ?? '' ),
            'highlight'     => ! empty( $tier['highlight'] ),
            'features'      => array_values( $features ),
            'button_text'   => sanitize_text_field( $tier['button_text'] ?? 'Get Started' ),
            'button_url'    => esc_url_raw( $tier['button_url'] ?? '#' ),
        ];
    }

    if ( empty( $normalized['tiers'] ) ) {
        return null;
    }

    return $normalized;
}
