<?php
require __DIR__ . '/../../connection.php';

function jsonResponse($arr)
{
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $id_pengguna = $_GET['id_pengguna'] ?? '';

    if ($id_pengguna === '') {
        jsonResponse([
            "status" => "error",
            "message" => "ID pengguna tidak ditemukan"
        ]);
    }

    $sql = "SELECT id_pengguna, username, email, role, foto_profil, status_akun
            FROM pengguna
            WHERE id_pengguna = ?";

    $stmt = sqlsrv_prepare($conn, $sql, [$id_pengguna]);

    if (!$stmt || !sqlsrv_execute($stmt)) {
        $errors = sqlsrv_errors();
        jsonResponse([
            "status" => "error",
            "message" => $errors[0]['message'] ?? "Gagal mengambil data"
        ]);
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user) {
        jsonResponse([
            "status" => "error",
            "message" => "Data tidak ditemukan"
        ]);
    }

    jsonResponse([
        "status" => "success",
        "data" => $user
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_pengguna = $_POST['id_pengguna'] ?? '';

    if ($id_pengguna === '') {
        jsonResponse([
            "status" => "error",
            "message" => "ID pengguna tidak ditemukan"
        ]);
    }

    if ($action === 'update') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if ($nama === '' || $email === '') {
            jsonResponse([
                "status" => "error",
                "message" => "Nama dan email wajib diisi"
            ]);
        }

        if ($password !== '' || $confirm_password !== '') {
            if ($password !== $confirm_password) {
                jsonResponse([
                    "status" => "error",
                    "message" => "Password dan konfirmasi password tidak sama"
                ]);
            }
        }

        $sqlCheck = "SELECT id_pengguna
                     FROM pengguna
                     WHERE email = ? AND id_pengguna <> ?";

        $stmtCheck = sqlsrv_prepare($conn, $sqlCheck, [$email, $id_pengguna]);

        if (!$stmtCheck || !sqlsrv_execute($stmtCheck)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Validasi email gagal"
            ]);
        }

        $emailExists = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if ($emailExists) {
            jsonResponse([
                "status" => "error",
                "message" => "Email sudah dipakai akun lain"
            ]);
        }

        if ($password !== '') {
            $sql = "UPDATE pengguna
                    SET username = ?, email = ?, password = ?
                    WHERE id_pengguna = ?";

            $params = [$nama, $email, $password, $id_pengguna];
        } else {
            $sql = "UPDATE pengguna
                    SET username = ?, email = ?
                    WHERE id_pengguna = ?";

            $params = [$nama, $email, $id_pengguna];
        }

        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Update gagal"
            ]);
        }

        jsonResponse([
            "status" => "success",
            "message" => "Data berhasil diupdate"
        ]);
    }

    if ($action === 'deactivate' || $action === 'delete') {
        $sql = "UPDATE pengguna
                SET status_akun = 0
                WHERE id_pengguna = ?";

        $stmt = sqlsrv_prepare($conn, $sql, [$id_pengguna]);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Gagal menonaktifkan akun"
            ]);
        }

        jsonResponse([
            "status" => "success",
            "message" => "Akun berhasil dinonaktifkan"
        ]);
    }

    jsonResponse([
        "status" => "error",
        "message" => "Action tidak valid"
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setting</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <style>
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            background: #000;
        }

        .page-shell {
            width: 100%;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .sidebar-wrap {
            flex: 0 0 260px;
            height: 100vh;
            overflow: hidden;
            background: #fff;
        }

        .content-wrap {
            flex: 1;
            height: 100vh;
            overflow: auto;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #E6EEFF;
        }

        .account-card {
            width: min(440px, 100%);
            min-height: calc(100vh - 4rem);
            background: rgb(123, 183, 255);
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,.35);
            color: #fff;
        }

        .account-title {
            margin: 0 0 1rem 0;
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .profile-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .profile-thumb {
            width: 80px;
            height: 80px;
            border-radius: 999px;
            overflow: hidden;
            border: 2px solid var(--primary-color);
            background: #0f0f0f;
            flex-shrink: 0;
        }

        .profile-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: .35rem .75rem;
            border-radius: 999px;
            background: var(--bg-gradient);
            color: white;
            font-size: .8rem;
            margin-bottom: 1rem;
        }

        .field {
            margin-bottom: 1rem;
        }

        .field label {
            display: block;
            margin-bottom: .4rem;
            font-size: .95rem;
            opacity: .9;
        }

        .field input {
            width: 100%;
            box-sizing: border-box;
            padding: .9rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.12);
            background: #fff;
            color: #333;
            outline: none;
            font-size: .95rem;
        }

        .field input:focus {
            border-color: var(--primary-light);
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.25rem;
        }

        .btn {
            border: 0;
            border-radius: 14px;
            padding: .9rem 1rem;
            cursor: pointer;
            font-weight: 700;
            font-size: .95rem;
        }

        .btn-save {
            background: var(--primary-color);
            color: white;
        }

        .btn-off {
            background: #f0a500;
            color: #111;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .muted {
            opacity: .7;
            font-size: .85rem;
            margin-top: .35rem;
        }

        @media (max-width: 900px) {
            .page-shell {
                flex-direction: column;
            }

            .sidebar-wrap {
                flex: 0 0 auto;
                width: 100%;
                height: auto;
            }

            .content-wrap {
                padding: 1rem;
                justify-content: center;
            }

            .account-card {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <div class="sideBar">
        <div style="width: 100%; height: 100%; display: flex; align-items: center; flex-direction: column;">
    <div class="logo-wrap">
        <img src="/cardhaven/assets/image/logo.svg">
    </div>

    <div class="profile-employee">
        <div class="photo-Profile">
            <img src="https://i.pinimg.com/736x/e8/2b/43/e82b43056d04e86c577a443485049d9b.jpg" style="object-fit: cover; width: 100%; height: 100%;">
        </div>
        <div class="userTag">
            <h2 class="coolveticaa" style="color: white; font-size: .65rem;">Super Admin</h2>
        </div>
        <div style="margin-top: 1rem;">
            <h2 id="userName" class="coolveticaa" style="font-size: 1rem; color: var(--primary-color);"></h2>
            <h3 id="userEmail" style="font-size: 0.75rem; opacity: 55%; margin: 0.25rem 0 0 0;"></h3>
        </div>
        <div style="width: 100%; margin-top: 0.5rem; display: flex; justify-content: center; gap: .75rem;">
            <a href="">
                <img src="/cardhaven/assets/image/inbox.svg" style="object-fit:fill; width: 1.15rem; height: 1.15rem;">
            </a>
            <a href="javascript:void(0)" id="btnLogout">
                <img src="/cardhaven/assets/image/logout.svg" style="object-fit:fill; width: 1.15rem; height: 1.15rem;">
            </a>
        </div>
    </div>

    <div class="navMenu">
        <h2 class="coolveticaa" style="font-size: 1rem; color: var(--primary-color); margin-bottom: 0.5rem;">Menu</h2>

        <div class="menuOption unselected">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/analytics.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Dashboard</h2>
            </a>
        </div>
        <div class="menuOption unselected">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/transaction.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Transaction</h2>
            </a>
        </div>
        <div class="menuOption unselected">
            <a href="superadmin" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/product.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Product</h2>
            </a>
        </div>
        <div class="menuOption selectedOption">
            <a href="/cardhaven/interface/super-admin-page/account-setting.php" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/setting.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Account Setting</h2>
            </a>
        </div>
        

        <?php include 'components/logout.php' ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("userName").textContent =
        sessionStorage.getItem("username") || "Guest";

    document.getElementById("userEmail").textContent =
        sessionStorage.getItem("userEmail") || "-";
});
</script>
    </div>

    <main class="content-wrap">
        <section class="account-card">
            <h2 class="account-title coolveticaa">Account Setting</h2>

            <div class="status-badge" id="statusAkun">Status: -</div>

            <div class="profile-row">
                <div class="profile-thumb">
                    <img id="fotoProfil" src="https://i.pinimg.com/736x/e8/2b/43/e82b43056d04e86c577a443485049d9b.jpg" alt="profile">
                </div>
                <div>
                    <div class="coolveticaa" style="font-size: 1rem;">Profile Data</div>
                    <div class="muted" id="profileInfo">-</div>
                </div>
            </div>

            <form id="accountForm">
                <div class="field">
                    <label>Nama</label>
                    <input type="text" id="nama" autocomplete="off">
                </div>

                <div class="field">
                    <label>Email</label>
                    <input type="email" id="email" autocomplete="off">
                </div>

                <div class="field">
                    <label>Password Baru</label>
                    <input type="password" id="password">
                </div>

                <div class="field">
                    <label>Konfirmasi Password</label>
                    <input type="password" id="confirmPassword">
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <!-- <button type="button" id="btnDeactivate" class="btn btn-off">Deactivate</button> -->
                    <button type="button" id="btnDelete" class="btn btn-delete">Delete Account</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
const idKaryawan = sessionStorage.getItem("id_pengguna");
const idKaryawann = localStorage.getItem("id_pengguna");


if ((!idKaryawan) && (!idKaryawann)) {
    window.location.href = "/cardhaven/interface/login-page/";
}

async function loadData() {
    const res = await fetch(`/cardhaven/interface/super-admin-page/account-setting.php?action=get&id_pengguna=${encodeURIComponent(idKaryawan || idKaryawann)}`);
    const data = await res.json();

    if (data.status !== "success") {
        alert(data.message || "Gagal ambil data");
        return;
    }

    const user = data.data;
    document.getElementById("nama").value = user.username || "";
    document.getElementById("email").value = user.email || "";
    document.getElementById("statusAkun").textContent = `Status: ${user.status_akun == 1 ? "Aktif" : "Nonaktif"}`;
    document.getElementById("profileInfo").textContent = `${user.username || "-"} • ${user.email || "-"}`;

    if (user.foto_profil) {
        document.getElementById("fotoProfil").src = `/cardhaven/image-profile/${user.foto_profil}`;
    }
}

loadData();

document.getElementById("accountForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const nama = document.getElementById("nama").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();

    if (password || confirmPassword) {
        if (password !== confirmPassword) {
            alert("Password dan konfirmasi password tidak sama");
            return;
        }
    }

    const formData = new FormData();
    formData.append("action", "update");
    formData.append("id_pengguna", idKaryawan);
    formData.append("nama", nama);
    formData.append("email", email);
    formData.append("password", password);
    formData.append("confirm_password", confirmPassword);

    const res = await fetch("/cardhaven/interface/super-admin-page/account-setting.php", {
        method: "POST",
        body: formData
    });

    const data = await res.json();

    if (data.status === "success") {
        sessionStorage.setItem("nama", nama);
        sessionStorage.setItem("userEmail", email);
        alert(data.message);
        location.reload();
    } else {
        alert(data.message || "Update gagal");
    }
});

document.getElementById("btnDeactivate").addEventListener("click", async () => {
    if (!confirm("Yakin mau nonaktifkan akun ini?")) return;

    const formData = new FormData();
    formData.append("action", "deactivate");
    formData.append("id_pengguna", idKaryawan);

    const res = await fetch("/cardhaven/interface/super-admin-page/account-setting.php", {
        method: "POST",
        body: formData
    });

    const data = await res.json();

    if (data.status === "success") {
        sessionStorage.clear();
        localStorage.clear();
        alert("Akun dinonaktifkan");
        window.location.href = "/cardhaven/interface/login-page/";
    } else {
        alert(data.message || "Gagal menonaktifkan akun");
    }
});

document.getElementById("btnDelete").addEventListener("click", async () => {
    if (!confirm("Yakin mau hapus akun? Akun akan dinonaktifkan dan kamu akan logout.")) return;

    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id_pengguna", idKaryawan);

    const res = await fetch("/cardhaven/interface/super-admin-page/account-setting.php", {
        method: "POST",
        body: formData
    });

    const data = await res.json();

    if (data.status === "success") {
        sessionStorage.clear();
        localStorage.clear();
        alert("Akun dinonaktifkan");
        window.location.href = "/cardhaven/interface/login-page/";
    } else {
        alert(data.message || "Gagal menonaktifkan akun");
    }
});
</script>
</body>
</html>