<?php
// Exception-Free Benchmark - Pattern 3: Distributed Context Fragmentation
// Testbed 4: The Asynchronous Exfiltration Barrier (Sink)
// Description: CLI Daemon worker that polls job queue, aggregates data asynchronously, and swallows exceptions.
// 3. Tiến trình cách ly (CLI Worker) âm thầm tính toán phía sau
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
echo "Financial Report Worker running...\n";

while (true) {
    $res = $db->query("SELECT id, region_filter FROM heavy_report_jobs WHERE status = 'PENDING' LIMIT 1");
    if ($res->num_rows > 0) {
        $job = $res->fetch_assoc();
        $job_id = (int)$job['id'];
        $region = $job['region_filter']; // Payload sống lại tại đây

        if (strlen($region) > 100) {
            $region = substr($region, 0, 100);
        }
        
        // Cập nhật trạng thái đang xử lý
        $db->query("UPDATE heavy_report_jobs SET status = 'PROCESSING' WHERE id = $job_id");
        
        // LỖ HỔNG (Dynamic Query Building): 
        // Phải nối chuỗi để tạo câu lệnh truy vấn phức tạp (Aggregation & Joins)
        $report_sql = "
            SELECT e.emp_name, e.tax_code, SUM(s.transaction_amount) * 0.15 AS commission, t.tax_rate
            FROM employees e 
            JOIN sales_transactions s ON e.id = s.emp_id 
            JOIN tax_brackets t ON e.salary_tier = t.tier_id
            WHERE e.region = '$region' 
            GROUP BY e.id
        ";
        
        try {
            // ĐIỂM NỔ SQLi: Payload nở ra và thực thi
            $data_res = $db->query($report_sql); 
            
            // Giả lập thời gian CPU tính toán cực nặng (5-10 giây)
            sleep(5); 
            
            // Lưu ra file tĩnh với UUID chống IDOR (Bức tường chặn DAST)
            $filename = uniqid('finance_report_') . '.csv';
            $download_dir = '/var/www/html/downloads';
            if (!is_dir($download_dir)) {
                @mkdir($download_dir, 0777, true);
            }
            $filepath = $download_dir . '/' . $filename;
            
            $file = fopen($filepath, 'w');
            if ($file && $data_res instanceof mysqli_result) {
                fputcsv($file, ['Employee Name', 'Tax Code', 'Calculated Commission', 'Tax Rate']); // Headers
                while ($row = $data_res->fetch_assoc()) { 
                    fputcsv($file, $row); 
                }
                fclose($file);
            }
            
            // Cập nhật DB báo cáo đã xong
            $db->query("UPDATE heavy_report_jobs SET status = 'DONE', report_url = '/downloads/$filename' WHERE id = $job_id");
            echo "Successfully compiled report for Job $job_id\n";
            
        } catch (Throwable $e) {
            // Hệ thống Worker bắt lỗi và nuốt gọn (hoặc ghi log hệ thống cục bộ). 
            // Luồng Web Fuzzer ở HTTP hoàn toàn không thể thấy Exception này!
            $db->query("UPDATE heavy_report_jobs SET status = 'FAILED' WHERE id = $job_id");
            echo "Job $job_id failed internally: " . $e->getMessage() . "\n";
        }
    }
    sleep(2); // Polling delay của Worker
}
?>