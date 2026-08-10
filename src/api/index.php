<?php
// Exception-Free Benchmark - Pattern 2: Intra-Application Taint Loss
// Testbed 3: The Serialization Taint-Loss (Sink)
// Description: Retrieves and decodes stored JSON, executing dynamic SQL query that triggers SQLi.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'silent_testbed';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);


$res = $db->query("SELECT config_value FROM site_options WHERE option_name = 'theme_settings'");
$theme_json = $res->fetch_assoc()['config_value'];

// Giải mã JSON thành mảng
$config = json_decode($theme_json, true);
$font = $config['font_family']; // Payload độc hại sống lại từ JSON

// Lỗ hổng Second-Order: Ghi log giao diện đang được render
$log_sql = "INSERT INTO render_logs (element_name, render_time) VALUES ('Font_Loaded: $font', NOW())";
$db->query($log_sql);

echo "<body style='font-family: " . htmlspecialchars($font) . "'>";
echo "<h1>Welcome to CMS</h1>";
?>