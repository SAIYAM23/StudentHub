<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

function eventEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function eventRedirect(): never
{
    header('Location: manageEvents.php');
    exit;
}

function eventFlash(string $type, string $message): void
{
    $_SESSION['event_flash'] = ['type' => $type, 'message' => $message];
}

function saveEventPoster(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Poster upload failed. Please try again.');
    }

    if (!isset($file['tmp_name'], $file['size']) ||
        !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Invalid uploaded file.');
    }

    if ((int) $file['size'] < 1 || (int) $file['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Poster must be no larger than 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new InvalidArgumentException(
            'Only JPEG, PNG, and WebP poster images are allowed.'
        );
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        throw new InvalidArgumentException('The uploaded file is not a valid image.');
    }

    if (($imageInfo[0] ?? 0) > 8000 || ($imageInfo[1] ?? 0) > 8000) {
        throw new InvalidArgumentException('Poster dimensions must not exceed 8000 × 8000 pixels.');
    }

    $directory = __DIR__ . '/storage/event-posters';

    if (!is_dir($directory) || !is_writable($directory)) {
        error_log('StudentHub event poster directory is missing or not writable.');
        throw new RuntimeException('Poster storage is unavailable. Check directory permissions.');
    }

    $filename = bin2hex(random_bytes(24)) . '.' . $allowed[$mime];
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to save the poster. Check directory permissions.');
    }

    return $filename;
}

function removeEventPoster(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }

    // Only generated filenames are eligible for deletion.
    if (!preg_match('/\A[a-f0-9]{48}\.(jpg|png|webp)\z/', $filename)) {
        error_log('StudentHub: refusing to remove an unexpected poster filename.');
        return;
    }

    $path = __DIR__ . '/storage/event-posters/' . $filename;

    if (is_file($path) && !unlink($path)) {
        error_log('StudentHub: unable to remove event poster ' . $filename);
    }
}

if (empty($_SESSION['event_csrf'])) {
    $_SESSION['event_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['event_csrf'], $token)) {
        http_response_code(403);
        exit('Invalid security token. Refresh the page and try again.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $newPoster = null;

    try {
        if ($action === 'add' || $action === 'update') {
            $id = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $date = trim((string) ($_POST['event_date'] ?? ''));
            $venue = trim((string) ($_POST['venue'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? 'General'));
            $status = (string) ($_POST['status'] ?? 'open');

            if (($action === 'update' && (!$id || $id < 1)) ||
                $title === '' || mb_strlen($title) > 150 ||
                mb_strlen($description) > 10000 ||
                $venue === '' || mb_strlen($venue) > 150 ||
                !in_array($category, [
    'Technical', 'Workshop', 'Sports', 'Cultural',
    'Seminar', 'Career', 'Social', 'General'
], true) ||
                !in_array($status, ['open', 'closed'], true)) {
                throw new InvalidArgumentException('Please enter valid event details.');
            }

            $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
                throw new InvalidArgumentException('Please select a valid event date.');
            }

            if (!isset($_FILES['poster'])) {
                throw new InvalidArgumentException('Poster upload field is missing.');
            }

            $newPoster = saveEventPoster($_FILES['poster']);

            if ($action === 'add') {
                $stmt = $pdo->prepare(
                    'INSERT INTO events
                    (title, description, event_date, venue, poster_filename, category, status)
                    VALUES (:title, :description, :event_date, :venue, :poster, :category, :status)'
                );

                $stmt->execute([
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'event_date' => $date,
                    'venue' => $venue,
                    'poster' => $newPoster,
                    'category' => $category,
                    'status' => $status,
                ]);

                $newEventId = (int) $pdo->lastInsertId();
                writeAuditLog(
                    'CREATE',
                    'event',
                    $newEventId,
                    'Created event: ' . $title
                );

                eventFlash('success', 'Event added successfully.');
            } else {
                $stmt = $pdo->prepare('SELECT poster_filename FROM events WHERE event_id = ?');
                $stmt->execute([$id]);
                $existing = $stmt->fetch();

                if (!$existing) {
                    throw new InvalidArgumentException('The event was not found.');
                }

                $posterToKeep = $newPoster ?? $existing['poster_filename'];

                $stmt = $pdo->prepare(
                    'UPDATE events SET title = :title, description = :description,
                     event_date = :event_date, venue = :venue, poster_filename = :poster,
                     category = :category, status = :status WHERE event_id = :id'
                );

                $stmt->execute([
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'event_date' => $date,
                    'venue' => $venue,
                    'poster' => $posterToKeep,
                    'category' => $category,
                    'status' => $status,
                    'id' => $id,
                ]);

                if ($newPoster !== null &&
                    !empty($existing['poster_filename'])) {
                    removeEventPoster((string) $existing['poster_filename']);
                }

                writeAuditLog(
                    'UPDATE',
                    'event',
                    (int) $id,
                    'Updated event: ' . $title
                );

                eventFlash('success', 'Event updated successfully.');
            }
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new InvalidArgumentException('Invalid event ID.');
            }

            $stmt = $pdo->prepare('SELECT title, poster_filename FROM events WHERE event_id = ?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch();

            if (!$existing) {
                throw new InvalidArgumentException('The event was not found.');
            }

            $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = ?');
            $stmt->execute([$id]);

            removeEventPoster(
                isset($existing['poster_filename'])
                    ? (string) $existing['poster_filename']
                    : null
            );

            writeAuditLog(
                'DELETE',
                'event',
                (int) $id,
                'Deleted event: ' . (string) ($existing['title'] ?? 'Unknown title')
            );

            eventFlash('success', 'Event deleted successfully.');
        } else {
            throw new InvalidArgumentException('Invalid operation requested.');
        }
    } catch (InvalidArgumentException $e) {
        if ($newPoster !== null) {
            removeEventPoster($newPoster);
        }
        eventFlash('error', $e->getMessage());
    } catch (Throwable $e) {
        if ($newPoster !== null) {
            removeEventPoster($newPoster);
        }
        error_log('StudentHub event CRUD error: ' . $e->getMessage());
        eventFlash('error', 'The operation failed. Please check your details and try again.');
    }

    eventRedirect();
}

$flash = $_SESSION['event_flash'] ?? null;
unset($_SESSION['event_flash']);

$csrfToken = (string) $_SESSION['event_csrf'];
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');

if (!in_array($statusFilter, ['', 'open', 'closed'], true)) {
    $statusFilter = '';
}

$editEvent = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = ?');
    $stmt->execute([$editId]);
    $editEvent = $stmt->fetch() ?: null;

    if (!$editEvent) {
        $flash = ['type' => 'error', 'message' => 'The requested event was not found.'];
    }
}

$where = [];
$params = [];

if ($categoryFilter !== '') {
    $where[] = 'category = ?';
    $params[] = $categoryFilter;
}

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

$sql = 'SELECT * FROM events';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY event_date DESC, event_id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$categories = $pdo->query(
    'SELECT DISTINCT category FROM events ORDER BY category'
)->fetchAll(PDO::FETCH_COLUMN);

$username = eventEscape((string) ($_SESSION['username'] ?? 'Administrator'));
$isEditing = is_array($editEvent);

$formEvent = $editEvent ?: [
    'event_id' => '',
    'title' => '',
    'description' => '',
    'event_date' => '',
    'venue' => '',
    'category' => 'General',
    'status' => 'open',
    'poster_filename' => null,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Manage Events</title>
    <link rel="stylesheet" href="Styles/common.css">
    <link rel="stylesheet" href="Styles/manageEvents.css">
    <link rel="stylesheet" href="Styles/manageEventsAdmin.css">
</head>
<body>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Your Digital Campus Companion</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle" class="button theme-toggle"
                aria-pressed="false">🌙 Dark mode</button>
    </div>
</header>

<nav aria-label="Admin Navigation">
    <button id="menu-toggle" type="button" aria-label="Toggle navigation"
            aria-expanded="false" aria-controls="main-nav">☰ Menu</button>
    <ul id="main-nav">
        <li><a href="adminDashboard.php">Admin Dashboard</a></li>
        <li><a href="manageStudents.php">Manage Students</a></li>
        <li><a href="manageEvents.php" class="active" aria-current="page">Manage Events</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<main>
    <section id="manage-title">
        <h2>Manage College Events</h2>
        <p>Welcome, <?= $username ?>! Add, update, view, and remove college events and posters.</p>
    </section>

    <?php if (is_array($flash)): ?>
        <div class="event-message <?= eventEscape((string) $flash['type']) ?>"
             role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= eventEscape((string) $flash['message']) ?>
        </div>
    <?php endif; ?>

    <section id="add-event" aria-labelledby="add-event-heading">
        <h2 id="add-event-heading"><?= $isEditing ? 'Update Event' : 'Add New Event' ?></h2>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= eventEscape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'add' ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="event_id"
                       value="<?= (int) $formEvent['event_id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

            <fieldset>
                <legend>Event Information</legend>

                <div>
                    <label for="title">Event Name</label>
                    <input type="text" id="title" name="title" maxlength="150"
                           value="<?= eventEscape((string) $formEvent['title']) ?>" required>
                </div>

                <div>
                    <label for="event-date">Date</label>
                    <input type="date" id="event-date" name="event_date"
                           value="<?= eventEscape((string) $formEvent['event_date']) ?>" required>
                </div>

                <div>
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" maxlength="150"
                           value="<?= eventEscape((string) $formEvent['venue']) ?>" required>
                </div>

                <div>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"
                              maxlength="10000"><?= eventEscape((string) ($formEvent['description'] ?? '')) ?></textarea>
                </div>


<div>
    <label for="category">Category</label>
    <select id="category" name="category" required>
        <?php
        $allowedCategories = [
            'Technical',
            'Workshop',
            'Sports',
            'Cultural',
            'Seminar',
            'Career',
            'Social',
            'General'
        ];
        ?>
        <?php foreach ($allowedCategories as $option): ?>
            <option value="<?= eventEscape($option) ?>"
                <?= $formEvent['category'] === $option ? 'selected' : '' ?>>
                <?= eventEscape($option) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


                <div>
                    <label for="status">Event Status</label>
                    <select id="status" name="status" required>
                        <option value="open" <?= $formEvent['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="closed" <?= $formEvent['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>

                <div>
                    <label for="poster">Poster (JPEG, PNG, WebP; maximum 2 MB)</label>
                    <input type="file" id="poster" name="poster"
                           accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($formEvent['poster_filename'])): ?>
                        <p>Current poster:</p>
                        <img class="event-poster-preview"
                             src="event_poster.php?id=<?= (int) $formEvent['event_id'] ?>"
                             alt="Current poster">
                        <p>Leave the file field empty to keep the existing poster.</p>
                    <?php endif; ?>
                </div>

                <div class="event-form-actions">
                    <button type="submit" class="button">
                        <?= $isEditing ? 'Save Changes' : 'Add Event' ?>
                    </button>
                    <button type="reset" class="button">Reset</button>
                    <?php if ($isEditing): ?>
                        <a class="button" href="manageEvents.php">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </fieldset>
        </form>
    </section>

    <section id="event-list" aria-labelledby="event-list-heading">
        <h2 id="event-list-heading">Existing Events</h2>

        <form method="get" class="event-filter">

<div>
    <label for="filter-category">Category</label>
    <select id="filter-category" name="category">
        <option value="">All categories</option>
        <?php foreach ($allowedCategories as $option): ?>
            <option value="<?= eventEscape($option) ?>"
                <?= $categoryFilter === $option ? 'selected' : '' ?>>
                <?= eventEscape($option) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

            <div>
                <label for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="">All statuses</option>
                    <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <button class="button" type="submit">Apply Filters</button>
            <a class="button" href="manageEvents.php">Clear Filters</a>
        </form>

        <div class="event-table-wrap">
            <table>
                <caption>College events stored in the database</caption>
                <thead>
                <tr>
                    <th scope="col">Poster</th>
                    <th scope="col">Event Name</th>
                    <th scope="col">Date</th>
                    <th scope="col">Venue</th>
                    <th scope="col">Category</th>
                    <th scope="col">Status</th>
                    <th scope="col">Description</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$events): ?>
                    <tr><td colspan="8">No events found. Add your first event above.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <?php if (!empty($event['poster_filename'])): ?>
                                    <img class="event-poster-thumb"
                                         src="event_poster.php?id=<?= (int) $event['event_id'] ?>"
                                         alt="Poster for <?= eventEscape((string) $event['title']) ?>">
                                <?php else: ?>
                                    No poster
                                <?php endif; ?>
                            </td>
                            <td><?= eventEscape((string) $event['title']) ?></td>
                            <td><?= eventEscape((string) $event['event_date']) ?></td>
                            <td><?= eventEscape((string) $event['venue']) ?></td>
                            <td><?= eventEscape((string) $event['category']) ?></td>
                            <td><span class="event-status <?= eventEscape((string) $event['status']) ?>">
                                <?= eventEscape(ucfirst((string) $event['status'])) ?>
                            </span></td>
                            <td><?= nl2br(eventEscape((string) ($event['description'] ?? ''))) ?></td>
                            <td class="event-actions">
                                <a class="button"
                                   href="manageEvents.php?edit=<?= (int) $event['event_id'] ?>">Edit</a>
                                <form method="post"
                                      onsubmit="return confirm('Delete this event permanently?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= eventEscape($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="event_id"
                                           value="<?= (int) $event['event_id'] ?>">
                                    <button class="button danger" type="submit">Delete</button>
                                </form>
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
    <p>&copy; <?= date('Y') ?> StudentHub</p>
</footer>

<script src="Scripts/practical4.js"></script>
</body>
</html>
