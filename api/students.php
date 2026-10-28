<?php
declare(strict_types=1);

/*
 * StudentHub Practical 13
 * JSON API for asynchronous student CRUD.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function apiResponse(int $status, array $payload): never
{
    http_response_code($status);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

// Start the existing StudentHub session and use its admin role.
require_once dirname(__DIR__) . '/auth.php';

// Return JSON instead of the redirect/plain-text responses normally
// used by the HTML pages.
if (empty($_SESSION['user_id'])) {
    apiResponse(401, [
        'success' => false,
        'message' => 'Please log in to continue.'
    ]);
}

$lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

if ($lastActivity === 0 || time() - $lastActivity > 900) {
    $_SESSION = [];
    session_destroy();

    apiResponse(401, [
        'success' => false,
        'message' => 'Your session has expired. Please log in again.'
    ]);
}

$_SESSION['last_activity'] = time();

if (($_SESSION['role'] ?? '') !== 'admin') {
    apiResponse(403, [
        'success' => false,
        'message' => 'Administrator access is required.'
    ]);
}

if (empty($_SESSION['student_csrf'])) {
    $_SESSION['student_csrf'] = bin2hex(random_bytes(32));
}

function validateStudent(array $data): array
{
    $enrollment = trim((string) ($data['enrollment_number'] ?? ''));
    $name = trim((string) ($data['full_name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $department = trim((string) ($data['department'] ?? ''));
    $semester = filter_var(
        $data['semester'] ?? null,
        FILTER_VALIDATE_INT
    );

    $allowedDepartments = [
        'Computer Engineering',
        'Information Technology',
        'Electronics',
        'Mechanical Engineering',
        'Civil Engineering'
    ];

    if (
        $enrollment === '' ||
        strlen($enrollment) > 20 ||
        $name === '' ||
        strlen($name) > 100 ||
        strlen($email) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($department, $allowedDepartments, true) ||
        $semester === false ||
        $semester === null ||
        $semester < 1 ||
        $semester > 12
    ) {
        apiResponse(422, [
            'success' => false,
            'message' => 'Enter valid student details. Semester must be between 1 and 12.'
        ]);
    }

    return [
        'enrollment_number' => $enrollment,
        'full_name' => $name,
        'email' => $email,
        'department' => $department,
        'semester' => $semester
    ];
}

function validStudentId(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id < 1) {
        apiResponse(422, [
            'success' => false,
            'message' => 'A valid student ID is required.'
        ]);
    }

    return $id;
}

try {
    require_once dirname(__DIR__) . '/db_mysqli.php';

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $search = trim((string) ($_GET['search'] ?? ''));
        $department = trim((string) ($_GET['department'] ?? ''));
        $semester = trim((string) ($_GET['semester'] ?? ''));

        $sql = 'SELECT student_id, enrollment_number, full_name,
                       email, department, semester, created_at
                FROM students
                WHERE 1 = 1';

        $types = '';
        $values = [];

        if ($search !== '') {
            $sql .= ' AND (
                full_name LIKE ? OR
                email LIKE ? OR
                enrollment_number LIKE ?
            )';

            $term = '%' . $search . '%';
            $types .= 'sss';
            $values[] = $term;
            $values[] = $term;
            $values[] = $term;
        }

        if ($department !== '') {
            $sql .= ' AND department = ?';
            $types .= 's';
            $values[] = $department;
        }

        if ($semester !== '') {
            $validSemester = filter_var(
                $semester,
                FILTER_VALIDATE_INT
            );

            if (
                $validSemester === false ||
                $validSemester < 1 ||
                $validSemester > 12
            ) {
                apiResponse(422, [
                    'success' => false,
                    'message' => 'Semester filter must be between 1 and 12.'
                ]);
            }

            $sql .= ' AND semester = ?';
            $types .= 'i';
            $values[] = $validSemester;
        }

        $sql .= ' ORDER BY student_id DESC';

        $stmt = $mysqli->prepare($sql);

        if ($values !== []) {
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $countResult = $mysqli->query(
            'SELECT COUNT(*) AS total FROM students'
        );

        $total = (int) $countResult->fetch_assoc()['total'];

        apiResponse(200, [
            'success' => true,
            'message' => 'Student records loaded successfully.',
            'data' => $students,
            'count' => count($students),
            'total' => $total,
            'csrf_token' => $_SESSION['student_csrf']
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');

        apiResponse(405, [
            'success' => false,
            'message' => 'This HTTP method is not supported.'
        ]);
    }

    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody ?: '', true);

    if (!is_array($data)) {
        apiResponse(400, [
            'success' => false,
            'message' => 'Request body must contain valid JSON.'
        ]);
    }

    $token = (string) (
        $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $data['csrf_token']
        ?? ''
    );

    if (
        $token === '' ||
        !hash_equals((string) $_SESSION['student_csrf'], $token)
    ) {
        apiResponse(403, [
            'success' => false,
            'message' => 'Invalid security token. Reload the page and try again.'
        ]);
    }

    $action = (string) ($data['action'] ?? '');

    if ($action === 'add' || $action === 'update') {
        $student = validateStudent($data);

        if ($action === 'add') {
            $stmt = $mysqli->prepare(
                'INSERT INTO students
                    (enrollment_number, full_name, email, department, semester)
                 VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'ssssi',
                $student['enrollment_number'],
                $student['full_name'],
                $student['email'],
                $student['department'],
                $student['semester']
            );

            $stmt->execute();
            $newId = $mysqli->insert_id;
            $stmt->close();

            apiResponse(201, [
                'success' => true,
                'message' => 'Student added successfully.',
                'student_id' => $newId
            ]);
        }

        $id = validStudentId($data['student_id'] ?? null);

        $stmt = $mysqli->prepare(
            'UPDATE students
             SET enrollment_number = ?, full_name = ?, email = ?,
                 department = ?, semester = ?
             WHERE student_id = ?'
        );

        $stmt->bind_param(
            'ssssii',
            $student['enrollment_number'],
            $student['full_name'],
            $student['email'],
            $student['department'],
            $student['semester'],
            $id
        );

        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();

        if ($changed === 0) {
            $check = $mysqli->prepare(
                'SELECT student_id FROM students WHERE student_id = ?'
            );

            $check->bind_param('i', $id);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$exists) {
                apiResponse(404, [
                    'success' => false,
                    'message' => 'Student not found.'
                ]);
            }
        }

        apiResponse(200, [
            'success' => true,
            'message' => $changed > 0
                ? 'Student updated successfully.'
                : 'No changes were necessary.'
        ]);
    }

    if ($action === 'delete') {
        $id = validStudentId($data['student_id'] ?? null);

        $stmt = $mysqli->prepare(
            'DELETE FROM students WHERE student_id = ?'
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            apiResponse(404, [
                'success' => false,
                'message' => 'Student not found.'
            ]);
        }

        apiResponse(200, [
            'success' => true,
            'message' => 'Student deleted successfully.'
        ]);
    }

    apiResponse(422, [
        'success' => false,
        'message' => 'Action must be add, update, or delete.'
    ]);
} catch (mysqli_sql_exception $e) {
    error_log('StudentHub JSON API database error: ' . $e->getMessage());

    if ((int) $e->getCode() === 1062) {
        apiResponse(409, [
            'success' => false,
            'message' => 'That enrollment number or email address is already registered.'
        ]);
    }

    apiResponse(500, [
        'success' => false,
        'message' => 'A database error occurred. Please try again.'
    ]);
} catch (Throwable $e) {
    error_log('StudentHub JSON API error: ' . $e->getMessage());

    apiResponse(500, [
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ]);
}
