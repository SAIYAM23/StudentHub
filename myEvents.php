<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('student');
require_once __DIR__ . '/db.php';

function myEventsEscape(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$registeredEvents = [];
$eventsError = false;
$studentFound = false;

// Reuse the same CSRF token as event registration.
if (empty($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

// Read and clear the cancellation feedback message.
$myEventsFlash = $_SESSION['my_events_flash'] ?? null;
unset($_SESSION['my_events_flash']);

try {
    $studentStmt = $pdo->prepare(
        'SELECT student_id FROM students WHERE email = ? LIMIT 1'
    );
    $studentStmt->execute([(string) $_SESSION['email']]);
    $studentId = $studentStmt->fetchColumn();

    if ($studentId !== false) {
        $studentFound = true;

        $eventsStmt = $pdo->prepare(
            'SELECT r.registration_id, e.event_id, e.title, e.event_date, e.venue,
                    e.category, e.status, r.registration_date
             FROM registrations r
             INNER JOIN events e ON e.event_id = r.event_id
             WHERE r.student_id = ?
             ORDER BY e.event_date ASC, r.registration_date DESC'
        );

        $eventsStmt->execute([(int) $studentId]);
        $registeredEvents = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Throwable $exception) {
    error_log('StudentHub My Events error: ' . $exception->getMessage());
    $eventsError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | My Events</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/myEvents.css?v=full-theme-3">
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

<?php require __DIR__ . '/includes/student_nav.php'; ?>

<main>
    <section id="events-title">
        <h2>My Registered Events</h2>
        <p>This list contains only the events registered using your StudentHub account.</p>
    </section>

    <?php if (is_array($myEventsFlash)): ?>
        <?php
            $flashType = in_array(
                $myEventsFlash['type'] ?? '',
                ['success', 'error', 'info'],
                true
            ) ? $myEventsFlash['type'] : 'info';
        ?>
        <p class="my-events-message <?= myEventsEscape($flashType) ?>"
           role="status">
            <?= myEventsEscape($myEventsFlash['message'] ?? '') ?>
        </p>
    <?php endif; ?>

    <section aria-labelledby="registered-events-heading">
        <h2 id="registered-events-heading">Registered Events</h2>

        <?php if ($eventsError): ?>
            <p role="alert">Your event registrations could not be loaded. Please try again later.</p>

        <?php elseif (!$studentFound): ?>
            <p>No student record is linked to this account yet.</p>

        <?php elseif ($registeredEvents === []): ?>
            <p>You have not registered for any events yet.</p>
            <p><a href="events.php" class="button">Explore College Events</a></p>

        <?php else: ?>
            <table id="events-table">
                <caption>Events registered by your account</caption>
                <thead>
                    <tr>
                        <th scope="col">Event Name</th>
                        <th scope="col">Category</th>
                        <th scope="col">Event Date</th>
                        <th scope="col">Venue</th>
                        <th scope="col">Registered On</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($registeredEvents as $event): ?>
                        <?php
                            $isPast = (string) $event['event_date'] < date('Y-m-d');

                            if ($isPast) {
                                $eventStatus = 'Completed';
                            } elseif ($event['status'] === 'closed') {
                                $eventStatus = 'Registration Closed';
                            } else {
                                $eventStatus = 'Upcoming';
                            }
                        ?>
                        <tr>
                            <td><?= myEventsEscape($event['title']) ?></td>
                            <td><?= myEventsEscape($event['category']) ?></td>
                            <td><?= myEventsEscape(date(
                                'd M Y',
                                strtotime((string) $event['event_date'])
                            )) ?></td>
                            <td><?= myEventsEscape($event['venue']) ?></td>
                            <td><?= myEventsEscape(date(
                                'd M Y',
                                strtotime((string) $event['registration_date'])
                            )) ?></td>
                            <td><?= myEventsEscape($eventStatus) ?></td>
                            <td>
                                <?php if (!$isPast): ?>
                                    <form action="cancel_registration.php"
                                          method="POST"
                                          class="cancel-registration-form"
                                          data-event-title="<?= myEventsEscape($event['title']) ?>"
                                          data-confirm-title="Cancel event registration?"
                                          data-confirm-message="Are you sure you want to cancel your registration for {event}? This action cannot be undone."
                                          data-confirm-confirm-label="Yes, cancel registration"
                                          data-confirm-cancel-label="Keep registration">
                                        <input type="hidden"
                                               name="registration_id"
                                               value="<?= (int) $event['registration_id'] ?>">
                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= myEventsEscape($_SESSION['registration_csrf']) ?>">
                                        <button type="submit" class="cancel-button">
                                            Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span>—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div id="back-button">
        <a href="dashboard.php" class="button">Back to Dashboard</a>
    </div>
</main>



<footer>
    <h2>StudentHub</h2>
    <p>Your Digital Campus Companion</p>
<p>&copy; <?= date('Y') ?> StudentHub. All Rights Reserved.</p>
</footer>

<script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
<script src="Scripts/confirmationDialog.js?v=shared-confirm-2" defer></script>
</body>
</html>
