<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireLogin();

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: adminDashboard.php');
    exit;
}

require_once __DIR__ . '/db.php';

$username = (string) ($_SESSION['username'] ?? 'Student');
$studentId = null;
$registeredEvents = 0;
$upcomingEventsCount = 0;
$upcomingEvents = [];
$dashboardError = false;

try {
    $studentQuery = $pdo->prepare(
        'SELECT student_id, full_name
         FROM students
         WHERE email = :email
         LIMIT 1'
    );
    $studentQuery->execute([':email' => (string) $_SESSION['email']]);
    $student = $studentQuery->fetch();

    if ($student) {
        $studentId = (int) $student['student_id'];
        $username = (string) $student['full_name'];

        $registrationQuery = $pdo->prepare(
            'SELECT COUNT(*)
             FROM registrations
             WHERE student_id = :student_id'
        );
        $registrationQuery->execute([':student_id' => $studentId]);
        $registeredEvents = (int) $registrationQuery->fetchColumn();
    }

    $upcomingEventsCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM events WHERE event_date >= CURRENT_DATE()'
    )->fetchColumn();

    $upcomingEvents = $pdo->query(
        'SELECT title, event_date, venue
         FROM events
         WHERE event_date >= CURRENT_DATE()
         ORDER BY event_date ASC, event_id ASC
         LIMIT 5'
    )->fetchAll();
} catch (Throwable $exception) {
    error_log('StudentHub student dashboard error: ' . $exception->getMessage());
    $dashboardError = true;
}

$username = escapeHtml($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Dashboard</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/dashboard.css">
    <style>
        .dashboard-event-list { list-style: none; padding: 0; margin: 0; }
        .dashboard-event-list li { padding: 0.85rem 0; border-bottom: 1px solid #ddd; }
        .dashboard-event-list li:last-child { border-bottom: 0; }
        .dashboard-event-list strong { display: block; margin-bottom: 0.25rem; }
        .dashboard-event-meta { font-size: 0.95rem; opacity: 0.85; }
        .dashboard-empty { padding: 1rem; border: 1px dashed #aaa; border-radius: 6px; }
        .dashboard-error { padding: 1rem; border: 1px solid #b91c1c; border-radius: 6px; }
    </style>
</head>
<body>
    <header>
        <div id="logo">
            <h1>StudentHub</h1>
            <p>Your Digital Campus Companion</p>
        </div>
        <div id="header-buttons">
            <button type="button" id="theme-toggle" class="button theme-toggle" aria-pressed="false">🌙 Dark mode</button>
        </div>
    </header>

    <?php require __DIR__ . '/includes/student_nav.php'; ?>

    <main>
        <section id="welcome">
            <h2>Welcome, <?= $username ?>!</h2>
            <p>
                This dashboard shows your StudentHub activity, registered events
                and upcoming campus events associated with your account.
            </p>
        </section>

        <?php if ($dashboardError): ?>
            <p class="dashboard-error" role="alert">
                Some dashboard information could not be loaded. Please try again later.
            </p>
        <?php else: ?>
            <section id="dashboard-cards" aria-labelledby="dashboard-summary">
                <h2 id="dashboard-summary">Your Dashboard Summary</h2>
                <article class="card">
                    <h3>Registered Events</h3>
                    <p><?= $registeredEvents ?></p>
                </article>
                <article class="card">
                    <h3>Upcoming College Events</h3>
                    <p><?= $upcomingEventsCount ?></p>
                </article>
                <article class="card">
                    <h3>Your Account</h3>
                    <p><?= $studentId !== null ? 'Active' : 'Pending' ?></p>
                </article>
            </section>

            <section id="recent-announcements">
                <h2>Upcoming Campus Events</h2>
                <?php if ($upcomingEvents === []): ?>
                    <p class="dashboard-empty">There are currently no upcoming events.</p>
                <?php else: ?>
                    <ul class="dashboard-event-list">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <li>
                                <strong><?= escapeHtml((string) $event['title']) ?></strong>
                                <span class="dashboard-event-meta">
                                    <?= escapeHtml(date('d M Y', strtotime((string) $event['event_date']))) ?>
                                    &nbsp;|&nbsp;
                                    <?= escapeHtml((string) $event['venue']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <p><a href="events.php" class="button">Explore Events</a></p>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
<p>&copy; 2026 StudentHub. All Rights Reserved.</p>
    </footer>

    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>
