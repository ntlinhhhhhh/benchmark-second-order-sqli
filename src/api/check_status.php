<?php
// Exception-Free Benchmark - Pattern 3: Distributed Context Fragmentation
// Testbed 4: The Asynchronous Exfiltration Barrier (Status/Polling)
// Description: Polling endpoint for frontend clients to check asynchronous task execution status.
// 2. Client gọi API này mỗi 10 giây để check tiến độ
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$job_id = (int)$_GET['job_id'];

$res = $db->query("SELECT status, report_url FROM heavy_report_jobs WHERE id = $job_id");
$job = $res ? $res->fetch_assoc() : null;

if (!$job) {
    echo json_encode(["status" => "error", "message" => "Job not found."]);
    exit;
}

if ($job['status'] === 'DONE') {
    // Trả về link tải báo cáo (Đã được Worker sinh ra với tên file ngẫu nhiên)
    echo json_encode(["status" => "ready", "download_url" => $job['report_url']]);
} else if ($job['status'] === 'FAILED') {
    echo json_encode(["status" => "error", "message" => "Report generation failed."]);
} else {
    echo json_encode(["status" => "processing"]);
}
?>