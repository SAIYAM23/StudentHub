<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Registration | StudentHub</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</head>
<body>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Practical 9 — Secure Registration</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle" class="button theme-toggle"
                aria-pressed="false">🌙 Dark mode</button>
    </div>
</header>
<main>
    <section id="register-box">
        <h1>Create StudentHub Account</h1>

        <form action="practical9_register.php" method="POST">
            <p>
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       minlength="3" maxlength="50" required>
            </p>

            <p>
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       maxlength="150" required>
            </p>

            <p>
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       minlength="8" required>
            </p>

            <p>
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password"
                       name="confirm_password" minlength="8" required>
            </p>

            <button type="submit">Register</button>
        </form>

        <p><a href="login.php">Back to login</a></p>
    </section>
</main>
</body>
</html>

<?php exit; endif;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed.');
}

$username = trim((string) ($_POST['username'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$errors = [];

if (!is_string($password) || !is_string($confirmPassword)) {
    $errors[] = 'Invalid password input.';
    $password = '';
    $confirmPassword = '';
}

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    $errors[] = 'Username must be 3–50 characters using letters, numbers, or underscores.';
}

if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must contain at least 8 characters.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!$errors) {
    try {
        $mysqli = new mysqli('localhost', 'root', '', 'studenthub');
        $mysqli->set_charset('utf8mb4');

        $check = $mysqli->prepare(
            'SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $check->bind_param('ss', $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = 'Username or email is already registered.';
        }

        $check->close();

        if (!$errors) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $mysqli->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
            );
            $insert->bind_param('sss', $username, $email, $passwordHash);
            $insert->execute();
            $insert->close();

            $success = 'Registration completed successfully.';
        }

        $mysqli->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Practical 9 registration error: ' . $e->getMessage());
        http_response_code(500);
        $errors[] = 'Registration could not be completed. Please try again.';
    }
}

if ($errors) {
    http_response_code(422);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Result | StudentHub</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</head>
<body>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Practical 9 — Registration Result</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle" class="button theme-toggle"
                aria-pressed="false">🌙 Dark mode</button>
    </div>
</header>
<main>
    <section id="register-box">
        <h1>Registration Result</h1>

        <?php if (isset($success)): ?>
            <p role="status"><?= h($success) ?></p>
            <p><a href="practical9_register.php">Register another account</a></p>
        <?php else: ?>
            <h2>Please correct the following errors</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <p><a href="practical9_register.php">Return to registration</a></p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
