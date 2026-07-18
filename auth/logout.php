<?php
/**
 * auth/logout.php
 * Menghapus session login di sisi server.
 * Dipanggil lewat CardHavenAuth.logout() dari tombol logout.
 */
require_once __DIR__ . '/session.php';

auth_logout();

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Logged out']);
