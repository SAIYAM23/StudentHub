<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$role = (string) ($_SESSION['role'] ?? '');

$homeStats = [
    'students' => 0,
    'upcoming_events' => 0,
    'registrations' => 0,
];
$upcomingEvents = [];
$homeDataAvailable = true;

try {
    $homeStats['students'] = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $homeStats['upcoming_events'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status = 'open'"
    )->fetchColumn();
    $homeStats['registrations'] = (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();

    $upcomingStmt = $pdo->query(
        "SELECT event_id, title, description, event_date, venue, category
         FROM events
         WHERE event_date >= CURDATE() AND status = 'open'
         ORDER BY event_date ASC, event_id ASC
         LIMIT 3"
    );
    $upcomingEvents = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    error_log('StudentHub homepage data error: ' . $exception->getMessage());
    $homeDataAvailable = false;
    $homeStats = ['students' => 0, 'upcoming_events' => 0, 'registrations' => 0];
    $upcomingEvents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Find college events and campus activities with StudentHub.">
    <title>StudentHub | Home</title>
    <link rel="stylesheet" href="Styles/common.css?v=site-shell-4">
    <link rel="stylesheet" href="Styles/home.css?v=home-simple-1">
</head>
<body class="home-page">
    <?php require __DIR__ . '/includes/site_header.php'; ?>
    <?php require __DIR__ . '/includes/site_navigation.php'; ?>

    <main class="home-main">
        <section class="home-intro" aria-labelledby="home-title">
            <p class="home-eyebrow">WELCOME TO STUDENTHUB</p>
            <h1 id="home-title">What's happening on campus?</h1>
            <p class="home-intro-text">Find upcoming college events, check the details and register for the activities you are interested in. StudentHub keeps the information in one place.</p>
            <a href="events.php" class="button home-events-button">Browse events</a>
        </section>

        <section class="home-section home-stats" aria-labelledby="home-stats-heading">
            <div class="section-heading">
                <h2 id="home-stats-heading">StudentHub at a glance</h2>
                <p>Current figures from the StudentHub database.</p>
            </div>
            <?php if ($homeDataAvailable): ?>
                <div class="stats-container">
                    <article class="stat-card">
                        <span class="stat-value"><?= number_format($homeStats['students']) ?></span>
                        <span class="stat-label">Registered students</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-value"><?= number_format($homeStats['upcoming_events']) ?></span>
                        <span class="stat-label">Upcoming open events</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-value"><?= number_format($homeStats['registrations']) ?></span>
                        <span class="stat-label">Event registrations</span>
                    </article>
                </div>
            <?php else: ?>
                <p class="home-empty-state">Statistics are temporarily unavailable. Please try again later.</p>
            <?php endif; ?>
        </section>

        <section class="home-section" aria-labelledby="featured-events-heading">
            <div class="section-heading section-heading-row">
                <div>
                    <h2 id="featured-events-heading">Upcoming events</h2>
                    <p>Events currently open for registration.</p>
                </div>
                <a href="events.php" class="text-link">View all events</a>
            </div>

            <?php if (!$homeDataAvailable): ?>
                <p class="home-empty-state">Upcoming events could not be loaded right now. Please try again later.</p>
            <?php elseif ($upcomingEvents): ?>
                <div class="event-container">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <article class="event-card">
                            <p class="event-category"><?= escapeHtml((string) ($event['category'] ?? 'General')) ?></p>
                            <h3><?= escapeHtml((string) $event['title']) ?></h3>
                            <p class="event-date"><time datetime="<?= escapeHtml((string) $event['event_date']) ?>"><?= escapeHtml(date('d M Y', strtotime((string) $event['event_date']))) ?></time></p>
                            <?php if (!empty($event['description'])): ?>
                                <p class="event-description"><?= escapeHtml((string) $event['description']) ?></p>
                            <?php endif; ?>
                            <div class="event-card-footer">
                                <span><?= escapeHtml((string) $event['venue']) ?></span>
                                <a href="events.php">View details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="home-empty-state">
                    <h3>No upcoming events at the moment</h3>
                    <p>There are currently no open events. Please check again later.</p>
                    <a href="events.php" class="button">View events page</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="home-how-it-works" aria-labelledby="how-heading">
            <h2 id="how-heading">Using StudentHub</h2>
            <div class="how-steps">
                <div><h3>Find an event</h3><p>Browse the events page to see dates, venues and descriptions.</p></div>
                <div><h3>Register</h3><p>Log in to your student account and register for an open event.</p></div>
                <div><h3>Keep track</h3><p>Visit My Events to review the events you have joined.</p></div>
            </div>
        </section>
    </main>

    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
        <p>Connecting students with college events, announcements and campus activities.</p>
<p>&copy; <?= date('Y') ?> StudentHub. All Rights Reserved.</p>
    </footer>

    <script src="Scripts/practical4.js?v=site-shell-4" defer></script>
</body>
</html>
