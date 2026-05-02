<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="<?php echo esc_attr( $wrapper_css ); ?>" data-billing="monthly">
    <div class="ai-pricing-header">
        <div>
            <p class="ai-pricing-eyebrow">Pricing</p>
            <h2 class="ai-pricing-title">A compact pricing breakdown for faster comparison</h2>
        </div>
        <div class="ai-toggle" role="tablist" aria-label="Billing period">
            <button class="active" data-type="monthly" type="button">Monthly</button>
            <button data-type="yearly" type="button">Yearly</button>
        </div>
    </div>

    <div class="ai-pricing-rows">
        <?php foreach ( $data['tiers'] as $tier ) : ?>
            <?php $is_featured = ! empty( $tier['highlight'] ) || ( $tier['name'] === $recommended ); ?>
            <article class="pricing-row <?php echo $is_featured ? 'featured' : ''; ?>">
                <div class="pricing-row-main">
                    <div class="pricing-row-meta">
                        <p class="pricing-plan"><?php echo esc_html( $tier['name'] ); ?></p>
                        <?php if ( ! empty( $tier['billing_text'] ) ) : ?>
                            <p class="pricing-card-note"><?php echo esc_html( $tier['billing_text'] ); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="price-block">
                        <div class="price">
                            <span class="currency-symbol"><?php echo esc_html( ai_pricing_get_currency_symbol( $data['currency'] ) ); ?></span>
                            <span class="price-value monthly"><?php echo esc_html( $tier['price_monthly'] ); ?></span>
                            <span class="price-value yearly"><?php echo esc_html( $tier['price_yearly'] ); ?></span>
                        </div>
                        <p class="billing-copy">
                            <span class="billing-duration monthly">per month</span>
                            <span class="billing-duration yearly">per year</span>
                        </p>
                    </div>
                </div>

                <ul class="pricing-features">
                    <?php foreach ( $tier['features'] as $feature ) : ?>
                        <li><span class="ai-feature-label"><?php echo esc_html( $feature ); ?></span></li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?php echo esc_url( $tier['button_url'] ?: '#' ); ?>" class="btn">
                    <?php echo esc_html( $tier['button_text'] ); ?>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
</div>
