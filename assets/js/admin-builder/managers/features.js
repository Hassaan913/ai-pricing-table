(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createFeatureManager(store) {
        function getFeatureById(featureId) {
            return store.state.features.find(function (feature) {
                return feature.id === featureId;
            });
        }

        function getFeatureIndex(featureId) {
            return store.state.features.findIndex(function (feature) {
                return feature.id === featureId;
            });
        }

        function createFeature(feature, index) {
            if (feature && typeof feature === "object") {
                return {
                    id: store.normalizeId(feature.id, "feature"),
                    label: String(feature.label || feature.title || feature.name || "Feature")
                };
            }

            return {
                id: store.nextId("feature"),
                label: String(feature || ("Feature " + (index + 1)))
            };
        }

        function setFeatureField(featureId, value) {
            const feature = getFeatureById(featureId);

            if (!feature) {
                return;
            }

            feature.label = value;
        }

        return {
            getFeatureById: getFeatureById,
            getFeatureIndex: getFeatureIndex,
            createFeature: createFeature,
            setFeatureField: setFeatureField
        };
    }

    api.managers = api.managers || {};
    api.managers.createFeatureManager = createFeatureManager;
})(window, jQuery);

