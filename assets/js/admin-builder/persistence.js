(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createPersistence(opts) {
        const store = opts.store;
        const $dataField = opts.$dataField;
        const $modeField = opts.$modeField;
        const planManager = opts.planManager;
        const featureManager = opts.featureManager;
        const matrixManager = opts.matrixManager;

        function serializeState() {
            matrixManager.filterMatrix();

            if (!store.state.plans.length && !store.state.features.length) {
                return "";
            }

            return JSON.stringify({
                mode: "manual",
                plans: store.state.plans,
                features: store.state.features,
                matrix: store.state.matrix
            });
        }

        function isDirty() {
            return serializeState() !== store.initialSerialized;
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

        function getValidation() {
            const errors = [];
            const warnings = [];

            if (!store.state.plans.length) {
                errors.push("Add at least one plan.");
            }

            if (!store.state.features.length) {
                errors.push("Add at least one feature.");
            }

            if (!matrixManager.countEnabledCells() && store.state.plans.length && store.state.features.length) {
                warnings.push("No plan currently includes a feature.");
            }

            if (!store.state.plans.some(function (plan) { return plan.highlight; })) {
                warnings.push("No featured manual plan is selected.");
            }

            return {
                isValid: !errors.length,
                errors: errors,
                warnings: warnings
            };
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

            store.state.plans = sourcePlans.map(function (plan, index) {
                const nextPlan = planManager.createPlan(plan, index);
                let suffix = 1;

                while (knownPlanIds[nextPlan.id]) {
                    nextPlan.id = store.normalizeId(nextPlan.id + "_" + suffix, "plan");
                    suffix += 1;
                }

                knownPlanIds[nextPlan.id] = true;
                planIndexMap[index] = nextPlan.id;

                return nextPlan;
            }).filter(function (plan) {
                return plan.title || plan.price_monthly || plan.price_yearly;
            });

            store.state.features = sourceFeatures.map(function (feature, index) {
                const nextFeature = featureManager.createFeature(feature, index);
                let suffix = 1;

                while (knownFeatureIds[nextFeature.id]) {
                    nextFeature.id = store.normalizeId(nextFeature.id + "_" + suffix, "feature");
                    suffix += 1;
                }

                knownFeatureIds[nextFeature.id] = true;
                featureIndexMap[index] = nextFeature.id;

                return nextFeature;
            }).filter(function (feature) {
                return feature.label;
            });

            store.state.matrix = {};

            Object.keys(sourceMatrix).forEach(function (key) {
                let planId = "";
                let featureId = "";

                if (!api.utils.isEnabled(sourceMatrix[key])) {
                    return;
                }

                if (key.indexOf("::") !== -1) {
                    const parts = key.split("::");

                    if (parts.length === 2) {
                        planId = store.normalizeId(parts[0], "plan");
                        featureId = store.normalizeId(parts[1], "feature");
                    }
                } else if (/^\d+_\d+$/.test(key)) {
                    const legacyParts = key.split("_");
                    planId = planIndexMap[parseInt(legacyParts[0], 10)] || "";
                    featureId = featureIndexMap[parseInt(legacyParts[1], 10)] || "";
                }

                if (!planId || !featureId || !knownPlanIds[planId] || !knownFeatureIds[featureId]) {
                    return;
                }

                store.state.matrix[api.utils.buildMatrixKey(planId, featureId)] = true;
            });

            matrixManager.filterMatrix();
        }

        return {
            serializeState: serializeState,
            isDirty: isDirty,
            saveData: saveData,
            getValidation: getValidation,
            hydrateState: hydrateState
        };
    }

    api.persistence = api.persistence || {};
    api.persistence.create = createPersistence;
})(window, jQuery);

