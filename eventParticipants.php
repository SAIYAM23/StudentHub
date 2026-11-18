<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/db.php';

function participantsEscape(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$eventId = filter_var(
    $_GET['event_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$eventStmt = $pdo->prepare(
    'SELECT event_id, title, event_date, venue, category, status
     FROM events
     WHERE event_id = ?
     LIMIT 1'
);
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

$participantsStmt = $pdo->prepare(
    'SELECT s.full_name, s.enrollment_number, s.email,
            s.department, s.semester, r.registration_date
     FROM registrations r
     INNER JOIN students s ON s.student_id = r.student_id
     WHERE r.event_id = ?
     ORDER BY r.registration_date ASC, r.registration_id ASC'
);
$participantsStmt->execute([$eventId]);
$participants = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);

$username = (string) ($_SESSION['username'] ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Event Participants</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/eventParticipants.css">
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
            aria-label="Toggle navigation"
            aria-expanded="false" aria-controls="main-nav">
        ☰ Menu
    </button>

    <ul id="main-nav">
        <li><a href="adminDashboard.php">Admin Dashboard</a></li>
        <li><a href="manageStudents.php">Manage Students</a></li>
        <li><a href="manageEvents.php" class="active">Manage Events</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<main>
    <section id="participants-title">
        <h2>Event Participants</h2>
        <p>Welcome, <?= participantsEscape($username) ?>!</p>
    </section>

    <section id="participants-details">
        <h2><?= participantsEscape($event['title']) ?></h2>

        <p><strong>Date:</strong>
            <?= participantsEscape(date('d M Y', strtotime((string) $event['event_date']))) ?>
        </p>

        <p><strong>Venue:</strong> <?= participantsEscape($event['venue']) ?></p>
        <p><strong>Category:</strong> <?= participantsEscape($event['category']) ?></p>
        <p><strong>Registration status:</strong>
            <?= participantsEscape(ucfirst((string) $event['status'])) ?>
        </p>

        <p class="participant-count">
            Total registered students: <strong><?= count($participants) ?></strong>
        </p>
    </section>

    <section id="participants-list">
        <h2>Registered Students</h2>

        <?php if (!$participants): ?>
            <p>No students have registered for this event yet.</p>
        <?php else: ?>
            <div class="participants-table-wrap">
                <table>
                    <caption>Students registered for <?= participantsEscape($event['title']) ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Enrollment No.</th>
                            <th scope="col">Student Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Department</th>
                            <th scope="col">Semester</th>
                            <th scope="col">Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $index => $participant): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= participantsEscape($participant['enrollment_number']) ?></td>
                                <td><?= participantsEscape($participant['full_name']) ?></td>
                                <td><?= participantsEscape($participant['email']) ?></td>
                                <td><?= participantsEscape($participant['department']) ?></td>
                                <td><?= participantsEscape($participant['semester']) ?></td>
                                <td><?= participantsEscape(date(
                                    'd M Y, h:i A',
                                    strtotime((string) $participant['registration_date'])
                                )) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <p class="participants-back">
        <a href="manageEvents.php" class="button">Back to Manage Events</a>
    </p>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> StudentHub</p>
</footer>

<script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>
