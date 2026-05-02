(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createStore(options) {
        const previewTemplate = options.previewTemplate || "basic_blue";

        const store = {
            state: {
                plans: [],
                features: [],
                matrix: {},
                previewBilling: "monthly"
            },
            idCounter: 0,
            initialSerialized: "",
            isSubmitting: false,
            previewTemplate: previewTemplate
        };

        store.nextId = function nextId(prefix) {
            store.idCounter += 1;
            return prefix + "_" + Date.now().toString(36) + "_" + store.idCounter;
        };

        store.normalizeId = function normalizeId(value, prefix) {
            const normalized = String(value || "")
                .toLowerCase()
                .replace(/[^a-z0-9_-]+/g, "_")
                .replace(/^_+|_+$/g, "");

            return normalized || store.nextId(prefix);
        };

        store.getManualIcons = function getManualIcons() {
            return window.aiPricingManualIcons || {};
        };

        store.normalizeIcon = function normalizeIcon(value) {
            const normalized = String(value || "")
                .toLowerCase()
                .replace(/[^a-z0-9_-]+/g, "-")
                .replace(/^-+|-+$/g, "");

            return Object.prototype.hasOwnProperty.call(store.getManualIcons(), normalized) ? normalized : "";
        };

        store.getTemplateClass = function getTemplateClass() {
            const currentTemplate = $("input[name='ai_template']:checked").val() || store.previewTemplate;
            return "ai-pricing-template-" + currentTemplate;
        };

        store.getTemplateLayout = function getTemplateLayout() {
            const currentTemplate = $("input[name='ai_template']:checked").val() || store.previewTemplate;
            const templates = window.aiPricingTemplates || {};
            return templates[currentTemplate] && templates[currentTemplate].layout ? templates[currentTemplate].layout : "cards";
        };

        store.getLayoutClass = function getLayoutClass() {
            return "ai-pricing-layout-" + store.getTemplateLayout();
        };

        return store;
    }

    api.state = api.state || {};
    api.state.createStore = createStore;
})(window, jQuery);

