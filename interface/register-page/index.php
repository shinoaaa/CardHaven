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
    <title>Buat Akun</title>
    <link rel="stylesheet" href="/CardHaven/interface/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/CardHaven/interface/register-page/script_register.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="image-wrap">
                <img src="/CardHaven/assets/image/login-image.svg">
            </div>

            <div class="form-section">
                <div class="form-container">
                    <h1 class="coolvetica">Sign Up</h1>
                    
                    <div class="social-login">
                        <!-- Tombol Login/Register Google -->
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

                    <form id="signupForm">
                        <div class="form-group">
                            <label>Username<span class="required">*</span></label>
                            <input type="text" name="username" placeholder="enter username...">
                            <div id="usernameError" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label>Email<span class="required">*</span></label>
                            <input type="email" name="email" placeholder="enter email...">
                            <div id="emailError" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label>Password<span class="required">*</span></label>
                            <input type="password" name="password" id="password" placeholder="enter password...">
                            <div id="passwordError" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label>Confirm password<span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="confirm password">
                            <div id="confirmPasswordError" class="error-message"></div>
                        </div>

                        <button type="submit" class="btn-signup">Sign Up</button>
                    </form>

                    <p class="footer-text">Have an account? <span><a href="login" style="text-decoration: underline; color: #0088FF;">Log in</a></span></p>
                    <p class="terms-text">By Proceeding you agree to our terms of use</p>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>