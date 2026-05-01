(function (window, $) {
    "use strict";

    const api = window.AIPricingManualBuilder && window.AIPricingManualBuilder.ns ? window.AIPricingManualBuilder.ns() : (window.AIPricingManualBuilder = window.AIPricingManualBuilder || {});

    function boot() {
        const $builder = $("#ai-manual-builder");
        const $dataField = $("#ai_manual_data");
        const $modeField = $("input[name='ai_pricing_mode'][value='manual']");
        const $form = $builder.closest("form");
        const previewTemplate = $("input[name='ai_template']:checked").val() || "basic_blue";

        if (!$builder.length || !$dataField.length) {
            return;
        }

        const store = api.state.createStore({ previewTemplate: previewTemplate });
        const planManager = api.managers.createPlanManager(store);
        const featureManager = api.managers.createFeatureManager(store);
        const matrixManager = api.managers.createMatrixManager(store);
        const operationsManager = api.managers.createOperationsManager({
            store: store,
            planManager: planManager,
            featureManager: featureManager,
            matrixManager: matrixManager
        });

        const persistence = api.persistence.create({
            store: store,
            $dataField: $dataField,
            $modeField: $modeField,
            planManager: planManager,
            featureManager: featureManager,
            matrixManager: matrixManager
        });

        const renderer = api.rendering.create({
            store: store,
            $builder: $builder,
            planManager: planManager,
            featureManager: featureManager,
            matrixManager: matrixManager,
            persistence: persistence
        });

        api.events.bind({
            store: store,
            $builder: $builder,
            $form: $form,
            planManager: planManager,
            featureManager: featureManager,
            matrixManager: matrixManager,
            operationsManager: operationsManager,
            renderer: renderer,
            persistence: persistence
        });

        persistence.hydrateState($dataField.val());
        store.initialSerialized = persistence.serializeState();
        renderer.render();
    }

    api.boot = boot;

    $(boot);
})(window, jQuery);

