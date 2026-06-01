<?php
require 'controller/accountController.php';

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$url = str_replace('/CardHaven', '', $request);

$segments = explode('/', trim($url, '/'));

if ($segments[0] === '' || $segments[0] === 'home') {
    include '../CardHaven/interface/login-page/index.php';
} 

// else if ($segments[0] === 'homew') {
//     // Ambil segment kedua (index 1), kalau gak ada set jadi null
//     $id = $segments[1] ?? null; 

//     if ($id !== null) {
//         // Direkomendasikan pakai kurung kurawal {$id} biar string interpolation-nya aman
//         echo "anak {$segments[0]} itu idnya {$id}";
//     } else {
//         // Antisipasi kalau user lupa ngetik ID di URL
//         echo "Lu masuk ke homew, tapi ID-nya mana jir? :v";
//     }
// }

else if ($segments[0] === 'register') {
    include '../CardHaven/interface/register-page/index.php';
} 
else {
    http_response_code(404);
    echo "404 Not Found";
}
?>