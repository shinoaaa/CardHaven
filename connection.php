<?php

    if (!function_exists('loadEnv')) {
        function loadEnv($path) {
            if (!file_exists($path)) return;
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                        putenv(sprintf('%s=%s', $name, $value));
                        $_ENV[$name] = $value;
                        $_SERVER[$name] = $value;
                    }
                }
            }
        }
    }
    
    loadEnv(__DIR__ . '/.env');

    $serverName = $_ENV['DB_SERVER'];
    $serverProp = [
        'database' => 'CardHaven',
        'UID' => 'sa',
        'PWD' => 'Admin123!',
        'TrustServerCertificate' => true
    ];

    $conn = sqlsrv_connect($serverName,$serverProp);

    if(!$conn){
        echo 'error jir';
        die(print_r(sqlsrv_errors(), true));
    }
    else{
        // echo "lesgo";
    }
?>