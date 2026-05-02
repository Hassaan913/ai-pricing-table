<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="<?php echo esc_attr( $wrapper_css ); ?>" data-billing="monthly">
    <div class="ai-pricing-shell">
        <div class="ai-pricing-intro">
            <p class="ai-pricing-eyebrow">Pricing</p>
            <h2 class="ai-pricing-title">Find the plan built for your current stage</h2>
            <p class="ai-pricing-summary">Compare the recommended tier first, then review the rest of the stack side by side.</p>
            <div class="ai-toggle" role="tablist" aria-label="Billing period">
                <button class="active" data-type="monthly" type="button">Monthly</button>
                <button data-type="yearly" type="button">Yearly</button>
            </div>
        </div>

        <div class="ai-pricing-table">
            <?php foreach ( $data['tiers'] as $tier ) : ?>
                <?php $is_featured = ! empty( $tier['highlight'] ) || ( $tier['name'] === $recommended ); ?>
                <article class="pricing-card <?php echo $is_featured ? 'featured spotlight-card' : ''; ?>">
                    <?php if ( $is_featured ) : ?>
                        <div class="badge">Recommended</div>
                    <?php endif; ?>

                    <div class="pricing-card-head">
                        <div>
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
</div>
