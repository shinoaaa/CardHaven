<?php
// Endpoint kirim pesan Contact Us via SMTP Gmail (PHPMailer).
// Konfigurasi diambil dari .env (root project):
//   SMTP_USER = alamat gmail pengirim (mis. cardhavensupport@gmail.com)
//   SMTP_PASS = Gmail App Password 16 digit (BUKAN password akun biasa)
//   CONTACT_TO = tujuan pesan (opsional; default = SMTP_USER)
// Kalau .env belum diisi, balas status "unconfigured" — front-end akan
// fallback ke mailto: sehingga fitur tetap jalan.
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../../diagnose.php';          // loader .env → $_ENV
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

auth_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true) ?: [];
$name    = trim((string)($body['name'] ?? ''));
$email   = trim((string)($body['email'] ?? ''));
$message = trim((string)($body['message'] ?? ''));
$website = trim((string)($body['website'] ?? '')); // honeypot — harus kosong

// Honeypot: field ini disembunyikan dari user. Kalau terisi, hampir pasti bot.
// Balas "success" palsu (tanpa mengirim apa pun) supaya bot tidak tahu difilter.
if ($website !== '') {
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent!']);
    exit;
}

// Validasi server-side (front-end sudah validasi juga, ini lapisan kedua)
if ($name === '' || mb_strlen($name) > 100) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your name.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']); exit;
}
if ($message === '' || mb_strlen($message) > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'Please write your message (max 5000 characters).']); exit;
}

// Cooldown 60 detik per session biar tidak dispam
$now  = time();
$last = (int)($_SESSION['contact_last_sent'] ?? 0);
if ($now - $last < 60) {
    echo json_encode(['status' => 'error', 'message' => 'Please wait a minute before sending another message.']); exit;
}

$smtpUser = trim($_ENV['SMTP_USER'] ?? '');
$smtpPass = trim($_ENV['SMTP_PASS'] ?? '');
$sendTo   = trim($_ENV['CONTACT_TO'] ?? '') ?: $smtpUser;

if ($smtpUser === '' || $smtpPass === '') {
    // Belum dikonfigurasi — front-end fallback ke mailto:
    echo json_encode(['status' => 'unconfigured', 'message' => 'Email service is not configured yet.']);
    exit;
}

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 15;

    // Gmail mengharuskan From = akun yang login; email user ditaruh di Reply-To
    // supaya tombol Reply di Gmail langsung membalas ke pengirim aslinya.
    $mail->setFrom($smtpUser, 'CardHaven Contact Form');
    $mail->addAddress($sendTo);
    $mail->addReplyTo($email, $name);

    $mail->Subject = '[CardHaven] Message from ' . $name;
    $mail->Body    = "Name : {$name}\nEmail: {$email}\n\n{$message}";

    $mail->send();

    $_SESSION['contact_last_sent'] = $now;
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send the message. Please try again later.']);
}
