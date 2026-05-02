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
            <?php foreach ( $data['plans'] as $plan ) : ?>
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
                        <?php foreach ( $data['features'] as $feature ) : ?>
                            <?php $key = ai_pricing_manual_matrix_key( $plan['id'], $feature['id'] ); ?>
                            <?php if ( empty( $data['matrix'][ $key ] ) ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php $icon_svg = ai_pricing_get_manual_feature_icon_svg( $feature['icon'] ?? '' ); ?>
                            <li class="<?php echo '' !== $icon_svg ? 'has-icon' : ''; ?>">
                                <?php if ( '' !== $icon_svg ) : ?>
                                    <span class="ai-feature-icon" aria-hidden="true"><?php echo $icon_svg; ?></span>
                                <?php endif; ?>
                                <span class="ai-feature-label"><?php echo esc_html( $feature['label'] ); ?></span>
                            </li>
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
