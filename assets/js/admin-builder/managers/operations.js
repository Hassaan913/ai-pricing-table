(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createOperationsManager(opts) {
        const store = opts.store;
        const planManager = opts.planManager;
        const featureManager = opts.featureManager;
        const matrixManager = opts.matrixManager;

        function duplicatePlan(planId) {
            const plan = planManager.getPlanById(planId);
            const planIndex = planManager.getPlanIndex(planId);
            const clonedPlan = plan ? $.extend({}, plan, {
                id: store.nextId("plan"),
                title: (plan.title || "Plan") + " Copy"
            }) : null;

            if (!clonedPlan) {
                return;
            }

            store.state.plans.splice(planIndex + 1, 0, clonedPlan);

            store.state.features.forEach(function (feature) {
                matrixManager.setMatrixValue(clonedPlan.id, feature.id, matrixManager.hasMatrixValue(planId, feature.id));
            });
        }

        function duplicateFeature(featureId) {
            const feature = featureManager.getFeatureById(featureId);
            const featureIndex = featureManager.getFeatureIndex(featureId);
            const clonedFeature = feature ? {
                id: store.nextId("feature"),
                label: (feature.label || "Feature") + " Copy"
            } : null;

            if (!clonedFeature) {
                return;
            }

            store.state.features.splice(featureIndex + 1, 0, clonedFeature);

            store.state.plans.forEach(function (plan) {
                matrixManager.setMatrixValue(plan.id, clonedFeature.id, matrixManager.hasMatrixValue(plan.id, featureId));
            });
        }

        return {
            duplicatePlan: duplicatePlan,
            duplicateFeature: duplicateFeature
        };
    }

    api.managers = api.managers || {};
    api.managers.createOperationsManager = createOperationsManager;
})(window, jQuery);

