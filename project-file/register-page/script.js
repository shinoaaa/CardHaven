document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (password !== confirmPassword) {
        alert("Konfirmasi password tidak cocok!");
        return;
    }

    const formData = new FormData(this);

    fetch('proses_signup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Registrasi berhasil! Silahkan login.');
            //window.location.href = 'login.php';
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});