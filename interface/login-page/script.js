document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const emailInput = document.getElementById("emailInput");
    const passwordInput = document.getElementById("passwordInput");
    const errorEmail = document.getElementById("error-email");
    const errorPass = document.getElementById("error-pass");
    const checkBox = document.getElementById("checkbox");
    const checkText = document.getElementById("checkText");
    
    let clicked = false;


    // Ini buat tes kalo ada localstorgae dan session dia bakal auto redirect ke berhasil
    // const isLocalLogin = localStorage.getItem("isLoggedIn") === "true";
    // const isSessionLogin = sessionStorage.getItem("isLoggedIn") === "true";

    // if (isLocalLogin || isSessionLogin) {
    //     window.location.replace("/CardHaven/berhasil"); 
    //     return;
    // }

    const rememberMe = () => {
        if (!clicked) {
            checkBox.style.backgroundColor = '#0088FF';
            checkBox.style.color = 'white';
            clicked = true;
        } else {
            checkBox.style.backgroundColor = '';
            checkBox.style.color = '#0088FF';
            clicked = false;
        }
    }

    checkBox.addEventListener('click', rememberMe);
    checkText.addEventListener('click', rememberMe);

    loginForm.addEventListener("submit", async function(e) {
        e.preventDefault();

        resetErrors([emailInput, passwordInput], [errorEmail, errorPass]);

        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        let isValid = true;

        if (!email) {
            showError(emailInput, errorEmail, "Email tidak boleh kosong");
            isValid = false;
        }
        
        if (!password) {
            showError(passwordInput, errorPass, "Password tidak boleh kosong");
            isValid = false;
        }

        if (!isValid) return;

        const formData = new FormData(this);
        formData.append("remember", clicked);

        try {
            const response = await fetch("/CardHaven/interface/login-page/login.php", {
                method: "POST",
                body: formData
            });

            const responseText = await response.text();
            
            try {
                const data = JSON.parse(responseText);
                
                if (data.status === "success") {
                    if (clicked) {
                        localStorage.setItem("isLoggedIn", "true");
                        localStorage.setItem("userEmail", email);
                        localStorage.setItem("token", data.token);
                    } else {
                        sessionStorage.setItem("isLoggedIn", "true");
                        sessionStorage.setItem("userEmail", email);
                        sessionStorage.setItem("token", data.token);
                    }
                    
                    alert("Login Berhasil!");
                    window.location.replace("/CardHaven/berhasil"); 
                    
                } else {
                    if (data.target === "email") {
                        showError(emailInput, errorEmail, data.message);
                    } else if (data.target === "password") {
                        showError(passwordInput, errorPass, data.message);
                    } else {
                        alert(data.message);
                    }
                }
            } catch (jsonError) {
                console.error("Server Error Response:", responseText);
                alert("Terjadi kesalahan pada server (PHP Error). Cek console log (F12) untuk melihat detailnya.");
            }

        } catch (error) {
            console.error("Fetch Error:", error);
            alert("Tidak dapat terhubung ke server.");
        }
    });
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