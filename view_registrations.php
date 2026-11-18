<?php
declare(strict_types=1);

// This is a local practical/demo page, not a production authentication system.
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Access denied. This demonstration page is available locally only.');
}

$csvFile = __DIR__ . '/storage/registrations.csv';
$records = [];
$error = '';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!is_file($csvFile) || !is_readable($csvFile)) {
    $error = 'The registration file is unavailable.';
} else {
    $handle = fopen($csvFile, 'r');

    if ($handle === false) {
        $error = 'Unable to read the registration file.';
    } else {
        flock($handle, LOCK_SH);

        $headers = fgetcsv($handle);

        if (is_array($headers)) {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $records[] = array_combine($headers, $row);
                }
            }
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stored Registrations | StudentHub</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
    <style>
        body { padding: 1.5rem; }
        body.dark-theme { background: #111820; color: #f4f7fb; }
        body.dark-theme .notice,
        body.dark-theme .table-wrapper { color: #f4f7fb; }
        body.dark-theme table { background: #1d2936; color: #f4f7fb; }
        body.dark-theme th { background: #2c3e51; color: #fff; }
        body.dark-theme td, body.dark-theme th { border-color: #536477; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: .65rem; text-align: left; }
        th { background: #eee; }
        .notice { padding: 1rem; margin: 1rem 0; }
    </style>
</head>
<body>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Local Registration Records</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle" class="button theme-toggle"
                aria-pressed="false">🌙 Dark mode</button>
    </div>
</header>
<main>
    <h1>Stored Student Registrations</h1>
    <p><a href="register.php">Back to registration form</a></p>

    <?php if ($error !== ''): ?>
        <p class="notice" role="alert"><?= h($error) ?></p>
    <?php elseif (!$records): ?>
        <p class="notice">No registrations have been stored yet.</p>
    <?php else: ?>
        <p>Total registrations: <?= count($records) ?></p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($records[0]) as $heading): ?>
                            <th><?= h((string) $heading) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <?php foreach ($record as $value): ?>
                                <td><?= h((string) $value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
