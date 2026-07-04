
<?php

// ==========================================
// FUNGSI UNTUK MEMBACA FILE .env
// ==========================================
function loadEnv($path) {
    if (!file_exists($path)) {
        die("File .env tidak ditemukan di path: " . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Abaikan baris komentar (yang diawali #)
        if (strpos(trim($line), '#') === 0) continue;

        // Pisahkan key dan value berdasarkan tanda '='
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Masukkan ke dalam environment variable PHP
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// 1. Arahkan path ini ke lokasi file .env kamu berada. 
// Asumsi: file .env ada di folder root (CardHaven/.env)
$envPath = __DIR__ . '/.env'; 
loadEnv($envPath);