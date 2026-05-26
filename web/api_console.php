<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "=== Live Console Log ===\n";
echo is_readable(LOG_FILE) ? (string)shell_exec('tail -n 80 ' . escapeshellarg(LOG_FILE)) : 'Log file not found.';
