<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function registerEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function registerPostString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Register</title>
    <link rel="stylesheet" href="Styles/common.css?v=site-shell-3">
    <link rel="stylesheet" href="Styles/register.css?v=auth-buttons-2">
</head>
<body>

    <!-- Header Starts -->
    <?php require __DIR__ . '/includes/site_header.php'; ?>
    <!-- Header Ends -->

    <!-- Navigation Starts -->
    <?php require __DIR__ . '/includes/site_navigation.php'; ?>
    <!-- Navigation Ends -->

    <!-- Main Content Starts -->
    <main>
        <section id="register-box" aria-labelledby="registration-heading">
            <h2 id="registration-heading">Student Registration</h2>

            <!-- Registration is processed by register.php. -->
            <form action="register.php" method="POST">

                <fieldset>
                    <legend>Account Information</legend>

                    <div class="field-container">
                        <label for="rollNo">Enrollment Number <span class="required">*</span></label>
                        <input type="text" id="rollNo" name="enrollment_number" placeholder="Enter Enrollment Number" autocomplete="off" aria-describedby="rollNoError" required>
                        <small class="field-error" id="rollNoError" aria-live="polite"></small>
                    </div>

                    <div class="field-container">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" placeholder="Enter Email Address" autocomplete="email" aria-describedby="emailError" required>
                        <small class="field-error" id="emailError" aria-live="polite"></small>
                    </div>

                    <div class="field-container">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Create Password" autocomplete="new-password" aria-describedby="passwordHelp passwordStrength passwordError" required>
                        <small id="passwordHelp" class="field-help">Use at least 8 characters, including uppercase, lowercase, and a number.</small>
                        <div class="strength-track" aria-hidden="true"><div id="strengthBar"></div></div>
                        <small id="passwordStrength" class="field-help" aria-live="polite">Password strength: not entered</small>
                        <small class="field-error" id="passwordError" aria-live="polite"></small>
                    </div>

                    <div class="field-container">
                        <label for="confirm-password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password" aria-describedby="confirm-passwordError" required>
                        <small class="field-error" id="confirm-passwordError" aria-live="polite"></small>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Personal Information</legend>

                    <div class="field-container">
                        <label for="fullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="fullName" name="full_name" placeholder="Enter Full Name" autocomplete="name" aria-describedby="fullNameError" required>
                        <small class="field-error" id="fullNameError" aria-live="polite"></small>
                    </div>

                    <div class="field-container">
                        <label for="dob">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="dob" name="date_of_birth" autocomplete="bday" aria-describedby="dobError" required>
                        <small class="field-error" id="dobError" aria-live="polite"></small>
                    </div>

                    <fieldset>
                        <legend>Gender <span class="required">*</span></legend>
                        <label>
                            <input type="radio" name="gender" value="male" required>
                            Male
                        </label>
                        <label>
                            <input type="radio" name="gender" value="female">
                            Female
                        </label>
                        <label>
                            <input type="radio" name="gender" value="other">
                            Other
                        </label>
                        <label>
                            <input type="radio" name="gender" value="prefer-not-to-say">
                            Prefer not to say
                        </label>
                        <small class="field-error" id="genderError" aria-live="polite"></small>
                    </fieldset>

                    <div class="field-container">
                        <label for="mobile">Mobile Number <span class="required">*</span></label>
                        <input type="tel" id="mobile" name="mobile" placeholder="Enter 10-digit mobile number" autocomplete="tel" inputmode="numeric" aria-describedby="mobileError" required>
                        <small class="field-error" id="mobileError" aria-live="polite"></small>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Academic Information</legend>

                    <div class="field-container">
                        <label for="department">Department <span class="required">*</span></label>
                        <select id="department" name="department" aria-describedby="departmentError" required>
                            <option value="">Select Department</option>
                            <option value="computer-engineering">Computer Engineering</option>
                            <option value="computer-science">Computer Science and Engineering</option>
                            <option value="information-technology">Information Technology</option>
                            <option value="mechanical-engineering">Mechanical Engineering</option>
                            <option value="civil-engineering">Civil Engineering</option>
                            <option value="electrical-engineering">Electrical Engineering</option>
                            <option value="electronics-communication">Electronics and Communication</option>
                        </select>
                        <small class="field-error" id="departmentError" aria-live="polite"></small>
                    </div>

                    <div class="field-container">
                        <label for="semester">Current Semester <span class="required">*</span></label>
                        <select id="semester" name="semester" aria-describedby="semesterError" required>
                            <option value="">Select Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                            <option value="7">Semester 7</option>
                            <option value="8">Semester 8</option>
                        </select>
                        <small class="field-error" id="semesterError" aria-live="polite"></small>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Areas of Interest</legend>

                    <p>Select the areas you are interested in:</p>

                    <label>
                        <input type="checkbox" name="interests[]" value="web-development">
                        Web Development
                    </label>

                    <label>
                        <input type="checkbox" name="interests[]" value="artificial-intelligence">
                        Artificial Intelligence
                    </label>

                    <label>
                        <input type="checkbox" name="interests[]" value="machine-learning">
                        Machine Learning
                    </label>

                    <label>
                        <input type="checkbox" name="interests[]" value="cyber-security">
                        Cyber Security
                    </label>

                    <label>
                        <input type="checkbox" name="interests[]" value="iot">
                        Internet of Things
                    </label>

                    <label>
                        <input type="checkbox" name="interests[]" value="data-science">
                        Data Science
                    </label>
                </fieldset>

                <fieldset>
                    <legend>Event Preferences</legend>

                    <p>Select the types of college events you are interested in:</p>

                    <label>
                        <input type="checkbox" name="event_preferences[]" value="technical">
                        Technical Events
                    </label>

                    <label>
                        <input type="checkbox" name="event_preferences[]" value="non-technical">
                        Non-Technical Events
                    </label>

                    <label>
                        <input type="checkbox" name="event_preferences[]" value="cultural">
                        Cultural Events
                    </label>

                    <label>
                        <input type="checkbox" name="event_preferences[]" value="sports">
                        Sports Events
                    </label>
                </fieldset>

                <fieldset>
                    <legend>Terms and Conditions</legend>

                    <label>
                        <input type="checkbox" id="terms" name="terms" value="accepted" required>
                        I agree to the StudentHub terms and conditions.
                    </label>

                    <label>
                        <input type="checkbox" id="privacy" name="privacy" value="accepted" required>
                        I agree to the StudentHub privacy policy.
                    </label>
                    <small class="field-error" id="termsError" aria-live="polite"></small>
                </fieldset>

                <div class="form-actions">
                    <input type="submit" id="submitbtn" value="Register" class="button">
                    <input type="reset" id="resetbtn" value="Reset" class="button">
                </div>

            </form>
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
    <script src="Scripts/formValidation.js" defer></script>
</body>
</html>
<?php exit; endif; ?>

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed.');
}

$enrollment = strtoupper(registerPostString('enrollment_number'));
$email = strtolower(registerPostString('email'));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$fullName = registerPostString('full_name');
$dob = registerPostString('date_of_birth');
$gender = registerPostString('gender');
$mobile = registerPostString('mobile');
$department = registerPostString('department');
$semester = registerPostString('semester');
$interests = $_POST['interests'] ?? [];
$eventPreferences = $_POST['event_preferences'] ?? [];

if (!is_string($password) || !is_string($confirmPassword)) {
    $errors[] = 'Invalid password input.';
    $password = '';
    $confirmPassword = '';
}

if (!preg_match('/^\d{2}[A-Z]{2}\d{3}$/', $enrollment)) {
    $errors[] = 'Enter a valid enrollment number, for example 25CE001.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^\d{2}[a-z]{2}\d{3}@charusat\.edu\.in$/i', $email)) {
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
if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = 'Select a valid gender option.';
}

if (!preg_match('/^(?:\+91[- ]?)?[6-9]\d{9}$/', $mobile)) {
    $errors[] = 'Enter a valid Indian mobile number.';
}

$departmentNames = [
    'computer-engineering' => 'Computer Engineering',
    'computer-science' => 'Computer Science and Engineering',
    'information-technology' => 'Information Technology',
    'mechanical-engineering' => 'Mechanical Engineering',
    'civil-engineering' => 'Civil Engineering',
    'electrical-engineering' => 'Electrical Engineering',
    'electronics-communication' => 'Electronics and Communication',
];

if (!array_key_exists($department, $departmentNames)) {
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
    'data-science',
];
$allowedEvents = ['technical', 'non-technical', 'cultural', 'sports'];

if (!is_array($interests) ||
    array_filter($interests, static fn($value): bool => !is_string($value) || !in_array($value, $allowedInterests, true))) {
    $errors[] = 'Invalid area of interest selected.';
    $interests = [];
}

if (!is_array($eventPreferences) ||
    array_filter($eventPreferences, static fn($value): bool => !is_string($value) || !in_array($value, $allowedEvents, true))) {
    $errors[] = 'Invalid event preference selected.';
    $eventPreferences = [];
}

if (($_POST['terms'] ?? '') !== 'accepted' ||
    ($_POST['privacy'] ?? '') !== 'accepted') {
    $errors[] = 'You must accept the terms and privacy policy.';
}

if (!$errors) {
    try {
        require __DIR__ . '/db.php';

        $pdo->beginTransaction();

        // The enrollment number is the student's unique account username.
        $checkUser = $pdo->prepare(
            'SELECT user_id FROM users WHERE username = :username OR email = :email LIMIT 1'
        );
        $checkUser->execute([
            ':username' => $enrollment,
            ':email' => $email,
        ]);

        if ($checkUser->fetch()) {
            throw new DomainException('This enrollment number or email is already registered. Please log in instead.');
        }

        $checkStudent = $pdo->prepare(
            'SELECT student_id FROM students WHERE enrollment_number = :enrollment OR email = :email LIMIT 1'
        );
        $checkStudent->execute([
            ':enrollment' => $enrollment,
            ':email' => $email,
        ]);

        if ($checkStudent->fetch()) {
            throw new DomainException('A student record with this enrollment number or email already exists. Please contact the administrator.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Password could not be secured.');
        }

        $insertUser = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role)
             VALUES (:username, :email, :password_hash, \'student\')'
        );
        $insertUser->execute([
            ':username' => $enrollment,
            ':email' => $email,
            ':password_hash' => $passwordHash,
        ]);
        $userId = (int) $pdo->lastInsertId();

        $insertStudent = $pdo->prepare(
            'INSERT INTO students
                (enrollment_number, full_name, email, department, semester)
             VALUES
                (:enrollment, :full_name, :email, :department, :semester)'
        );
        $insertStudent->execute([
            ':enrollment' => $enrollment,
            ':full_name' => $fullName,
            ':email' => $email,
            ':department' => $departmentNames[$department],
            ':semester' => (int) $semester,
        ]);

        $insertProfile = $pdo->prepare(
            'INSERT INTO student_profiles
                (user_id, date_of_birth, gender, mobile, interests, event_preferences,
                 terms_accepted_at, privacy_accepted_at)
             VALUES
                (:user_id, :dob, :gender, :mobile, :interests, :event_preferences,
                 CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $insertProfile->execute([
            ':user_id' => $userId,
            ':dob' => $dob,
            ':gender' => $gender,
            ':mobile' => $mobile,
            ':interests' => json_encode(array_values($interests), JSON_THROW_ON_ERROR),
            ':event_preferences' => json_encode(array_values($eventPreferences), JSON_THROW_ON_ERROR),
        ]);

        $pdo->commit();
        $success = true;
    } catch (DomainException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = $exception->getMessage();
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('StudentHub registration error: ' . $exception->getMessage());

        if ($exception instanceof PDOException && $exception->getCode() === '23000') {
            $errors[] = 'This enrollment number or email is already registered. Please log in instead.';
        } else {
            $errors[] = 'Registration could not be completed. Please check that the StudentHub database migration has been applied, then try again.';
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
    <link rel="stylesheet" href="Styles/common.css?v=site-shell-3">
    <link rel="stylesheet" href="Styles/register.css?v=auth-buttons-2">
    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</head>
<body>
<?php require __DIR__ . '/includes/site_header.php'; ?>
<?php require __DIR__ . '/includes/site_navigation.php'; ?>
<main>
    <section id="register-box" aria-labelledby="registration-result-heading">
        <h1 id="registration-result-heading">Registration Result</h1>

        <?php if ($success): ?>
            <p role="status">Your StudentHub account has been created successfully.</p>
            <p>You can now log in using your CHARUSAT email address and the password you created.</p>
            <p><a class="button" href="login.php">Go to Login</a></p>
        <?php else: ?>
            <h2>Please correct the following issues</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= registerEscape($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <p><a class="button" href="register.php">Return to registration form</a></p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
