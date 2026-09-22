<?php
$source = __DIR__ . '/logo.jpeg';
if (!file_exists($source)) {
    die("logo.jpeg not found\n");
}

$img = imagecreatefromjpeg($source);
$w = imagesx($img);
$h = imagesy($img);

$minX = $w; $minY = $h; $maxX = 0; $maxY = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r < 235 || $g < 235 || $b < 235) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

echo "Logo content bounding box: X=$minX, Y=$minY, W=" . ($maxX - $minX) . ", H=" . ($maxY - $minY) . "\n";

// Add padding
$pad = 20;
$cropX = max(0, $minX - $pad);
$cropY = max(0, $minY - $pad);
$cropW = min($w - $cropX, ($maxX - $minX) + ($pad * 2));
$cropH = min($h - $cropY, ($maxY - $minY) + ($pad * 2));

$cropped = imagecrop($img, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
imagepng($cropped, __DIR__ . '/logo-tight.png');

// Monogram Emblem Only (left part)
$monogramW = intval($cropH * 1.1);
$monogram = imagecrop($img, ['x' => $cropX, 'y' => $cropY, 'width' => $monogramW, 'height' => $cropH]);
imagepng($monogram, __DIR__ . '/logo-monogram.png');

// Generate crisp white/transparent version for dark headers
$trans = imagecreatetruecolor($cropW, $cropH);
imagealphablending($trans, false);
imagesavealpha($trans, true);
$transparent = imagecolorallocatealpha($trans, 0, 0, 0, 127);
imagefilledrectangle($trans, 0, 0, $cropW, $cropH, $transparent);

for ($y = 0; $y < $cropH; $y++) {
    for ($x = 0; $x < $cropW; $x++) {
        $rgb = imagecolorat($cropped, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $brightness = ($r + $g + $b) / 3;
        if ($brightness < 200) {
            // Anti-aliased white pixel
            $alpha = intval(($brightness / 200) * 127);
            $color = imagecolorallocatealpha($trans, 255, 255, 255, $alpha);
            imagesetpixel($trans, $x, $y, $color);
        }
    }
}
imagepng($trans, __DIR__ . '/logo-white.png');

echo "Generated logo-tight.png, logo-monogram.png and logo-white.png successfully!\n";
