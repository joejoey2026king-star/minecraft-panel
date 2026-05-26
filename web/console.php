<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
page_header('Console', 'console');
?>
<div class="grid">
    <section class="card full">
        <h2>Live Server Console</h2>
        <pre class="console" data-live-console>Loading console...</pre>
        <form method="post" class="controls" style="margin-top:12px">
            <button name="action" value="clear_console" class="button-secondary">Clear Console Display</button>
            <button name="action" value="restart" class="button-secondary">Restart Server</button>
        </form>
    </section>
    <section class="card half">
        <h2>Broadcast Message</h2>
        <form method="post" class="row">
            <input name="command" placeholder="Message to online players" required>
            <button name="action" value="command" class="button-success">Send</button>
        </form>
    </section>
    <section class="card half">
        <h2>Gamerule</h2>
        <form method="post" class="row">
            <select name="gamerule"><?php foreach ($gamerules as $rule): ?><option><?= htmlspecialchars($rule) ?></option><?php endforeach; ?></select>
            <input name="value" placeholder="true / false / number" required>
            <button name="action" value="gamerule" class="button-success">Apply</button>
        </form>
    </section>
</div>
<?php page_footer(); ?>
