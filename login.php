<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$error = '';

$messages = [
    'login_required' => 'Please log in to continue.',
    'timeout' => 'Your session expired. Please log in again.',
    'invalid' => 'Invalid email or password.',
];

if (isset($_GET['error'])) {
    $error = $messages[(string) $_GET['error']] ?? '';
}

if (!empty($_SESSION['user_id'])) {
    $destination = ($_SESSION['role'] ?? '') === 'admin'
        ? 'adminDashboard.php'
        : 'dashboard.php';

    header('Location: ' . $destination);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $submittedPassword = $_POST['password'] ?? '';

    if (
        !is_string($submittedPassword) ||
        $submittedPassword === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150 ||
        strlen($submittedPassword) > 4096
    ) {
        $error = 'Please enter a valid email and password.';
    } else {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $mysqli = new mysqli('localhost', 'root', '', 'studenthub');
            $mysqli->set_charset('utf8mb4');

            $stmt = $mysqli->prepare(
                'SELECT u.user_id,
                        COALESCE(s.full_name, u.username) AS username,
                        u.email, u.password_hash, u.role
                 FROM users AS u
                 LEFT JOIN students AS s ON s.email = u.email
                 WHERE u.email = ?
                 LIMIT 1'
            );

            $stmt->bind_param('s', $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();
            $mysqli->close();

            if (
                $user &&
                password_verify(
                    $submittedPassword,
                    $user['password_hash']
                )
            ) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                $destination = $user['role'] === 'admin'
                    ? 'adminDashboard.php'
                    : 'dashboard.php';

                header('Location: ' . $destination);
                exit;
            }

            $error = 'Invalid email or password.';
        } catch (Throwable $exception) {
            error_log(
                'StudentHub login error: ' . $exception->getMessage()
            );

            http_response_code(500);
            $error = 'Login is temporarily unavailable. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Login</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/login.css?v=login-layout-2">
</head>
<body class="login-page">

    <!-- Header Starts -->
    <?php require __DIR__ . '/includes/site_header.php'; ?>
    <!-- Header Ends -->

    <!-- Navigation Starts -->
    <?php require __DIR__ . '/includes/site_navigation.php'; ?>
    <!-- Navigation Ends -->

    <!-- Main Content Starts -->
    <main>
        <section id="login-box" aria-labelledby="login-heading">
            <h2 id="login-heading">Student Login</h2>

            <?php if ($error !== ''): ?>
                <p class="error-message" role="alert">
                    <?= escapeHtml($error) ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div>
                    <label for="idEmail">Email:</label>
                    <input
                        type="email"
                        id="idEmail"
                        name="email"
                        placeholder="Enter Email"
                        autocomplete="username"
                        maxlength="150"
                        required
                    >
                </div>

                <div>
                    <label for="pass">Password:</label>
                    <input
                        type="password"
                        id="pass"
                        name="password"
                        placeholder="Enter Password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="login-actions">
                    <input
                        type="submit"
                        id="submitbtn"
                        value="Login"
                        class="button"
                    >
                    <a href="register.php" class="button">Register</a>
                </div>
            </form>

            <p class="admin-link">
                <strong>Administrator?</strong>
                Sign in here using your administrator account.
            </p>
        </section>
    </main>
    <!-- Main Content Ends -->

    <!-- Footer Starts -->
    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
<p>&copy; 2026 StudentHub. All Rights Reserved.</p>
    </footer>
    <!-- Footer Ends -->

    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>
