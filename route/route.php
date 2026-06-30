<?php
ini_set('display_errors', 0);

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = str_replace('/CardHaven', '', $request);
$segments = explode('/', trim($url, '/'));

if ($segments[0] === '' || $segments[0] === 'home') {
    include '../CardHaven/interface/page-customer/index.php';
} 
else if ($segments[0] === 'register') {
    include '../CardHaven/interface/register-page/index.php';
} 
else if ($segments[0] === 'login') {
    include '../CardHaven/interface/login-page/index.php';
} 
else if ($segments[0] === 'dashboard') {
    include '../CardHaven/interface/page-admin/index.php';
} 
else if ($segments[0] === 'settingaccount') {
    include '../CardHaven/interface/super-admin-page/account-setting.php';
} 
else if ($segments[0] === 'checkout') {
    include '../CardHaven/interface/checkout/checkout.php';
} 
else if ($segments[0] === 'profilepage') {
    include '../CardHaven/interface/page-profile/index.php';
}




// else if ($segments[0] === 'owner') {
//     include '../CardHaven/interface/owner-page/index.php';
// } 
else {
    http_response_code(404);
    echo "404 Not Found";
}
?>