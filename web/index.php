<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$online = server_online();
$installed = installed_version();
page_header('Overview', 'overview');
?>
<div class="grid">
    <section class="card">
        <h2>Server Status</h2>
        <p class="status <?= $online ? '' : 'offline' ?>"><?= $online ? 'Online' : 'Offline' ?></p>
        <p class="muted"><?= htmlspecialchars(server_address()) ?></p>
    </section>
    <section class="card">
        <h2>Bedrock Version</h2>
        <div class="stat"><?= htmlspecialchars($installed ?? 'Unknown') ?></div>
        <a class="muted" href="management.php">View updates and management</a>
    </section>
    <section class="card">
        <h2>Quick Controls</h2>
        <form method="post" class="controls">
            <button name="action" value="start" class="button-success">Start</button>
            <button name="action" value="restart" class="button-secondary">Restart</button>
            <button name="action" value="stop" class="button-danger">Stop</button>
        </form>
    </section>
    <section class="card">
        <h2>Uptime</h2>
        <div class="stat"><?= htmlspecialchars(uptime_text()) ?></div>
        <p class="muted">System uptime</p>
    </section>
    <section class="card">
        <h2>Memory</h2>
        <div class="stat"><?= htmlspecialchars(memory_text()) ?></div>
        <p class="muted">Used / installed memory</p>
    </section>
    <section class="card">
        <h2>Storage</h2>
        <div class="stat"><?= htmlspecialchars(format_bytes(disk_total_space('/') - disk_free_space('/'))) ?></div>
        <p class="muted">of <?= htmlspecialchars(format_bytes(disk_total_space('/'))) ?> used</p>
    </section>
    <section class="card wide">
        <h2>Recent Console Activity</h2>
        <pre class="console terminal-small" data-live-console>Loading console...</pre>
    </section>
    <section class="card">
        <h2>Common Tasks</h2>
        <div class="actions">
            <a class="button button-success" href="worlds.php">Upload or Back Up Worlds</a>
            <a class="button button-secondary" href="settings.php">Edit Server Settings</a>
            <a class="button button-secondary" href="console.php">Open Console</a>
        </div>
    </section>
</div>
<?php page_footer(); ?>
