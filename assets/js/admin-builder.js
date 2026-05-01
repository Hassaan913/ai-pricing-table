(function (window, $) {
    "use strict";

    $(function () {
        if (window.AIPricingManualBuilder && typeof window.AIPricingManualBuilder.boot === "function") {
            window.AIPricingManualBuilder.boot();
        }
    });
})(window, jQuery);
