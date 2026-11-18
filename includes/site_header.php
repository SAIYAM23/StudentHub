<?php
$siteHeaderLoggedIn = !empty($_SESSION['user_id']);
$siteHeaderRole = (string) ($_SESSION['role'] ?? '');
?>
<header>
    <div id="logo">
        <h1>StudentHub</h1>
        <p>Your Digital Campus Companion</p>
    </div>
    <div id="header-buttons">
        <button type="button" id="theme-toggle" class="button theme-toggle" aria-pressed="false">🌙 Dark mode</button>
        <?php if (!$siteHeaderLoggedIn): ?>
            <a href="login.php" class="button">Login</a>
            <a href="register.php" class="button">Register</a>
        <?php elseif ($siteHeaderRole === 'admin'): ?>
            <a href="adminDashboard.php" class="button">Admin Dashboard</a>
            <a href="logout.php" class="button">Logout</a>
        <?php endif; ?>
    </div>
</header>
