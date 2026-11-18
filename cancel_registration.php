<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('student');
require_once __DIR__ . '/db.php';

function cancellationRedirect(string $type, string $message): never
{
    $_SESSION['my_events_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    session_write_close();

    header('Location: myEvents.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: myEvents.php', true, 303);
    exit;
}

// Validate the CSRF token.
$submittedToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['registration_csrf'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(403);
    exit('Invalid security token. Return to My Events and try again.');
}

// Validate the registration ID.
$registrationId = filter_var(
    $_POST['registration_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$registrationId || $registrationId < 1) {
    cancellationRedirect('error', 'Invalid registration selected.');
}

try {
    // Find the student associated with the logged-in account.
    $studentStmt = $pdo->prepare(
        'SELECT student_id FROM students WHERE email = ? LIMIT 1'
    );
    $studentStmt->execute([(string) $_SESSION['email']]);
    $studentId = $studentStmt->fetchColumn();

    if ($studentId === false) {
        cancellationRedirect('error', 'Your student record could not be found.');
    }

    // Delete only this student's registration, and only if the event
    // is today or in the future. The event itself is never deleted.
    $deleteStmt = $pdo->prepare(
        'DELETE r
         FROM registrations r
         INNER JOIN events e ON e.event_id = r.event_id
         WHERE r.registration_id = ?
           AND r.student_id = ?
           AND e.event_date >= CURRENT_DATE'
    );

    $deleteStmt->execute([
        (int) $registrationId,
        (int) $studentId,
    ]);

    if ($deleteStmt->rowCount() === 1) {
        cancellationRedirect(
            'success',
            'Your event registration has been cancelled successfully.'
        );
    }

    cancellationRedirect(
        'info',
        'This registration could not be cancelled. It may already be cancelled or the event may have passed.'
    );

} catch (Throwable $exception) {
    error_log('StudentHub cancellation error: ' . $exception->getMessage());

    cancellationRedirect(
        'error',
        'Cancellation failed. Please try again later.'
    );
}
