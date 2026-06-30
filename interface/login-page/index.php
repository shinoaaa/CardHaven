<?php
// =========================================================
// 1. CONFIG GOOGLE OAUTH
// =========================================================
// $client_id     = ***REMOVED_GOOGLE_ID***
$redirect_uri  = "http://localhost/cardhaven/interface/login-page/google-callback.php"; 
$scope         = "email profile openid";
$response_type = "code";

$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id'     => $client_id,
    'redirect_uri'  => $redirect_uri,
    'scope'         => $scope,
    'response_type' => $response_type
]); 

// =========================================================
// 2. CONFIG DISCORD OAUTH
// =========================================================
// $client_id_discord     = ***REMOVED_DISCORD_ID***
$redirect_uri_discord  = "http://localhost/cardhaven/interface/login-page/discord-callback.php"; 
$scope_discord         = "identify email"; 
$response_type_discord = "code";

$discord_login_url = "https://discord.com/api/oauth2/authorize?" . http_build_query([
    'client_id'     => $client_id_discord,
    'redirect_uri'  => $redirect_uri_discord,
    'scope'         => $scope_discord,
    'response_type' => $response_type_discord
]);

// =========================================================
// 3. CONFIG FACEBOOK OAUTH
// =========================================================
// $client_id_facebook     = ***REMOVED_FACEBOOK_ID***
$redirect_uri_facebook  = "http://localhost/cardhaven/interface/login-page/facebook-callback.php"; 
$scope_facebook         = "email";
$response_type_facebook = "code";

$facebook_login_url = "https://www.facebook.com/v20.0/dialog/oauth?" . http_build_query([
    'client_id'     => $client_id_facebook,
    'redirect_uri'  => $redirect_uri_facebook,
    'scope'         => $scope_facebook,
    'response_type' => $response_type_facebook
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="image-wrap">
                <img src="/cardhaven/assets/image/login-image.svg" style="object-fit: cover; height: 100%;">
            </div>
            <div class="form-section" id="login-wrap">
                <div class="form-container">
                    <h1 class="coolvetica">Login</h1>
                    <div class="social-login">
                        <!-- Tombol Login Google -->
                        <a href="<?php echo $google_login_url; ?>" class="social-btn" style="display: flex; justify-content: center; align-items: center; text-decoration: none;">
                            <img src="/cardhaven/assets/image/google.svg" alt="Google">
                        </a>
                        
                        <!-- Tombol Discord -->
                        <a href="<?php echo $discord_login_url; ?>" class="social-btn" style="display: flex; justify-content: center; align-items: center; text-decoration: none;">
                            <img src="/cardhaven/assets/image/discord.svg" alt="Discord">
                        </a>
                        
                        <!-- Tombol Facebook -->
                        <a href="<?php echo $facebook_login_url; ?>" class="social-btn" style="display: flex; justify-content: center; align-items: center; text-decoration: none;">
                            <img src="/cardhaven/assets/image/facebook.svg" alt="Facebook">
                        </a>
                    </div>

                    <div class="divider">
                        <span>Or</span>
                    </div>

                    <form id="loginForm" novalidate>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="emailInput" name="email" placeholder="enter email..." required>
                            <small id="error-email" class="error-message"></small>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" id="passwordInput" name="password" placeholder="enter password..." required>
                            <small id="error-pass" class="error-message"></small>
                        </div>
                        
                        <div style="width: 100%; height: 25px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 17px;">
                            <div style="width: auto; height: 100%; display: flex; align-items: center; gap: 10px;">
                                <div id="checkbox">✔</div>
                                <p id="checkText" style="text-decoration: underline; color: #0088FF; font-size: 13px; cursor: pointer;">Remember Me</p>
                            </div>
                            <a style="color: #0088FF; font-size: 13px; cursor: pointer;" id="forgot-button">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn-signup">Login</button>
                    </form>

                    <p style="margin-bottom: 16px;" class="footer-text">Doesn't have an account? <span><a href="register" style="text-decoration: underline; color: #0088FF;">Sign Up</a></span></p>
                    <a href="home" style="text-decoration: underline; color: #0088FF;">Return to home page</a>
                </div>
            </div>
            <div class="form-section" id="forgot-wrap">
                <?php include 'components/forgotPassword.php' ?>
            </div>
        </div>
    </div>

    <script src="/cardhaven/interface/login-page/script.js"></script>
</body>
</html>