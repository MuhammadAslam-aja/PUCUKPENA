<?php
// admin/upload.php — Handler upload gambar (opsional, dipanggil via AJAX)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file    = $_FILES['file'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error code: ' . $file['error']]);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['error' => 'File terlalu besar. Maksimal 5MB.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['error' => 'Format tidak didukung. Gunakan JPG, PNG, atau WebP.']);
    exit;
}

// Validasi MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['error' => 'File bukan gambar yang valid.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$newName = 'img_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
$dest    = $uploadDir . $newName;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode([
        'success' => true,
        'path'    => 'uploads/' . $newName,
        'url'     => '../uploads/' . $newName,
        'name'    => $newName,
    ]);
} else {
    echo json_encode(['error' => 'Gagal memindahkan file. Periksa izin folder /uploads/']);
}
