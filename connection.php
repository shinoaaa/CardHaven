<?php 
    $serverName = '192.168.137.1';
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