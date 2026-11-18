<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/db.php';

$username = escapeHtml(
    (string) ($_SESSION['username'] ?? 'Administrator')
);

$totalStudents = 0;
$totalEvents = 0;
$totalUsers = 0;
$totalRegistrations = 0;

$recentEvents = [];
$recentRegistrations = [];
$recentAuditLogs = [];
$auditLogAvailable = false;
$eventStatusAvailable = false;
$dashboardError = false;

try {
    // Fetch live dashboard statistics.
    $totalStudents = (int) $pdo
        ->query('SELECT COUNT(*) FROM students')
        ->fetchColumn();

    $totalEvents = (int) $pdo
        ->query('SELECT COUNT(*) FROM events')
        ->fetchColumn();

    $totalUsers = (int) $pdo
        ->query('SELECT COUNT(*) FROM users')
        ->fetchColumn();

    $totalRegistrations = (int) $pdo
        ->query('SELECT COUNT(*) FROM registrations')
        ->fetchColumn();

    // The status column is added by the Practical 12 migration.
    // Keep the dashboard usable if an older database has not received it yet.
    $eventStatusAvailable = (bool) $pdo
        ->query("SHOW COLUMNS FROM events LIKE 'status'")
        ->fetch(PDO::FETCH_ASSOC);

    $eventStatusSelect = $eventStatusAvailable
        ? 'status'
        : 'NULL AS status';

    $recentEvents = $pdo->query(
        'SELECT event_id, title, event_date, ' . $eventStatusSelect . '
         FROM events
         ORDER BY event_date DESC, event_id DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the latest event registrations.
    $recentRegistrations = $pdo->query(
        'SELECT r.registration_id,
                r.registration_date,
                s.full_name,
                s.enrollment_number,
                e.title AS event_title
         FROM registrations r
         INNER JOIN students s
             ON r.student_id = s.student_id
         INNER JOIN events e
             ON r.event_id = e.event_id
         ORDER BY r.registration_date DESC,
                  r.registration_id DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);

    // Audit logging is an optional feature in existing StudentHub databases.
    // Do not fail the whole dashboard when the optional table is not installed.
    $auditLogAvailable = (bool) $pdo
        ->query("SHOW TABLES LIKE 'audit_logs'")
        ->fetchColumn();

    if ($auditLogAvailable) {
        try {
            $recentAuditLogs = $pdo->query(
                'SELECT audit_id, username, action, entity_type,
                        entity_id, details, created_at
                 FROM audit_logs
                 ORDER BY audit_id DESC
                 LIMIT 10'
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $auditException) {
            error_log(
                'StudentHub optional audit log unavailable: '
                . $auditException->getMessage()
            );
            $auditLogAvailable = false;
            $recentAuditLogs = [];
        }
    }

} catch (Throwable $exception) {
    error_log(
        'StudentHub admin dashboard error: '
        . $exception->getMessage()
    );

    $dashboardError = true;
}

function dashboardEscape(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub | Admin Dashboard</title>

    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/adminDashboard.css">

    <style>
        .dashboard-section {
            margin: 2rem 0;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .dashboard-stat {
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border-color, #d8dce2);
            background: var(--card-background, #ffffff);
            color: var(--text-color, #202124);
        }

        .dashboard-stat h3 {
            margin-top: 0;
            font-size: 1rem;
        }

        .dashboard-stat .stat-number {
            margin-bottom: 0;
            font-size: 2rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .dashboard-table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-top: 1rem;
        }

        .dashboard-table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .dashboard-table-wrap th,
        .dashboard-table-wrap td {
            padding: 0.8rem;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border-color, #d8dce2);
        }

        .dashboard-table-wrap th {
            font-weight: 700;
        }

        .dashboard-empty,
        .dashboard-notice {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .dashboard-notice {
            border: 1px solid #d97706;
        }

        .dashboard-empty {
            border: 1px solid var(--border-color, #d8dce2);
        }

        .dashboard-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .dashboard-status.open {
            background: #dcfce7;
            color: #166534;
        }

        .dashboard-status.closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .dashboard-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        @media (max-width: 600px) {
            .dashboard-table-wrap th,
            .dashboard-table-wrap td {
                padding: 0.55rem;
            }

            .dashboard-stat .stat-number {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>

<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Your Digital Campus Companion</p>
    </div>

    <div id="header-buttons">
        <button type="button"
                id="theme-toggle"
                class="button theme-toggle"
                aria-pressed="false">
            🌙 Dark mode
        </button>
    </div>
</header>

<nav aria-label="Admin Navigation">
    <button id="menu-toggle"
            type="button"
            aria-label="Toggle navigation"
            aria-expanded="false"
            aria-controls="main-nav">
        ☰ Menu
    </button>

    <ul id="main-nav">
        <li>
            <a href="adminDashboard.php"
               class="active"
               aria-current="page">
                Admin Dashboard
            </a>
        </li>
        <li><a href="manageStudents.php">Manage Students</a></li>
        <li><a href="manageEvents.php">Manage Events</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<main>
    <section id="admin-welcome">
        <h2>Welcome, <?= $username ?>!</h2>

        <p>
            Welcome to the StudentHub Admin Panel.
            Monitor student records, college events,
            registrations, and administrator activity.
        </p>

        <div class="dashboard-links">
            <a class="button" href="manageStudents.php">
                Manage Students
            </a>

            <a class="button" href="manageEvents.php">
                Manage Events
            </a>
        </div>
    </section>

    <?php if ($dashboardError): ?>
        <div class="dashboard-notice" role="alert">
            Some dashboard information could not be loaded.
            Please check the database connection and PHP error log.
        </div>
    <?php endif; ?>

    <section id="admin-cards"
             class="dashboard-section"
             aria-labelledby="admin-summary-heading">

        <h2 id="admin-summary-heading">Dashboard Summary</h2>

        <div class="dashboard-stats">
            <article class="dashboard-stat">
                <h3>Total Students</h3>
                <p class="stat-number">
                    <?= number_format($totalStudents) ?>
                </p>
            </article>

            <article class="dashboard-stat">
                <h3>Total Events</h3>
                <p class="stat-number">
                    <?= number_format($totalEvents) ?>
                </p>
            </article>

            <article class="dashboard-stat">
                <h3>Total Users</h3>
                <p class="stat-number">
                    <?= number_format($totalUsers) ?>
                </p>
            </article>

            <article class="dashboard-stat">
                <h3>Event Registrations</h3>
                <p class="stat-number">
                    <?= number_format($totalRegistrations) ?>
                </p>
            </article>
        </div>
    </section>

    <section id="recent-events"
             class="dashboard-section"
             aria-labelledby="recent-events-heading">

        <h2 id="recent-events-heading">Recent Events</h2>

        <div class="dashboard-table-wrap">
            <table>
                <caption>
                    Latest events stored in the StudentHub database
                </caption>

                <thead>
                    <tr>
                        <th scope="col">Event</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$recentEvents): ?>
                        <tr>
                            <td colspan="3">No events found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentEvents as $event): ?>
                            <tr>
                                <td>
                                    <?= dashboardEscape($event['title']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($event['event_date']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($event['status'])): ?>
                                        <span class="dashboard-status <?= dashboardEscape($event['status']) ?>">
                                            <?= dashboardEscape(ucfirst((string) $event['status'])) ?>
                                        </span>
                                    <?php else: ?>
                                        Not configured
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="recent-registrations"
             class="dashboard-section"
             aria-labelledby="recent-registrations-heading">

        <h2 id="recent-registrations-heading">
            Latest Event Registrations
        </h2>

        <div class="dashboard-table-wrap">
            <table>
                <caption>
                    Most recent student registrations for college events
                </caption>

                <thead>
                    <tr>
                        <th scope="col">Student</th>
                        <th scope="col">Enrollment Number</th>
                        <th scope="col">Event</th>
                        <th scope="col">Registration Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$recentRegistrations): ?>
                        <tr>
                            <td colspan="4">
                                No event registrations have been recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentRegistrations as $registration): ?>
                            <tr>
                                <td>
                                    <?= dashboardEscape($registration['full_name']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($registration['enrollment_number']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($registration['event_title']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($registration['registration_date']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="audit-log"
             class="dashboard-section"
             aria-labelledby="audit-log-heading">

        <h2 id="audit-log-heading">Recent Audit Activity</h2>

        <p>
            This section displays recent recorded actions,
            including the administrator, affected record,
            and time of the action.
        </p>

        <div class="dashboard-table-wrap">
            <table>
                <caption>
                    Latest actions recorded in the audit log
                </caption>

                <thead>
                    <tr>
                        <th scope="col">Date and Time</th>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">Entity</th>
                        <th scope="col">Record ID</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$auditLogAvailable): ?>
                        <tr>
                            <td colspan="6">
                                Audit activity is not enabled in this database. The rest of the dashboard works normally.
                            </td>
                        </tr>
                    <?php elseif (!$recentAuditLogs): ?>
                        <tr>
                            <td colspan="6">
                                No audit activity has been recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentAuditLogs as $log): ?>
                            <tr>
                                <td>
                                    <?= dashboardEscape($log['created_at']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($log['username']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($log['action']) ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($log['entity_type']) ?>
                                </td>

                                <td>
                                    <?= $log['entity_id'] === null
                                        ? '—'
                                        : (int) $log['entity_id'] ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($log['details']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
