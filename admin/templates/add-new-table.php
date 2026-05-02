<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$table_id       = $table ? $table->ID : 0;
$table_title    = $table ? $table->post_title : '';
$business_name  = $table ? get_post_meta( $table_id, '_ai_business_name', true ) : '';
$audience       = $table ? get_post_meta( $table_id, '_ai_audience', true ) : '';
$features       = $table ? get_post_meta( $table_id, '_ai_features', true ) : '';
$manual_data    = $table ? get_post_meta( $table_id, '_ai_pricing_data', true ) : '';
$ai_json        = $table ? get_post_meta( $table_id, '_ai_pricing_json', true ) : '';
$selected_tpl   = $table ? get_post_meta( $table_id, '_ai_template', true ) : 'basic_blue';
$pricing_mode   = $table ? get_post_meta( $table_id, '_ai_pricing_mode', true ) : 'ai';
$is_editing     = $table_id > 0;
$is_pro         = function_exists( 'ai_pricing_table_is_pro' ) ? (bool) ai_pricing_table_is_pro() : false;
$selected_tpl   = \AI_Pricing_Table\Templates::sanitize_template_key( $selected_tpl, $is_pro );
$free_templates = \AI_Pricing_Table\Templates::get_free_templates();
$pro_templates  = \AI_Pricing_Table\Templates::get_pro_templates();

if ( ! in_array( $pricing_mode, [ 'ai', 'manual' ], true ) ) {
    $pricing_mode = ! empty( $manual_data ) ? 'manual' : 'ai';
}

ob_start();
?>
<h1><?php echo esc_html( $is_editing ? 'Edit Table' : 'Add New Table' ); ?></h1>
<p>Create and save pricing tables without opening the default WordPress post editor. This v1 ships as shortcode-only.</p>

<form method="post" action="">
    <?php wp_nonce_field( 'ai_pricing_save_table', 'ai_pricing_nonce' ); ?>
    <input type="hidden" name="ai_pricing_action" value="save_table" />
    <input type="hidden" name="table_id" value="<?php echo esc_attr( $table_id ); ?>" />
    <input type="hidden" name="ai_pricing_json" id="ai_pricing_json" value="<?php echo esc_attr( $ai_json ); ?>" />

    <div class="ai-admin-card">
        <h2>Table Details</h2>
        <?php if ( $is_editing ) : ?>
            <p><strong>Embed shortcode:</strong> <code>[ai_pricing_table id="<?php echo esc_html( (string) $table_id ); ?>"]</code></p>
        <?php else : ?>
            <p><strong>Embed shortcode:</strong> Save this table first, then copy the generated shortcode into any page, post, or widget.</p>
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="table_title">Table Title</label></th>
                <td><input type="text" id="table_title" name="table_title" class="regular-text" value="<?php echo esc_attr( $table_title ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row">Pricing Mode</th>
                <td>
                    <fieldset class="ai-mode-switch">
                        <label>
                            <input type="radio" name="ai_pricing_mode" value="ai" <?php checked( $pricing_mode, 'ai' ); ?> />
                            AI Generated
                        </label>
                        <label>
                            <input type="radio" name="ai_pricing_mode" value="manual" <?php checked( $pricing_mode, 'manual' ); ?> />
                            Manual Builder
                        </label>
                    </fieldset>
                    <p class="description">Choose which saved dataset the frontend should render.</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="ai-pricing-mode-panel ai-pricing-mode-panel-ai" data-mode-panel="ai">
        <div class="ai-admin-card">
            <h2>AI Inputs</h2>
            <p>These inputs are used for AI generation and saved with the pricing table.</p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ai_business_name">Business Name</label></th>
                    <td><input type="text" id="ai_business_name" name="ai_business_name" class="regular-text" value="<?php echo esc_attr( $business_name ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ai_audience">Target Audience</label></th>
                    <td><input type="text" id="ai_audience" name="ai_audience" class="regular-text" value="<?php echo esc_attr( $audience ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ai_features">Main Features</label></th>
                    <td><textarea id="ai_features" name="ai_features" rows="4" class="large-text"><?php echo esc_textarea( $features ); ?></textarea></td>
                </tr>
            </table>
        </div>

        <div class="ai-admin-card">
            <h2>AI Generator</h2>
            <p>Generate a structured pricing table from your business details. Using Generate will switch the mode to AI.</p>
            <p>
                <button type="button" id="ai-generate-btn" class="button button-primary">Generate Pricing With AI</button>
            </p>
            <div id="ai-pricing-result" class="ai-result-box"></div>
        </div>

        <div class="ai-admin-card">
            <h2>Live Preview (AI)</h2>
            <p>
                This preview updates after AI generation and when you switch templates.
                <?php if ( ! $is_pro ) : ?>
                    <br><strong>Pro:</strong> Inline editing inside the AI preview is locked for Pro users.
                <?php endif; ?>
            </p>
            <div id="ai-ai-preview"></div>
        </div>
    </div>

    <div class="ai-pricing-mode-panel ai-pricing-mode-panel-manual" data-mode-panel="manual">
        <div class="ai-admin-card">
            <h2>Manual Builder</h2>
            <p>Use this if you want full control over plans and included features. Editing manual data will switch the mode to Manual Builder.</p>
            <div id="ai-manual-builder"></div>
            <input type="hidden" name="ai_manual_data" id="ai_manual_data" value="<?php echo esc_attr( $manual_data ); ?>" />
        </div>
    </div>

    <div class="ai-admin-card">
        <h2>Template</h2>
        <p>The selected template controls the visual skin applied by the shared shortcode renderer. Free and Pro availability is managed from the template registry.</p>

        <div class="ai-template-section">
            <div class="ai-template-section-head">
                <h3>Free Templates</h3>
                <span class="ai-template-section-meta"><?php echo esc_html( count( $free_templates ) ); ?> included</span>
            </div>
            <div class="ai-template-grid">
                <?php foreach ( $free_templates as $template_key => $template ) : ?>
                    <label class="ai-template-option">
                        <input type="radio" name="ai_template" value="<?php echo esc_attr( $template_key ); ?>" <?php checked( $selected_tpl, $template_key ); ?> />
                        <span
                            class="ai-template-card"
                            style="
                                --ai-template-preview-bg: <?php echo esc_attr( $template['preview']['bg'] ); ?>;
                                --ai-template-preview-surface: <?php echo esc_attr( $template['preview']['surface'] ); ?>;
                                --ai-template-preview-accent: <?php echo esc_attr( $template['preview']['accent'] ); ?>;
                                --ai-template-preview-text: <?php echo esc_attr( $template['preview']['text'] ); ?>;
                            "
                        >
                            <span class="ai-template-preview" aria-hidden="true">
                                <span class="ai-template-preview-top"></span>
                                <span class="ai-template-preview-cards">
                                    <span></span>
                                    <span class="is-featured"></span>
                                    <span></span>
                                </span>
                            </span>
                            <span class="ai-template-copy">
                                <span class="ai-template-title-row">
                                    <span class="ai-template-name"><?php echo esc_html( $template['name'] ); ?></span>
                                    <span class="ai-template-badge is-free">Free</span>
                                </span>
                                <span class="ai-template-description"><?php echo esc_html( $template['description'] ); ?></span>
                            </span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ai-template-section">
            <div class="ai-template-section-head">
                <h3>Pro Templates</h3>
                <span class="ai-template-section-meta"><?php echo esc_html( count( $pro_templates ) ); ?> premium skins</span>
            </div>
            <div class="ai-template-grid">
                <?php foreach ( $pro_templates as $template_key => $template ) : ?>
                    <label class="ai-template-option is-pro">
                        <input type="radio" name="ai_template" value="<?php echo esc_attr( $template_key ); ?>" <?php checked( $selected_tpl, $template_key ); ?> <?php disabled( ! $is_pro ); ?> />
                        <span
                            class="ai-template-card"
                            style="
                                --ai-template-preview-bg: <?php echo esc_attr( $template['preview']['bg'] ); ?>;
                                --ai-template-preview-surface: <?php echo esc_attr( $template['preview']['surface'] ); ?>;
                                --ai-template-preview-accent: <?php echo esc_attr( $template['preview']['accent'] ); ?>;
                                --ai-template-preview-text: <?php echo esc_attr( $template['preview']['text'] ); ?>;
                            "
                        >
                            <span class="ai-template-preview" aria-hidden="true">
                                <span class="ai-template-preview-top"></span>
                                <span class="ai-template-preview-cards">
                                    <span></span>
                                    <span class="is-featured"></span>
                                    <span></span>
                                </span>
                            </span>
                            <span class="ai-template-copy">
                                <span class="ai-template-title-row">
                                    <span class="ai-template-name"><?php echo esc_html( $template['name'] ); ?></span>
                                    <span class="ai-template-badge">Pro</span>
                                </span>
                                <span class="ai-template-description"><?php echo esc_html( $template['description'] ); ?></span>
                            </span>
                            <?php if ( ! $is_pro ) : ?>
                                <span class="ai-template-lock">Upgrade to unlock</span>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <p>
        <?php submit_button( $is_editing ? 'Update Table' : 'Save Table', 'primary', 'submit', false ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ai_pricing_tables' ) ); ?>" class="button">Back to All Tables</a>
    </p>
</form>

<script>
window.aiPricingExistingData = {
    manual: <?php echo wp_json_encode( $manual_data ); ?>,
    ai: <?php echo wp_json_encode( $ai_json ); ?>,
    mode: <?php echo wp_json_encode( $pricing_mode ); ?>
};
window.aiPricingManualIcons = <?php echo wp_json_encode( ai_pricing_get_manual_feature_icons_for_js() ); ?>;
</script>

<script>
(function () {
    function getMode() {
        var checked = document.querySelector("input[name='ai_pricing_mode']:checked");
        return checked && checked.value ? checked.value : "ai";
    }

    function applyMode(nextMode) {
        var mode = nextMode || getMode();
        var panels = document.querySelectorAll("[data-mode-panel]");
        for (var i = 0; i < panels.length; i++) {
            var panel = panels[i];
            var panelMode = panel.getAttribute("data-mode-panel");
            var isActive = panelMode === mode;
            panel.classList.toggle("is-hidden", !isActive);
        }
    }

    document.addEventListener("change", function (event) {
        var target = event.target;
        if (!target || target.name !== "ai_pricing_mode") return;
        applyMode(target.value);
    });

    // Apply on load using saved mode.
    applyMode(getMode());

    // Keep in sync with other scripts that programmatically toggle modes.
    document.addEventListener("click", function (event) {
        var target = event.target;
        if (!target) return;
        if (target.id === "ai-generate-btn") {
            window.setTimeout(function () { applyMode("ai"); }, 0);
        }
    });

    document.addEventListener("input", function (event) {
        var builder = event.target && event.target.closest ? event.target.closest("#ai-manual-builder") : null;
        if (!builder) return;
        window.setTimeout(function () { applyMode("manual"); }, 0);
    });
})();
</script>
<?php
$content = ob_get_clean();

include __DIR__ . '/layout.php';
