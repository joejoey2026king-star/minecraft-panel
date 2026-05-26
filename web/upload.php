<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    exit('Authentication required');
}

header('Content-Type: text/plain; charset=utf-8');

$chunkDir = __DIR__ . '/uploads/chunks';
if (!is_dir($chunkDir) && !mkdir($chunkDir, 0750, true)) {
    http_response_code(500);
    exit('Cannot create upload directory');
}

if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('No valid chunk received');
}

$name = basename($_POST['name'] ?? '');
$index = filter_input(INPUT_POST, 'index', FILTER_VALIDATE_INT);
$total = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_INT);
if (!preg_match('/\.(zip|tar\.gz)$/i', $name) || $index === null || $index === false ||
    $total === null || $total === false ||
    $index < 0 || $total < 1 || $index >= $total) {
    http_response_code(400);
    exit('Invalid upload details');
}

if ($index === 0) {
    foreach (glob($chunkDir . '/' . $name . '.part*') ?: [] as $oldChunk) {
        unlink($oldChunk);
    }
}

$target = $chunkDir . '/' . $name . '.part' . $index;
if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $target)) {
    http_response_code(500);
    exit("Failed chunk $index");
}

echo "Chunk " . ($index + 1) . " of $total uploaded";
