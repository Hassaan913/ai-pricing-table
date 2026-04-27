jQuery(function ($) {
    const $button = $("#ai-generate-btn");
    const $result = $("#ai-pricing-result");
    const $jsonField = $("#ai_pricing_json");
    const $modeField = $("input[name='ai_pricing_mode'][value='ai']");

    if (!$button.length) {
        return;
    }

    $button.on("click", function () {
        const business = String($("#ai_business_name").val() || "").trim();
        const audience = String($("#ai_audience").val() || "").trim();
        const features = String($("#ai_features").val() || "").trim();

        if (!business && !audience && !features) {
            $result.html(
                '<div class="notice notice-warning inline"><p>Please fill at least one field (Business Name, Target Audience, or Main Features) before generating.</p></div>'
            );
            return;
        }

        $button.prop("disabled", true).text("Generating...");
        $result.html("<p>Generating pricing table...</p>");

        $.ajax({
            url: aiPricingAdmin.ajaxUrl,
            type: "POST",
            dataType: "json",
            data: {
                action: "ai_generate_pricing",
                nonce: aiPricingAdmin.nonce,
                business: business,
                audience: audience,
                features: features
            }
        }).done(function (response) {
            if (!response || !response.success) {
                const message = response && response.data && response.data.message
                    ? response.data.message
                    : "AI generation failed.";
                $result.html('<div class="notice notice-error inline"><p>' + message + "</p></div>");
                return;
            }

            $jsonField.val(response.data.json);
            $modeField.prop("checked", true);
            $result.html("<pre>" + JSON.stringify(response.data.pricing, null, 2) + "</pre>");
        }).fail(function () {
            $result.html('<div class="notice notice-error inline"><p>Request failed.</p></div>');
        }).always(function () {
            $button.prop("disabled", false).text("Generate Pricing With AI");
        });
    });
});
