<?php
// Central Router, Helper APIs, and Interactive Dashboard UI

// Redirect PHP error logs to a local file in the workspace directory (src/php_errors.log)
ini_set('error_log', '/var/www/html/php_errors.log');

// Disable buggy PDO query hooks from uopz fuzzer instrumentation to prevent local dashboard crashes
if (function_exists('uopz_unset_return')) {
    try {
        uopz_unset_return('PDO', 'query');
    } catch (\Throwable $t) {}
}

// Define a fallback mock for the fuzzer's auto-prepended instrumentation to prevent crashes
if (!function_exists('__fuzzer_rewrite_select_query')) {
    function __fuzzer_rewrite_select_query($query) {
        return $query;
    }
}

// 1. Dynamic Routing to Vulnerable APIs
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// =========================================================================
// Exception-Free Benchmark API Router
// =========================================================================

// --- Pattern 1: System-Level Exception Masking ---

// Testbed 1: The Explicit Silent Mode
// Source: POST /api/track -> raw_traffic (Prepared Stmt)
// Sink  : GET  /cron/aggregate_stats -> agent_stats (ERRMODE_SILENT)
if ($request_path === '/api/track') {
    require_once __DIR__ . '/api/track.php';
    exit;
}
if ($request_path === '/cron/aggregate_stats') {
    require_once __DIR__ . '/api/aggregate_stats.php';
    exit;
}

// Testbed 2.1: The Batching Blackhole (multi_query)
// Source: POST /register -> clients (Prepared Stmt)
// Sink  : GET  /billing -> loyalty_points / system_logs (multi_query)
if ($request_path === '/register') {
    require_once __DIR__ . '/api/register.php';
    exit;
}
if ($request_path === '/billing') {
    require_once __DIR__ . '/api/billing.php';
    exit;
}

// Testbed 2.2: The Asynchronous Socket Blackhole (MYSQLI_ASYNC)
// Combined: POST /api/fast_log -> metric_logs (MYSQLI_ASYNC without reap)
if ($request_path === '/api/fast_log') {
    require_once __DIR__ . '/api/fast_log.php';
    exit;
}

// --- Pattern 2: Intra-Application Taint Loss ---

// Testbed 3: The Serialization Taint-Loss
// Source: POST /admin/save_theme -> site_options (json_encode)
// Sink  : GET  /public/index.php -> render_logs (json_decode + SQLi)
if ($request_path === '/admin/save_theme') {
    require_once __DIR__ . '/api/save_theme.php';
    exit;
}
if ($request_path === '/public/index.php') {
    require_once __DIR__ . '/api/index.php';
    exit;
}

// --- Pattern 3: Distributed Context Fragmentation ---

// Testbed 4: The Asynchronous Exfiltration Barrier
// Source: POST /api/request_report -> heavy_report_jobs (Prepared Stmt)
// Worker: GET  /daemon/financial_worker -> financial_worker (Background Daemon CLI)
// Status: GET  /api/check_status -> heavy_report_jobs (Polling)
if ($request_path === '/api/request_report' || $request_path === '/api/request_report.php') {
    require_once __DIR__ . '/api/request_report.php';
    exit;
}
if ($request_path === '/daemon/financial_worker' || $request_path === '/api/financial_worker.php') {
    require_once __DIR__ . '/api/financial_worker.php';
    exit;
}
if ($request_path === '/api/check_status' || $request_path === '/api/check_status.php') {
    require_once __DIR__ . '/api/check_status.php';
    exit;
}
?>
