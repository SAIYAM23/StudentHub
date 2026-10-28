<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');

$username = escapeHtml(
    (string) ($_SESSION['username'] ?? 'Administrator')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Admin Dashboard</title>
    <link rel="stylesheet" href="Styles/common.css">
    <link rel="stylesheet" href="Styles/adminDashboard.css">
</head>
<body>

    <!-- Header Starts -->
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
    <!-- Header Ends -->

    <!-- Navigation Starts -->
    <nav aria-label="Admin Navigation">
        <button id="menu-toggle" type="button"
                aria-label="Toggle navigation" aria-expanded="false"
                aria-controls="main-nav">☰ Menu</button>

	<ul id="main-nav">
	    <li><a href="adminDashboard.php" class="active"
		   aria-current="page">Admin Dashboard</a></li>
	    <li><a href="manageStudents.php">Manage Students</a></li>
	    <li><a href="manageEvents.html">Manage Events</a></li>
	    <li><a href="logout.php">Logout</a></li>
	</ul>

    </nav>
    <!-- Navigation Ends -->

    <!-- Main Content Starts -->
    <main>
        <section id="admin-welcome">
            <h2>Welcome, <?= $username ?>!</h2>
            <p>
                Welcome to the StudentHub Admin Panel.
                Here you can manage college events and monitor
                the StudentHub portal.
            </p>
        </section>

        <section id="admin-cards"
                 aria-labelledby="admin-summary-heading">
            <h2 id="admin-summary-heading">Dashboard Summary</h2>

            <article class="admin-card">
                <h3>Total Students</h3>
                <p>350</p>
            </article>

            <article class="admin-card">
                <h3>Total Events</h3>
                <p>6</p>
            </article>

            <article class="admin-card">
                <h3>Registered Students</h3>
                <p>215</p>
            </article>
        </section>

        <section id="recent-events">
            <h2>Recent Events</h2>
            <table>
                <caption>
                    Recent StudentHub events and their current status
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Event</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Tech Fest</td>
                        <td>05 Aug 2026</td>
                        <td>Open</td>
                    </tr>
                    <tr>
                        <td>Hackathon</td>
                        <td>18 Aug 2026</td>
                        <td>Open</td>
                    </tr>
                    <tr>
                        <td>Sports Meet</td>
                        <td>25 Aug 2026</td>
                        <td>Upcoming</td>
                    </tr>
                </tbody>
            </table>
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
