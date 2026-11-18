<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | About</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/about.css">
</head>
<body>

    <!-- Header Starts -->
    <?php require __DIR__ . '/includes/site_header.php'; ?>
    <!-- Header Ends -->

    <!-- Navigation Starts -->
    <?php require __DIR__ . '/includes/site_navigation.php'; ?>
    <!-- Navigation Ends -->

    <!-- Main Content Starts -->
    <main>
        <section id="about-intro">
            <h2>About StudentHub</h2>
            <p>
                StudentHub is a simple web portal developed for students to
                access information related to college events, announcements
                and campus activities from a single platform.
            </p>
        </section>

        <section class="about-section">
            <h2>Our Vision</h2>
            <p>
                To build an easy-to-use digital platform that keeps students
                informed about all important college activities.
            </p>
        </section>

        <section class="about-section">
            <h2>Our Mission</h2>
            <p>
                To encourage students to participate in technical,
                cultural and sports events by providing timely
                information and updates.
            </p>
        </section>

        <section class="about-section">
            <h2>Objectives</h2>
            <ul>
                <li>Provide event information.</li>
                <li>Share important announcements.</li>
                <li>Increase student participation.</li>
                <li>Maintain student event records.</li>
                <li>Improve campus communication.</li>
            </ul>
        </section>

        <section class="about-section">
            <h2>Features</h2>
            <ul>
                <li>Student Registration</li>
                <li>Student Login</li>
                <li>View Upcoming Events</li>
                <li>Manage Student Profile</li>
                <li>Admin Event Management</li>
            </ul>
        </section>

        <section id="why-studenthub">
            <h2>Why Choose StudentHub?</h2>
            <p>
                StudentHub brings together event details, announcements,
                student information and campus updates in one place,
                making it easier for students to stay connected with
                college activities.
            </p>
        </section>
    </main>
    <!-- Main Content Ends -->

    <!-- Footer Starts -->
    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
<p>&copy; 2026 StudentHub. All Rights Reserved.</p>
    </footer>
    <!-- Footer Ends -->

    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>