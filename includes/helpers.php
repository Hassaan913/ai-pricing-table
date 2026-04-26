<?php
/**
 * Helper functions for AI Pricing Table plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize manual builder IDs.
 *
 * @param mixed  $value Raw identifier.
 * @param string $prefix Fallback prefix.
 * @param int    $index Fallback index.
 * @return string
 */
function ai_pricing_sanitize_manual_id( $value, $prefix, $index ) {
    $value = sanitize_key( (string) $value );

    if ( '' !== $value ) {
        return $value;
    }

    return sanitize_key( $prefix . '_' . $index );
}

/**
 * Build a stable matrix key for manual tables.
 *
 * @param string $plan_id Plan identifier.
 * @param string $feature_id Feature identifier.
 * @return string
 */
function ai_pricing_manual_matrix_key( $plan_id, $feature_id ) {
    return sanitize_key( $plan_id ) . '::' . sanitize_key( $feature_id );
}

/**
 * Normalize boolean-like values.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function ai_pricing_normalize_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return (bool) intval( $value );
    }

    return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true );
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
        } else {
            $label      = sanitize_text_field( $feature );
            $feature_id = ai_pricing_sanitize_manual_id( '', 'feature', $feature_index + 1 );
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
        } elseif ( preg_match( '/^\d+_\d+$/', (string) $key ) ) {
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

    $recommended = $data['recommended_tier'] ?? '';
    $wrapper_css = ai_pricing_get_wrapper_classes( 'ai', $template );

    ob_start();
    ?>
    <div class="<?php echo esc_attr( $wrapper_css ); ?>" data-billing="monthly">
        <div class="ai-pricing-header">
            <div>
                <p class="ai-pricing-eyebrow">Pricing</p>
                <h2 class="ai-pricing-title">Choose the plan that fits your workflow</h2>
            </div>
            <div class="ai-toggle" role="tablist" aria-label="Billing period">
                <button class="active" data-type="monthly" type="button">Monthly</button>
                <button data-type="yearly" type="button">Yearly</button>
            </div>
        </div>

        <div class="ai-pricing-table">
            <?php foreach ( $data['tiers'] as $tier ) : ?>
                <?php $is_featured = ! empty( $tier['highlight'] ) || ( $tier['name'] === $recommended ); ?>
                <article class="pricing-card <?php echo $is_featured ? 'featured' : ''; ?>">
                    <?php if ( $is_featured ) : ?>
                        <div class="badge">Most Popular</div>
                    <?php endif; ?>

                    <p class="pricing-plan"><?php echo esc_html( $tier['name'] ); ?></p>

                    <div class="price-block">
                        <div class="price">
                            <span class="currency-symbol"><?php echo esc_html( ai_pricing_get_currency_symbol( $data['currency'] ) ); ?></span>
                            <span class="price-value monthly"><?php echo esc_html( $tier['price_monthly'] ); ?></span>
                            <span class="price-value yearly"><?php echo esc_html( $tier['price_yearly'] ); ?></span>
                        </div>
                        <p class="billing-copy">
                            <span class="billing-duration monthly">per month</span>
                            <span class="billing-duration yearly">per year</span>
                            <?php if ( ! empty( $tier['billing_text'] ) ) : ?>
                                <span class="billing-note"><?php echo esc_html( $tier['billing_text'] ); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <ul class="pricing-features">
                        <?php foreach ( $tier['features'] as $feature ) : ?>
                            <li><?php echo esc_html( $feature ); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="<?php echo esc_url( $tier['button_url'] ?: '#' ); ?>" class="btn">
                        <?php echo esc_html( $tier['button_text'] ); ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
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

    $wrapper_css = ai_pricing_get_wrapper_classes( 'manual', $template );

    ob_start();
    ?>
    <div class="<?php echo esc_attr( $wrapper_css ); ?>" data-billing="monthly">
        <div class="ai-pricing-header">
            <div>
                <p class="ai-pricing-eyebrow">Manual Pricing Table</p>
                <h2 class="ai-pricing-title">Structured plans with feature-by-feature control</h2>
            </div>
            <div class="ai-toggle" role="tablist" aria-label="Billing period">
                <button class="active" data-type="monthly" type="button">Monthly</button>
                <button data-type="yearly" type="button">Yearly</button>
            </div>
        </div>

        <div class="ai-pricing-table">
            <?php foreach ( $data['plans'] as $plan_index => $plan ) : ?>
                <?php
                $price_monthly = '' !== $plan['price_monthly'] ? $plan['price_monthly'] : $plan['price_yearly'];
                $price_yearly  = '' !== $plan['price_yearly'] ? $plan['price_yearly'] : $price_monthly;
                ?>
                <article class="pricing-card <?php echo ! empty( $plan['highlight'] ) ? 'featured' : ''; ?>">
                    <?php if ( ! empty( $plan['highlight'] ) ) : ?>
                        <div class="badge">Featured</div>
                    <?php endif; ?>

                    <p class="pricing-plan"><?php echo esc_html( $plan['title'] ); ?></p>
                    <div class="price-block">
                        <div class="price">
                            <span class="price-value monthly"><?php echo esc_html( $price_monthly ); ?></span>
                            <span class="price-value yearly"><?php echo esc_html( $price_yearly ); ?></span>
                        </div>
                        <p class="billing-copy">
                            <span class="billing-duration monthly">per month</span>
                            <span class="billing-duration yearly">per year</span>
                            <?php if ( ! empty( $plan['billing_text'] ) ) : ?>
                                <span class="billing-note"><?php echo esc_html( $plan['billing_text'] ); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <ul class="pricing-features">
                        <?php foreach ( $data['features'] as $feature_index => $feature ) : ?>
                            <?php $key = ai_pricing_manual_matrix_key( $plan['id'], $feature['id'] ); ?>
                            <?php if ( empty( $data['matrix'][ $key ] ) ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <li><?php echo esc_html( $feature['label'] ); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="<?php echo esc_url( $plan['button_url'] ?: '#' ); ?>" class="btn">
                        <?php echo esc_html( $plan['button_text'] ); ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
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
