<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('student');
require_once __DIR__ . '/db.php';

$profile = null;
$profileError = false;

try {
    $stmt = $pdo->prepare(
        'SELECT u.username, u.email,
                s.enrollment_number, s.full_name, s.department, s.semester,
                sp.date_of_birth, sp.gender, sp.mobile,
                sp.interests, sp.event_preferences
         FROM users u
         LEFT JOIN students s ON s.email = u.email
         LEFT JOIN student_profiles sp ON sp.user_id = u.user_id
         WHERE u.user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $profile = $stmt->fetch();
} catch (Throwable $exception) {
    error_log('StudentHub profile error: ' . $exception->getMessage());
    $profileError = true;
}

function profileValue(mixed $value): string
{
    if ($value === null || trim((string) $value) === '') {
        return 'Not provided';
    }
    return escapeHtml((string) $value);
}

function profileDate(mixed $value): string
{
    if ($value === null || $value === '') {
        return 'Not provided';
    }
    try {
        return escapeHtml((new DateTimeImmutable((string) $value))->format('d F Y'));
    } catch (Throwable) {
        return 'Not provided';
    }
}

function profileList(mixed $json, array $labels): array
{
    if (!is_string($json) || $json === '') {
        return [];
    }
    $values = json_decode($json, true);
    if (!is_array($values)) {
        return [];
    }
    $result = [];
    foreach ($values as $value) {
        if (is_string($value) && isset($labels[$value])) {
            $result[] = $labels[$value];
        }
    }
    return $result;
}

$interestLabels = [
    'web-development' => 'Web Development',
    'artificial-intelligence' => 'Artificial Intelligence',
    'machine-learning' => 'Machine Learning',
    'cyber-security' => 'Cyber Security',
    'iot' => 'Internet of Things',
    'data-science' => 'Data Science',
];
$eventLabels = [
    'technical' => 'Technical Events',
    'non-technical' => 'Non-Technical Events',
    'cultural' => 'Cultural Events',
    'sports' => 'Sports Events',
];
$interests = $profile ? profileList($profile['interests'] ?? null, $interestLabels) : [];
$eventPreferences = $profile ? profileList($profile['event_preferences'] ?? null, $eventLabels) : [];
$genderLabels = [
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other',
    'prefer-not-to-say' => 'Prefer not to say',
];
$gender = $profile ? ($genderLabels[(string) ($profile['gender'] ?? '')] ?? $profile['gender'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Profile</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/profile.css">
</head>
<body>
    <header>
        <div id="logo"><h1>StudentHub</h1><p>Your Digital Campus Companion</p></div>
        <div id="header-buttons">
            <button type="button" id="theme-toggle" class="button theme-toggle" aria-pressed="false">🌙 Dark mode</button>
        </div>
    </header>

    <?php require __DIR__ . '/includes/student_nav.php'; ?>

    <main>
        <section id="profile-box" aria-labelledby="profile-heading">
            <h2 id="profile-heading">Student Profile</h2>

            <?php if ($profileError): ?>
                <p role="alert">Your profile could not be loaded. Please try again later.</p>
            <?php elseif (!$profile): ?>
                <p role="alert">The account profile could not be found.</p>
            <?php else: ?>
                <section class="profile-section" aria-labelledby="account-heading">
                    <h3 id="account-heading">Account Information</h3>
                    <dl>
                        <div><dt>Enrollment Number</dt><dd><?= profileValue($profile['enrollment_number']) ?></dd></div>
                        <div><dt>Email Address</dt><dd><?= profileValue($profile['email']) ?></dd></div>
                    </dl>
                </section>

                <section class="profile-section" aria-labelledby="personal-heading">
                    <h3 id="personal-heading">Personal Information</h3>
                    <dl>
                        <div><dt>Full Name</dt><dd><?= profileValue($profile['full_name'] ?? $profile['username']) ?></dd></div>
                        <div><dt>Date of Birth</dt><dd><?= profileDate($profile['date_of_birth']) ?></dd></div>
                        <div><dt>Gender</dt><dd><?= profileValue($gender) ?></dd></div>
                        <div><dt>Mobile Number</dt><dd><?= profileValue($profile['mobile']) ?></dd></div>
                    </dl>
                </section>

                <section class="profile-section" aria-labelledby="academic-heading">
                    <h3 id="academic-heading">Academic Information</h3>
                    <dl>
                        <div><dt>Department</dt><dd><?= profileValue($profile['department']) ?></dd></div>
                        <div><dt>Current Semester</dt><dd><?= profileValue($profile['semester']) ?></dd></div>
                    </dl>
                </section>

                <section class="profile-section" aria-labelledby="interests-heading">
                    <h3 id="interests-heading">Areas of Interest</h3>
                    <?php if ($interests): ?>
                        <ul><?php foreach ($interests as $interest): ?><li><?= escapeHtml($interest) ?></li><?php endforeach; ?></ul>
                    <?php else: ?><p>Not provided</p><?php endif; ?>
                </section>

                <section class="profile-section" aria-labelledby="events-heading">
                    <h3 id="events-heading">Event Preferences</h3>
                    <?php if ($eventPreferences): ?>
                        <ul><?php foreach ($eventPreferences as $preference): ?><li><?= escapeHtml($preference) ?></li><?php endforeach; ?></ul>
                    <?php else: ?><p>Not provided</p><?php endif; ?>
                </section>
            <?php endif; ?>
        </section>

        <section id="profile-actions" aria-label="Profile Actions">
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </section>
    </main>

    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
<p>&copy; 2026 StudentHub. All Rights Reserved.</p>
    </footer>

    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>
