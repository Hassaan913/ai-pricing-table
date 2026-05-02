(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createRenderer(opts) {
        const store = opts.store;
        const $builder = opts.$builder;
        const planManager = opts.planManager;
        const featureManager = opts.featureManager;
        const matrixManager = opts.matrixManager;
        const persistence = opts.persistence;

        const escapeHtml = api.utils.escapeHtml;
        const featureIcons = window.aiPricingManualIcons || {};

        function getFeatureIconOptions(selectedIcon) {
            let html = '<option value="">No icon</option>';

            Object.keys(featureIcons).forEach(function (slug) {
                const icon = featureIcons[slug] || {};
                const selected = slug === selectedIcon ? ' selected' : '';
                html += '<option value="' + escapeHtml(slug) + '"' + selected + '>' + escapeHtml(icon.label || slug) + '</option>';
            });

            return html;
        }

        function getFeatureIconSvg(iconSlug) {
            if (!iconSlug || !featureIcons[iconSlug] || !featureIcons[iconSlug].svg) {
                return "";
            }

            return featureIcons[iconSlug].svg;
        }

        function getStatusMarkup() {
            const validation = persistence.getValidation();
            const dirty = persistence.isDirty();
            const enabledCells = matrixManager.countEnabledCells();
            let badgeClass = "is-saved";
            let badgeText = "Saved";
            let message = "Manual builder is in sync with the saved version.";

            if (!validation.isValid) {
                badgeClass = "is-invalid";
                badgeText = "Needs Attention";
                message = validation.errors.join(" ");
            } else if (dirty) {
                badgeClass = "is-dirty";
                badgeText = "Unsaved Changes";
                message = "You have manual-builder changes that are not saved yet.";
            } else if (validation.warnings.length) {
                badgeClass = "is-warning";
                badgeText = "Review";
                message = validation.warnings.join(" ");
            }

            return `
            <div class="ai-builder-status">
                <div class="ai-builder-status-main">
                    <span class="ai-builder-status-badge ${badgeClass}">${badgeText}</span>
                    <div class="ai-builder-status-copy">
                        <strong>${escapeHtml(message)}</strong>
                        <p>${store.state.plans.length} plans, ${store.state.features.length} features, ${enabledCells} enabled cells</p>
                    </div>
                </div>
<!--                <div class="ai-builder-quick-actions">-->
<!--                    <button type="button" class="button" id="ai-preview-add-plan">+ Plan</button>-->
<!--                    <button type="button" class="button" id="ai-preview-add-feature">+ Feature</button>-->
<!--                    <button type="button" class="button" id="ai-enable-all-features">Enable All</button>-->
<!--                    <button type="button" class="button" id="ai-clear-all-features">Clear All</button>-->
<!--                </div>-->
            </div>
        `;
        }

        function renderPlans() {
            if (!store.state.plans.length) {
                $("#plans-list").html('<p class="description">No plans yet. Add your first plan to start building the table.</p>');
                return;
            }

            let html = "";

            store.state.plans.forEach(function (plan) {
                html += `
                <div class="plan-item" data-id="${escapeHtml(plan.id)}">
                    <div class="plan-item-main">
                        <button type="button" class="button-link ai-drag-handle" aria-label="Drag plan">
                            <span class="dashicons dashicons-move"></span>
                        </button>
                        <input type="text" value="${escapeHtml(plan.title)}" data-id="${escapeHtml(plan.id)}" data-field="title" class="plan-field plan-field-title" placeholder="Plan name">
                        <input type="text" value="${escapeHtml(plan.price_monthly)}" data-id="${escapeHtml(plan.id)}" data-field="price_monthly" class="plan-field" placeholder="$29 / month">
                        <input type="text" value="${escapeHtml(plan.price_yearly)}" data-id="${escapeHtml(plan.id)}" data-field="price_yearly" class="plan-field" placeholder="$290 / year">
                    </div>
                    <div class="plan-item-advanced">
                        <input type="text" value="${escapeHtml(plan.billing_text)}" data-id="${escapeHtml(plan.id)}" data-field="billing_text" class="plan-field" placeholder="Billed annually">
                        <input type="text" value="${escapeHtml(plan.button_text)}" data-id="${escapeHtml(plan.id)}" data-field="button_text" class="plan-field" placeholder="Get Started">
                        <input type="url" value="${escapeHtml(plan.button_url)}" data-id="${escapeHtml(plan.id)}" data-field="button_url" class="plan-field" placeholder="https://example.com/signup">
                        <label class="ai-inline-check">
                            <input type="checkbox" class="plan-highlight" data-id="${escapeHtml(plan.id)}" ${plan.highlight ? "checked" : ""}>
                            <span>Featured</span>
                        </label>
                        <button type="button" class="button duplicate-plan" data-id="${escapeHtml(plan.id)}">Duplicate</button>
                        <button type="button" class="button remove-plan" data-id="${escapeHtml(plan.id)}">Remove</button>
                    </div>
                </div>
            `;
            });

            $("#plans-list").html(html);
        }

        function renderFeatures() {
            if (!store.state.features.length) {
                $("#features-list").html('<p class="description">No features yet. Add a feature row to build the comparison matrix.</p>');
                return;
            }

            let html = "";

            store.state.features.forEach(function (feature) {
                const currentIcon = String(feature.icon || "");
                const currentIconLabel = currentIcon && featureIcons[currentIcon] ? featureIcons[currentIcon].label : "No icon";
                html += `
                <div class="feature-item" data-id="${escapeHtml(feature.id)}">
                    <button type="button" class="button-link ai-drag-handle" aria-label="Drag feature">
                        <span class="dashicons dashicons-move"></span>
                    </button>
                    <input type="text" value="${escapeHtml(feature.label)}" data-id="${escapeHtml(feature.id)}" class="feature-title" placeholder="Feature">
                    <label class="ai-feature-icon-select-wrap">
                        <span class="screen-reader-text">Feature icon</span>
                        <select class="feature-icon" data-id="${escapeHtml(feature.id)}" aria-label="Feature icon">
                            ${getFeatureIconOptions(currentIcon)}
                        </select>
                    </label>
                    <span class="ai-feature-icon-label">${escapeHtml(currentIconLabel)}</span>
                    <button type="button" class="button duplicate-feature" data-id="${escapeHtml(feature.id)}">Duplicate</button>
                    <button type="button" class="button remove-feature" data-id="${escapeHtml(feature.id)}">Remove</button>
                </div>
            `;
            });

            $("#features-list").html(html);
        }

        function renderMatrix() {
            if (!store.state.plans.length || !store.state.features.length) {
                $("#matrix").html('<p class="description">Add at least one plan and one feature to map what each plan includes. Use the bulk actions to fill whole rows or columns quickly.</p>');
                return;
            }

            let html = "<table class='widefat striped ai-matrix-table'><thead><tr><th>Feature</th>";

            store.state.plans.forEach(function (plan) {
                html += `
                <th>
                    <div class="ai-matrix-head">
                        <strong>${escapeHtml(plan.title || "Plan")}</strong>
                        <div class="ai-matrix-bulk-actions">
                            <button type="button" class="button-link ai-bulk-plan" data-plan-id="${escapeHtml(plan.id)}" data-state="1">All</button>
                            <button type="button" class="button-link ai-bulk-plan" data-plan-id="${escapeHtml(plan.id)}" data-state="0">None</button>
                        </div>
                    </div>
                </th>
            `;
            });

            html += "</tr></thead><tbody>";

            store.state.features.forEach(function (feature) {
                html += `
                <tr>
                    <td>
                        <div class="ai-matrix-row-head">
                            <strong>${escapeHtml(feature.label || "Feature")}</strong>
                            <div class="ai-matrix-bulk-actions">
                                <button type="button" class="button-link ai-bulk-feature" data-feature-id="${escapeHtml(feature.id)}" data-state="1">All</button>
                                <button type="button" class="button-link ai-bulk-feature" data-feature-id="${escapeHtml(feature.id)}" data-state="0">None</button>
                            </div>
                        </div>
                    </td>
            `;

                store.state.plans.forEach(function (plan) {
                    const checked = matrixManager.hasMatrixValue(plan.id, feature.id) ? "checked" : "";

                    html += `
                    <td>
                        <label class="ai-matrix-check">
                            <input
                                type="checkbox"
                                class="matrix-check"
                                data-plan-id="${escapeHtml(plan.id)}"
                                data-feature-id="${escapeHtml(feature.id)}"
                                ${checked}
                            >
                            <span>Included</span>
                        </label>
                    </td>
                `;
                });

                html += "</tr>";
            });

            html += "</tbody></table>";

            $("#matrix").html(html);
        }

        function renderPreview() {
            const $preview = $("#ai-manual-preview");

            if (!$preview.length) {
                return;
            }

            if (!store.state.plans.length) {
                $preview.html('<div class="ai-preview-empty-state"><p>Add plans and features to see a live manual-table preview.</p></div>');
                return;
            }

            const layout = store.getTemplateLayout();

            function renderFeatureList(enabledFeatures) {
                if (!enabledFeatures.length) {
                    return '<li class="ai-preview-empty-feature">No enabled features yet</li>';
                }

                return enabledFeatures.map(function (feature) {
                    const iconSvg = getFeatureIconSvg(feature.icon);
                    return `
                        <li class="${iconSvg ? "has-icon" : ""}">
                            ${iconSvg ? `<span class="ai-feature-icon" aria-hidden="true">${iconSvg}</span>` : ""}
                            <span
                                class="ai-preview-editable"
                                contenteditable="true"
                                spellcheck="false"
                                data-preview-field="feature_label"
                                data-feature-id="${escapeHtml(feature.id)}"
                            >${escapeHtml(feature.label || "Feature")}</span>
                        </li>
                    `;
                }).join("");
            }

            function renderFeatureControls(plan) {
                let html = `
                    <div class="ai-preview-card-controls">
                        <p>Quick feature toggles</p>
                        <div class="ai-preview-feature-switches">
                `;

                store.state.features.forEach(function (feature) {
                    const enabled = matrixManager.hasMatrixValue(plan.id, feature.id);

                    html += `
                        <div class="ai-preview-feature-switch ${enabled ? "is-enabled" : ""}">
                            <button
                                type="button"
                                class="ai-preview-feature-toggle"
                                data-plan-id="${escapeHtml(plan.id)}"
                                data-feature-id="${escapeHtml(feature.id)}"
                                aria-pressed="${enabled ? "true" : "false"}"
                            >${enabled ? "On" : "Off"}</button>
                            <span
                                class="ai-preview-editable ai-preview-feature-control-label"
                                contenteditable="true"
                                spellcheck="false"
                                data-preview-field="feature_label"
                                data-feature-id="${escapeHtml(feature.id)}"
                            >${escapeHtml(feature.label || "Feature")}</span>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;

                return html;
            }

            function renderPlanBody(plan, enabledFeatures, monthlyPrice, yearlyPrice) {
                return `
                    <ul class="pricing-features">
                        ${renderFeatureList(enabledFeatures)}
                    </ul>
                    <a href="${escapeHtml(plan.button_url || "#")}" class="btn ai-preview-button-link" target="_blank" rel="noopener noreferrer">
                        <span
                            class="ai-preview-editable"
                            contenteditable="true"
                            spellcheck="false"
                            data-preview-field="button_text"
                            data-plan-id="${escapeHtml(plan.id)}"
                        >${escapeHtml(plan.button_text || "Get Started")}</span>
                    </a>
                    <div class="ai-preview-url-row">
                        <label>
                            <span>CTA URL</span>
                            <input type="url" class="plan-url-inline" data-id="${escapeHtml(plan.id)}" value="${escapeHtml(plan.button_url || "#")}" placeholder="https://example.com/signup">
                        </label>
                    </div>
                    ${renderFeatureControls(plan)}
                    <div class="ai-preview-footer">Quick actions here update the builder, preview, and saved manual JSON together.</div>
                `;
            }

            function renderPlanArticle(plan) {
                const enabledFeatures = store.state.features.filter(function (feature) {
                    return matrixManager.hasMatrixValue(plan.id, feature.id);
                });
                const monthlyPrice = plan.price_monthly || plan.price_yearly || "$0";
                const yearlyPrice = plan.price_yearly || plan.price_monthly || "$0";
                const cardActions = `
                    <div class="ai-preview-card-actions">
                        <button type="button" class="button-link ai-preview-card-action toggle-featured" data-id="${escapeHtml(plan.id)}">${plan.highlight ? "Unfeature" : "Feature"}</button>
                        <button type="button" class="button-link ai-preview-card-action duplicate-plan" data-id="${escapeHtml(plan.id)}">Duplicate</button>
                        <button type="button" class="button-link ai-preview-card-action remove-plan" data-id="${escapeHtml(plan.id)}">Remove</button>
                    </div>
                `;
                const priceMarkup = `
                    <div class="price-block">
                        <div class="price">
                            <span
                                class="price-value monthly ai-preview-editable"
                                contenteditable="true"
                                spellcheck="false"
                                data-preview-field="price_monthly"
                                data-plan-id="${escapeHtml(plan.id)}"
                            >${escapeHtml(monthlyPrice)}</span>
                            <span
                                class="price-value yearly ai-preview-editable"
                                contenteditable="true"
                                spellcheck="false"
                                data-preview-field="price_yearly"
                                data-plan-id="${escapeHtml(plan.id)}"
                            >${escapeHtml(yearlyPrice)}</span>
                        </div>
                        <p class="billing-copy">
                            <span class="billing-duration monthly">per month</span>
                            <span class="billing-duration yearly">per year</span>
                            <span
                                class="billing-note ai-preview-editable"
                                contenteditable="true"
                                spellcheck="false"
                                data-preview-field="billing_text"
                                data-plan-id="${escapeHtml(plan.id)}"
                            >${escapeHtml(plan.billing_text || "Add billing note")}</span>
                        </p>
                    </div>
                `;

                if (layout === "rows") {
                    return `
                        <article class="pricing-row ai-preview-card ${plan.highlight ? "featured" : ""}">
                            <div class="pricing-row-main">
                                <div class="pricing-row-meta">
                                    <p class="pricing-plan">
                                        <span
                                            class="ai-preview-editable"
                                            contenteditable="true"
                                            spellcheck="false"
                                            data-preview-field="title"
                                            data-plan-id="${escapeHtml(plan.id)}"
                                        >${escapeHtml(plan.title || "Plan")}</span>
                                    </p>
                                    ${cardActions}
                                </div>
                                ${priceMarkup}
                            </div>
                            ${renderPlanBody(plan, enabledFeatures, monthlyPrice, yearlyPrice)}
                        </article>
                    `;
                }

                if (layout === "spotlight") {
                    return `
                        <article class="pricing-card ai-preview-card ${plan.highlight ? "featured spotlight-card" : ""}">
                            ${plan.highlight ? '<div class="badge">Featured</div>' : ""}
                            <div class="pricing-card-head">
                                <div>
                                    <p class="pricing-plan">
                                        <span
                                            class="ai-preview-editable"
                                            contenteditable="true"
                                            spellcheck="false"
                                            data-preview-field="title"
                                            data-plan-id="${escapeHtml(plan.id)}"
                                        >${escapeHtml(plan.title || "Plan")}</span>
                                    </p>
                                    ${cardActions}
                                </div>
                                ${priceMarkup}
                            </div>
                            ${renderPlanBody(plan, enabledFeatures, monthlyPrice, yearlyPrice)}
                        </article>
                    `;
                }

                return `
                    <article class="pricing-card ai-preview-card ${plan.highlight ? "featured" : ""}">
                        ${plan.highlight ? '<div class="badge">Featured</div>' : ""}
                        <div class="ai-preview-card-head">
                            <p class="pricing-plan">
                                <span
                                    class="ai-preview-editable"
                                    contenteditable="true"
                                    spellcheck="false"
                                    data-preview-field="title"
                                    data-plan-id="${escapeHtml(plan.id)}"
                                >${escapeHtml(plan.title || "Plan")}</span>
                            </p>
                            ${cardActions}
                        </div>
                        ${priceMarkup}
                        ${renderPlanBody(plan, enabledFeatures, monthlyPrice, yearlyPrice)}
                    </article>
                `;
            }

            let html = `
            <div class="ai-manual-preview-shell">
                <div class="ai-pricing-wrapper ai-pricing-mode-manual ${escapeHtml(store.getTemplateClass())} ${escapeHtml(store.getLayoutClass())} ai-manual-preview-wrapper" data-billing="${escapeHtml(store.state.previewBilling)}">
            `;

            if (layout === "spotlight") {
                html += `
                    <div class="ai-pricing-shell">
                        <div class="ai-pricing-intro">
                            <p class="ai-pricing-eyebrow">Manual Preview</p>
                            <h2 class="ai-pricing-title">Lead with the highlighted plan and compare the rest below</h2>
                            <p class="ai-pricing-summary">This preview mirrors the spotlight frontend layout for the selected template family.</p>
                            <div class="ai-toggle" role="tablist" aria-label="Billing period">
                                <button type="button" class="ai-preview-billing-toggle ${store.state.previewBilling === "monthly" ? "active" : ""}" data-type="monthly">Monthly</button>
                                <button type="button" class="ai-preview-billing-toggle ${store.state.previewBilling === "yearly" ? "active" : ""}" data-type="yearly">Yearly</button>
                            </div>
                        </div>
                        <div class="ai-pricing-table">
                `;
            } else if (layout === "rows") {
                html += `
                    <div class="ai-pricing-header">
                        <div>
                            <p class="ai-pricing-eyebrow">Manual Preview</p>
                            <h2 class="ai-pricing-title">Compact row preview with inline editing and quick controls</h2>
                        </div>
                        <div class="ai-toggle" role="tablist" aria-label="Billing period">
                            <button type="button" class="ai-preview-billing-toggle ${store.state.previewBilling === "monthly" ? "active" : ""}" data-type="monthly">Monthly</button>
                            <button type="button" class="ai-preview-billing-toggle ${store.state.previewBilling === "yearly" ? "active" : ""}" data-type="yearly">Yearly</button>
                        </div>
                    </div>
                    <div class="ai-pricing-rows">
                `;
            } else {
                html += `
                    <div class="ai-pricing-header">
                        <div>
                            <p class="ai-pricing-eyebrow">Manual Preview</p>
                            <h2 class="ai-pricing-title">Edit content inline and toggle features directly from preview</h2>
                        </div>
                        <div class="ai-toggle" role="tablist" aria-label="Billing period">
                            <button type="button" class="ai-preview-billing-toggle ${store.state.previewBilling === "monthly" ? "active" : ""}" data-type="monthly">Monthly</button>
                            <button type="button" class="ai-preview-billing-toggle ${store.state.previewBilling === "yearly" ? "active" : ""}" data-type="yearly">Yearly</button>
                        </div>
                    </div>
                    <div class="ai-pricing-table">
                `;
            }

            store.state.plans.forEach(function (plan) {
                html += renderPlanArticle(plan);
            });

            html += `
                    </div>
                </div>
            </div>
        `;

            $preview.html(html);
        }

        function initSortables() {
            if (typeof $.fn.sortable !== "function") {
                return;
            }

            const sortableOptions = {
                handle: ".ai-drag-handle",
                placeholder: "ai-sort-placeholder",
                forcePlaceholderSize: true
            };

            $("#plans-list").sortable($.extend({}, sortableOptions, {
                items: ".plan-item",
                update: function () {
                    store.state.plans = api.utils.reorderCollection(store.state.plans, api.utils.getOrderedIds("#plans-list"));
                    render();
                }
            }));

            $("#features-list").sortable($.extend({}, sortableOptions, {
                items: ".feature-item",
                update: function () {
                    store.state.features = api.utils.reorderCollection(store.state.features, api.utils.getOrderedIds("#features-list"));
                    render();
                }
            }));
        }

        function render() {
            $builder.html(`
            <div class="ai-builder-layout">
                <div class="ai-builder">
                    ${getStatusMarkup()}
                    <div class="ai-builder-section">
                        <h3>Plans</h3>
                        <div id="plans-list" class="ai-sortable-list"></div>
                        <button type="button" id="add-plan" class="button">+ Add Plan</button>
                    </div>

                    <div class="ai-builder-section">
                        <h3>Features</h3>
                        <p class="description">Drag to reorder features or duplicate a row when multiple plans share a similar feature.</p>
                        <div id="features-list" class="ai-sortable-list"></div>
                        <button type="button" id="add-feature" class="button">+ Add Feature</button>
                    </div>

                    <div class="ai-builder-section">
                        <h3>Matrix</h3>
                        <div id="matrix"></div>
                    </div>
                </div>

                <div class="ai-builder-preview-panel">
                    <div class="ai-builder-preview-head">
                        <h3>Live Preview</h3>
                        <p>Drag in the builder, toggle features here, edit text inline, and switch monthly/yearly preview states.</p>
                    </div>
                    <div id="ai-manual-preview"></div>
                </div>
            </div>
        `);

            renderPlans();
            renderFeatures();
            renderMatrix();
            renderPreview();
            initSortables();
            persistence.saveData();
        }

        return {
            render: render,
            renderPreview: renderPreview
        };
    }

    api.rendering = api.rendering || {};
    api.rendering.create = createRenderer;
})(window, jQuery);

