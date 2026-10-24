<?php
// Simple guest photo upload handler. Requires PHP (Interserver/cPanel supports this by default).
$targetDir = __DIR__ . '/uploads/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Belt and braces: never let the web server run anything in uploads/, and don't list it.
$htaccess = $targetDir . '.htaccess';
if (!is_file($htaccess)) {
    file_put_contents($htaccess, <<<HTACCESS
Options -Indexes -ExecCGI
<FilesMatch "\.(?i:php\d*|phtml|phar|phps|pht|cgi|pl|py|sh|shtml|html?|svg|js)$">
    Require all denied
</FilesMatch>

HTACCESS);
}

$maxBytes = 15 * 1024 * 1024; // 15MB per photo
// Detected MIME type => extension we save it with. The uploader's filename is never used.
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/heic' => 'heic',
    'image/heif' => 'heic',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

function fail($msg) {
    http_response_code(400);
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Upload failed</title></head><body style='font-family:sans-serif;text-align:center;padding:80px 20px;'><h1>Something went wrong</h1><p>$msg</p><p><a href='index.html#gallery'>&larr; Back to the site</a></p></body></html>";
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    fail('No photo was received. Please try again.');
}

$file = $_FILES['photo'];

if ($file['size'] > $maxBytes) {
    fail('That photo is too large (15MB max).');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowed[$mime])) {
    fail('Please upload a photo file (JPG, PNG, HEIC, WEBP, or GIF).');
}

$ext = $allowed[$mime];
$safeName = 'guest-' . date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $targetDir . $safeName)) {
    fail('We could not save your photo. Please try again.');
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Thank you!</title></head><body style='font-family:sans-serif;text-align:center;padding:80px 20px;'><h1>Thank you!</h1><p>Your photo was uploaded. We can't wait to see it.</p><p><a href='index.html#gallery'>&larr; Back to the site</a></p></body></html>";
