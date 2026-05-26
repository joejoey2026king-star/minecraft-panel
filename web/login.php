<?php
session_start();

$USERNAME = 'admin';
$PASSWORD = 'mika';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = (string)($_POST['username'] ?? '');
    $pass = (string)($_POST['password'] ?? '');
    if ($user === $USERNAME && $pass === $PASSWORD) {
        $_SESSION['logged_in'] = true;
        $_SESSION['panel_toast'] = ['message' => 'Login successful.', 'type' => 'success'];
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In | Minecraft Panel</title>
<link rel="stylesheet" href="assets/login.css?v=6">
<script defer src="assets/panel.js?v=6"></script>
</head>
<body>
<button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to light theme" title="Switch to light theme">
    <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"></path></svg>
    <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 15.2A8.8 8.8 0 0 1 8.8 3.5 9 9 0 1 0 20.5 15.2Z"></path></svg>
</button>
<div class="login-box">
    <div class="brand-mark">M</div>
    <h2>Minecraft Panel</h2>
    <p>Sign in to manage your Bedrock server.</p>
    <?php if ($error !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <input name="username" placeholder="Username" autocomplete="username" required>
        <input name="password" type="password" placeholder="Password" autocomplete="current-password" required>
        <button type="submit">Sign In</button>
    </form>
</div>
</body>
</html>
