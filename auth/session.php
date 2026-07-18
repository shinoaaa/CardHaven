<?php
/**
 * auth/session.php
 * ---------------------------------------------------------------------------
 * SATU-SATUNYA sumber identitas pengguna (id_pengguna & role) untuk seluruh
 * aplikasi CardHaven.
 *
 * ATURAN PENTING:
 *   - id_pengguna & role HANYA boleh dibaca lewat auth_id() / auth_role() /
 *     auth_user(), yang mengambil datanya dari PHP session ($_SESSION).
 *   - JANGAN PERNAH mengambil id_pengguna atau role dari $_POST / $_GET /
 *     JSON body / localStorage / sessionStorage. Nilai dari browser bisa
 *     dipalsukan user, jadi tidak boleh dipercaya untuk otorisasi maupun audit.
 *   - Nilai yang dikirim ke browser (window.CH_AUTH) sifatnya HANYA untuk
 *     tampilan (nama, foto, sembunyikan menu). Server tetap wajib cek sendiri.
 *
 * Cara pakai:
 *   Halaman (HTML):
 *       require_once __DIR__ . '/../../auth/session.php';
 *       auth_require_role([ROLE_EMPLOYEE, ROLE_MANAGER, ROLE_OWNER]);
 *
 *   Controller (JSON/AJAX):
 *       require_once __DIR__ . '/../../auth/session.php';
 *       $user = auth_api_require_role([ROLE_MANAGER, ROLE_OWNER]);
 *       $id_user = $user['id'];
 * ---------------------------------------------------------------------------
 */

// ── Konstanta role ──────────────────────────────────────────────────────────
// Nilai ini mengikuti kolom `role` di tabel `pengguna`.
if (!defined('ROLE_CUSTOMER')) define('ROLE_CUSTOMER', 0);
if (!defined('ROLE_EMPLOYEE')) define('ROLE_EMPLOYEE', 1);
if (!defined('ROLE_MANAGER'))  define('ROLE_MANAGER',  2);
if (!defined('ROLE_OWNER'))    define('ROLE_OWNER',    3);

/** Semua role pegawai (yang boleh masuk dashboard admin). */
function auth_staff_roles(): array
{
    return [ROLE_EMPLOYEE, ROLE_MANAGER, ROLE_OWNER];
}

/** Nama role untuk ditampilkan di UI. */
function auth_role_label(int $role): string
{
    switch ($role) {
        case ROLE_EMPLOYEE: return 'Employee';
        case ROLE_MANAGER:  return 'Manager';
        case ROLE_OWNER:    return 'Owner';
        default:            return 'Customer';
    }
}

// ── Boot session ────────────────────────────────────────────────────────────

/**
 * Mulai PHP session dengan konfigurasi cookie yang aman.
 * Aman dipanggil berkali-kali (idempotent).
 */
function auth_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Cookie session tidak boleh dibaca/ditulis JavaScript, dan hanya lewat cookie
    // (bukan ?PHPSESSID= di URL) supaya tidak gampang dibajak.
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.use_strict_mode', '1');

    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,      // sampai browser ditutup; diperpanjang saat "Remember me"
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https, // di localhost (http) tetap false supaya login tetap jalan
        'httponly' => true,   // JS tidak bisa baca cookie session
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ── Login / Logout ──────────────────────────────────────────────────────────

/**
 * Simpan identitas pengguna ke session setelah kredensial terbukti benar.
 * Dipanggil dari login.php dan callback OAuth (Google/Facebook/Discord).
 *
 * @param array $user  Baris dari tabel `pengguna` (minimal id_pengguna & role).
 * @param bool  $remember  true = cookie session bertahan 7 hari ("Remember me").
 */
function auth_login(array $user, bool $remember = false): void
{
    auth_session_start();

    // Cegah session fixation: ganti ID session begitu hak akses naik.
    session_regenerate_id(true);

    $_SESSION['id_pengguna'] = (int)($user['id_pengguna'] ?? 0);
    $_SESSION['role']        = (int)($user['role'] ?? ROLE_CUSTOMER);
    $_SESSION['username']    = (string)($user['username'] ?? '');
    $_SESSION['email']       = (string)($user['email'] ?? '');
    $_SESSION['login_at']    = time();

    if ($remember) {
        // Perpanjang umur cookie session jadi 7 hari.
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => time() + 604800,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.gc_maxlifetime', '604800');
    }
}

/** Hapus session login sepenuhnya (dipakai endpoint logout). */
function auth_logout(): void
{
    auth_session_start();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}

// ── Pembacaan identitas (INI fungsi utamanya) ───────────────────────────────

/**
 * FUNGSI UTAMA — ambil identitas pengguna yang sedang login.
 * Dipakai di semua halaman dan semua fitur.
 *
 * @return array{id:int, role:int, username:string, email:string, logged_in:bool, is_staff:bool, role_label:string}
 *         id = 0 dan logged_in = false kalau belum login.
 */
function auth_user(): array
{
    auth_session_start();

    $id   = (int)($_SESSION['id_pengguna'] ?? 0);
    $role = (int)($_SESSION['role'] ?? ROLE_CUSTOMER);

    return [
        'id'         => $id,
        'role'       => $role,
        'username'   => (string)($_SESSION['username'] ?? ''),
        'email'      => (string)($_SESSION['email'] ?? ''),
        'logged_in'  => $id > 0,
        'is_staff'   => $id > 0 && in_array($role, auth_staff_roles(), true),
        'role_label' => auth_role_label($role),
    ];
}

/** id_pengguna yang sedang login, atau 0 kalau belum login. */
function auth_id(): int
{
    return auth_user()['id'];
}

/** role pengguna yang sedang login (0/1/2/3). */
function auth_role(): int
{
    return auth_user()['role'];
}

/** Sudah login atau belum. */
function auth_check(): bool
{
    return auth_user()['logged_in'];
}

/** Cek apakah role user termasuk salah satu dari daftar role yang diizinkan. */
function auth_is(array $roles): bool
{
    $u = auth_user();
    return $u['logged_in'] && in_array($u['role'], array_map('intval', $roles), true);
}

/** Cek apakah user adalah pegawai (employee/manager/owner). */
function auth_is_staff(): bool
{
    return auth_user()['is_staff'];
}

// ── Penjaga akses untuk HALAMAN (redirect) ──────────────────────────────────

/** Wajib login; kalau belum, lempar ke halaman login. */
function auth_require_login(string $redirect = '/CardHaven/login'): array
{
    $u = auth_user();
    if (!$u['logged_in']) {
        header('Location: ' . $redirect);
        exit;
    }
    return $u;
}

/**
 * Wajib login DAN role-nya termasuk yang diizinkan.
 * Kalau belum login -> ke halaman login. Kalau role tidak cocok -> ke $denied.
 */
function auth_require_role(array $roles, string $denied = '/CardHaven/home'): array
{
    $u = auth_require_login();
    if (!in_array($u['role'], array_map('intval', $roles), true)) {
        header('Location: ' . $denied);
        exit;
    }
    return $u;
}

// ── Penjaga akses untuk CONTROLLER / API (JSON) ─────────────────────────────

/** Balas JSON error lalu berhenti. */
function auth_json_fail(int $status, string $message): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

/**
 * Wajib login untuk endpoint JSON. Balas 401 kalau belum login.
 * @return array identitas dari auth_user()
 */
function auth_api_require_login(): array
{
    $u = auth_user();
    if (!$u['logged_in']) {
        auth_json_fail(401, 'Your session has expired. Please sign in again.');
    }
    return $u;
}

/**
 * Wajib login + role tertentu untuk endpoint JSON. Balas 403 kalau role salah.
 * @return array identitas dari auth_user()
 */
function auth_api_require_role(array $roles): array
{
    $u = auth_api_require_login();
    if (!in_array($u['role'], array_map('intval', $roles), true)) {
        auth_json_fail(403, 'You do not have permission to perform this action.');
    }
    return $u;
}

// ── Jembatan ke JavaScript ──────────────────────────────────────────────────

/**
 * Cetak identitas dari session ke dalam <script> sebagai window.CH_AUTH,
 * supaya JS tidak perlu lagi baca localStorage/sessionStorage.
 *
 * CATATAN: ini cuma untuk kebutuhan TAMPILAN (nama user, sembunyikan menu).
 * Server tetap wajib memvalidasi sendiri lewat auth_require_role() /
 * auth_api_require_role(). Data di sini tidak menambah hak akses apa pun.
 *
 * Panggil di dalam <head> setiap halaman, sebelum script lain dimuat.
 */
function auth_emit_js(): void
{
    $u = auth_user();

    $payload = [
        'id'        => $u['id'],
        'role'      => $u['role'],
        'username'  => $u['username'],
        'email'     => $u['email'],
        'loggedIn'  => $u['logged_in'],
        'isStaff'   => $u['is_staff'],
        'roleLabel' => $u['role_label'],
    ];

    echo '<script>window.CH_AUTH = '
        . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        . ";</script>\n";
    echo '<script src="/cardhaven/interface/auth.js?v=' . filemtime(__DIR__ . '/../interface/auth.js') . "\"></script>\n";
}
