<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/cardhaven/interface/login-page/style.css">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div style="width: 45%; height: 100%; display: flex; justify-content: center;">
                <img src="/cardhaven/assets/image/login-image.svg" style="object-fit: cover; height: 100%;">
            </div>
            <div class="form-section">
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

                    <form id="loginForm">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="enter username..." required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="enter email..." required>
                        </div>
                        <div style="width: 100%; height: 25px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 17px;">
                            <div style="width: auto; height: 100%; display: flex; align-items: center; gap: 10px;">
                                <div id="checkbox">
                                    ✔
                                </div>
                                <p style="text-decoration: underline; color: #0088FF;  font-size: 13px;">Remember Me</p>
                            </div>
                            <a style="color: #0088FF;  font-size: 13px;">
                                Forgot Password?
                            </a>
                        </div>

                        <button type="submit" class="btn-signup">Login</button>
                    </form>

                    <p style="margin-bottom: 16px;" class="footer-text">Doesn't have an account? <span><a href="register" style="text-decoration: underline; color: #0088FF;">Sign Up</a></span></p>
                    <a href="" style="text-decoration: underline; color: #0088FF;">Return to home page</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const checkBox = document.getElementById("checkbox");
        let clicked = true;

        checkBox.addEventListener('click', () => {

            if(clicked){
                checkBox.style.backgroundColor = '#0088FF';
                checkBox.style.color = 'white'
                clicked = false;
            }
            else{
                checkBox.style.backgroundColor = '';
                checkBox.style.color = '#0088FF'
                clicked = true;
            }
        })

    </script>
</body>
</html>