-- created database
CREATE DATABASE IF NOT EXISTS db;
USE db;

-- drop tables if they exist
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS loyalty_points;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS phuzz_sensor;
DROP TABLE IF EXISTS render_logs;
DROP TABLE IF EXISTS site_options;
DROP TABLE IF EXISTS agent_stats;
DROP TABLE IF EXISTS raw_traffic;
DROP TABLE IF EXISTS heavy_report_jobs;
DROP TABLE IF EXISTS sales_transactions;
DROP TABLE IF EXISTS tax_brackets;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS metric_logs;

-- =========================================================================
-- EXCEPTION-FREE BENCHMARK - PATTERN 1: SYSTEM-LEVEL EXCEPTION MASKING
-- =========================================================================

-- --- Testbed 1: The Explicit Silent Mode ---
CREATE TABLE IF NOT EXISTS raw_traffic (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45) DEFAULT NULL,
    user_agent   VARCHAR(255) DEFAULT NULL,
    access_time  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_processed TINYINT(1) DEFAULT 0,
    INDEX idx_processed (is_processed)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS agent_stats (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    agent_string VARCHAR(255) DEFAULT NULL,
    hit_count    INT DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_agent_string (agent_string)
) ENGINE=InnoDB;

-- --- Testbed 2.1: The Batching Blackhole (multi_query) ---
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_synced TINYINT(1) DEFAULT 0,
    INDEX idx_synced (is_synced)
);

CREATE TABLE loyalty_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    current_tier VARCHAR(20) DEFAULT 'BRONZE',
    total_spent INT DEFAULT 0,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --- Testbed 2.2: The Asynchronous Socket Blackhole (MYSQLI_ASYNC) ---
CREATE TABLE IF NOT EXISTS metric_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_agent   VARCHAR(255) DEFAULT NULL,
    access_time  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================================
-- EXCEPTION-FREE BENCHMARK - PATTERN 2: INTRA-APPLICATION TAINT LOSS
-- =========================================================================

-- --- Testbed 3: The Serialization Taint-Loss ---
CREATE TABLE site_options (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    option_name  VARCHAR(100)  NOT NULL,
    config_value VARCHAR(255)  NOT NULL,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_option_name (option_name)
) ENGINE=InnoDB;
 
INSERT IGNORE INTO site_options (option_name, config_value)
VALUES (
    'theme_settings',
    '{"color":"white","font_family":"Arial"}'
);

CREATE TABLE render_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    element_name  VARCHAR(255) NOT NULL,
    render_time   DATETIME     NOT NULL
) ENGINE=InnoDB;

-- =========================================================================
-- EXCEPTION-FREE BENCHMARK - PATTERN 3: DISTRIBUTED CONTEXT FRAGMENTATION
-- =========================================================================

-- --- Testbed 4: The Asynchronous Exfiltration Barrier ---
CREATE TABLE IF NOT EXISTS heavy_report_jobs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    requester_id  INT DEFAULT 1,
    region_filter VARCHAR(255) NOT NULL,
    status        VARCHAR(20) DEFAULT 'PENDING',
    report_url    VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tax_brackets (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    tier_id  VARCHAR(10) NOT NULL UNIQUE,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.10
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    emp_name    VARCHAR(100) NOT NULL,
    salary_tier VARCHAR(10) NOT NULL DEFAULT 'A1',
    tax_code    VARCHAR(30) NOT NULL DEFAULT 'TAX1001',
    region      VARCHAR(50) NOT NULL DEFAULT 'North_America',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_region (region)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_transactions (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    emp_id             INT NOT NULL,
    transaction_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    transaction_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_id (emp_id)
) ENGINE=InnoDB;

INSERT IGNORE INTO tax_brackets (tier_id, tax_rate) VALUES
    ('A1', 0.10),
    ('A2', 0.15),
    ('B1', 0.20);

INSERT IGNORE INTO employees (id, emp_name, salary_tier, tax_code, region) VALUES
    (1, 'Alice Smith', 'A1', 'TAX1001', 'North_America'),
    (2, 'Bob Johnson', 'A2', 'TAX1002', 'North_America');

INSERT IGNORE INTO sales_transactions (emp_id, transaction_amount) VALUES
    (1, 5000000.00),
    (1, 7500000.00);

-- =========================================================================
-- TABLES FOR PHUZZ (KEEP UNCHANGED)
-- =========================================================================
CREATE TABLE IF NOT EXISTS __phuzz_insert (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marker VARCHAR(100) DEFAULT 'marker'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS __phuzz_update (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marker VARCHAR(100) DEFAULT 'marker'
) ENGINE=InnoDB;
INSERT IGNORE INTO __phuzz_update (id, marker) VALUES (1, 'marker');

CREATE TABLE IF NOT EXISTS __phuzz_delete (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marker VARCHAR(100) DEFAULT 'marker'
) ENGINE=InnoDB;
INSERT IGNORE INTO __phuzz_delete (id, marker) VALUES (1, 'marker');

CREATE TABLE IF NOT EXISTS __phuzz_history (                                                                                                                           
        pz_trace_id VARCHAR(100) NOT NULL PRIMARY KEY,                                                                                                                
        url TEXT NOT NULL,                                                                                                                                              
        method VARCHAR(10) NOT NULL,           
        request_data TEXT,                                                                                                                         
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP                                                                                                                  
) ENGINE=InnoDB;