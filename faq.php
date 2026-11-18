<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | FAQ</title>
    <link rel="stylesheet" href="Styles/common.css?v=full-theme-2">
    <link rel="stylesheet" href="Styles/faq.css">
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
        <section id="faq-intro">
            <h2>Frequently Asked Questions</h2>
            <p>
                Find answers to the most commonly asked questions
                about StudentHub and college events.
            </p>
        </section>

        <section aria-labelledby="faq-heading">
            <h2 id="faq-heading">Common Questions</h2>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-1" aria-expanded="false" aria-controls="faq-answer-1">1. What is StudentHub?</button>
                <div class="faq-answer" id="faq-answer-1" role="region" aria-labelledby="faq-question-1" hidden><p>StudentHub is a web portal that helps students access college events, announcements and campus information.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-2" aria-expanded="false" aria-controls="faq-answer-2">2. Who can register on StudentHub?</button>
                <div class="faq-answer" id="faq-answer-2" role="region" aria-labelledby="faq-question-2" hidden><p>Only students of the college can create a StudentHub account.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-3" aria-expanded="false" aria-controls="faq-answer-3">3. How can I register for an event?</button>
                <div class="faq-answer" id="faq-answer-3" role="region" aria-labelledby="faq-question-3" hidden><p>Login to your account and visit the Events page to register for available events.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-4" aria-expanded="false" aria-controls="faq-answer-4">4. Can I update my profile?</button>
                <div class="faq-answer" id="faq-answer-4" role="region" aria-labelledby="faq-question-4" hidden><p>Yes. Students can update their personal information from the Profile page after logging in.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-5" aria-expanded="false" aria-controls="faq-answer-5">5. Can I participate in more than one event?</button>
                <div class="faq-answer" id="faq-answer-5" role="region" aria-labelledby="faq-question-5" hidden><p>Yes. Students can register for multiple events based on eligibility and availability.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-6" aria-expanded="false" aria-controls="faq-answer-6">6. Who manages the events?</button>
                <div class="faq-answer" id="faq-answer-6" role="region" aria-labelledby="faq-question-6" hidden><p>College administrators manage and update all event information through the Admin Dashboard.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-7" aria-expanded="false" aria-controls="faq-answer-7">7. What should I do if I face any issue?</button>
                <div class="faq-answer" id="faq-answer-7" role="region" aria-labelledby="faq-question-7" hidden><p>Visit the Contact page and submit your query using the contact form.</p></div>
            </article>

            <article class="faq-box">
                <button type="button" class="faq-question" id="faq-question-8" aria-expanded="false" aria-controls="faq-answer-8">8. Is StudentHub free to use?</button>
                <div class="faq-answer" id="faq-answer-8" role="region" aria-labelledby="faq-question-8" hidden><p>Yes. StudentHub is completely free for all students of the college.</p></div>
            </article>
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
</body>
</html>