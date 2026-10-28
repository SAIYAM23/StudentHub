<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/audit.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function studentEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bindStudentValues(
    mysqli_stmt $stmt,
    string $types,
    array &$values
): void {
    if ($values === []) {
        return;
    }

    $references = [];
    foreach ($values as $key => &$value) {
        $references[$key] = &$value;
    }

    array_unshift($references, $types);
    call_user_func_array([$stmt, 'bind_param'], $references);
}

function studentRedirect(): never
{
    header('Location: manageStudents.php');
    exit;
}

if (empty($_SESSION['student_csrf'])) {
    $_SESSION['student_csrf'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['student_flash'])) {
    $_SESSION['student_flash'] = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['student_csrf'], $token)) {
        http_response_code(403);
        exit('Invalid security token. Refresh the page and try again.');
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'add' || $action === 'update') {
            $id = filter_var(
                $_POST['student_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $enrollment = trim((string) ($_POST['enrollment_number'] ?? ''));
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $department = trim((string) ($_POST['department'] ?? ''));
            $semester = filter_var(
                $_POST['semester'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                ($action === 'update' && (!$id || $id < 1)) ||
                $enrollment === '' ||
                strlen($enrollment) > 20 ||
                $name === '' ||
                strlen($name) > 100 ||
                strlen($email) > 150 ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                $department === '' ||
                strlen($department) > 100 ||
                $semester === false ||
                $semester === null ||
                $semester < 1 ||
                $semester > 12
            ) {
                throw new InvalidArgumentException(
                    'Please enter valid details in every field. Semester must be between 1 and 12.'
                );
            }

            if ($action === 'add') {
                $stmt = $mysqli->prepare(
                    'INSERT INTO students
                    (enrollment_number, full_name, email, department, semester)
                    VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'ssssi',
                    $enrollment,
                    $name,
                    $email,
                    $department,
                    $semester
                );
                $stmt->execute();
                $newStudentId = (int) $mysqli->insert_id;
                $stmt->close();

                writeAuditLog(
                    'CREATE',
                    'student',
                    $newStudentId,
                    'Created student: ' . $enrollment
                );

                $_SESSION['student_flash'] = [
                    'type' => 'success',
                    'message' => 'Student added successfully.'
                ];
            } else {
                $stmt = $mysqli->prepare(
                    'UPDATE students
                     SET enrollment_number = ?, full_name = ?, email = ?,
                         department = ?, semester = ?
                     WHERE student_id = ?'
                );

                $stmt->bind_param(
                    'ssssii',
                    $enrollment,
                    $name,
                    $email,
                    $department,
                    $semester,
                    $id
                );
                $stmt->execute();
                $changed = $stmt->affected_rows;
                $stmt->close();

                if ($changed > 0) {
                    writeAuditLog(
                        'UPDATE',
                        'student',
                        (int) $id,
                        'Updated student: ' . $enrollment
                    );
                }

                $_SESSION['student_flash'] = [
                    'type' => 'success',
                    'message' => $changed > 0
                        ? 'Student updated successfully.'
                        : 'No changes were made, or the student was not found.'
                ];
            }
        } elseif ($action === 'delete') {
            $id = filter_var(
                $_POST['student_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new InvalidArgumentException('Invalid student ID.');
            }

            $stmt = $mysqli->prepare(
                'DELETE FROM students WHERE student_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted > 0) {
                writeAuditLog(
                    'DELETE',
                    'student',
                    (int) $id,
                    'Deleted student record'
                );
            }

            $_SESSION['student_flash'] = [
                'type' => $deleted > 0 ? 'success' : 'error',
                'message' => $deleted > 0
                    ? 'Student deleted successfully.'
                    : 'Student was not found.'
            ];
        } else {
            throw new InvalidArgumentException('Invalid operation requested.');
        }
    } catch (InvalidArgumentException $e) {
        $_SESSION['student_flash'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
    } catch (mysqli_sql_exception $e) {
        error_log('StudentHub student CRUD error: ' . $e->getMessage());

        $_SESSION['student_flash'] = [
            'type' => 'error',
            'message' => $e->getCode() === 1062
                ? 'Enrollment number or email already exists.'
                : 'The operation failed. Please check the details and try again.'
        ];
    }

    studentRedirect();
}

$flash = $_SESSION['student_flash'];
unset($_SESSION['student_flash']);

$csrfToken = (string) $_SESSION['student_csrf'];

$search = trim((string) ($_GET['search'] ?? ''));
$departmentFilter = trim((string) ($_GET['department'] ?? ''));
$semesterFilter = trim((string) ($_GET['semester'] ?? ''));

$allowedSemesters = array_map('strval', range(1, 12));
if (!in_array($semesterFilter, $allowedSemesters, true)) {
    $semesterFilter = '';
}

$editStudent = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $stmt = $mysqli->prepare(
        'SELECT student_id, enrollment_number, full_name, email,
                department, semester
         FROM students WHERE student_id = ?'
    );
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editStudent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editStudent) {
        $flash = [
            'type' => 'error',
            'message' => 'The requested student was not found.'
        ];
    }
}

$departments = [];
$result = $mysqli->query(
    'SELECT DISTINCT department FROM students ORDER BY department'
);

while ($row = $result->fetch_assoc()) {
    $departments[] = $row['department'];
}
$result->free();

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR enrollment_number LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'sss';
}

if ($departmentFilter !== '') {
    $where[] = 'department = ?';
    $params[] = $departmentFilter;
    $types .= 's';
}

if ($semesterFilter !== '') {
    $where[] = 'semester = ?';
    $params[] = (int) $semesterFilter;
    $types .= 'i';
}

$sql = 'SELECT student_id, enrollment_number, full_name, email,
               department, semester, created_at
        FROM students';

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY student_id DESC';

$stmt = $mysqli->prepare($sql);
bindStudentValues($stmt, $types, $params);
$stmt->execute();
$studentsResult = $stmt->get_result();

$students = [];
while ($row = $studentsResult->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

$totalResult = $mysqli->query('SELECT COUNT(*) AS total FROM students');
$totalStudents = (int) $totalResult->fetch_assoc()['total'];
$totalResult->free();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="studenthub_students.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Student ID',
        'Enrollment Number',
        'Full Name',
        'Email',
        'Department',
        'Semester',
        'Created At'
    ]);

    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_id'],
            $student['enrollment_number'],
            $student['full_name'],
            $student['email'],
            $student['department'],
            $student['semester'],
            $student['created_at']
        ]);
    }

    fclose($output);
    exit;
}

$username = studentEscape(
    (string) ($_SESSION['username'] ?? 'Administrator')
);

$formStudent = $editStudent ?? [
    'student_id' => '',
    'enrollment_number' => '',
    'full_name' => '',
    'email' => '',
    'department' => '',
    'semester' => ''
];

$exportQuery = $_GET;
$exportQuery['export'] = 'csv';
$exportUrl = 'manageStudents.php?' . http_build_query($exportQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Manage Students</title>
    <link rel="stylesheet" href="Styles/common.css">
    <link rel="stylesheet" href="Styles/manageStudents.css">
</head>
<body>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Your Digital Campus Companion</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle"
                class="button theme-toggle" aria-pressed="false">
            🌙 Dark mode
        </button>
    </div>
</header>

<nav aria-label="Admin Navigation">
    <button id="menu-toggle" type="button"
            aria-label="Toggle navigation" aria-expanded="false"
            aria-controls="main-nav">☰ Menu</button>
    <ul id="main-nav">
        <li><a href="adminDashboard.php">Admin Dashboard</a></li>
        <li><a href="manageStudents.php" class="active"
               aria-current="page">Manage Students</a></li>
        <li><a href="manageEvents.php">Manage Events</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<main>
    <section id="manage-students-title">
        <h2>Student Management</h2>
        <p>Welcome, <?= $username ?>! Add, view, edit, search, filter,
            and remove student records.</p>
    </section>

    <?php if (is_array($flash)): ?>
        <div class="student-message <?= studentEscape((string) $flash['type']) ?>"
             role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= studentEscape((string) $flash['message']) ?>
        </div>
    <?php endif; ?>

    <section id="student-summary">
        <h2>Total Student Records</h2>
        <p><?= $totalStudents ?></p>
    </section>

    <section id="student-form-section">
        <h2><?= $editStudent ? 'Edit Student' : 'Add New Student' ?></h2>

        <form action="manageStudents.php" method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= studentEscape($csrfToken) ?>">
            <input type="hidden" name="action"
                   value="<?= $editStudent ? 'update' : 'add' ?>">

            <?php if ($editStudent): ?>
                <input type="hidden" name="student_id"
                       value="<?= (int) $formStudent['student_id'] ?>">
            <?php endif; ?>

            <fieldset>
                <legend>Student Information</legend>

                <div class="student-form-grid">
                    <div>
                        <label for="enrollment_number">Enrollment Number</label>
                        <input type="text" id="enrollment_number"
                               name="enrollment_number" maxlength="20"
                               value="<?= studentEscape((string) $formStudent['enrollment_number']) ?>"
                               required>
                    </div>

                    <div>
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               maxlength="100"
                               value="<?= studentEscape((string) $formStudent['full_name']) ?>"
                               required>
                    </div>

                    <div>
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               maxlength="150"
                               value="<?= studentEscape((string) $formStudent['email']) ?>"
                               required>
                    </div>

		<div>
		    <label for="department">Department</label>
		    <select id="department" name="department" required>
			<option value="">Select Department</option>
			<?php
			$departmentOptions = [
			    'Computer Engineering',
			    'Information Technology',
			    'Electronics',
			    'Mechanical Engineering',
			    'Civil Engineering'
			];
			?>
			<?php foreach ($departmentOptions as $option): ?>
			    <option value="<?= studentEscape($option) ?>"
				<?= (string) $formStudent['department'] === $option
				    ? 'selected' : '' ?>>
				<?= studentEscape($option) ?>
			    </option>
			<?php endforeach; ?>
		    </select>
		</div>

                    <div>
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>"
                                    <?= (string) $formStudent['semester'] === (string) $i
                                        ? 'selected' : '' ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="student-form-actions">
                    <button type="submit" class="button">
                        <?= $editStudent ? 'Update Student' : 'Add Student' ?>
                    </button>
                    <?php if ($editStudent): ?>
                        <a class="button" href="manageStudents.php">Cancel Edit</a>
                    <?php else: ?>
                        <button type="reset" class="button">Reset</button>
                    <?php endif; ?>
                </div>
            </fieldset>
        </form>
    </section>

    <section id="student-list-section">
        <h2>Student Records</h2>

        <form action="manageStudents.php" method="get"
              id="student-search-form">
            <div>
                <label for="search">Search name, email, or enrollment number</label>
                <input type="search" id="search" name="search"
                       placeholder="Enter search text..."
                       value="<?= studentEscape($search) ?>">
            </div>

            <div>
                <label for="department-filter">Filter by Department</label>
                <select id="department-filter" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= studentEscape($department) ?>"
                            <?= $departmentFilter === $department ? 'selected' : '' ?>>
                            <?= studentEscape($department) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="semester-filter">Filter by Semester</label>
                <select id="semester-filter" name="semester">
                    <option value="">All Semesters</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>"
                            <?= $semesterFilter === (string) $i ? 'selected' : '' ?>>
                            Semester <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="student-filter-actions">
                <button type="submit" class="button">Search / Filter</button>
                <a class="button" href="manageStudents.php">Clear Filters</a>
                <a class="button" href="<?= studentEscape($exportUrl) ?>">Export CSV</a>
            </div>
        </form>

        <p class="student-result-count">
            Showing <?= count($students) ?> matching record(s).
        </p>

        <div class="student-table-wrapper">
            <table>
                <caption>Student records matching the selected search and filters</caption>
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Enrollment</th>
                        <th scope="col">Full Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Department</th>
                        <th scope="col">Semester</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students === []): ?>
                    <tr>
                        <td colspan="7">No student records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= (int) $student['student_id'] ?></td>
                            <td><?= studentEscape($student['enrollment_number']) ?></td>
                            <td><?= studentEscape($student['full_name']) ?></td>
                            <td><?= studentEscape($student['email']) ?></td>
                            <td><?= studentEscape($student['department']) ?></td>
                            <td><?= (int) $student['semester'] ?></td>
                            <td class="student-row-actions">
                                <a class="button"
                                   href="manageStudents.php?edit=<?= (int) $student['student_id'] ?>">
                                    Edit
                                </a>

                                <form action="manageStudents.php" method="post"
                                      onsubmit="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= studentEscape($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id"
                                           value="<?= (int) $student['student_id'] ?>">
                                    <button type="submit" class="button delete-button">
                                        Delete
                                    </button>
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
    <h2>StudentHub</h2>
    <p>Your Digital Campus Companion</p>
    <p>Email: <a href="mailto:studenthub@college.edu">studenthub@college.edu</a></p>
    <p>&copy; 2026 StudentHub. All Rights Reserved.</p>
</footer>

<script src="Scripts/practical4.js" defer></script>
<script src="Scripts/studentSearch.js" defer></script>
</body>
</html>
