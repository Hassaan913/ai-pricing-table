(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createMatrixManager(store) {
        const buildMatrixKey = api.utils.buildMatrixKey;
        const isEnabled = api.utils.isEnabled;

        function hasMatrixValue(planId, featureId) {
            return !!store.state.matrix[buildMatrixKey(planId, featureId)];
        }

        function setMatrixValue(planId, featureId, enabled) {
            const key = buildMatrixKey(planId, featureId);

            if (enabled) {
                store.state.matrix[key] = true;
            } else {
                delete store.state.matrix[key];
            }
        }

        function setPlanAllFeatures(planId, enabled) {
            store.state.features.forEach(function (feature) {
                setMatrixValue(planId, feature.id, enabled);
            });
        }

        function setFeatureAcrossPlans(featureId, enabled) {
            store.state.plans.forEach(function (plan) {
                setMatrixValue(plan.id, featureId, enabled);
            });
        }

        function filterMatrix() {
            const validPlanIds = {};
            const validFeatureIds = {};
            const nextMatrix = {};

            store.state.plans.forEach(function (plan) {
                validPlanIds[plan.id] = true;
            });

            store.state.features.forEach(function (feature) {
                validFeatureIds[feature.id] = true;
            });

            Object.keys(store.state.matrix).forEach(function (key) {
                const parts = key.split("::");

                if (parts.length !== 2) {
                    return;
                }

                if (!validPlanIds[parts[0]] || !validFeatureIds[parts[1]] || !isEnabled(store.state.matrix[key])) {
                    return;
                }

                nextMatrix[buildMatrixKey(parts[0], parts[1])] = true;
            });

            store.state.matrix = nextMatrix;
        }

        function countEnabledCells() {
            return Object.keys(store.state.matrix).filter(function (key) {
                return isEnabled(store.state.matrix[key]);
            }).length;
        }

        return {
            hasMatrixValue: hasMatrixValue,
            setMatrixValue: setMatrixValue,
            setPlanAllFeatures: setPlanAllFeatures,
            setFeatureAcrossPlans: setFeatureAcrossPlans,
            filterMatrix: filterMatrix,
            countEnabledCells: countEnabledCells
        };
    }

    api.managers = api.managers || {};
    api.managers.createMatrixManager = createMatrixManager;
})(window, jQuery);

