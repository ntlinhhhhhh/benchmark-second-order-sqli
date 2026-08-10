<?php
// Exception-Free Benchmark - Pattern 2: Intra-Application Taint Loss
// Testbed 3: The Serialization Taint-Loss (Source)
// Description: Encodes input into JSON, saving it dynamically and causing taint loss in SAST.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'silent_testbed';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$raw_font = $_POST['font'] ?? 'Arial';

// Defense Layer 1: Application-level Data Truncation
// Cắt chuỗi để đảm bảo khi json_encode, tổng độ dài không vượt quá VARCHAR(255)
// Điều này ngăn chặn triệt để lỗi "Data too long" ở lệnh execute()
if (strlen($raw_font) > 100) {
    $raw_font = substr($raw_font, 0, 100); 
}

$theme_config = [
    'color' => $_POST['color'] ?? 'white',
    'font_family' => $raw_font
];

// SAST BLIND SPOT: Taint Loss tại đây!
$json_data = json_encode($theme_config); 

// Defense Layer 2: Parameterized Statement
$stmt = $db->prepare("UPDATE site_options SET config_value = ? WHERE option_name = 'theme_settings'");
$stmt->bind_param("s", $json_data);

try {
    $stmt->execute();
    echo "Theme saved successfully! (HTTP 200 OK)";
} catch (Exception $e) {
    // Nuốt lỗi (nếu có) để duy trì trạng thái Silent
    error_log("DB Error: " . $e->getMessage());
}
?>