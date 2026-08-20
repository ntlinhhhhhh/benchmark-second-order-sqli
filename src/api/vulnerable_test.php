<?php
// Endpoint kiểm thử lỗ hổng SQL Injection tiêu chuẩn (Standard First-Order SQLi)
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'db';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$client = $_GET['client_name'];

if (!empty($client)) {
    // Lỗ hổng SQL Injection tiêu chuẩn dạng thủ tục: mysqli_query
    $sql = "SELECT * FROM clients WHERE client_name = '$client'";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "Queried client: " . htmlspecialchars($client);
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
} else {
    echo "Please send client_name parameter.";
}
?>
