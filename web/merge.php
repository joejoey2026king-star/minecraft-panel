<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    exit('Authentication required');
}

header('Content-Type: text/plain; charset=utf-8');

$name = basename($_GET['name'] ?? '');
$total = filter_input(INPUT_GET, 'total', FILTER_VALIDATE_INT);
if (!preg_match('/\.(zip|tar\.gz)$/i', $name) || $total === null || $total === false || $total < 1) {
    http_response_code(400);
    exit('Invalid file details');
}

$uploadDir = __DIR__ . '/uploads';
$chunkDir = $uploadDir . '/chunks';
$final = $uploadDir . '/' . $name;
@unlink($final);

for ($i = 0; $i < $total; $i++) {
    if (!is_file($chunkDir . '/' . $name . '.part' . $i)) {
        http_response_code(400);
        exit("Missing chunk $i");
    }
}

$fp = fopen($final, 'wb');
if (!$fp) {
    http_response_code(500);
    exit('Cannot create merged upload');
}

for ($i = 0; $i < $total; $i++) {
    $chunkFile = $chunkDir . '/' . $name . '.part' . $i;
    $in = fopen($chunkFile, 'rb');
    if (!$in || stream_copy_to_stream($in, $fp) === false) {
        if ($in) fclose($in);
        fclose($fp);
        @unlink($final);
        http_response_code(500);
        exit("Failed to merge chunk $i");
    }
    fclose($in);
    unlink($chunkFile);
}
fclose($fp);

echo "File uploaded and merged: $name";
