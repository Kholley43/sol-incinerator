<?php
// Generate a simple favicon on-the-fly
header('Content-Type: image/png');
$size = 32;
$img = imagecreatetruecolor($size, $size);

// Colors
$bg = imagecolorallocate($img, 10, 14, 23);
$accent = imagecolorallocate($img, 58, 134, 255);
$accent2 = imagecolorallocate($img, 0, 198, 255);
$flame = imagecolorallocate($img, 255, 69, 0);
$flame2 = imagecolorallocate($img, 255, 135, 0);

// Background
imagefilledrectangle($img, 0, 0, $size, $size, $bg);

// Draw trash can
$width = $size * 0.6;
$height = $size * 0.7;
$x = ($size - $width) / 2;
$y = ($size - $height) / 2;

// Trash can top
// Create gradient effect for top
for ($i = 0; $i < 4; $i++) {
    $color = $i < 2 ? $accent : $accent2;
    imagefilledrectangle($img, $x - 2, $y - 3 + $i, $x + $width + 2, $y - 2 + $i, $color);
}

// Trash can body
// Left side
imageline($img, $x, $y, $x, $y + $height, $accent);
// Right side
imageline($img, $x + $width, $y, $x + $width, $y + $height, $accent2);
// Bottom
imageline($img, $x, $y + $height, $x + $width, $y + $height, $accent2);

// Flames
for ($i = 0; $i < 5; $i++) {
    $flameX = $x + $width/5 + ($i * $width/5);
    $flameHeight = rand($size/5, $size/3);
    
    // Create gradient flames
    $steps = $flameHeight;
    for ($j = 0; $j < $steps; $j++) {
        $ratio = $j / $steps;
        $color = $j < $steps/2 ? $flame : $flame2;
        imagesetpixel($img, $flameX, $y + $height/2 - $j, $color);
    }
}

// Output the image
imagepng($img);
imagedestroy($img);
?>
