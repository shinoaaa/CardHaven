const controllerUrl = '/CardHaven/interface/account-setting/account-setting-controller.php';
const userId = sessionStorage.getItem("id_pengguna") || localStorage.getItem("id_pengguna");

if (!userId) {
    window.location.href = "../../login-page/";
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function setValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
}

document.addEventListener("DOMContentLoaded", () => {
    setText("userName", sessionStorage.getItem("username") || sessionStorage.getItem("nama") || "Guest");
    setText("userEmail", sessionStorage.getItem("userEmail") || "-");

    loadData();

    const form = document.getElementById("accountForm");
    if (form) {
        form.addEventListener("submit", handleSubmit);
    }

    const btnDeactivate = document.getElementById("btnDeactivate");
    if (btnDeactivate) {
        btnDeactivate.addEventListener("click", handleDeactivate);
    }

    const btnDelete = document.getElementById("btnDelete");
    if (btnDelete) {
        btnDelete.addEventListener("click", handleDelete);
    }
});

async function loadData() {
    try {
        const res = await fetch(`${controllerUrl}?action=get&id_pengguna=${encodeURIComponent(userId)}`);
        const data = await res.json();

        if (data.status !== "success") {
            alert(data.message || "Gagal ambil data");
            return;
        }

        const user = data.data;
        setValue("nama", user.username || "");
        setValue("email", user.email || "");
        setText("statusAkun", `Status: ${user.status_akun == 1 ? "Aktif" : "Nonaktif"}`);
        setText("profileInfo", `${user.username || "-"} • ${user.email || "-"}`);

        const foto = document.getElementById("fotoProfil");
        if (foto && user.foto_profil) {
            foto.src = `../../../image-profile/${user.foto_profil}`;
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
}

async function handleSubmit(e) {
    e.preventDefault();

    const nama = document.getElementById("nama").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();

    if (!nama || !email) {
        alert("Nama dan email wajib diisi");
        return;
    }

    if (password || confirmPassword) {
        if (password !== confirmPassword) {
            alert("Password dan konfirmasi password tidak sama");
            return;
        }
    }

    try {
        const formData = new FormData();
        formData.append("action", "update");
        formData.append("id_pengguna", userId);
        formData.append("nama", nama);
        formData.append("email", email);
        formData.append("password", password);
        formData.append("confirm_password", confirmPassword);

        const res = await fetch(controllerUrl, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.setItem("username", nama);
            sessionStorage.setItem("nama", nama);
            sessionStorage.setItem("userEmail", email);

            alert(data.message || "Data berhasil diupdate");
            location.reload();
        } else {
            alert(data.message || "Update gagal");
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
}

async function handleDeactivate() {
    if (!confirm("Yakin mau nonaktifkan akun ini?")) return;

    try {
        const formData = new FormData();
        formData.append("action", "deactivate");
        formData.append("id_pengguna", userId);

        const res = await fetch(controllerUrl, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.clear();
            localStorage.clear();
            alert(data.message || "Akun dinonaktifkan");
            window.location.href = "home";
        } else {
            alert(data.message || "Gagal menonaktifkan akun");
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
}

async function handleDelete() {
    if (!confirm("Yakin mau hapus akun? Akun akan dinonaktifkan dan kamu akan logout.")) return;

    try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("id_pengguna", userId);

        const res = await fetch(controllerUrl, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.clear();
            localStorage.clear();
            alert(data.message || "Akun dinonaktifkan");
            window.location.href = "../../login-page/";
        } else {
            alert(data.message || "Gagal menghapus akun");
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
}