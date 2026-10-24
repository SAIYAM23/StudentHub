"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#register-box form");
    if (!form) return;

    const ids = ["rollNo", "email", "password", "confirm-password", "fullName", "dob", "mobile", "department", "semester"];
    const get = (id) => document.getElementById(id);

    function errorElement(id) {
        return get(id + "Error");
    }

    function showError(id, message) {
        const field = get(id);
        const output = errorElement(id);
        if (field) {
            field.classList.toggle("invalid", Boolean(message));
            field.setAttribute("aria-invalid", message ? "true" : "false");
        }
        if (output) output.textContent = message || "";
    }

    function validate(id) {
        const field = get(id);
        const value = field.value.trim();
        if (!value) return "This field is required.";

        switch (id) {
            case "rollNo":
                return /^\d{2}[A-Za-z]{2}\d{3}$/.test(value)
                    ? "" : "Enter your enrollment number in the format 25CE001.";
            case "email":
                return /^\d{2}[A-Za-z]{2}\d{3}@charusat\.edu\.in$/i.test(value)
                    ? "" : "Use your CHARUSAT email, e.g. 25CE001@charusat.edu.in.";
            case "password":
                return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(value)
                    ? "" : "Use at least 8 characters with uppercase, lowercase, and a number.";
            case "confirm-password":
                return value === get("password").value
                    ? "" : "Passwords do not match.";
            case "fullName":
                return /^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/.test(value)
                    ? "" : "Enter a valid name using letters, spaces, apostrophes, or hyphens.";
            case "dob": {
                const date = new Date(value + "T00:00:00");
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return date <= today ? "" : "Date of birth cannot be in the future.";
            }
            case "mobile":
                return /^(?:\+91[- ]?)?[6-9]\d{9}$/.test(value.replace(/\s+/g, " "))
                    ? "" : "Enter a valid Indian 10-digit mobile number (optionally with +91).";
            case "department":
            case "semester":
                return value ? "" : "Please select an option.";
            default:
                return "";
        }
    }

    function updatePasswordStrength() {
        const password = get("password").value;
        const bar = get("strengthBar");
        const text = get("passwordStrength");
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        const labels = ["not entered", "weak", "fair", "good", "strong"];
        if (bar) {
            bar.style.width = (score * 25) + "%";
            bar.dataset.level = labels[score];
        }
        if (text) text.textContent = "Password strength: " + (password ? labels[score] : "not entered");
    }

    ids.forEach((id) => {
        const field = get(id);
        if (!field) return;
        const eventName = field.tagName === "SELECT" ? "change" : "input";
        field.addEventListener(eventName, () => {
            // Show feedback after the user starts interacting or after submit.
            if (field.dataset.touched === "true" || form.dataset.submitted === "true") {
                showError(id, validate(id));
            }
            if (id === "password") {
                updatePasswordStrength();
                if (get("confirm-password").value) showError("confirm-password", validate("confirm-password"));
            }
        });
        field.addEventListener("blur", () => {
            field.dataset.touched = "true";
            showError(id, validate(id));
        });
    });

    document.querySelectorAll('input[name="gender"]').forEach((radio) => {
        radio.addEventListener("change", () => showError("gender", ""));
    });
    ["terms", "privacy"].forEach((id) => {
        get(id).addEventListener("change", () => {
            const message = get("terms").checked && get("privacy").checked ? "" : "Please accept both the terms and privacy policy.";
            const output = get("termsError");
            if (output) output.textContent = message;
        });
    });

    form.addEventListener("submit", (event) => {
        form.dataset.submitted = "true";
        let valid = true;
        ids.forEach((id) => {
            const message = validate(id);
            showError(id, message);
            if (message) valid = false;
        });

        const genderSelected = Boolean(document.querySelector('input[name="gender"]:checked'));
        const genderError = get("genderError");
        if (genderError) genderError.textContent = genderSelected ? "" : "Please select a gender option.";
        if (!genderSelected) valid = false;

        const accepted = get("terms").checked && get("privacy").checked;
        const termsError = get("termsError");
        if (termsError) termsError.textContent = accepted ? "" : "Please accept both the terms and privacy policy.";
        if (!accepted) valid = false;

        if (!valid) {
            event.preventDefault();
            const firstInvalid = form.querySelector('[aria-invalid="true"]');
            if (firstInvalid) firstInvalid.focus();
        }
    });

    form.addEventListener("reset", () => {
        form.dataset.submitted = "false";
        window.setTimeout(() => {
            ids.forEach((id) => {
                const field = get(id);
                if (field) {
                    field.classList.remove("invalid");
                    field.removeAttribute("aria-invalid");
                    delete field.dataset.touched;
                }
                showError(id, "");
            });
            showError("gender", "");
            if (get("termsError")) get("termsError").textContent = "";
            updatePasswordStrength();
        }, 0);
    });

    updatePasswordStrength();
});
