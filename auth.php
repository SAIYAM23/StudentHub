<?php
declare(strict_types=1);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_name('STUDENTHUB_SESSION');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/StudentHub/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php?error=login_required');
        exit;
    }

    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

    if ($lastActivity === 0 || time() - $lastActivity > 900) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();

        header('Location: login.php?error=timeout');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('403 Forbidden: You are not authorized to access this page.');
    }
}
