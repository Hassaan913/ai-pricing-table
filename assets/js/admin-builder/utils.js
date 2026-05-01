(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

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

    api.utils = api.utils || {};
    api.utils.escapeHtml = escapeHtml;
    api.utils.buildMatrixKey = buildMatrixKey;
    api.utils.isEnabled = isEnabled;
    api.utils.getOrderedIds = getOrderedIds;
    api.utils.reorderCollection = reorderCollection;
})(window, jQuery);

