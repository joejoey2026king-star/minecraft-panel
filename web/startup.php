<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$gamemode = server_property('gamemode', 'survival');
$difficulty = server_property('difficulty', 'easy');
$allowCheats = server_property('allow-cheats', 'false') === 'true';
$maxPlayers = server_property('max-players', '10');
$serverPort = server_property('server-port', '19132');
$viewDistance = server_property('view-distance', '16');
$tickDistance = server_property('tick-distance', '4');
page_header('Startup', 'startup');
?>
<div class="grid">
    <section class="card full">
        <h2>Bedrock Startup Settings</h2>
        <p class="muted">Control common server settings without editing the file manually. Restart the server after saving to apply changes.</p>
        <form method="post" class="startup-form">
            <div class="setting-grid">
                <label class="setting">
                    <span>Gamemode</span>
                    <select name="gamemode">
                        <?php foreach (['survival', 'creative', 'adventure'] as $option): ?>
                        <option value="<?= $option ?>" <?= $gamemode === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="setting">
                    <span>Difficulty</span>
                    <select name="difficulty">
                        <?php foreach (['peaceful', 'easy', 'normal', 'hard'] as $option): ?>
                        <option value="<?= $option ?>" <?= $difficulty === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="setting toggle-setting">
                    <span>Allow Cheats</span>
                    <span class="switch">
                        <input type="checkbox" name="allow_cheats" value="true" <?= $allowCheats ? 'checked' : '' ?>>
                        <span class="switch-slider"></span>
                        <span class="switch-state"><?= $allowCheats ? 'Enabled' : 'Disabled' ?></span>
                    </span>
                </label>
                <label class="setting">
                    <span>Max Players</span>
                    <input type="number" name="max_players" min="1" max="1000" value="<?= htmlspecialchars($maxPlayers) ?>" required>
                </label>
                <label class="setting">
                    <span>Server Port</span>
                    <input type="number" name="server_port" min="1" max="65535" value="<?= htmlspecialchars($serverPort) ?>" required>
                </label>
                <label class="setting">
                    <span>View Distance</span>
                    <input type="number" name="view_distance" min="5" max="96" value="<?= htmlspecialchars($viewDistance) ?>" required>
                    <small>Higher values require more CPU and network bandwidth.</small>
                </label>
                <label class="setting">
                    <span>Tick Distance</span>
                    <input type="number" name="tick_distance" min="4" max="12" value="<?= htmlspecialchars($tickDistance) ?>" required>
                    <small>Bedrock valid range is 4 to 12.</small>
                </label>
            </div>
            <div class="controls form-actions">
                <button name="action" value="save_startup" class="button-success">Save Startup Settings</button>
                <button name="action" value="restart" class="button-secondary">Restart Server</button>
            </div>
        </form>
    </section>
</div>
<?php page_footer(); ?>
