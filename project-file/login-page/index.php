<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../global.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div style="width: 45%; height: 100%; display: flex; justify-content: center;">
                <img src="../../assets/image/login-image.svg" style="object-fit: cover; height: 100%;">
            </div>
            <div style="background-color: gray; width: 50%; height: 100%; justify-content: center; align-items: center">
                <h1>Login</h1>
                <div>or</div>
                <form id="form-login" method="POST">
                    <div class="form-group">
                        <label>Email<span class="required">*</span></label><input type="email" name="email" class="form-box">
                        <div class="keterangan" id="err-email"></div>
                    </div>
                    <div class="form-group">
                        <label>Password<span class="required">*</span></label><input type="password" name="password" class="form-box">
                        <div class="keterangan" id="err-password"></div>
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <input type="checkbox" name="remember" class="form-box"><label for="remember">Remember me</label>
                            </div>
                                <a href="forgot-password.php">Forgot password?</a>
                        </div>
                        <div>
                            <button type="submit" class="btn-primary">Login</button>
                        </div>
                    </div>
                </form>
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                <a href="" class="btn-secondary">Return to Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>