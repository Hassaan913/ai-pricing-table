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
