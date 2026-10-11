<?php
// POST (admin) a picture as multipart "file". It is redrawn at most 800px wide or high
// and saved in /uploads (kept between deploys, see deploy.yml). Returns {"url": "/uploads/<name>"}.
require __DIR__ . '/auth.php';

header('Content-Type: application/json');
require_admin();

$file = $_FILES['file']['tmp_name'] ?? '';
$img = is_uploaded_file($file) && getimagesize($file) ? imagecreatefromstring(file_get_contents($file)) : false;
if (!$img) {
    http_response_code(400);
    exit('{"error":"not an image"}');
}

$w = imagesx($img);
$h = imagesy($img);
$scale = min(1, 800 / max($w, $h));
$out = imagecreatetruecolor(max(1, round($w * $scale)), max(1, round($h * $scale)));
imagealphablending($out, false);
imagesavealpha($out, true);
imagecopyresampled($out, $img, 0, 0, 0, 0, imagesx($out), imagesy($out), $w, $h);

// Re-encoding also drops anything hidden in the original file. WebP keeps transparent drawings.
$dir = dirname(__DIR__) . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0755);
$name = bin2hex(random_bytes(8)) . (function_exists('imagewebp') ? '.webp' : '.jpg');
function_exists('imagewebp') ? imagewebp($out, "$dir/$name", 82) : imagejpeg($out, "$dir/$name", 85);

echo json_encode(['url' => "/uploads/$name"]);
