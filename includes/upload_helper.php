<?php
function handleUpload(array $file, string $subdir, array $allowedMimes, int $maxBytes): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed (error code ' . $file['error'] . ').', 'path' => ''];
    }

    if ($file['size'] > $maxBytes) {
        $mb = round($maxBytes / 1024 / 1024);
        return ['error' => "File exceeds maximum size of {$mb}MB.", 'path' => ''];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes, true)) {
        return ['error' => 'Invalid file type. Detected: ' . htmlspecialchars($mime), 'path' => ''];
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('', true) . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/uploads/' . $subdir . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0775);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['error' => 'Could not save file. Check server write permissions.', 'path' => ''];
    }

    return ['error' => '', 'path' => 'assets/uploads/' . $subdir . '/' . $filename];
}
