<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$installed = installed_version();
$latest = latest_version();
if (!$installed && $latest && is_executable(SERVER_DIR . '/bedrock_server')) {
    $installed = $latest;
}
$updateAvailable = $installed && $latest && version_compare($latest, $installed, '>');
page_header('Management', 'management');
?>
<div class="grid">
    <section class="card half">
        <h2>Server Version</h2>
        <p class="version-line"><span>Installed</span><strong><?= htmlspecialchars($installed ?? 'Unknown') ?></strong></p>
        <p class="version-line"><span>Latest available</span><strong><?= htmlspecialchars($latest ?? 'Unable to check') ?></strong></p>
        <?php if ($updateAvailable): ?>
        <p class="warning">A new Bedrock server release is ready.</p>
        <form method="post"><button name="action" value="update" class="button-success">Update Server</button></form>
        <?php elseif ($installed && $latest): ?>
        <p class="good">Your server is up to date.</p>
        <?php endif; ?>
    </section>
    <section class="card half">
        <h2>Server Installation</h2>
        <p class="muted">Reinstall and updates preserve configuration and worlds with a backup. Uninstall permanently deletes server worlds.</p>
        <div class="actions">
            <form method="post"><button name="action" value="install" class="button-secondary">Install Server Files</button></form>
            <form method="post"><button name="action" value="reinstall" class="button-secondary">Reinstall Safely (Keeps Worlds)</button></form>
            <form method="post"><button name="action" value="uninstall" class="button-danger" onclick="return confirm('Delete the server and all worlds?');">Uninstall (Deletes Worlds)</button></form>
        </div>
    </section>
</div>
<?php page_footer(); ?>
