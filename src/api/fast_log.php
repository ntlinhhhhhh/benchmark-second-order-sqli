<?php
// Exception-Free Benchmark - Pattern 1: System-Level Exception Masking
// Testbed 2.2: The Asynchronous Socket Blackhole (MYSQLI_ASYNC) (Combined Source & Sink)
// Description: Performs asynchronous fire-and-forget query without calling mysqli_reap_async_query.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Nhận payload từ User-Agent
$raw_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// LỖ HỔNG: Nối chuỗi trực tiếp vào truy vấn
$sql = "INSERT INTO metric_logs (user_agent, access_time) VALUES ('$raw_agent', NOW())";

// NGHIỆP VỤ FIRE-AND-FORGET (Bắn và Quên):
// Lập trình viên dùng cờ MYSQLI_ASYNC để query không block (chặn) luồng PHP.
// PHP đẩy gói tin SQL qua TCP Socket tới MySQL rồi đi tiếp ngay lập tức.
$db->query($sql, MYSQLI_ASYNC);

// TỬ HUYỆT CỦA CGF (PHUZZ):
// Lập trình viên KHÔNG BAO GIỜ gọi hàm `mysqli_reap_async_query($db)`.
// Nếu có lỗi cú pháp do SQLi, thông báo lỗi từ MySQL Server trả về sẽ 
// bị kẹt lại ở bộ đệm Socket. Zend Engine không bao giờ biết có lỗi xảy ra, 
// không sinh ra ngoại lệ. Fuzzer nhận HTTP 200 OK và hoàn toàn mù lòa!
echo "Event logged asynchronously! (HTTP 200 OK)";
?>