<?php
$siteCurrentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$siteIsStudent = !empty($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'student');
if ($siteIsStudent) {
    require __DIR__ . '/student_nav.php';
} else {
    $siteNavigationItems = [
        ['label' => 'Home', 'href' => 'index.php', 'page' => 'index.php'],
        ['label' => 'About', 'href' => 'about.php', 'page' => 'about.php'],
        ['label' => 'Events', 'href' => 'events.php', 'page' => 'events.php'],
        ['label' => 'FAQ', 'href' => 'faq.php', 'page' => 'faq.php'],
        ['label' => 'Contact', 'href' => 'contact.php', 'page' => 'contact.php'],
    ];
?>
<nav aria-label="Main Navigation">
    <button id="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">☰ Menu</button>
    <ul id="main-nav">
        <?php foreach ($siteNavigationItems as $item): ?>
            <li><a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" <?= $siteCurrentPage === $item['page'] ? 'class="active" aria-current="page"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php } ?>
