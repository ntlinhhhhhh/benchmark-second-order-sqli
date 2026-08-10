<?php
// Exception-Free Benchmark - Pattern 1: System-Level Exception Masking
// Testbed 1: The Explicit Silent Mode (Sink)
// Description: The cronjob is set to ERRMODE_SILENT, causing SQL errors to be swallowed.
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

// TỬ HUYỆT CỦA CGF (PHUZZ): 
// Tiến trình chạy ngầm (cronjob) được set SILENT MODE để nếu 1 bản ghi lỗi,
// nó không ném Exception làm chết (crash) toàn bộ tiến trình thống kê của các bản ghi khác.
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT); 

// Lấy các log truy cập chưa được thống kê
$stmt = $pdo->query("SELECT id, user_agent FROM raw_traffic WHERE is_processed = 0");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $ua = $row['user_agent']; // Payload độc hại được lấy ra
    
    // NGHIỆP VỤ THỐNG KÊ (AGGREGATION):
    // Lập trình viên cố tình dùng chuỗi $ua làm khóa để cộng dồn (hit_count) 
    // các lượt truy cập có cùng một loại trình duyệt (User-Agent).
    $sql = "INSERT INTO agent_stats (agent_string, hit_count) VALUES ('$ua', 1)"
            . " ON DUPLICATE KEY UPDATE hit_count = hit_count + 1";
    
    // Thực thi câu lệnh. Nếu payload chứa SQLi làm gãy cú pháp, 
    // driver PDO sẽ CHỈ trả về false, TUYỆT ĐỐI KHÔNG ném Exception!
    $pdo->query($sql); 
    
    // Đánh dấu bản ghi đã xử lý (Luồng chạy vẫn tiếp tục bình thường)
    $pdo->query("UPDATE raw_traffic SET is_processed = 1 WHERE id = $id");
}

echo "Aggregation complete. (HTTP 200 OK)";
?>