<?php
// Exception-Free Benchmark - Pattern 3: Distributed Context Fragmentation
// Testbed 4: The Asynchronous Exfiltration Barrier (Source)
// Description: Registers calculation job in background queue, decoupling request and execution threads.
// 1. Nhận yêu cầu kết xuất báo cáo và đẩy vào Queue
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$admin_id = $_SESSION['user_id'] ?? 1;
// Người dùng chọn Khu vực (Region) để thống kê. Đây là nơi chứa Payload.
$raw_region = $_POST['region'] ?? 'North_America';

// Defense Layer: Cắt chuỗi để chống tràn bộ đệm, gây ảo giác an toàn cho SAST
$safe_region = substr($raw_region, 0, 100);

// Xử lý an toàn: Lưu Job vào CSDL bằng Prepared Statement
$stmt = $db->prepare("INSERT INTO heavy_report_jobs (requester_id, region_filter, status) VALUES (?, ?, 'PENDING')");
$stmt->bind_param("is", $admin_id, $safe_region);
$stmt->execute();

// Trả về Job ID để Client có thể Polling (Giải phóng luồng HTTP ngay lập tức)
echo json_encode(["status" => "queued", "job_id" => $stmt->insert_id, "message" => "Heavy calculation started in background."]);
?>