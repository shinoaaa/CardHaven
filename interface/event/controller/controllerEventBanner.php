<?php
// Endpoint FormData khusus banner event (dipakai saat Edit Event).
//   - Upload banner baru  → simpan file, hapus file lama, update foto_banner
//   - Hapus banner (remove=1) → set foto_banner NULL, hapus file lama
// Semua perubahan DB lewat stored procedure dbo.sp_ManageEventBanner yang
// sekaligus mengembalikan banner lama (untuk menghapus file-nya).
// Manager & Owner saja. Dipisah dari controllerEdit.php yang berbasis JSON.
ob_start();
require __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../auth/session.php';
ob_end_clean();
header('Content-Type: application/json');

$actor = auth_api_require_role([ROLE_MANAGER, ROLE_OWNER])['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'Method not allowed']); exit; }

$idEvent = (int)($_POST['id_event'] ?? 0);
$remove  = ($_POST['remove'] ?? '') === '1';
if ($idEvent <= 0) { echo json_encode(['success' => false, 'error' => 'Invalid event id']); exit; }

$bannerDir = __DIR__ . '/../../../assets/image/image-banner/';

/**
 * Panggil sp_ManageEventBanner. Mengembalikan foto_banner LAMA (string|null)
 * bila sukses, atau false bila query gagal.
 */
function manageEventBanner($conn, int $idEvent, ?string $newPath, bool $remove) {
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventBanner(?, ?, ?)}", [
        $idEvent, $newPath, $remove ? 1 : 0
    ]);
    if ($stmt === false) return false;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return $row['old_banner'] ?? null;
}

// ── Kasus 1: upload banner baru ─────────────────────────────────────────────
if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['banner']['size'] > 3 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Banner is too large (max 3 MB)']); exit;
    }
    $info    = @getimagesize($_FILES['banner']['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) {
        echo json_encode(['success' => false, 'error' => 'Banner must be a JPG, PNG, or WEBP image']); exit;
    }
    if (!is_dir($bannerDir)) @mkdir($bannerDir, 0777, true);

    $fileName = 'EVT_' . time() . '_' . uniqid() . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($_FILES['banner']['tmp_name'], $bannerDir . $fileName)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save the banner']); exit;
    }
    $newPath = 'assets/image/image-banner/' . $fileName;

    $oldBanner = manageEventBanner($conn, $idEvent, $newPath, false);
    if ($oldBanner === false) {
        @unlink($bannerDir . $fileName); // rollback file kalau DB gagal
        echo json_encode(['success' => false, 'error' => sqlsrv_errors()[0]['message'] ?? 'Database error']); exit;
    }
    if (!empty($oldBanner)) @unlink($bannerDir . basename($oldBanner));

    echo json_encode(['success' => true, 'foto_banner' => $newPath]);
    exit;
}

// ── Kasus 2: hapus banner ───────────────────────────────────────────────────
if ($remove) {
    $oldBanner = manageEventBanner($conn, $idEvent, null, true);
    if ($oldBanner === false) {
        echo json_encode(['success' => false, 'error' => sqlsrv_errors()[0]['message'] ?? 'Database error']); exit;
    }
    if (!empty($oldBanner)) @unlink($bannerDir . basename($oldBanner));

    echo json_encode(['success' => true, 'foto_banner' => null]);
    exit;
}

// Tidak ada perubahan → tidak menyentuh DB sama sekali.
echo json_encode(['success' => true, 'unchanged' => true]);
