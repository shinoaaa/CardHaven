document.getElementById('signupForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Ambil elemen input
    const username = this.querySelector('input[name="username"]');
    const email = this.querySelector('input[name="email"]');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    // Ambil elemen error
    const usernameError = document.getElementById('usernameError');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    // Reset status error
    resetErrors([username, email, password, confirmPassword], 
                [usernameError, emailError, passwordError, confirmPasswordError]);

    let isValid = true;

    // 1. Validasi Kosong
    if (!username.value.trim()) {
        showError(username, usernameError, "Please enter your username");
        isValid = false;
    }

    if (!email.value.trim()) {
        showError(email, emailError, "Please enter your email");
        isValid = false;
    }

    // 2. Validasi Panjang Password (8-12 karakter)
    if (password.value.length < 8 || password.value.length > 12) {
        showError(password, passwordError, "Password must be 8 - 12 characters long");
        isValid = false;
    }else if (!password.value.trim()) {
        showError(password, passwordError, "Password must be filled");
        isValid = false;
    }else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password.value)) {
        showError(password, passwordError, "Password must contain a special character");
        isValid = false;
    }

    // 3. Validasi Konfirmasi Password
    if (password.value !== confirmPassword.value) {
        showError(confirmPassword, confirmPasswordError, "Confirm password does not match!");
        isValid = false;
    } else if (!confirmPassword.value) {
        showError(confirmPassword, confirmPasswordError, "Please confirm your password");
        isValid = false;
    }

    if (!isValid) return;

    // Jika valid, kirim data ke PHP
    const formData = new FormData(this);

    try {
        const response = await fetch('/CardHaven/interface/register-page/proses_signup.php', {
            method: 'POST',
            body: formData
        });

        // Ambil text mentah dulu untuk mengecek apakah ada error PHP (HTML)
        const responseText = await response.text();
        
        try {
            // Coba ubah text ke JSON
            const data = JSON.parse(responseText);
            
            if (data.status === 'success') {
                alert('Registration successful! Please login.');
                window.location.href = 'home'; 
            } else {
                alert('Failed: ' + data.message);
            }
        } catch (jsonError) {
            // Jika gagal parse JSON, berarti PHP mengirim error HTML
            console.error("Server Error Response:", responseText);
            alert("An error occurred on the server (PHP Error). Check the console log (F12) for details.");
        }

    } catch (error) {
        console.error('Fetch Error:', error);
        alert('Unable to connect to the server.');
    }
});

function showError(inputElement, errorElement, message) {
    inputElement.style.borderColor = "red";
    errorElement.innerText = message;
    errorElement.style.display = "block";
}

function resetErrors(inputs, errors) {
    inputs.forEach(input => {
        input.style.borderColor = "#0F3891";
    });
    errors.forEach(error => {
        error.style.display = "none";
    });
}