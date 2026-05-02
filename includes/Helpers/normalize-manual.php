<?php
/**
 * Normalize manual-builder payloads into a stable schema.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize manual pricing data.
 *
 * @param mixed $data Raw manual payload.
 * @return array|null
 */
function ai_pricing_normalize_manual_data( $data ) {
    if ( is_string( $data ) ) {
        $data = json_decode( $data, true );
    }

    if ( ! is_array( $data ) || empty( $data['plans'] ) || empty( $data['features'] ) ) {
        return null;
    }

    $plans = [];
    $features = [];
    $matrix = [];
    $plan_ids_by_index = [];
    $feature_ids_by_index = [];
    $known_plan_ids = [];
    $known_feature_ids = [];

    foreach ( $data['plans'] as $plan_index => $plan ) {
        if ( is_array( $plan ) ) {
            $title         = sanitize_text_field( $plan['title'] ?? $plan['name'] ?? '' );
            $legacy_price  = sanitize_text_field( $plan['price'] ?? '' );
            $price_monthly = sanitize_text_field( $plan['price_monthly'] ?? $legacy_price );
            $price_yearly  = sanitize_text_field( $plan['price_yearly'] ?? $legacy_price );
            $billing_text  = sanitize_text_field( $plan['billing_text'] ?? '' );
            $highlight     = ai_pricing_normalize_bool( $plan['highlight'] ?? false );
            $button_text   = sanitize_text_field( $plan['button_text'] ?? 'Get Started' );
            $button_url    = esc_url_raw( $plan['button_url'] ?? '#' );
            $plan_id       = ai_pricing_sanitize_manual_id( $plan['id'] ?? '', 'plan', $plan_index + 1 );
        } else {
            $title         = sanitize_text_field( $plan );
            $price_monthly = '';
            $price_yearly  = '';
            $billing_text  = '';
            $highlight     = false;
            $button_text   = 'Get Started';
            $button_url    = '#';
            $plan_id       = ai_pricing_sanitize_manual_id( '', 'plan', $plan_index + 1 );
        }

        if ( '' === $title && '' === $price_monthly && '' === $price_yearly ) {
            continue;
        }

        $plan_suffix = count( $plans ) + 1;

        while ( isset( $known_plan_ids[ $plan_id ] ) ) {
            $plan_suffix++;
            $plan_id = ai_pricing_sanitize_manual_id( '', 'plan', $plan_suffix );
        }

        $plans[] = [
            'id'            => $plan_id,
            'title'         => '' !== $title ? $title : 'Plan',
            'price_monthly' => $price_monthly,
            'price_yearly'  => $price_yearly,
            'billing_text'  => $billing_text,
            'highlight'     => $highlight,
            'button_text'   => '' !== $button_text ? $button_text : 'Get Started',
            'button_url'    => '' !== $button_url ? $button_url : '#',
        ];

        $plan_ids_by_index[ (string) $plan_index ] = $plan_id;
        $known_plan_ids[ $plan_id ] = true;
    }

    foreach ( $data['features'] as $feature_index => $feature ) {
        if ( is_array( $feature ) ) {
            $label      = sanitize_text_field( $feature['label'] ?? $feature['title'] ?? $feature['name'] ?? '' );
            $feature_id = ai_pricing_sanitize_manual_id( $feature['id'] ?? '', 'feature', $feature_index + 1 );
            $icon       = ai_pricing_sanitize_manual_icon( $feature['icon'] ?? '' );
        } else {
            $label      = sanitize_text_field( $feature );
            $feature_id = ai_pricing_sanitize_manual_id( '', 'feature', $feature_index + 1 );
            $icon       = '';
        }

        if ( '' === $label ) {
            continue;
        }

        $feature_suffix = count( $features ) + 1;

        while ( isset( $known_feature_ids[ $feature_id ] ) ) {
            $feature_suffix++;
            $feature_id = ai_pricing_sanitize_manual_id( '', 'feature', $feature_suffix );
        }

        $features[] = [
            'id'    => $feature_id,
            'label' => $label,
            'icon'  => $icon,
        ];

        $feature_ids_by_index[ (string) $feature_index ] = $feature_id;
        $known_feature_ids[ $feature_id ] = true;
    }

    if ( empty( $plans ) || empty( $features ) ) {
        return null;
    }

    foreach ( (array) ( $data['matrix'] ?? [] ) as $key => $enabled ) {
        if ( ! ai_pricing_normalize_bool( $enabled ) ) {
            continue;
        }

        $plan_id = '';
        $feature_id = '';

        if ( false !== strpos( (string) $key, '::' ) ) {
            [ $plan_candidate, $feature_candidate ] = array_pad( explode( '::', (string) $key, 2 ), 2, '' );

            $plan_candidate = sanitize_key( $plan_candidate );
            $feature_candidate = sanitize_key( $feature_candidate );

            if ( isset( $known_plan_ids[ $plan_candidate ] ) && isset( $known_feature_ids[ $feature_candidate ] ) ) {
                $plan_id = $plan_candidate;
                $feature_id = $feature_candidate;
            }
        } elseif ( preg_match( '/^\\d+_\\d+$/', (string) $key ) ) {
            [ $plan_index, $feature_index ] = explode( '_', (string) $key );
            $plan_id = $plan_ids_by_index[ $plan_index ] ?? '';
            $feature_id = $feature_ids_by_index[ $feature_index ] ?? '';
        }

        if ( '' === $plan_id || '' === $feature_id ) {
            continue;
        }

        $matrix[ ai_pricing_manual_matrix_key( $plan_id, $feature_id ) ] = true;
    }

    return [
        'mode'     => 'manual',
        'plans'    => array_values( $plans ),
        'features' => array_values( $features ),
        'matrix'   => $matrix,
    ];
}
