<?php
// C:\xampp\htdocs\CardHaven\diagnose.php
echo "<h2 style='font-family: sans-serif;'>CardHaven Assets Scanner</h2>";
$root = __DIR__;
$assetsPath = $root . '/assets';

if (!is_dir($assetsPath)) {
    echo "<p style='color: red; font-family: sans-serif;'><b>Error:</b> Folder <code>$assetsPath</code> tidak ditemukan!</p>";
    exit;
}

echo "<p style='font-family: sans-serif;'>Memindai seluruh isi folder: <b>$assetsPath</b></p>";

function scanDirectory($dir, $rootPath) {
    $result = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $result[$item] = scanDirectory($path, $rootPath);
        } else {
            // Sederhanakan path untuk ditampilkan
            $relativePath = str_replace($rootPath, '', $path);
            $relativePath = str_replace('\\', '/', $relativePath);
            $result[] = $relativePath;
        }
    }
    return $result;
}

$files = scanDirectory($assetsPath, $root);

echo "<pre style='background: #f4f4f4; border: 1px solid #ccc; padding: 15px; font-family: monospace; font-size: 14px;'>";
print_r($files);
echo "</pre>";
?>