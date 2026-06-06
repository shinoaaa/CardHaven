document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const emailInput = document.getElementById("emailInput");
    const passwordInput = document.getElementById("passwordInput");
    const errorEmail = document.getElementById("error-email");
    const errorPass = document.getElementById("error-pass");
    const checkBox = document.getElementById("checkbox");
    const checkText = document.getElementById("checkText");
    
    let clicked = false;

    // --- JALUR AUTO REDIRECT JIKA SUDAH LOGIN ---
    const savedToken = localStorage.getItem("token") || sessionStorage.getItem("token");
    const savedRole = localStorage.getItem("role") || sessionStorage.getItem("role");

    if (savedToken && savedRole !== null) {
        if (savedRole == "2") window.location.replace("/CardHaven/superadmin");
        else if (savedRole == "1") window.location.replace("/CardHaven/admin");
        else if (savedRole == "3") window.location.replace("/CardHaven/owner");
        else if (savedRole == "0") window.location.replace("/CardHaven/home");
    }

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
                    const storage = clicked ? localStorage : sessionStorage;
                    storage.setItem("userEmail", email);
                    storage.setItem("role", data.role);
                    storage.setItem("id_pengguna", data.id_pengguna); 
                    storage.setItem("username", data.username); 
                    
                    alert("Login Berhasil!");
                    
                    if (data.role == 2) {
                        window.location.replace("/CardHaven/superadmin");
                    }
                    else {
                        window.location.replace("/CardHaven/home");
                    }
                    
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
                alert("Terjadi kesalahan pada server.");
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
    inputs.forEach(input => { input.style.borderColor = "#0F3891"; });
    errors.forEach(error => { error.style.display = "none"; });
}