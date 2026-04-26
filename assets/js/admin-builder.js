jQuery(function ($) {
    const $builder = $("#ai-manual-builder");
    const $dataField = $("#ai_manual_data");
    const $modeField = $("input[name='ai_pricing_mode'][value='manual']");
    const $form = $builder.closest("form");
    const previewTemplate = $("input[name='ai_template']:checked").val() || "basic_blue";
    const state = {
        plans: [],
        features: [],
        matrix: {},
        previewBilling: "monthly"
    };
    let idCounter = 0;
    let initialSerialized = "";
    let isSubmitting = false;

    if (!$builder.length || !$dataField.length) {
        return;
    }

    function nextId(prefix) {
        idCounter += 1;
        return prefix + "_" + Date.now().toString(36) + "_" + idCounter;
    }

    function normalizeId(value, prefix) {
        const normalized = String(value || "")
            .toLowerCase()
            .replace(/[^a-z0-9_-]+/g, "_")
            .replace(/^_+|_+$/g, "");

        return normalized || nextId(prefix);
    }

    function escapeHtml(value) {
        return $("<div>").text(value || "").html();
    }

    function buildMatrixKey(planId, featureId) {
        return planId + "::" + featureId;
    }

    function isEnabled(value) {
        if (typeof value === "boolean") {
            return value;
        }

        if (typeof value === "number") {
            return value === 1;
        }

        return ["1", "true", "yes", "on"].indexOf(String(value).toLowerCase()) !== -1;
    }

    function getPlanById(planId) {
        return state.plans.find(function (plan) {
            return plan.id === planId;
        });
    }

    function getFeatureById(featureId) {
        return state.features.find(function (feature) {
            return feature.id === featureId;
        });
    }

    function getPlanIndex(planId) {
        return state.plans.findIndex(function (plan) {
            return plan.id === planId;
        });
    }

    function getFeatureIndex(featureId) {
        return state.features.findIndex(function (feature) {
            return feature.id === featureId;
        });
    }

    function hasMatrixValue(planId, featureId) {
        return !!state.matrix[buildMatrixKey(planId, featureId)];
    }

    function setMatrixValue(planId, featureId, enabled) {
        const key = buildMatrixKey(planId, featureId);

        if (enabled) {
            state.matrix[key] = true;
        } else {
            delete state.matrix[key];
        }
    }

    function setPlanField(planId, field, value) {
        const plan = getPlanById(planId);

        if (!plan) {
            return;
        }

        if (field === "highlight") {
            plan.highlight = !!value;
            return;
        }

        plan[field] = value;
    }

    function setFeatureField(featureId, value) {
        const feature = getFeatureById(featureId);

        if (!feature) {
            return;
        }

        feature.label = value;
    }

    function setPlanAllFeatures(planId, enabled) {
        state.features.forEach(function (feature) {
            setMatrixValue(planId, feature.id, enabled);
        });
    }

    function setFeatureAcrossPlans(featureId, enabled) {
        state.plans.forEach(function (plan) {
            setMatrixValue(plan.id, featureId, enabled);
        });
    }

    function getTemplateClass() {
        const currentTemplate = $("input[name='ai_template']:checked").val() || previewTemplate;
        return "ai-pricing-template-" + currentTemplate;
    }

    function createPlan(plan, index) {
        const source = plan && typeof plan === "object" ? plan : {};
        const legacyPrice = source.price || "";

        return {
            id: normalizeId(source.id, "plan"),
            title: String(source.title || source.name || plan || ("Plan " + (index + 1))),
            price_monthly: String(source.price_monthly || legacyPrice || ""),
            price_yearly: String(source.price_yearly || legacyPrice || ""),
            billing_text: String(source.billing_text || ""),
            highlight: !!source.highlight,
            button_text: String(source.button_text || "Get Started"),
            button_url: String(source.button_url || "#")
        };
    }

    function createFeature(feature, index) {
        if (feature && typeof feature === "object") {
            return {
                id: normalizeId(feature.id, "feature"),
                label: String(feature.label || feature.title || feature.name || "Feature")
            };
        }

        return {
            id: nextId("feature"),
            label: String(feature || ("Feature " + (index + 1)))
        };
    }

    function filterMatrix() {
        const validPlanIds = {};
        const validFeatureIds = {};
        const nextMatrix = {};

        state.plans.forEach(function (plan) {
            validPlanIds[plan.id] = true;
        });

        state.features.forEach(function (feature) {
            validFeatureIds[feature.id] = true;
        });

        Object.keys(state.matrix).forEach(function (key) {
            const parts = key.split("::");

            if (parts.length !== 2) {
                return;
            }

            if (!validPlanIds[parts[0]] || !validFeatureIds[parts[1]] || !isEnabled(state.matrix[key])) {
                return;
            }

            nextMatrix[buildMatrixKey(parts[0], parts[1])] = true;
        });

        state.matrix = nextMatrix;
    }

    function countEnabledCells() {
        return Object.keys(state.matrix).filter(function (key) {
            return isEnabled(state.matrix[key]);
        }).length;
    }

    function getValidation() {
        const errors = [];
        const warnings = [];

        if (!state.plans.length) {
            errors.push("Add at least one plan.");
        }

        if (!state.features.length) {
            errors.push("Add at least one feature.");
        }

        if (!countEnabledCells() && state.plans.length && state.features.length) {
            warnings.push("No plan currently includes a feature.");
        }

        if (!state.plans.some(function (plan) { return plan.highlight; })) {
            warnings.push("No featured manual plan is selected.");
        }

        return {
            isValid: !errors.length,
            errors: errors,
            warnings: warnings
        };
    }

    function serializeState() {
        filterMatrix();

        if (!state.plans.length && !state.features.length) {
            return "";
        }

        return JSON.stringify({
            mode: "manual",
            plans: state.plans,
            features: state.features,
            matrix: state.matrix
        });
    }

    function isDirty() {
        return serializeState() !== initialSerialized;
    }

    function getStatusMarkup() {
        const validation = getValidation();
        const dirty = isDirty();
        const enabledCells = countEnabledCells();
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
                        <p>${state.plans.length} plans, ${state.features.length} features, ${enabledCells} enabled cells</p>
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

    function saveData() {
        const serialized = serializeState();

        if (!serialized) {
            $dataField.val("");
            return;
        }

        $modeField.prop("checked", true);
        $dataField.val(serialized);
    }

    function hydrateState(raw) {
        let parsed = raw;

        if (!parsed && window.aiPricingExistingData && window.aiPricingExistingData.manual) {
            parsed = window.aiPricingExistingData.manual;
        }

        if (!parsed) {
            return;
        }

        try {
            parsed = typeof parsed === "string" ? JSON.parse(parsed) : parsed;
        } catch (error) {
            return;
        }

        if (!parsed || typeof parsed !== "object") {
            return;
        }

        const sourcePlans = Array.isArray(parsed.plans) ? parsed.plans : [];
        const sourceFeatures = Array.isArray(parsed.features) ? parsed.features : [];
        const sourceMatrix = parsed.matrix && typeof parsed.matrix === "object" ? parsed.matrix : {};
        const planIndexMap = {};
        const featureIndexMap = {};
        const knownPlanIds = {};
        const knownFeatureIds = {};

        state.plans = sourcePlans.map(function (plan, index) {
            const nextPlan = createPlan(plan, index);
            let suffix = 1;

            while (knownPlanIds[nextPlan.id]) {
                nextPlan.id = normalizeId(nextPlan.id + "_" + suffix, "plan");
                suffix += 1;
            }

            knownPlanIds[nextPlan.id] = true;
            planIndexMap[index] = nextPlan.id;

            return nextPlan;
        }).filter(function (plan) {
            return plan.title || plan.price_monthly || plan.price_yearly;
        });

        state.features = sourceFeatures.map(function (feature, index) {
            const nextFeature = createFeature(feature, index);
            let suffix = 1;

            while (knownFeatureIds[nextFeature.id]) {
                nextFeature.id = normalizeId(nextFeature.id + "_" + suffix, "feature");
                suffix += 1;
            }

            knownFeatureIds[nextFeature.id] = true;
            featureIndexMap[index] = nextFeature.id;

            return nextFeature;
        }).filter(function (feature) {
            return feature.label;
        });

        state.matrix = {};

        Object.keys(sourceMatrix).forEach(function (key) {
            let planId = "";
            let featureId = "";

            if (!isEnabled(sourceMatrix[key])) {
                return;
            }

            if (key.indexOf("::") !== -1) {
                const parts = key.split("::");

                if (parts.length === 2) {
                    planId = normalizeId(parts[0], "plan");
                    featureId = normalizeId(parts[1], "feature");
                }
            } else if (/^\d+_\d+$/.test(key)) {
                const legacyParts = key.split("_");
                planId = planIndexMap[parseInt(legacyParts[0], 10)] || "";
                featureId = featureIndexMap[parseInt(legacyParts[1], 10)] || "";
            }

            if (!planId || !featureId || !knownPlanIds[planId] || !knownFeatureIds[featureId]) {
                return;
            }

            state.matrix[buildMatrixKey(planId, featureId)] = true;
        });

        filterMatrix();
    }

    function duplicatePlan(planId) {
        const plan = getPlanById(planId);
        const planIndex = getPlanIndex(planId);
        const clonedPlan = plan ? $.extend({}, plan, {
            id: nextId("plan"),
            title: (plan.title || "Plan") + " Copy"
        }) : null;

        if (!clonedPlan) {
            return;
        }

        state.plans.splice(planIndex + 1, 0, clonedPlan);

        state.features.forEach(function (feature) {
            setMatrixValue(clonedPlan.id, feature.id, hasMatrixValue(planId, feature.id));
        });

        render();
    }

    function duplicateFeature(featureId) {
        const feature = getFeatureById(featureId);
        const featureIndex = getFeatureIndex(featureId);
        const clonedFeature = feature ? {
            id: nextId("feature"),
            label: (feature.label || "Feature") + " Copy"
        } : null;

        if (!clonedFeature) {
            return;
        }

        state.features.splice(featureIndex + 1, 0, clonedFeature);

        state.plans.forEach(function (plan) {
            setMatrixValue(plan.id, clonedFeature.id, hasMatrixValue(plan.id, featureId));
        });

        render();
    }

    function renderPlans() {
        if (!state.plans.length) {
            $("#plans-list").html('<p class="description">No plans yet. Add your first plan to start building the table.</p>');
            return;
        }

        let html = "";

        state.plans.forEach(function (plan) {
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
        if (!state.features.length) {
            $("#features-list").html('<p class="description">No features yet. Add a feature row to build the comparison matrix.</p>');
            return;
        }

        let html = "";

        state.features.forEach(function (feature) {
            html += `
                <div class="feature-item" data-id="${escapeHtml(feature.id)}">
                    <button type="button" class="button-link ai-drag-handle" aria-label="Drag feature">
                        <span class="dashicons dashicons-move"></span>
                    </button>
                    <input type="text" value="${escapeHtml(feature.label)}" data-id="${escapeHtml(feature.id)}" class="feature-title" placeholder="Feature">
                    <button type="button" class="button duplicate-feature" data-id="${escapeHtml(feature.id)}">Duplicate</button>
                    <button type="button" class="button remove-feature" data-id="${escapeHtml(feature.id)}">Remove</button>
                </div>
            `;
        });

        $("#features-list").html(html);
    }

    function renderMatrix() {
        if (!state.plans.length || !state.features.length) {
            $("#matrix").html('<p class="description">Add at least one plan and one feature to map what each plan includes. Use the bulk actions to fill whole rows or columns quickly.</p>');
            return;
        }

        let html = "<table class='widefat striped ai-matrix-table'><thead><tr><th>Feature</th>";

        state.plans.forEach(function (plan) {
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

        state.features.forEach(function (feature) {
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

            state.plans.forEach(function (plan) {
                const checked = hasMatrixValue(plan.id, feature.id) ? "checked" : "";

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

        if (!state.plans.length) {
            $preview.html('<div class="ai-preview-empty-state"><p>Add plans and features to see a live manual-table preview.</p></div>');
            return;
        }

        let html = `
            <div class="ai-manual-preview-shell">
                <div class="ai-pricing-wrapper ai-pricing-mode-manual ${escapeHtml(getTemplateClass())} ai-manual-preview-wrapper" data-billing="${escapeHtml(state.previewBilling)}">
                    <div class="ai-pricing-header">
                        <div>
                            <p class="ai-pricing-eyebrow">Manual Preview</p>
                            <h2 class="ai-pricing-title">Edit content inline and toggle features directly from preview</h2>
                        </div>
                        <div class="ai-toggle" role="tablist" aria-label="Billing period">
                            <button type="button" class="ai-preview-billing-toggle ${state.previewBilling === "monthly" ? "active" : ""}" data-type="monthly">Monthly</button>
                            <button type="button" class="ai-preview-billing-toggle ${state.previewBilling === "yearly" ? "active" : ""}" data-type="yearly">Yearly</button>
                        </div>
                    </div>
                    <div class="ai-pricing-table">
        `;

        state.plans.forEach(function (plan) {
            const enabledFeatures = state.features.filter(function (feature) {
                return hasMatrixValue(plan.id, feature.id);
            });
            const monthlyPrice = plan.price_monthly || plan.price_yearly || "$0";
            const yearlyPrice = plan.price_yearly || plan.price_monthly || "$0";

            html += `
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
                        <div class="ai-preview-card-actions">
                            <button type="button" class="button-link ai-preview-card-action toggle-featured" data-id="${escapeHtml(plan.id)}">${plan.highlight ? "Unfeature" : "Feature"}</button>
                            <button type="button" class="button-link ai-preview-card-action duplicate-plan" data-id="${escapeHtml(plan.id)}">Duplicate</button>
                            <button type="button" class="button-link ai-preview-card-action remove-plan" data-id="${escapeHtml(plan.id)}">Remove</button>
                        </div>
                    </div>
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
                    <ul class="pricing-features">
            `;

            if (!enabledFeatures.length) {
                html += '<li class="ai-preview-empty-feature">No enabled features yet</li>';
            } else {
                enabledFeatures.forEach(function (feature) {
                    html += `
                        <li>
                            <span
                                class="ai-preview-editable"
                                contenteditable="true"
                                spellcheck="false"
                                data-preview-field="feature_label"
                                data-feature-id="${escapeHtml(feature.id)}"
                            >${escapeHtml(feature.label || "Feature")}</span>
                        </li>
                    `;
                });
            }

            html += `
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
                    <div class="ai-preview-card-controls">
                        <p>Quick feature toggles</p>
                        <div class="ai-preview-feature-switches">
            `;

            state.features.forEach(function (feature) {
                const enabled = hasMatrixValue(plan.id, feature.id);

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
                    <div class="ai-preview-footer">Quick actions here update the builder, preview, and saved manual JSON together.</div>
                </article>
            `;
        });

        html += `
                    </div>
                </div>
            </div>
        `;

        $preview.html(html);
    }

    function getOrderedIds(selector) {
        return $(selector).children("[data-id]").map(function () {
            return $(this).data("id");
        }).get();
    }

    function reorderCollection(collection, orderedIds) {
        const itemsById = {};

        collection.forEach(function (item) {
            itemsById[item.id] = item;
        });

        return orderedIds.map(function (id) {
            return itemsById[id];
        }).filter(Boolean);
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
                state.plans = reorderCollection(state.plans, getOrderedIds("#plans-list"));
                render();
            }
        }));

        $("#features-list").sortable($.extend({}, sortableOptions, {
            items: ".feature-item",
            update: function () {
                state.features = reorderCollection(state.features, getOrderedIds("#features-list"));
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
        saveData();
    }

    $(document).on("click", "#add-plan, #ai-preview-add-plan", function () {
        state.plans.push(createPlan({}, state.plans.length));
        render();
    });

    $(document).on("click", "#add-feature, #ai-preview-add-feature", function () {
        state.features.push(createFeature({}, state.features.length));
        render();
    });

    $(document).on("click", "#ai-enable-all-features", function () {
        state.plans.forEach(function (plan) {
            setPlanAllFeatures(plan.id, true);
        });
        render();
    });

    $(document).on("click", "#ai-clear-all-features", function () {
        state.plans.forEach(function (plan) {
            setPlanAllFeatures(plan.id, false);
        });
        render();
    });

    $(document).on("click", ".ai-bulk-plan", function () {
        setPlanAllFeatures($(this).data("plan-id"), $(this).data("state") === 1 || $(this).data("state") === "1");
        render();
    });

    $(document).on("click", ".ai-bulk-feature", function () {
        setFeatureAcrossPlans($(this).data("feature-id"), $(this).data("state") === 1 || $(this).data("state") === "1");
        render();
    });

    $(document).on("click", ".duplicate-plan", function () {
        duplicatePlan($(this).data("id"));
    });

    $(document).on("click", ".duplicate-feature", function () {
        duplicateFeature($(this).data("id"));
    });

    $(document).on("click", ".remove-plan", function () {
        const planId = $(this).data("id");

        state.plans = state.plans.filter(function (plan) {
            return plan.id !== planId;
        });

        filterMatrix();
        render();
    });

    $(document).on("click", ".remove-feature", function () {
        const featureId = $(this).data("id");

        state.features = state.features.filter(function (feature) {
            return feature.id !== featureId;
        });

        filterMatrix();
        render();
    });

    $(document).on("change", ".plan-field", function () {
        setPlanField($(this).data("id"), $(this).data("field"), $(this).val());
        render();
    });

    $(document).on("change", ".plan-highlight", function () {
        setPlanField($(this).data("id"), "highlight", $(this).is(":checked"));
        render();
    });

    $(document).on("change", ".feature-title", function () {
        setFeatureField($(this).data("id"), $(this).val());
        render();
    });

    $(document).on("change", ".matrix-check", function () {
        setMatrixValue($(this).data("plan-id"), $(this).data("feature-id"), $(this).is(":checked"));
        render();
    });

    $(document).on("click", ".ai-preview-feature-toggle", function () {
        const $toggle = $(this);
        const planId = $toggle.data("plan-id");
        const featureId = $toggle.data("feature-id");

        setMatrixValue(planId, featureId, !hasMatrixValue(planId, featureId));
        render();
    });

    $(document).on("click", ".toggle-featured", function () {
        const planId = $(this).data("id");
        const plan = getPlanById(planId);

        if (!plan) {
            return;
        }

        plan.highlight = !plan.highlight;
        render();
    });

    $(document).on("change", ".plan-url-inline", function () {
        setPlanField($(this).data("id"), "button_url", $(this).val());
        render();
    });

    $(document).on("click", ".ai-preview-billing-toggle", function () {
        state.previewBilling = $(this).data("type") === "yearly" ? "yearly" : "monthly";
        renderPreview();
    });

    $(document).on("keydown", ".ai-preview-editable", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            $(this).blur();
        }
    });

    $(document).on("input", ".ai-preview-editable", function () {
        const $field = $(this);
        const value = $field.text().replace(/\s+/g, " ").trim();
        const previewField = $field.data("preview-field");
        const planId = $field.data("plan-id");
        const featureId = $field.data("feature-id");

        if (planId) {
            setPlanField(planId, previewField, value);
        } else if (featureId) {
            setFeatureField(featureId, value);
        }

        saveData();
    });

    $(document).on("blur", ".ai-preview-editable", function () {
        const $field = $(this);
        const previewField = $field.data("preview-field");
        const fallbackMap = {
            title: "Plan",
            price_monthly: "$0",
            price_yearly: "$0",
            billing_text: "Add billing note",
            feature_label: "Feature",
            button_text: "Get Started"
        };
        const value = $field.text().replace(/\s+/g, " ").trim() || fallbackMap[previewField];
        const planId = $field.data("plan-id");
        const featureId = $field.data("feature-id");

        if (planId) {
            setPlanField(planId, previewField, value);
        } else if (featureId) {
            setFeatureField(featureId, value);
        }

        render();
    });

    $(document).on("change", "input[name='ai_template']", function () {
        renderPreview();
    });

    $form.on("submit", function (event) {
        const validation = getValidation();

        isSubmitting = true;

        if ($modeField.is(":checked") && !validation.isValid) {
            isSubmitting = false;
            event.preventDefault();
            render();
            $builder[0].scrollIntoView({ behavior: "smooth", block: "start" });
            return false;
        }

        initialSerialized = serializeState();

        return true;
    });

    $(window).on("beforeunload", function () {
        if (isSubmitting || !isDirty()) {
            return undefined;
        }

        return "You have unsaved manual builder changes.";
    });

    hydrateState($dataField.val());
    initialSerialized = serializeState();
    render();
});
