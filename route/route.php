<?php
ini_set('display_errors', 0);

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = str_replace('/CardHaven', '', $request);
$segments = explode('/', trim($url, '/'));

if ($segments[0] === '' || $segments[0] === 'home') {
    include '../CardHaven/interface/login-page/index.php';
} 
else if ($segments[0] === 'register') {
    include '../CardHaven/interface/register-page/index.php';
} 
else if ($segments[0] === 'superadmin') {
    include '../CardHaven/interface/super-admin-page/index.php';
} 
else if ($segments[0] === 'admin') {
    include '../CardHaven/interface/admin-page/index.php';
} 
// else if ($segments[0] === 'owner') {
//     include '../CardHaven/interface/owner-page/index.php';
// } 
else {
    http_response_code(404);
    echo "404 Not Found njir";
}
?>