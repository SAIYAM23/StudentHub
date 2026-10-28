<?php
declare(strict_types=1);

/**
 * Record a successful action in the StudentHub audit log.
 *
 * Logging failures are written to the PHP error log and do not
 * interrupt the main student or event management operation.
 */
function writeAuditLog(
    string $action,
    string $entityType,
    ?int $entityId = null,
    ?string $details = null
): void {
    try {
        require_once __DIR__ . '/auth.php';

        // Use the connection from the global scope if it was
        // already initialized by the calling page.
        global $mysqli;

        require_once __DIR__ . '/db_mysqli.php';

        if (!($mysqli instanceof mysqli)) {
            throw new RuntimeException(
                'The MySQLi database connection is not available.'
            );
        }

        $userId = isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : null;

        $username = substr(
            (string) ($_SESSION['username'] ?? 'Unknown'),
            0,
            50
        );

        $action = substr($action, 0, 50);
        $entityType = substr($entityType, 0, 50);

        if ($details !== null) {
            $details = substr($details, 0, 500);
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($ipAddress !== null) {
            $ipAddress = substr($ipAddress, 0, 45);
        }

        $stmt = $mysqli->prepare(
            'INSERT INTO audit_logs
                (user_id, username, action, entity_type,
                 entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'isssiss',
            $userId,
            $username,
            $action,
            $entityType,
            $entityId,
            $details,
            $ipAddress
        );

        $stmt->execute();
        $stmt->close();
    } catch (Throwable $exception) {
        error_log(
            'StudentHub audit logging failed: '
            . $exception->getMessage()
        );
    }
}
