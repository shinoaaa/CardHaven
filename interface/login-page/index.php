<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
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
                        <button class="social-btn"><img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"></button>
                        <button class="social-btn"><img src="https://img.icons8.com/ios-filled/50/000000/github.png" alt="Github"></button>
                        <button class="social-btn"><img src="https://img.icons8.com/ios-filled/50/000000/mac-os.png" alt="Apple"></button>
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
                    <a href="" style="text-decoration: underline; color: #0088FF;">Return to home page</a>
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