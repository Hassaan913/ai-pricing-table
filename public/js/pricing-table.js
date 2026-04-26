document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".ai-pricing-wrapper").forEach(function (wrapper) {
        const buttons = wrapper.querySelectorAll(".ai-toggle button");

        if (!buttons.length) {
            return;
        }

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                const type = this.dataset.type;

                wrapper.setAttribute("data-billing", type);

                buttons.forEach(function (candidate) {
                    candidate.classList.toggle("active", candidate === button);
                });
            });
        });
    });
});
