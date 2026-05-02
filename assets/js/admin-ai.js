jQuery(function ($) {
    const $button = $("#ai-generate-btn");
    const $result = $("#ai-pricing-result");
    const $jsonField = $("#ai_pricing_json");
    const $modeField = $("input[name='ai_pricing_mode'][value='ai']");
    const $preview = $("#ai-ai-preview");
    const isPro = !!(window.aiPricingAdmin && window.aiPricingAdmin.isPro);
    const templateRegistry = window.aiPricingTemplates || {};
    let currentAi = null;

    if (!$button.length) {
        return;
    }

    function escapeHtml(value) {
        return $("<div>").text(value == null ? "" : String(value)).html();
    }

    function currencySymbol(code) {
        const symbols = {
            USD: "$",
            EUR: "EUR ",
            GBP: "GBP ",
            PKR: "PKR "
        };
        const key = String(code || "USD").toUpperCase();
        return symbols[key] || (key ? key + " " : "$");
    }

    function normalizeAiData(raw) {
        let parsed = raw;
        if (!parsed) return null;

        if (typeof parsed === "string") {
            try {
                parsed = JSON.parse(parsed);
            } catch (e) {
                return null;
            }
        }

        if (!parsed || typeof parsed !== "object" || !Array.isArray(parsed.tiers) || !parsed.tiers.length) {
            return null;
        }

        const tiers = parsed.tiers
            .filter(function (tier) { return tier && typeof tier === "object" && tier.name; })
            .map(function (tier) {
                return {
                    name: String(tier.name || ""),
                    price_monthly: String(tier.price_monthly == null ? "" : tier.price_monthly),
                    price_yearly: String(tier.price_yearly == null ? "" : tier.price_yearly),
                    billing_text: String(tier.billing_text || ""),
                    highlight: !!tier.highlight,
                    features: Array.isArray(tier.features) ? tier.features.map(function (f) { return String(f || ""); }).filter(Boolean) : [],
                    button_text: String(tier.button_text || "Get Started"),
                    button_url: String(tier.button_url || "#")
                };
            });

        if (!tiers.length) return null;

        return {
            tiers: tiers,
            recommended_tier: String(parsed.recommended_tier || ""),
            currency: String(parsed.currency || "USD")
        };
    }

    function persistCurrentAi() {
        if (!currentAi) return;
        try {
            $jsonField.val(JSON.stringify(currentAi));
            $modeField.prop("checked", true);
        } catch (e) {
            // ignore
        }
    }

    function updateAiValue(payload) {
        if (!currentAi || !payload) return;
        const tierIndex = typeof payload.tierIndex === "number" ? payload.tierIndex : null;
        if (tierIndex == null || !currentAi.tiers || !currentAi.tiers[tierIndex]) return;

        const tier = currentAi.tiers[tierIndex];
        const field = payload.field;
        const value = String(payload.value == null ? "" : payload.value);

        if (field === "name") tier.name = value;
        if (field === "price_monthly") tier.price_monthly = value;
        if (field === "price_yearly") tier.price_yearly = value;
        if (field === "billing_text") tier.billing_text = value;
        if (field === "button_text") tier.button_text = value;
        if (field === "button_url") tier.button_url = value || "#";

        if (field === "feature") {
            const featureIndex = typeof payload.featureIndex === "number" ? payload.featureIndex : null;
            if (featureIndex == null) return;
            if (!Array.isArray(tier.features)) tier.features = [];
            tier.features[featureIndex] = value;
            tier.features = tier.features.map(function (f) { return String(f || ""); }).filter(Boolean);
        }
    }

    function getTemplateKey() {
        return $("input[name='ai_template']:checked").val() || "basic_blue";
    }

    function getTemplateLayout(templateKey) {
        return templateRegistry[templateKey] && templateRegistry[templateKey].layout
            ? templateRegistry[templateKey].layout
            : "cards";
    }

    function renderAiPreview(aiData) {
        if (!$preview.length) {
            return;
        }

        const normalized = normalizeAiData(aiData);
        currentAi = normalized;
        if (!normalized) {
            $preview.html('<div class="ai-preview-empty-state"><p>No AI pricing data yet. Click “Generate Pricing With AI” to build a preview.</p></div>');
            return;
        }

        const templateKey = getTemplateKey();
        const layout = getTemplateLayout(templateKey);
        const recommended = normalized.recommended_tier || "";
        const symbol = currencySymbol(normalized.currency);
        const editableClass = isPro ? "ai-ai-preview-editable" : "";
        const editableAttr = isPro ? 'contenteditable="true" spellcheck="false"' : "";

        function renderFeatureItems(tier, tierIndex) {
            return (tier.features || []).map(function (feature, featureIndex) {
                return `<li><span class="${editableClass}" ${editableAttr} data-ai-field="feature" data-ai-tier-index="${tierIndex}" data-ai-feature-index="${featureIndex}">${escapeHtml(feature)}</span></li>`;
            }).join("");
        }

        function renderPriceMarkup(tier, tierIndex) {
            return `
                <div class="price-block">
                    <div class="price">
                        <span class="currency-symbol">${escapeHtml(symbol)}</span>
                        <span class="price-value monthly ${editableClass}" ${editableAttr} data-ai-field="price_monthly" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.price_monthly)}</span>
                        <span class="price-value yearly ${editableClass}" ${editableAttr} data-ai-field="price_yearly" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.price_yearly)}</span>
                    </div>
                    <p class="billing-copy">
                        <span class="billing-duration monthly">per month</span>
                        <span class="billing-duration yearly">per year</span>
                        <span class="billing-note ${editableClass}" ${editableAttr} data-ai-field="billing_text" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.billing_text || "Add billing note")}</span>
                    </p>
                </div>
            `;
        }

        function renderPlanFooter(tier, tierIndex) {
            return `
                <a href="${escapeHtml(tier.button_url || "#")}" class="btn" target="_blank" rel="noopener noreferrer">
                    <span class="${editableClass}" ${editableAttr} data-ai-field="button_text" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.button_text || "Get Started")}</span>
                </a>
                ${isPro ? `
                    <div class="ai-preview-url-row">
                        <label>
                            <span>CTA URL</span>
                            <input type="url" class="ai-plan-url-inline" data-ai-tier-index="${tierIndex}" value="${escapeHtml(tier.button_url || "#")}" placeholder="https://example.com/signup">
                        </label>
                    </div>
                ` : ""}
            `;
        }

        function renderTier(tier, tierIndex) {
            const isFeatured = tier.highlight || (tier.name === recommended);

            if (layout === "rows") {
                return `
                    <article class="pricing-row ${isFeatured ? "featured" : ""}">
                        <div class="pricing-row-main">
                            <div class="pricing-row-meta">
                                <p class="pricing-plan">
                                    <span class="${editableClass}" ${editableAttr} data-ai-field="name" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.name)}</span>
                                </p>
                            </div>
                            ${renderPriceMarkup(tier, tierIndex)}
                        </div>
                        <ul class="pricing-features">
                            ${renderFeatureItems(tier, tierIndex)}
                        </ul>
                        ${renderPlanFooter(tier, tierIndex)}
                    </article>
                `;
            }

            if (layout === "spotlight") {
                return `
                    <article class="pricing-card ${isFeatured ? "featured spotlight-card" : ""}">
                        ${isFeatured ? '<div class="badge">Recommended</div>' : ""}
                        <div class="pricing-card-head">
                            <div>
                                <p class="pricing-plan">
                                    <span class="${editableClass}" ${editableAttr} data-ai-field="name" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.name)}</span>
                                </p>
                            </div>
                            ${renderPriceMarkup(tier, tierIndex)}
                        </div>
                        <ul class="pricing-features">
                            ${renderFeatureItems(tier, tierIndex)}
                        </ul>
                        ${renderPlanFooter(tier, tierIndex)}
                    </article>
                `;
            }

            return `
                <article class="pricing-card ${isFeatured ? "featured" : ""}">
                    ${isFeatured ? '<div class="badge">Most Popular</div>' : ""}
                    <p class="pricing-plan">
                        <span class="${editableClass}" ${editableAttr} data-ai-field="name" data-ai-tier-index="${tierIndex}">${escapeHtml(tier.name)}</span>
                    </p>
                    ${renderPriceMarkup(tier, tierIndex)}
                    <ul class="pricing-features">
                        ${renderFeatureItems(tier, tierIndex)}
                    </ul>
                    ${renderPlanFooter(tier, tierIndex)}
                </article>
            `;
        }

        let html = `<div class="ai-pricing-wrapper ai-pricing-mode-ai ai-pricing-template-${escapeHtml(templateKey)} ai-pricing-layout-${escapeHtml(layout)}" data-billing="monthly">`;

        if (layout === "spotlight") {
            html += `
                <div class="ai-pricing-shell">
                    <div class="ai-pricing-intro">
                        <p class="ai-pricing-eyebrow">AI Preview</p>
                        <h2 class="ai-pricing-title">${isPro ? "Preview the spotlight layout and edit inline" : "Preview the spotlight layout"}</h2>
                        <p class="ai-pricing-summary">This preview mirrors the selected spotlight template family instead of the default card stack.</p>
                        <div class="ai-toggle" role="tablist" aria-label="Billing period">
                            <button class="active" data-type="monthly" type="button">Monthly</button>
                            <button data-type="yearly" type="button">Yearly</button>
                        </div>
                    </div>
                    <div class="ai-pricing-table">
            `;
        } else if (layout === "rows") {
            html += `
                <div class="ai-pricing-header">
                    <div>
                        <p class="ai-pricing-eyebrow">AI Preview</p>
                        <h2 class="ai-pricing-title">${isPro ? "Preview the row layout and edit inline" : "Preview the row layout"}</h2>
                    </div>
                    <div class="ai-toggle" role="tablist" aria-label="Billing period">
                        <button class="active" data-type="monthly" type="button">Monthly</button>
                        <button data-type="yearly" type="button">Yearly</button>
                    </div>
                </div>
                <div class="ai-pricing-rows">
            `;
        } else {
            html += `
                <div class="ai-pricing-header">
                    <div>
                        <p class="ai-pricing-eyebrow">AI Preview</p>
                        <h2 class="ai-pricing-title">${isPro ? "Edit AI content inline (Pro)" : "Preview AI output (Pro to edit inline)"}</h2>
                    </div>
                    <div class="ai-toggle" role="tablist" aria-label="Billing period">
                        <button class="active" data-type="monthly" type="button">Monthly</button>
                        <button data-type="yearly" type="button">Yearly</button>
                    </div>
                </div>
                <div class="ai-pricing-table">
            `;
        }

        normalized.tiers.forEach(function (tier, tierIndex) {
            html += renderTier(tier, tierIndex);
        });

        html += `
                </div>
                ${!isPro ? `
                    <div class="ai-pro-lock-overlay" role="note" aria-label="Pro feature locked">
                        <div class="ai-pro-lock-overlay-card">
                            <strong>Pro feature</strong>
                            <p>Inline editing for the AI preview is available for Pro users only.</p>
                        </div>
                    </div>
                ` : ""}
            </div>
        `;

        $preview.html(html);
    }

    function renderFromJsonField() {
        // Use AJAX preview instead of JavaScript rendering
        if (typeof updateAiPreview === 'function') {
            updateAiPreview();
        }
    }

    $button.on("click", function () {
        const business = String($("#ai_business_name").val() || "").trim();
        const audience = String($("#ai_audience").val() || "").trim();
        const features = String($("#ai_features").val() || "").trim();

        if (!business && !audience && !features) {
            $result.html(
                '<div class="notice notice-warning inline"><p>Please fill at least one field (Business Name, Target Audience, or Main Features) before generating.</p></div>'
            );
            return;
        }

        $button.prop("disabled", true).text("Generating...");
        $result.html("<p>Generating pricing table...</p>");

        $.ajax({
            url: aiPricingAdmin.ajaxUrl,
            type: "POST",
            dataType: "json",
            data: {
                action: "ai_generate_pricing",
                nonce: aiPricingAdmin.nonce,
                business: business,
                audience: audience,
                features: features
            }
        }).done(function (response) {
            if (!response || !response.success) {
                const message = response && response.data && response.data.message
                    ? response.data.message
                    : "AI generation failed.";
                $result.html('<div class="notice notice-error inline"><p>' + message + "</p></div>");
                return;
            }

            $jsonField.val(response.data.json);
            $modeField.prop("checked", true);
            $result.html("<pre>" + JSON.stringify(response.data.pricing, null, 2) + "</pre>");
            // Use AJAX preview instead of JavaScript rendering
            if (typeof updateAiPreview === 'function') {
                updateAiPreview();
            }
        }).fail(function () {
            $result.html('<div class="notice notice-error inline"><p>Request failed.</p></div>');
        }).always(function () {
            $button.prop("disabled", false).text("Generate Pricing With AI");
        });
    });

    $(document).on("click", "#ai-ai-preview .ai-toggle button", function () {
        const $wrapper = $(this).closest(".ai-pricing-wrapper");
        const type = $(this).data("type") === "yearly" ? "yearly" : "monthly";
        $wrapper.attr("data-billing", type);
        $(this).siblings("button").removeClass("active");
        $(this).addClass("active");
    });

    $(document).on("keydown", "#ai-ai-preview .ai-ai-preview-editable", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            $(this).blur();
        }
    });

    $(document).on("input", "#ai-ai-preview .ai-ai-preview-editable", function () {
        const $field = $(this);
        const tierIndex = parseInt($field.data("ai-tier-index"), 10);
        const featureIndexRaw = $field.data("ai-feature-index");
        const featureIndex = featureIndexRaw === undefined ? null : parseInt(featureIndexRaw, 10);
        const field = String($field.data("ai-field") || "");
        const value = $field.text().replace(/\s+/g, " ").trim();

        updateAiValue({
            tierIndex: isNaN(tierIndex) ? null : tierIndex,
            featureIndex: featureIndex == null || isNaN(featureIndex) ? null : featureIndex,
            field: field,
            value: value
        });
        persistCurrentAi();
    });

    $(document).on("blur", "#ai-ai-preview .ai-ai-preview-editable", function () {
        // Normalize empty fields to safe defaults and re-render.
        const $field = $(this);
        const tierIndex = parseInt($field.data("ai-tier-index"), 10);
        const featureIndexRaw = $field.data("ai-feature-index");
        const featureIndex = featureIndexRaw === undefined ? null : parseInt(featureIndexRaw, 10);
        const field = String($field.data("ai-field") || "");
        const fallbackMap = {
            name: "Plan",
            price_monthly: "0",
            price_yearly: "0",
            billing_text: "Add billing note",
            feature: "Feature",
            button_text: "Get Started"
        };
        const value = $field.text().replace(/\s+/g, " ").trim() || (fallbackMap[field] || "");

        updateAiValue({
            tierIndex: isNaN(tierIndex) ? null : tierIndex,
            featureIndex: featureIndex == null || isNaN(featureIndex) ? null : featureIndex,
            field: field,
            value: value
        });
        persistCurrentAi();
        renderAiPreview(currentAi);
    });

    $(document).on("change", "#ai-ai-preview .ai-plan-url-inline", function () {
        const tierIndex = parseInt($(this).data("ai-tier-index"), 10);
        const url = String($(this).val() || "").trim() || "#";
        updateAiValue({
            tierIndex: isNaN(tierIndex) ? null : tierIndex,
            field: "button_url",
            value: url
        });
        persistCurrentAi();
        renderAiPreview(currentAi);
    });

    $(document).on("change", "input[name='ai_template']", function () {
        renderFromJsonField();
    });

    // Initial render (edit screen: show saved AI JSON preview)
    renderFromJsonField();
});
