(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function bindEvents(opts) {
        const store = opts.store;
        const $builder = opts.$builder;
        const $form = opts.$form;
        const planManager = opts.planManager;
        const featureManager = opts.featureManager;
        const matrixManager = opts.matrixManager;
        const operationsManager = opts.operationsManager;
        const renderer = opts.renderer;
        const persistence = opts.persistence;

        $(document).on("click", "#add-plan, #ai-preview-add-plan", function () {
            store.state.plans.push(planManager.createPlan({}, store.state.plans.length));
            renderer.render();
        });

        $(document).on("click", "#add-feature, #ai-preview-add-feature", function () {
            store.state.features.push(featureManager.createFeature({}, store.state.features.length));
            renderer.render();
        });

        $(document).on("click", "#ai-enable-all-features", function () {
            store.state.plans.forEach(function (plan) {
                matrixManager.setPlanAllFeatures(plan.id, true);
            });
            renderer.render();
        });

        $(document).on("click", "#ai-clear-all-features", function () {
            store.state.plans.forEach(function (plan) {
                matrixManager.setPlanAllFeatures(plan.id, false);
            });
            renderer.render();
        });

        $(document).on("click", ".ai-bulk-plan", function () {
            matrixManager.setPlanAllFeatures($(this).data("plan-id"), $(this).data("state") === 1 || $(this).data("state") === "1");
            renderer.render();
        });

        $(document).on("click", ".ai-bulk-feature", function () {
            matrixManager.setFeatureAcrossPlans($(this).data("feature-id"), $(this).data("state") === 1 || $(this).data("state") === "1");
            renderer.render();
        });

        $(document).on("click", ".duplicate-plan", function () {
            operationsManager.duplicatePlan($(this).data("id"));
            renderer.render();
        });

        $(document).on("click", ".duplicate-feature", function () {
            operationsManager.duplicateFeature($(this).data("id"));
            renderer.render();
        });

        $(document).on("click", ".remove-plan", function () {
            const planId = $(this).data("id");

            store.state.plans = store.state.plans.filter(function (plan) {
                return plan.id !== planId;
            });

            matrixManager.filterMatrix();
            renderer.render();
        });

        $(document).on("click", ".remove-feature", function () {
            const featureId = $(this).data("id");

            store.state.features = store.state.features.filter(function (feature) {
                return feature.id !== featureId;
            });

            matrixManager.filterMatrix();
            renderer.render();
        });

        $(document).on("change", ".plan-field", function () {
            planManager.setPlanField($(this).data("id"), $(this).data("field"), $(this).val());
            renderer.render();
        });

        $(document).on("change", ".plan-highlight", function () {
            planManager.setPlanField($(this).data("id"), "highlight", $(this).is(":checked"));
            renderer.render();
        });

        $(document).on("change", ".feature-title", function () {
            featureManager.setFeatureField($(this).data("id"), "label", $(this).val());
            renderer.render();
        });

        $(document).on("change", ".feature-icon", function () {
            featureManager.setFeatureField($(this).data("id"), "icon", $(this).val());
            renderer.render();
        });

        $(document).on("change", ".matrix-check", function () {
            matrixManager.setMatrixValue($(this).data("plan-id"), $(this).data("feature-id"), $(this).is(":checked"));
            renderer.render();
        });

        $(document).on("click", ".ai-preview-feature-toggle", function () {
            const $toggle = $(this);
            const planId = $toggle.data("plan-id");
            const featureId = $toggle.data("feature-id");

            matrixManager.setMatrixValue(planId, featureId, !matrixManager.hasMatrixValue(planId, featureId));
            renderer.render();
        });

        $(document).on("click", ".toggle-featured", function () {
            planManager.toggleFeatured($(this).data("id"));
            renderer.render();
        });

        $(document).on("change", ".plan-url-inline", function () {
            planManager.setPlanField($(this).data("id"), "button_url", $(this).val());
            renderer.render();
        });

        $(document).on("click", ".ai-preview-billing-toggle", function () {
            store.state.previewBilling = $(this).data("type") === "yearly" ? "yearly" : "monthly";
            renderer.renderPreview();
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
                planManager.setPlanField(planId, previewField, value);
            } else if (featureId) {
                featureManager.setFeatureField(featureId, "label", value);
            }

            persistence.saveData();
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
                planManager.setPlanField(planId, previewField, value);
            } else if (featureId) {
                featureManager.setFeatureField(featureId, "label", value);
            }

            renderer.render();
        });

        $(document).on("change", "input[name='ai_template']", function () {
            renderer.renderPreview();
        });

        $form.on("submit", function (event) {
            const validation = persistence.getValidation();

            store.isSubmitting = true;

            if ($("input[name='ai_pricing_mode'][value='manual']").is(":checked") && !validation.isValid) {
                store.isSubmitting = false;
                event.preventDefault();
                renderer.render();
                $builder[0].scrollIntoView({ behavior: "smooth", block: "start" });
                return false;
            }

            store.initialSerialized = persistence.serializeState();

            return true;
        });

        $(window).on("beforeunload", function () {
            if (store.isSubmitting || !persistence.isDirty()) {
                return undefined;
            }

            return "You have unsaved manual builder changes.";
        });
    }

    api.events = api.events || {};
    api.events.bind = bindEvents;
})(window, jQuery);

