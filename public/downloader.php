<?php
// public/downloader.php
// Script to reliably download Adminer core file
ini_set('display_errors', 1);
error_reporting(E_ALL);

$url = 'https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-en.php';
$destination = __DIR__ . '/adminer_core.php';

echo "<h2>Adminer Downloader</h2>";
echo "Target: $destination<br>";
echo "Source: $url<br><br>";

// 1. Try file_get_contents
echo "Attempting download via file_get_contents... ";
$context = stream_context_create([
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ],
    "http" => [
        "follow_location" => true
    ]
]);

$content = @file_get_contents($url, false, $context);

if ($content !== false && strlen($content) > 50000) {
    if (file_put_contents($destination, $content)) {
        echo "<b style='color:green'>Success!</b><br>";
        echo "Saved " . strlen($content) . " bytes.<br>";
    } else {
        echo "<b style='color:red'>Failed to write file.</b> Permission check required.<br>";
    }
} else {
    echo "<b style='color:red'>Failed.</b><br>";
    if ($content)
        echo "Valid content not received. Size: " . strlen($content) . "<br>";

    // 2. Try cURL
    echo "<br>Attempting download via cURL... ";
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $content = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($content && strlen($content) > 50000) {
            if (file_put_contents($destination, $content)) {
                echo "<b style='color:green'>Success!</b><br>";
                echo "Saved " . strlen($content) . " bytes.<br>";
            } else {
                echo "<b style='color:red'>Failed to write file.</b><br>";
            }
        } else {
            echo "<b style='color:red'>Failed.</b> cURL Error: $error<br>";
        }
    } else {
        echo "cURL not available.<br>";
    }
}

// Validation
if (file_exists($destination) && filesize($destination) > 50000) {
    echo "<hr><h3>Verification</h3>";
    echo "File exists and size looks correct (" . filesize($destination) . " bytes).<br>";
    echo "First 50 chars: <code>" . htmlspecialchars(substr(file_get_contents($destination), 0, 50)) . "</code><br>";
    echo "<br><a href='/public/adminer.php' style='font-size:20px; font-weight:bold;'>Open Adminer Now</a>";
} else {
    echo "<hr><h3 style='color:red'>Critical Failure</h3>";
    echo "Could not download Adminer. Please manually download <b>adminer-4.8.1-en.php</b> and rename it to <b>public/adminer_core.php</b>.";
}
