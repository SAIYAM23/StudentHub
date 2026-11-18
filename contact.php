<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Contact</title>
    <link rel="stylesheet" href="Styles/common.css?v=contact-clean-1">
    <link rel="stylesheet" href="Styles/contact.css?v=contact-clean-1">
</head>
<body>
    <?php require __DIR__ . '/includes/site_header.php'; ?>
    <?php require __DIR__ . '/includes/site_navigation.php'; ?>

    <main class="contact-page">
        <section id="contact-info" aria-labelledby="contact-heading">
            <h2 id="contact-heading">Contact StudentHub</h2>
            <p>
                StudentHub is used to access student information and keep up
                with campus events. For help with your account, event
                registration or student records, contact your institution's
                StudentHub administrator through an official college
                communication channel.
            </p>
        </section>

        <section id="contact-form" aria-labelledby="contact-help-heading">
            <h2 id="contact-help-heading">How can we help?</h2>
            <p>
                When requesting support, include the page you were using,
                a short description of the issue and (if relevant) the event
                name. Do not send your password or other sensitive login details.
            </p>
            <div class="contact-help-links">
                <a class="button" href="faq.php">Read Frequently Asked Questions</a>
                <a class="button" href="events.php">Browse Events</a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a class="button" href="<?= (($_SESSION['role'] ?? '') === 'admin') ? 'adminDashboard.php' : 'dashboard.php' ?>">Go to Dashboard</a>
                <?php else: ?>
                    <a class="button" href="login.php">Login to StudentHub</a>
                <?php endif; ?>
            </div>
            <p class="contact-configuration-note">
                Direct email, phone and message-submission details are not
                configured in this installation, so this page does not display
                placeholder contact information or a non-working message form.
            </p>
        </section>
    </main>

    <footer>
        <h2>StudentHub</h2>
        <p>Your Digital Campus Companion</p>
        <p>&copy; <?= date('Y') ?> StudentHub. All Rights Reserved.</p>
    </footer>

    <script src="Scripts/practical4.js?v=theme-audit-3" defer></script>
</body>
</html>
