<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="<?php echo esc_attr( $wrapper_css ); ?>" data-billing="monthly">
    <div class="ai-pricing-shell">
        <div class="ai-pricing-intro">
            <p class="ai-pricing-eyebrow">Manual Pricing Table</p>
            <h2 class="ai-pricing-title">Lead with your recommended plan, then compare the rest</h2>
            <p class="ai-pricing-summary">This layout gives your highlighted plan more visual weight while keeping every tier fully visible.</p>
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
                $is_featured   = ! empty( $plan['highlight'] );
                ?>
                <article class="pricing-card <?php echo $is_featured ? 'featured spotlight-card' : ''; ?>">
                    <?php if ( $is_featured ) : ?>
                        <div class="badge">Featured</div>
                    <?php endif; ?>

                    <div class="pricing-card-head">
                        <div>
                            <p class="pricing-plan"><?php echo esc_html( $plan['title'] ); ?></p>
                            <?php if ( ! empty( $plan['billing_text'] ) ) : ?>
                                <p class="pricing-card-note"><?php echo esc_html( $plan['billing_text'] ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="price-block">
                            <div class="price">
                                <span class="price-value monthly"><?php echo esc_html( $price_monthly ); ?></span>
                                <span class="price-value yearly"><?php echo esc_html( $price_yearly ); ?></span>
                            </div>
                            <p class="billing-copy">
                                <span class="billing-duration monthly">per month</span>
                                <span class="billing-duration yearly">per year</span>
                            </p>
                        </div>
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
</div>
