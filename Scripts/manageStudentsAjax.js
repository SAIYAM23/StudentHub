document.addEventListener("DOMContentLoaded", () => {
    const apiUrl = "api/students.php";

    const form = document.getElementById("ajax-student-form");
    const searchForm = document.getElementById("ajax-search-form");
    const tableBody = document.getElementById("ajax-student-body");
    const message = document.getElementById("ajax-message");
    const loading = document.getElementById("loading-indicator");
    const totalStudents = document.getElementById("total-students");
    const visibleStudents = document.getElementById("visible-students");
    const saveButton = document.getElementById("save-student");
    const cancelButton = document.getElementById("cancel-edit");

    let csrfToken = "";
    let students = [];
    let debounceTimer;
    let activeController = null;

    function showMessage(text, type = "success") {
        message.textContent = text;
        message.className = `student-message ${type}`;
        message.hidden = false;
        message.setAttribute("role", type === "error" ? "alert" : "status");
    }

    function clearMessage() {
        message.hidden = true;
        message.textContent = "";
        message.className = "student-message";
    }

    async function request(url, options = {}) {
        const response = await fetch(url, options);

        let result;

        try {
            result = await response.json();
        } catch {
            throw new Error(
                `The server returned an invalid response (HTTP ${response.status}).`
            );
        }

        if (!response.ok || result.success !== true) {
            if (response.status === 401) {
                throw new Error(
                    "Your session may have expired. Log in again and reopen this page."
                );
            }

            throw new Error(
                result.message || `Request failed (HTTP ${response.status}).`
            );
        }

        return result;
    }

    function addCell(row, value) {
        const cell = document.createElement("td");
        cell.textContent = value ?? "";
        row.appendChild(cell);
        return cell;
    }

    function renderStudents(records) {
        tableBody.replaceChildren();

        if (records.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = 7;
            cell.textContent = "No matching student records found.";
            row.appendChild(cell);
            tableBody.appendChild(row);
            return;
        }

        records.forEach(student => {
            const row = document.createElement("tr");

            addCell(row, student.student_id);
            addCell(row, student.enrollment_number);
            addCell(row, student.full_name);
            addCell(row, student.email);
            addCell(row, student.department);
            addCell(row, `Semester ${student.semester}`);

            const actions = addCell(row, "");
            actions.className = "student-row-actions";

            const editButton = document.createElement("button");
            editButton.type = "button";
            editButton.className = "button";
            editButton.textContent = "Edit";
            editButton.addEventListener("click", () => startEdit(student));

            const deleteButton = document.createElement("button");
            deleteButton.type = "button";
            deleteButton.className = "button delete-button";
            deleteButton.textContent = "Delete";
            deleteButton.addEventListener("click", () => deleteStudent(student));

            actions.append(editButton, document.createTextNode(" "), deleteButton);
            tableBody.appendChild(row);
        });
    }

    async function loadStudents(updateAddress = false) {
        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        loading.hidden = false;

        const params = new URLSearchParams(new FormData(searchForm));
        const query = params.toString();
        const url = apiUrl + (query ? `?${query}` : "");

        try {
            const result = await request(url, {
                method: "GET",
                headers: { "Accept": "application/json" },
                signal: activeController.signal
            });

            csrfToken = result.csrf_token;
            students = result.data;

            renderStudents(students);
            totalStudents.textContent = result.total;
            visibleStudents.textContent =
                `Showing ${result.count} matching record(s).`;

            if (updateAddress) {
                history.replaceState({}, "", location.pathname + (query ? `?${query}` : ""));
            }
        } catch (error) {
            if (error.name !== "AbortError") {
                showMessage(error.message, "error");
                tableBody.replaceChildren();

                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 7;
                cell.textContent = "Unable to load records.";
                row.appendChild(cell);
                tableBody.appendChild(row);
            }
        } finally {
            loading.hidden = true;
            activeController = null;
        }
    }

    function startEdit(student) {
        document.getElementById("student_id").value = student.student_id;
        document.getElementById("enrollment_number").value = student.enrollment_number;
        document.getElementById("full_name").value = student.full_name;
        document.getElementById("email").value = student.email;
        document.getElementById("department").value = student.department;
        document.getElementById("semester").value = student.semester;

        document.getElementById("form-heading").textContent = "Edit Student";
        saveButton.textContent = "Update Student";
        cancelButton.hidden = false;
        clearMessage();

        form.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function resetEditor() {
        form.reset();
        document.getElementById("student_id").value = "";
        document.getElementById("form-heading").textContent = "Add New Student";
        saveButton.textContent = "Add Student";
        cancelButton.hidden = true;
    }

    form.addEventListener("submit", async event => {
        event.preventDefault();
        clearMessage();

        if (!form.reportValidity()) {
            return;
        }

        const id = document.getElementById("student_id").value;
        const payload = Object.fromEntries(new FormData(form).entries());
        payload.action = id ? "update" : "add";

        if (id) {
            payload.student_id = Number(id);
        } else {
            delete payload.student_id;
        }

        payload.semester = Number(payload.semester);

        saveButton.disabled = true;
        saveButton.textContent = "Saving…";

        try {
            const result = await request(apiUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify(payload)
            });

            showMessage(result.message);
            resetEditor();
            await loadStudents();
        } catch (error) {
            showMessage(error.message, "error");
        } finally {
            saveButton.disabled = false;

            if (!cancelButton.hidden) {
                saveButton.textContent = "Update Student";
            } else {
                saveButton.textContent = "Add Student";
            }
        }
    });

    async function deleteStudent(student) {
        const confirmed = await window.studentHubConfirm(
            `Delete ${student.full_name} (ID ${student.student_id})? This cannot be undone.`,
            {
                title: "Delete student?",
                confirmLabel: "Delete student",
                cancelLabel: "Keep student"
            }
        );

        if (!confirmed) return;

        clearMessage();

        try {
            const result = await request(apiUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    action: "delete",
                    student_id: Number(student.student_id)
                })
            });

            showMessage(result.message);
            await loadStudents();
        } catch (error) {
            showMessage(error.message, "error");
        }
    }

    searchForm.addEventListener("submit", event => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        loadStudents(true);
    });

    document.getElementById("ajax-search").addEventListener("input", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadStudents(true), 350);
    });

    document.getElementById("ajax-department").addEventListener("change", () => {
        loadStudents(true);
    });

    document.getElementById("ajax-semester").addEventListener("change", () => {
        loadStudents(true);
    });

    document.getElementById("clear-filters").addEventListener("click", event => {
        event.preventDefault();
        searchForm.reset();
        loadStudents(true);
    });

    cancelButton.addEventListener("click", resetEditor);

    document.getElementById("reset-form").addEventListener("click", () => {
        window.setTimeout(resetEditor, 0);
    });

    loadStudents();
});
