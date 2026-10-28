<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');

if (empty($_SESSION['student_csrf'])) {
    $_SESSION['student_csrf'] = bin2hex(random_bytes(32));
}

$username = escapeHtml((string) ($_SESSION['username'] ?? 'Admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | AJAX Student Management</title>
    <link rel="stylesheet" href="Styles/common.css">
    <link rel="stylesheet" href="Styles/manageStudents.css">
    <link rel="stylesheet" href="Styles/manageStudentsAjax.css">
    <script src="Scripts/practical4.js" defer></script>
    <script src="Scripts/manageStudentsAjax.js" defer></script>
</head>
<body>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Your Digital Campus Companion</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle"
                class="button theme-toggle" aria-pressed="false">
            🌙 Dark mode
        </button>
    </div>
</header>

<nav aria-label="Admin Navigation">
    <button id="menu-toggle" type="button"
            aria-label="Toggle navigation" aria-expanded="false"
            aria-controls="main-nav">☰ Menu</button>
    <ul id="main-nav">
        <li><a href="adminDashboard.php">Admin Dashboard</a></li>
        <li><a href="manageStudents.php">Manage Students</a></li>
        <li><a href="manageStudentsAjax.php" class="active"
               aria-current="page">AJAX Student Management</a></li>
        <li><a href="manageEvents.php">Manage Events</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<main>
    <section id="manage-students-title">
        <h2>AJAX Student Management</h2>
        <p>Welcome, <?= $username ?>! Manage student records using
            PHP JSON API and JavaScript Fetch without reloading the page.</p>
    </section>

    <div id="ajax-message" class="student-message" role="status"
         aria-live="polite" hidden></div>

    <section id="student-summary">
        <h2>Total Student Records</h2>
        <p id="total-students" aria-live="polite">Loading…</p>
        <p id="visible-students" class="student-result-count"></p>
    </section>

    <section id="student-form-section">
        <h2 id="form-heading">Add New Student</h2>

        <form id="ajax-student-form">
            <input type="hidden" id="student_id" name="student_id">

            <fieldset>
                <legend>Student Information</legend>

                <div class="student-form-grid">
                    <div>
                        <label for="enrollment_number">Enrollment Number</label>
                        <input type="text" id="enrollment_number"
                               name="enrollment_number" maxlength="20" required>
                    </div>

                    <div>
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               maxlength="100" required>
                    </div>

                    <div>
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               maxlength="150" required>
                    </div>

                    <div>
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option>Computer Engineering</option>
                            <option>Information Technology</option>
                            <option>Electronics</option>
                            <option>Mechanical Engineering</option>
                            <option>Civil Engineering</option>
                        </select>
                    </div>

                    <div>
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>">
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="student-form-actions">
                    <button type="submit" class="button" id="save-student">
                        Add Student
                    </button>
                    <button type="button" class="button" id="cancel-edit" hidden>
                        Cancel Edit
                    </button>
                    <button type="reset" class="button" id="reset-form">
                        Reset
                    </button>
                </div>
            </fieldset>
        </form>
    </section>

    <section id="student-list-section">
        <h2>Student Records</h2>

        <form id="ajax-search-form">
            <div>
                <label for="ajax-search">Search</label>
                <input type="search" id="ajax-search" name="search"
                       placeholder="Name, email or enrollment number">
            </div>

            <div>
                <label for="ajax-department">Department</label>
                <select id="ajax-department" name="department">
                    <option value="">All Departments</option>
                    <option>Computer Engineering</option>
                    <option>Information Technology</option>
                    <option>Electronics</option>
                    <option>Mechanical Engineering</option>
                    <option>Civil Engineering</option>
                </select>
            </div>

            <div>
                <label for="ajax-semester">Semester</label>
                <select id="ajax-semester" name="semester">
                    <option value="">All Semesters</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>">Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="student-filter-actions">
                <button type="submit" class="button">Search</button>
                <button type="reset" class="button" id="clear-filters">
                    Clear Filters
                </button>
            </div>
        </form>

        <p id="loading-indicator" role="status" hidden>
            Loading student records…
        </p>

        <div class="student-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Enrollment</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ajax-student-body">
                    <tr><td colspan="7">Loading student records…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> StudentHub. All rights reserved.</p>
</footer>
</body>
</html>
