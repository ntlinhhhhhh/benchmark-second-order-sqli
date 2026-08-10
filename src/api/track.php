<?php
// Exception-Free Benchmark - Pattern 1: System-Level Exception Masking
// Testbed 1: The Explicit Silent Mode (Source)
// Description: Logs tracking details safely using prepared statements, later read by Sink.
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
// Ở Source, lập trình viên code rất cẩn thận, bật Exception nghiêm ngặt
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Lấy User-Agent từ HTTP Header (Fuzzer hộp đen thường lãng quên Header)
$raw_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$agent = substr($raw_agent, 0, 255); 

// Đã thêm access_time (NOW()) theo đúng chuẩn thiết kế Log hệ thống
$stmt = $pdo->prepare("INSERT INTO raw_traffic (ip_address, user_agent, access_time) VALUES (?, ?, NOW())");

try {
    $stmt->execute([$_SERVER['REMOTE_ADDR'], $agent]);
    echo "Tracking enabled. (HTTP 200 OK)";
} catch (Exception $e) {
    error_log("Tracking error: " . $e->getMessage());
}
?>