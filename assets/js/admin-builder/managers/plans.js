(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function createPlanManager(store) {
        function getPlanById(planId) {
            return store.state.plans.find(function (plan) {
                return plan.id === planId;
            });
        }

        function getPlanIndex(planId) {
            return store.state.plans.findIndex(function (plan) {
                return plan.id === planId;
            });
        }

        function createPlan(plan, index) {
            const source = plan && typeof plan === "object" ? plan : {};
            const legacyPrice = source.price || "";

            return {
                id: store.normalizeId(source.id, "plan"),
                title: String(source.title || source.name || plan || ("Plan " + (index + 1))),
                price_monthly: String(source.price_monthly || legacyPrice || ""),
                price_yearly: String(source.price_yearly || legacyPrice || ""),
                billing_text: String(source.billing_text || ""),
                highlight: !!source.highlight,
                button_text: String(source.button_text || "Get Started"),
                button_url: String(source.button_url || "#")
            };
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

        function toggleFeatured(planId) {
            const plan = getPlanById(planId);

            if (!plan) {
                return;
            }

            plan.highlight = !plan.highlight;
        }

        return {
            getPlanById: getPlanById,
            getPlanIndex: getPlanIndex,
            createPlan: createPlan,
            setPlanField: setPlanField,
            toggleFeatured: toggleFeatured
        };
    }

    api.managers = api.managers || {};
    api.managers.createPlanManager = createPlanManager;
})(window, jQuery);

