/* StudentHub reusable, accessible confirmation dialog. */
(() => {
    "use strict";

    const dialog = document.createElement("dialog");
    dialog.className = "studenthub-confirm-dialog";
    dialog.setAttribute("aria-labelledby", "studenthub-confirm-title");
    dialog.setAttribute("aria-describedby", "studenthub-confirm-message");
    dialog.innerHTML = `
        <div class="studenthub-confirm-content">
            <div class="studenthub-confirm-icon" aria-hidden="true">!</div>
            <h2 id="studenthub-confirm-title">Please confirm</h2>
            <p id="studenthub-confirm-message"></p>
            <div class="studenthub-confirm-actions">
                <button type="button" class="studenthub-confirm-button studenthub-confirm-cancel">
                    Go back
                </button>
                <button type="button" class="studenthub-confirm-button studenthub-confirm-accept">
                    Confirm
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(dialog);

    const title = dialog.querySelector("#studenthub-confirm-title");
    const message = dialog.querySelector("#studenthub-confirm-message");
    const cancelButton = dialog.querySelector(".studenthub-confirm-cancel");
    const acceptButton = dialog.querySelector(".studenthub-confirm-accept");

    let resolvePending = null;

    dialog.addEventListener("close", () => {
        if (resolvePending) {
            resolvePending(dialog.returnValue === "confirm");
            resolvePending = null;
        }
    });

    cancelButton.addEventListener("click", () => dialog.close("cancel"));
    acceptButton.addEventListener("click", () => dialog.close("confirm"));

    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) {
            dialog.close("cancel");
        }
    });

    window.studentHubConfirm = (text, options = {}) => {
        if (typeof dialog.showModal !== "function") {
            // Do not fall back to browser-native confirmation popups.
            return Promise.resolve(false);
        }

        if (dialog.open) {
            return Promise.resolve(false);
        }

        title.textContent = options.title || "Please confirm";
        message.textContent = String(text || "Are you sure you want to continue?");
        cancelButton.textContent = options.cancelLabel || "Go back";
        acceptButton.textContent = options.confirmLabel || "Confirm";

        return new Promise((resolve) => {
            resolvePending = resolve;
            dialog.showModal();
            cancelButton.focus();
        });
    };

    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage) {
            return;
        }

        event.preventDefault();

        const messageText = form.dataset.confirmMessage.replace(
            "{event}",
            form.dataset.eventTitle || "this event"
        );

        window.studentHubConfirm(messageText, {
            title: form.dataset.confirmTitle,
            confirmLabel: form.dataset.confirmConfirmLabel,
            cancelLabel: form.dataset.confirmCancelLabel
        }).then((confirmed) => {
            if (confirmed) {
                HTMLFormElement.prototype.submit.call(form);
            }
        });
    });
})();
