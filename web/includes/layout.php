<?php
function page_header(string $title, string $active): void {
    $address = server_address();
    $online = server_online();
    $navigation = [
        'overview' => ['index.php', 'Overview'],
        'console' => ['console.php', 'Console'],
        'worlds' => ['worlds.php', 'Worlds'],
        'startup' => ['startup.php', 'Startup'],
        'settings' => ['settings.php', 'Configuration'],
        'management' => ['management.php', 'Management']
    ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> | Minecraft Panel</title>
<link rel="stylesheet" href="assets/panel.css?v=6">
<script defer src="assets/panel.js?v=6"></script>
</head>
<body>
<header class="topbar">
    <button class="menu-toggle" type="button" data-menu-toggle>Menu</button>
    <div class="brand"><span class="brand-mark">M</span><div>Minecraft Panel<small>Bedrock Hosting</small></div></div>
    <div class="top-actions">
        <button class="theme-toggle button-secondary" type="button" data-theme-toggle aria-label="Switch to light theme" title="Switch to light theme">
            <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"></path></svg>
            <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 15.2A8.8 8.8 0 0 1 8.8 3.5 9 9 0 1 0 20.5 15.2Z"></path></svg>
        </button>
        <div class="server-chip"><span class="dot <?= $online ? '' : 'offline' ?>"></span><?= htmlspecialchars($address) ?></div>
    </div>
</header>
<aside class="sidebar" data-sidebar>
    <nav>
    <?php foreach ($navigation as $key => [$url, $label]): ?>
        <a class="<?= $key === $active ? 'active' : '' ?>" href="<?= $url ?>"><?= $label ?></a>
    <?php endforeach; ?>
    </nav>
    <a class="logout" href="?logout=1">Sign out</a>
</aside>
<main class="page">
    <div class="page-title"><div><h1><?= htmlspecialchars($title) ?></h1><p>Manage your Minecraft Bedrock server securely.</p></div>
    <button class="copy-button button-secondary" type="button" data-copy="<?= htmlspecialchars($address) ?>" aria-label="Copy server address">
        <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 8h10v12H9z"></path><path d="M5 16H4V4h10v1"></path></svg>
        <span><?= htmlspecialchars($address) ?></span>
    </button></div>
    <div class="toast-stack" data-toast-stack aria-live="polite">
    <?php global $message, $messageType; if ($message !== ''): ?>
        <div class="toast toast-<?= $messageType === 'error' ? 'error' : 'success' ?>" data-toast>
            <span><?= nl2br(htmlspecialchars($message)) ?></span>
            <button class="toast-close" type="button" data-toast-close aria-label="Close notification">&times;</button>
        </div>
    <?php endif; ?>
    </div>
<?php
}

function page_footer(): void {
?>
</main>
</body>
</html>
<?php
}
