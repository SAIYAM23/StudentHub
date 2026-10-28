document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("student-search-form");

    if (!form) return;

    const searchInput = form.querySelector('[name="search"]');
    const departmentSelect = form.querySelector('[name="department"]');
    const semesterSelect = form.querySelector('[name="semester"]');

    let debounceTimer;
    let activeRequest = null;

    async function updateStudentResults(updateAddress = true) {
        const params = new URLSearchParams(new FormData(form));
        const queryString = params.toString();
        const url = "manageStudents.php" + (queryString ? "?" + queryString : "");

        // Cancel an older search if a newer one starts.
        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = new AbortController();

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                signal: activeRequest.signal
            });

            if (!response.ok) {
                throw new Error("Could not load student records.");
            }

            const html = await response.text();
            const parsedPage = new DOMParser().parseFromString(html, "text/html");

            const newBody = parsedPage.querySelector(
                "#student-list-section tbody"
            );
            const currentBody = document.querySelector(
                "#student-list-section tbody"
            );
            const newCount = parsedPage.querySelector(".student-result-count");
            const currentCount = document.querySelector(".student-result-count");
            const newExport = parsedPage.querySelector('a[href*="export=csv"]');
            const currentExport = document.querySelector('a[href*="export=csv"]');

            if (!newBody || !currentBody || !newCount || !currentCount) {
                throw new Error("The student results could not be updated.");
            }

            // Replace only the results, not the entire page.
            currentBody.replaceWith(newBody);
            currentCount.replaceWith(newCount);

            if (newExport && currentExport) {
                currentExport.href = newExport.href;
            }

            if (updateAddress) {
                history.pushState({}, "", url);
            }
        } catch (error) {
            if (error.name !== "AbortError") {
                console.error(error);
                alert("Unable to update student results. Please try again.");
            }
        } finally {
            activeRequest = null;
        }
    }

    // Search / Filter button: update results without a page reload.
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        updateStudentResults();
    });

    // Automatically filter when a department or semester is selected.
    departmentSelect.addEventListener("change", () => {
        updateStudentResults();
    });

    semesterSelect.addEventListener("change", () => {
        updateStudentResults();
    });

    // Update search results shortly after typing stops.
    searchInput.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => updateStudentResults(), 350);
    });

    // Clear Filters without reloading the page.
    const clearLink = Array.from(form.parentElement.querySelectorAll("a"))
        .find(link => {
            try {
                const target = new URL(link.href);
                return target.pathname.endsWith("/manageStudents.php")
                    && !target.search;
            } catch {
                return false;
            }
        });

    if (clearLink) {
        clearLink.addEventListener("click", (event) => {
            event.preventDefault();
            clearTimeout(debounceTimer);
            form.reset();
            updateStudentResults();
        });
    }

    // Support browser Back and Forward buttons.
    window.addEventListener("popstate", () => {
        const params = new URLSearchParams(window.location.search);

        searchInput.value = params.get("search") || "";
        departmentSelect.value = params.get("department") || "";
        semesterSelect.value = params.get("semester") || "";

        updateStudentResults(false);
    });
});
