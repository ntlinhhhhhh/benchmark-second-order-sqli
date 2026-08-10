<?php
// Exception-Free Benchmark - Pattern 1: System-Level Exception Masking
// Testbed 2.1: The Batching Blackhole (multi_query) (Source)
// Description: Registers client safely using prepared statements, later read by Sink.
// Kích hoạt chế độ ném ngoại lệ nghiêm ngặt của MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Kết nối đến cơ sở dữ liệu thực nghiệm
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'silent_testbed';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Nhận dữ liệu đầu vào (Payload sẽ được fuzzer tiêm vào đây)
$raw_client_name = $_POST['client_name'] ?? '';

// Defense Layer 1: Application-level Data Truncation
// Cắt chuỗi để khớp với giới hạn VARCHAR(100) của schema, tránh lỗi "Data too long"
if (strlen($raw_client_name) > 100) {
    $raw_client_name = substr($raw_client_name, 0, 100);
}

// Defense Layer 2: Parameterized Prepared Statement
// Sử dụng Prepare Statement để ngăn chặn SQLi truyền thống ngay tại Source
$stmt = $db->prepare("INSERT INTO clients (client_name, registered_at) VALUES (?, NOW())");
$stmt->bind_param("s", $raw_client_name);

// them system log prepared statement
$log_stmt = $db->prepare("INSERT INTO system_logs (client_id, event_type, message) VALUES (LAST_INSERT_ID(), 'NEW_CLIENT_REGISTERED', ?)");
$log_message = "New client registered: " . $raw_client_name;
$log_stmt->bind_param("s", $log_message);

try {
    $stmt->execute();
    $log_stmt->execute();
    // Luôn trả về HTTP 200 OK nếu lưu thành công chuỗi payload
    echo "Client registered successfully!";
} catch (Exception $e) {
    // Nuốt lỗi và chỉ ghi log cục bộ (Exception-Free đối với Fuzzer)
    error_log("System error: " . $e->getMessage());
}
?>