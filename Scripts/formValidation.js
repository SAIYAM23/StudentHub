const form = document.querySelector("form");

const fields = [
    "fullName",
    "rollNo",
    "email",
    "department",
    "pass",
    "cPass"
];


// Check a field and return an error message
function validate(id) {

    const value = document.getElementById(id).value.trim();

    // Empty field
    if (value === "") {
        return "";
    }

    // Full Name
    if (id === "fullName" &&
        !/^[A-Za-z]+( [A-Za-z]+)*$/.test(value)) {
        return "Only alphabets and single spaces are allowed.";
    }

    // Enrollment Number
    if (id === "rollNo" &&
        !/^\d{2}[A-Za-z]{2}\d{3}$/.test(value)) {
        return "Enter a valid enrollment number like 25CE001.";
    }

    // Email
    if (id === "email" &&
        !/^\d{2}[A-Za-z]{2}\d{3}@charusat\.edu\.in$/.test(value)) {
        return "Enter a valid CHARUSAT email.";
    }

    // Department
    if (id === "department" && value === "") {
        return "";
    }

    // Password
    if (id === "pass" &&
        !/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/.test(value)) {
        return "Password must have 8 characters, uppercase, lowercase and number.";
    }

    // Confirm Password
    if (id === "cPass" &&
        value !== document.getElementById("pass").value) {
        return "Passwords do not match.";
    }

    return null;
}


// Show error
function showError(id, message) {

    document.getElementById(id).classList.add("invalid");

    document.getElementById(id + "Error").textContent = message;
}


// Remove error
function removeError(id) {

    document.getElementById(id).classList.remove("invalid");

    document.getElementById(id + "Error").textContent = "";
}


// Show required *
function showStar(id) {
    document.getElementById(id + "Star").style.display = "inline";
}


// Hide required *
function hideStar(id) {
    document.getElementById(id + "Star").style.display = "none";
}


// Stop Tab when current field is empty or wrong
fields.forEach(function(id) {

    const field = document.getElementById(id);

    // Validate while typing
    field.addEventListener("input", function() {

        const value = this.value.trim();
        const error = validate(id);

        if (value === "") {
            removeError(id);
            return;
        }

        if (error) {
            showError(id, error);
        }
        else {
            removeError(id);
        }
    });


    // Control Tab
    field.addEventListener("keydown", function(event) {

        if (event.key === "Tab") {

            const value = this.value.trim();
            const error = validate(id);

            // Empty → allow Tab
            if (value === "") {
                return;
            }

            // Wrong → stop Tab
            if (error) {
                event.preventDefault();
                showError(id, error);
            }
        }
    });

});


// Submit
form.addEventListener("submit", function(event) {

    let formValid = true;

    fields.forEach(function(id) {

        const field = document.getElementById(id);
        const value = field.value.trim();
        const error = validate(id);

        // Remove previous attention effect
        field.classList.remove("attention");

        // Empty field
        if (value === "") {

            formValid = false;

            // Blink empty field
            void field.offsetWidth;
            field.classList.add("attention");
        }

        // Wrong input
        else if (error) {

            formValid = false;

            showError(id, error);

            // Blink wrong field
            void field.offsetWidth;
            field.classList.add("attention");
        }
    });


    if (!formValid) {
        event.preventDefault();
    }
});