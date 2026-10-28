<?php
declare(strict_types=1);

session_start();

$errors = [];
$success = '';
$storageDirectory = __DIR__ . '/storage';
$csvFile = $storageDirectory . '/registrations.csv';

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function allowedValue(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed. Please submit the registration form.');
}

$enrollment = postString('enrollment_number');
$email = postString('email');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$fullName = postString('full_name');
$dob = postString('date_of_birth');
$gender = postString('gender');
$mobile = postString('mobile');
$department = postString('department');
$semester = postString('semester');

$interests = $_POST['interests'] ?? [];
$eventPreferences = $_POST['event_preferences'] ?? [];

if (!is_string($password) || !is_string($confirmPassword)) {
    $errors[] = 'Invalid password input.';
    $password = '';
    $confirmPassword = '';
}

if (!preg_match('/^\d{2}[A-Za-z]{2}\d{3}$/', $enrollment)) {
    $errors[] = 'Enter a valid enrollment number, for example 25CE001.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^\d{2}[A-Za-z]{2}\d{3}@charusat\.edu\.in$/i', $email)) {
    $errors[] = 'Use a valid CHARUSAT email address.';
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    $errors[] = 'Password must have at least 8 characters, uppercase, lowercase, and a number.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/", $fullName)) {
    $errors[] = 'Enter a valid full name.';
}

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
$dateErrors = DateTimeImmutable::getLastErrors();

if (!$date ||
    ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
    $date->format('Y-m-d') !== $dob ||
    $date > new DateTimeImmutable('today')) {
    $errors[] = 'Enter a valid date of birth that is not in the future.';
}

$allowedGenders = ['male', 'female', 'other', 'prefer-not-to-say'];
if (!allowedValue($gender, $allowedGenders)) {
    $errors[] = 'Select a valid gender option.';
}

if (!preg_match('/^(?:\+91[- ]?)?[6-9]\d{9}$/', $mobile)) {
    $errors[] = 'Enter a valid Indian mobile number.';
}

$allowedDepartments = [
    'computer-engineering',
    'computer-science',
    'information-technology',
    'mechanical-engineering',
    'civil-engineering',
    'electrical-engineering',
    'electronics-communication'
];

if (!allowedValue($department, $allowedDepartments)) {
    $errors[] = 'Select a valid department.';
}

if (!in_array($semester, ['1', '2', '3', '4', '5', '6', '7', '8'], true)) {
    $errors[] = 'Select a valid semester.';
}

$allowedInterests = [
    'web-development',
    'artificial-intelligence',
    'machine-learning',
    'cyber-security',
    'iot',
    'data-science'
];

$allowedEvents = ['technical', 'non-technical', 'cultural', 'sports'];

if (!is_array($interests) ||
    count(array_diff($interests, $allowedInterests)) > 0) {
    $errors[] = 'Invalid area of interest selected.';
    $interests = [];
}

if (!is_array($eventPreferences) ||
    count(array_diff($eventPreferences, $allowedEvents)) > 0) {
    $errors[] = 'Invalid event preference selected.';
    $eventPreferences = [];
}

if (($_POST['terms'] ?? '') !== 'accepted' ||
    ($_POST['privacy'] ?? '') !== 'accepted') {
    $errors[] = 'You must accept the terms and privacy policy.';
}

if (!$errors) {
    if (!is_dir($storageDirectory) || !is_writable($storageDirectory)) {
        $errors[] = 'Storage is not available. Please contact the administrator.';
    } else {
        $handle = fopen($csvFile, 'c+');

        if ($handle === false) {
            $errors[] = 'Unable to open the registration storage file.';
        } elseif (!flock($handle, LOCK_EX)) {
            fclose($handle);
            $errors[] = 'Unable to lock the registration storage file.';
        } else {
            $stat = fstat($handle);
            $isEmpty = $stat['size'] === 0;

            if ($isEmpty) {
                fputcsv($handle, [
                    'enrollment_number',
                    'email',
                    'full_name',
                    'date_of_birth',
                    'gender',
                    'mobile',
                    'department',
                    'semester',
                    'interests',
                    'event_preferences',
                    'registered_at'
                ]);
            }

            rewind($handle);
            $existingEnrollments = [];
            $existingEmails = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (($row[0] ?? '') === 'enrollment_number') {
                    continue;
                }
                $existingEnrollments[] = strtolower($row[0] ?? '');
                $existingEmails[] = strtolower($row[1] ?? '');
            }

            if (in_array(strtolower($enrollment), $existingEnrollments, true)) {
                $errors[] = 'This enrollment number is already registered.';
            }

            if (in_array(strtolower($email), $existingEmails, true)) {
                $errors[] = 'This email address is already registered.';
            }

            if (!$errors) {
                fseek($handle, 0, SEEK_END);

                $row = [
                    $enrollment,
                    $email,
                    $fullName,
                    $dob,
                    $gender,
                    $mobile,
                    $department,
                    $semester,
                    implode('|', $interests),
                    implode('|', $eventPreferences),
                    date('c')
                ];

                if (fputcsv($handle, $row) === false) {
                    $errors[] = 'Unable to save the registration.';
                } else {
                    $success = 'Registration saved successfully.';
                }
            }

            flock($handle, LOCK_UN);
            fclose($handle);
        }
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
    <link rel="stylesheet" href="Styles/common.css">
    <link rel="stylesheet" href="Styles/register.css">
</head>
<body>
<main>
    <section id="register-box">
        <h1>Registration Result</h1>

        <?php if ($success !== ''): ?>
            <p role="status">
                <?= escapeHtml($success) ?>
            </p>
            <p>Your registration details have been stored.</p>
            <p><a href="register.html">Back to registration</a></p>
            <p><a href="view_registrations.php">View stored registrations</a></p>
        <?php else: ?>
            <h2>Please correct the following errors</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= escapeHtml($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <p><a href="register.html">Return to registration form</a></p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
