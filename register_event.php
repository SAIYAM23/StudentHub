<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function registrationRedirect(string $type, string $message): never
{
    $_SESSION['registration_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    // Persist the flash message before redirecting.
    session_write_close();

    header('Location: events.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php');
    exit;
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    if (($_SESSION['role'] ?? '') === 'admin') {
        registrationRedirect('error', 'Administrator accounts cannot register for events.');
    }

    header('Location: login.php?error=login_required');
    exit;
}

$submittedToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['registration_csrf'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(403);
    exit('Invalid security token. Return to the events page and try again.');
}

$eventId = filter_var(
    $_POST['event_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$eventId || $eventId < 1) {
    registrationRedirect('error', 'Invalid event selected.');
}

try {
    $pdo->beginTransaction();

    // Lock the event row while checking eligibility and registering.
    $eventStmt = $pdo->prepare(
        'SELECT event_id, title, event_date, status
         FROM events
         WHERE event_id = ?
         FOR UPDATE'
    );
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $pdo->rollBack();
        registrationRedirect('error', 'The selected event could not be found.');
    }

    if ($event['status'] !== 'open') {
        $pdo->rollBack();
        registrationRedirect('error', 'Registration for this event is closed.');
    }

    if ((string) $event['event_date'] < date('Y-m-d')) {
        $pdo->rollBack();
        registrationRedirect('error', 'You cannot register for an event that has already taken place.');
    }

    $studentStmt = $pdo->prepare(
        'SELECT student_id FROM students WHERE email = ? LIMIT 1'
    );
    $studentStmt->execute([(string) $_SESSION['email']]);
    $studentId = $studentStmt->fetchColumn();

    if ($studentId === false) {
        $pdo->rollBack();
        registrationRedirect('error', 'Your student record was not found. Contact the administrator.');
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO registrations (student_id, event_id)
         VALUES (?, ?)'
    );

    $insertStmt->execute([(int) $studentId, (int) $eventId]);

    $pdo->commit();

    registrationRedirect(
        'success',
        'You have successfully registered for ' . (string) $event['title'] . '!'
    );

} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // The database unique key is the final protection against duplicates.
    if (($exception->errorInfo[1] ?? null) === 1062) {
        registrationRedirect('info', 'You are already registered for this event.');
    }

    error_log('StudentHub event registration error: ' . $exception->getMessage());
    registrationRedirect('error', 'Registration failed. Please try again later.');

} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('StudentHub event registration error: ' . $exception->getMessage());
    registrationRedirect('error', 'Something went wrong. Please try again.');
}
