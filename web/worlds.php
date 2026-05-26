<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['download_backup']) && $_GET['download_backup'] === 'latest') {
    $files = glob(BACKUP_DIR . '/bedrock_world_*.tar.gz') ?: [];
    rsort($files);
    $file = $files[0] ?? null;
    if (!$file || !is_readable($file)) {
        http_response_code(404);
        exit('No backup found.');
    }
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

require_once __DIR__ . '/includes/layout.php';
page_header('Worlds & Backups', 'worlds');
?>
<div class="grid">
    <section class="card wide">
        <h2>Upload & Restore World</h2>
        <p class="muted">Upload `.zip` or `.tar.gz` backups. Existing matching worlds are backed up before restoration.</p>
        <div id="dropZone">Drop a world backup here, or click to choose a file</div>
        <input id="worldFile" type="file" accept=".zip,.tar.gz" hidden>
        <div class="file-selected" id="selectedFile"></div>
        <button id="uploadBtn" class="button-success" type="button">Upload & Restore</button>
        <div class="progress"><span id="uploadBar"></span></div>
        <pre class="console terminal-small" id="uploadLog">Ready for a world backup.</pre>
    </section>
    <section class="card">
        <h2>Backup Tools</h2>
        <p class="muted">Create a fresh backup before large changes or download your latest archived world.</p>
        <div class="backup-actions">
            <form method="post"><button name="action" value="backup" class="button-success">Create Backup Now</button></form>
            <a class="button button-success download" href="?download_backup=latest" data-download>Download Latest Backup</a>
        </div>
    </section>
</div>
<?php page_footer(); ?>
