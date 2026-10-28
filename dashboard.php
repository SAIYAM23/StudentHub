<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireLogin();

// Admin accounts must use the admin dashboard.
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: adminDashboard.php');
    exit;
}

$username = escapeHtml(
    (string) ($_SESSION['username'] ?? 'Student')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Dashboard</title>
    <link rel="stylesheet" href="Styles/common.css">
    <link rel="stylesheet" href="Styles/dashboard.css">
</head>
<body>

    <!-- Header Starts -->
    <header>
        <div id="logo">
            <h1>StudentHub</h1>
            <p>Your Digital Campus Companion</p>
        </div>

        <div id="header-buttons">
            <button
                type="button"
                id="theme-toggle"
                class="button theme-toggle"
                aria-pressed="false"
            >🌙 Dark mode</button>
        </div>
    </header>
    <!-- Header Ends -->

    <!-- Navigation Starts -->
    <nav aria-label="Student Navigation">
        <button
            id="menu-toggle"
            type="button"
            aria-label="Toggle navigation"
            aria-expanded="false"
            aria-controls="main-nav"
        >☰ Menu</button>

        <ul id="main-nav">
            <li>
                <a href="dashboard.php" class="active"
                   aria-current="page">Dashboard</a>
            </li>
            <li><a href="profile.html">Profile</a></li>
            <li><a href="myEvents.html">My Events</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <!-- Navigation Ends -->

    <!-- Main Content Starts -->
    <main>
        <section id="welcome">
            <h2>Welcome, <?= $username ?>!</h2>
            <p>
                You have successfully logged into StudentHub.
                Here you can manage your profile, view registered
                events and stay updated with college announcements.
            </p>
        </section>

        <section id="dashboard-cards"
                 aria-labelledby="dashboard-summary">
            <h2 id="dashboard-summary">Dashboard Summary</h2>

            <article class="card">
                <h3>Registered Events</h3>
                <p>3</p>
            </article>

            <article class="card">
                <h3>Upcoming Events</h3>
                <p>6</p>
            </article>

            <article class="card">
                <h3>Announcements</h3>
                <p>5</p>
            </article>
        </section>

        <section id="recent-announcements">
            <h2>Recent Announcements</h2>
            <ul>
                <li>Hackathon registrations are open.</li>
                <li>Sports Meet will begin from August 10.</li>
                <li>Career Guidance Seminar starts next week.</li>
                <li>Library timing extended till 8 PM.</li>
            </ul>
        </section>
    </main>
    <!-- Main Content Ends -->

    <!-- Footer Starts -->
    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
        <p>
            Email:
            <a href="mailto:studenthub@college.edu">
                studenthub@college.edu
            </a>
        </p>
        <p>&copy; 2026 StudentHub. All Rights Reserved.</p>
    </footer>
    <!-- Footer Ends -->

    <script src="Scripts/practical4.js" defer></script>
</body>
</html>
