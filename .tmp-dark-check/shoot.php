<?php
// temporary: downscale the attached reference screenshot so it can be attached back
$src = 'C:/Users/p.c/Downloads/ChatGPT Image Sep 29, 2026, 12_08_08 PM.png';
$dst = __DIR__ . '/reference.png';

$img = imagecreatefrompng($src);
$w = imagesx($img);
$h = imagesy($img);
$targetW = 1000;
$targetH = (int) round($h * ($targetW / $w));
$out = imagecreatetruecolor($targetW, $targetH);
imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
imagecopyresampled($out, $img, 0, 0, 0, 0, $targetW, $targetH, $w, $h);
imagepng($out, $dst, 9);

clearstatcache();
echo "src {$w}x{$h} -> {$targetW}x{$targetH}, " . round(filesize($dst) / 1024) . " KB\n";
