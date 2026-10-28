<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/db.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$stmt = $pdo->prepare(
    'SELECT poster_filename FROM events WHERE event_id = ?'
);
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event || empty($event['poster_filename'])) {
    http_response_code(404);
    exit('Poster not found.');
}

$filename = (string) $event['poster_filename'];

if (!preg_match('/\A[a-f0-9]{48}\.(jpg|png|webp)\z/', $filename)) {
    http_response_code(404);
    exit('Poster not found.');
}

$path = __DIR__ . '/storage/event-posters/' . $filename;

if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('Poster file not found.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path);

$allowedTypes = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];

$extension = pathinfo($filename, PATHINFO_EXTENSION);

if (!isset($allowedTypes[$extension]) || $mime !== $allowedTypes[$extension]) {
    http_response_code(415);
    exit('Unsupported poster type.');
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="event-poster.' . $extension . '"');
header('Cache-Control: private, no-store, max-age=0');
header('Content-Length: ' . (string) filesize($path));

readfile($path);
exit;
