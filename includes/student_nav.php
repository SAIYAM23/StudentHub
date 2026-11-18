<?php
declare(strict_types=1);

$currentStudentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

$studentNavigationItems = [
    ['label' => 'Home', 'href' => 'index.php', 'page' => 'index.php'],
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'page' => 'dashboard.php'],
    ['label' => 'Profile', 'href' => 'profile.php', 'page' => 'profile.php'],
    ['label' => 'Events', 'href' => 'events.php', 'page' => 'events.php'],
    ['label' => 'My Events', 'href' => 'myEvents.php', 'page' => 'myEvents.php'],
    ['label' => 'Logout', 'href' => 'logout.php', 'page' => null],
];
?>
<nav class="student-navigation" aria-label="Student Navigation">
    <button id="menu-toggle" type="button"
            aria-label="Toggle navigation"
            aria-expanded="false" aria-controls="main-nav">
        ☰ Menu
    </button>

    <ul id="main-nav">
        <?php foreach ($studentNavigationItems as $item): ?>
            <?php $isActive = $item['page'] !== null && $currentStudentPage === $item['page']; ?>
            <li>
                <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                   <?= $isActive ? 'class="active" aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
