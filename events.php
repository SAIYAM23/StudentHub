<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$role = (string) ($_SESSION['role'] ?? '');
$isStudent = $isLoggedIn && $role === 'student';
$isAdmin = $isLoggedIn && $role === 'admin';

function eventsPageEscape(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

if (empty($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['registration_flash'] ?? null;
unset($_SESSION['registration_flash']);

$studentId = null;
$registeredEventIds = [];
$studentLookupError = false;

try {
    if ($isStudent) {
        $studentStmt = $pdo->prepare(
            'SELECT student_id FROM students WHERE email = ? LIMIT 1'
        );
        $studentStmt->execute([(string) $_SESSION['email']]);
        $foundId = $studentStmt->fetchColumn();

        if ($foundId !== false) {
            $studentId = (int) $foundId;

            $registrationStmt = $pdo->prepare(
                'SELECT event_id FROM registrations WHERE student_id = ?'
            );
            $registrationStmt->execute([$studentId]);

            $registeredEventIds = array_map(
                'intval',
                $registrationStmt->fetchAll(PDO::FETCH_COLUMN)
            );
        } else {
            $studentLookupError = true;
        }
    }

    $events = $pdo->query(
        'SELECT event_id, title, description, event_date, venue,
                poster_filename, category, status
         FROM events
         ORDER BY event_date ASC, event_id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {
    error_log('StudentHub events page error: ' . $exception->getMessage());
    $events = [];
    $studentLookupError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Events</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/events.css?v=full-theme-4">
</head>
<body>

<?php require __DIR__ . '/includes/site_header.php'; ?>

<?php if ($isStudent): ?>
    <?php require __DIR__ . '/includes/student_nav.php'; ?>
<?php else: ?>
<?php require __DIR__ . '/includes/site_navigation.php'; ?>
<?php endif; ?>

<main>
    <section id="event-intro">
        <h2>College Events</h2>
        <p>
            StudentHub keeps students updated with all technical,
            cultural and sports events conducted in the college.
            Explore upcoming events and participate according to
            your interests.
        </p>
    </section>

    <?php if (is_array($flash)): ?>
        <div class="registration-message <?= eventsPageEscape($flash['type'] ?? 'info') ?>"
             role="<?= ($flash['type'] ?? '') === 'error' ? 'alert' : 'status' ?>">
            <?= eventsPageEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <?php if ($studentLookupError && $isStudent): ?>
        <p class="registration-message error" role="alert">
            Your student record could not be loaded. Please contact the administrator.
        </p>
    <?php endif; ?>

    <section id="event-container" aria-label="College events">
        <h2 class="visually-hidden">College Events</h2>

        <?php if (!$events): ?>
            <p class="event-empty">No events are currently available.</p>
        <?php else: ?>

            <?php foreach ($events as $event): ?>
                <?php
                    $eventId = (int) $event['event_id'];
                    $isPast = (string) $event['event_date'] < date('Y-m-d');
                    $isRegistered = in_array($eventId, $registeredEventIds, true);
                    $canRegister = $isStudent
                        && !$studentLookupError
                        && !$isPast
                        && $event['status'] === 'open'
                        && !$isRegistered;
                ?>

                <article class="event-card">
                    <?php if (!empty($event['poster_filename'])): ?>
                        <img
                            class="event-card-poster"
                            src="event_poster.php?id=<?= $eventId ?>"
                            alt="Poster for <?= eventsPageEscape($event['title']) ?>"
                            loading="lazy">
                    <?php endif; ?>

                    <h3><?= eventsPageEscape($event['title']) ?></h3>

                    <p>
                        <strong>Date:</strong>
                        <?= eventsPageEscape(date('d M Y', strtotime((string) $event['event_date']))) ?>
                    </p>

                    <p>
                        <strong>Venue:</strong>
                        <?= eventsPageEscape($event['venue']) ?>
                    </p>

                    <p>
                        <strong>Category:</strong>
                        <?= eventsPageEscape($event['category']) ?>
                    </p>

                    <?php if (!empty($event['description'])): ?>
                        <p><?= nl2br(eventsPageEscape($event['description'])) ?></p>
                    <?php endif; ?>

                    <?php if ($isRegistered): ?>
                        <span class="event-registration-status registered">
                            Already Registered
                        </span>

                    <?php elseif ($isPast): ?>
                        <span class="event-registration-status closed">
                            Event Completed
                        </span>

                    <?php elseif ($event['status'] === 'closed'): ?>
                        <span class="event-registration-status closed">
                            Registration Closed
                        </span>

                    <?php elseif ($canRegister): ?>
                        <form action="register_event.php" method="post">
                            <input type="hidden" name="csrf_token"
                                   value="<?= eventsPageEscape($_SESSION['registration_csrf']) ?>">
                            <input type="hidden" name="event_id"
                                   value="<?= $eventId ?>">
                            <button type="submit" class="button">
                                Register Now
                            </button>
                        </form>

                    <?php elseif ($isAdmin): ?>
                        <span class="event-registration-status">
                            Admin accounts cannot register
                        </span>

                    <?php else: ?>
                        <a href="login.php?error=login_required" class="button">
                            Login to Register
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>
</main>

<footer>
    <h2>StudentHub</h2>
    <p>Your Digital Campus Companion</p>
<p>&copy; <?= date('Y') ?> StudentHub. All Rights Reserved.</p>
</footer>

<script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>
