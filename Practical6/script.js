const dataType = document.getElementById("dataType");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const sortBy = document.getElementById("sortBy");
const recordContainer = document.getElementById("recordContainer");
const statusMessage = document.getElementById("statusMessage");
const sectionTitle = document.getElementById("sectionTitle");
const prevButton = document.getElementById("prevButton");
const nextButton = document.getElementById("nextButton");
const pageInfo = document.getElementById("pageInfo");

const recordsPerPage = 6;

let allRecords = [];
let currentPage = 1;
let requestNumber = 0;

function getRecordName(record) {
    return record.name || record.title || record.question || "";
}

function getRecordCategory(record) {
    return record.category || record.course || "Other";
}

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (character) {
        const entities = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        };

        return entities[character];
    });
}

async function loadData() {
    const thisRequest = ++requestNumber;

    currentPage = 1;
    allRecords = [];
    recordContainer.innerHTML = "";
    statusMessage.textContent = "Loading data...";
    pageInfo.textContent = "Page 1";
    updateButtons(0);

    sectionTitle.textContent =
        dataType.options[dataType.selectedIndex].text;

    try {
        const response = await fetch(dataType.value);

        if (!response.ok) {
            throw new Error("Unable to load JSON file. HTTP status: " +
                response.status);
        }

        const data = await response.json();

        if (!Array.isArray(data)) {
            throw new Error("The JSON data must be an array.");
        }

        if (thisRequest !== requestNumber) {
            return;
        }

        allRecords = data;

        populateCategories();
        renderRecords();
    } catch (error) {
        if (thisRequest !== requestNumber) {
            return;
        }

        allRecords = [];
        recordContainer.innerHTML = "";
        statusMessage.textContent =
            "Error loading data: " + error.message;
        pageInfo.textContent = "Page 0";
        updateButtons(0);

        console.error("Fetch error:", error);
    }
}

function populateCategories() {
    const categories = [
        ...new Set(allRecords.map(getRecordCategory))
    ].sort((a, b) => a.localeCompare(b));

    categoryFilter.innerHTML =
        '<option value="all">All Categories</option>';

    categories.forEach(category => {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
}

function getFilteredRecords() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    const selectedCategory = categoryFilter.value;

    let filteredRecords = allRecords.filter(record => {
        const searchableText = Object.values(record)
            .map(value => String(value))
            .join(" ")
            .toLowerCase();

        const matchesSearch = searchableText.includes(searchTerm);

        const matchesCategory =
            selectedCategory === "all" ||
            getRecordCategory(record) === selectedCategory;

        return matchesSearch && matchesCategory;
    });

    if (sortBy.value === "asc") {
        filteredRecords.sort((a, b) =>
            getRecordName(a).localeCompare(getRecordName(b))
        );
    } else if (sortBy.value === "desc") {
        filteredRecords.sort((a, b) =>
            getRecordName(b).localeCompare(getRecordName(a))
        );
    }

    return filteredRecords;
}

function renderRecords() {
    const filteredRecords = getFilteredRecords();
    const totalPages = Math.ceil(filteredRecords.length / recordsPerPage);

    if (totalPages === 0) {
        currentPage = 1;
    } else if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * recordsPerPage;
    const pageRecords = filteredRecords.slice(
        startIndex,
        startIndex + recordsPerPage
    );

    recordContainer.innerHTML = "";

    if (pageRecords.length === 0) {
        statusMessage.textContent = allRecords.length === 0
            ? "No records available."
            : "No matching records found.";

        pageInfo.textContent = "Page 0 of 0";
        updateButtons(0);
        return;
    }

    statusMessage.textContent =
        "Showing " + pageRecords.length + " of " +
        filteredRecords.length + " matching records.";

    pageRecords.forEach(record => {
        const card = document.createElement("article");
        card.className = "record-card";

        card.innerHTML = createCardContent(record);
        recordContainer.appendChild(card);
    });

    pageInfo.textContent =
        "Page " + currentPage + " of " + totalPages;

    updateButtons(totalPages);
}

function createCardContent(record) {
    if (dataType.value === "events.json") {
        return `
            <h3>${escapeHTML(record.title)}</h3>
            <span class="category">${escapeHTML(record.category)}</span>
            <p><strong>Date:</strong> ${escapeHTML(record.date)}</p>
            <p>${escapeHTML(record.description)}</p>
            <p><strong>Venue:</strong> ${escapeHTML(record.venue)}</p>
        `;
    }

    if (dataType.value === "students.json") {
        return `
            <h3>${escapeHTML(record.name)}</h3>
            <span class="category">${escapeHTML(record.course)}</span>
            <p><strong>Student ID:</strong> ${escapeHTML(record.studentId)}</p>
            <p><strong>Year:</strong> ${escapeHTML(record.year)}</p>
            <p><strong>Email:</strong> ${escapeHTML(record.email)}</p>
        `;
    }

    return `
        <h3>${escapeHTML(record.question)}</h3>
        <span class="category">${escapeHTML(record.category)}</span>
        <p>${escapeHTML(record.answer)}</p>
    `;
}

function updateButtons(totalPages) {
    prevButton.disabled = currentPage <= 1;
    nextButton.disabled =
        totalPages === 0 || currentPage >= totalPages;
}

dataType.addEventListener("change", () => {
    searchInput.value = "";
    sortBy.value = "default";
    loadData();
});

searchInput.addEventListener("input", () => {
    currentPage = 1;
    renderRecords();
});

categoryFilter.addEventListener("change", () => {
    currentPage = 1;
    renderRecords();
});

sortBy.addEventListener("change", () => {
    currentPage = 1;
    renderRecords();
});

prevButton.addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;
        renderRecords();
    }
});

nextButton.addEventListener("click", () => {
    const totalPages = Math.ceil(
        getFilteredRecords().length / recordsPerPage
    );

    if (currentPage < totalPages) {
        currentPage++;
        renderRecords();
    }
});

loadData();
