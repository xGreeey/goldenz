<?php
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Bootstrap application
try {
    if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
        require_once __DIR__ . '/../bootstrap/app.php';
    } else {
        require_once __DIR__ . '/../bootstrap/autoload.php';
    }
} catch (Exception $e) {
    error_log('Bootstrap error: ' . $e->getMessage());
}

// Include database functions
require_once __DIR__ . '/../includes/database.php';

// Get employees with expiring licenses (active employees only)
$expiring_employees = [];

try {
    $pdo = get_db_connection();
    
    // Query for active employees with expiring licenses (within 30 days or expired)
    $sql = "SELECT 
                e.id,
                e.employee_no,
                e.surname,
                e.first_name,
                e.middle_name,
                e.post,
                e.license_no,
                e.license_exp_date,
                e.status,
                CASE 
                    WHEN e.license_exp_date < CURDATE() THEN 'expired'
                    WHEN DATEDIFF(e.license_exp_date, CURDATE()) <= 7 THEN 'critical'
                    WHEN DATEDIFF(e.license_exp_date, CURDATE()) <= 15 THEN 'high'
                    WHEN DATEDIFF(e.license_exp_date, CURDATE()) <= 30 THEN 'medium'
                    ELSE 'valid'
                END as urgency_level,
                CASE 
                    WHEN e.license_exp_date < CURDATE() THEN ABS(DATEDIFF(e.license_exp_date, CURDATE()))
                    ELSE DATEDIFF(e.license_exp_date, CURDATE())
                END as days_diff
            FROM employees e
            WHERE e.status = 'Active'
            AND e.license_no IS NOT NULL 
            AND e.license_no != ''
            AND e.license_exp_date IS NOT NULL 
            AND e.license_exp_date != ''
            AND e.license_exp_date != '0000-00-00'
            AND (
                e.license_exp_date < CURDATE() 
                OR e.license_exp_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            )
            ORDER BY 
                CASE urgency_level
                    WHEN 'expired' THEN 1
                    WHEN 'critical' THEN 2
                    WHEN 'high' THEN 3
                    WHEN 'medium' THEN 4
                    ELSE 5
                END,
                e.license_exp_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $all_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group employees by urgency level
    $expiring_employees = [
        'expired' => [],
        'critical' => [],
        'high' => [],
        'medium' => []
    ];
    
    foreach ($all_employees as $employee) {
        $urgency = $employee['urgency_level'];
        if (isset($expiring_employees[$urgency])) {
            $expiring_employees[$urgency][] = $employee;
        }
    }
    
    $total_count = count($all_employees);
    
} catch (Exception $e) {
    error_log('Error fetching expiring licenses: ' . $e->getMessage());
    $expiring_employees = [
        'expired' => [],
        'critical' => [],
        'high' => [],
        'medium' => []
    ];
    $total_count = 0;
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Alerts Display - Golden Z-5 HR System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../public/logo.svg">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link rel="apple-touch-icon" href="../public/logo.svg">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', 'Open Sans', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            overflow: hidden;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        
        .alerts-container {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            background: #f8fafc;
            overflow: hidden;
        }
        
        .header-section {
            background: #ffffff;
            padding: 1rem 2rem;
            border-bottom: 2px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            z-index: 100;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            height: auto;
        }
        
        .header-left {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .header-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
            letter-spacing: -0.02em;
        }
        
        .header-section .system-name {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .header-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }
        
        .header-date-time {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .header-date {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .compliance-summary {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            min-width: 80px;
        }
        
        .summary-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .summary-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .summary-value.expired {
            color: #ef4444;
        }
        
        .summary-value.critical {
            color: #f59e0b;
        }
        
        .summary-value.high {
            color: #f97316;
        }
        
        .summary-value.medium {
            color: #eab308;
        }
        
        .columns-container {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            padding: 0;
            background: #f8fafc;
            overflow: hidden;
            margin-top: 0;
            padding-top: 100px;
            height: calc(100vh - 100px - 50px);
        }
        
        .column {
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        
        .column:last-child {
            border-right: none;
        }
        
        .column-header {
            padding: 1rem 1.25rem;
            border-bottom: 2px solid;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 5;
            min-height: 60px;
        }
        
        .column-header.expired {
            border-bottom-color: #ef4444;
        }
        
        .column-header.critical {
            border-bottom-color: #f59e0b;
        }
        
        .column-header.high {
            border-bottom-color: #f97316;
        }
        
        .column-header.medium {
            border-bottom-color: #eab308;
        }
        
        .column-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .column-title i {
            font-size: 1rem;
        }
        
        .column-count {
            background: #e2e8f0;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            min-width: 28px;
            text-align: center;
        }
        
        .column-header.expired .column-title {
            color: #ef4444;
        }
        
        .column-header.expired .column-count {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .column-header.critical .column-title {
            color: #f59e0b;
        }
        
        .column-header.critical .column-count {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .column-header.high .column-title {
            color: #f97316;
        }
        
        .column-header.high .column-count {
            background: #fed7aa;
            color: #f97316;
        }
        
        .column-header.medium .column-title {
            color: #eab308;
        }
        
        .column-header.medium .column-count {
            background: #fef9c3;
            color: #eab308;
        }
        
        .column-header.expired .column-title {
            color: #ef4444;
        }
        
        .column-header.expired .column-count {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .column-header.critical .column-title {
            color: #f59e0b;
        }
        
        .column-header.critical .column-count {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .column-header.high .column-title {
            color: #f97316;
        }
        
        .column-header.high .column-count {
            background: #fed7aa;
            color: #f97316;
        }
        
        .column-header.medium .column-title {
            color: #eab308;
        }
        
        .column-header.medium .column-count {
            background: #fef9c3;
            color: #eab308;
        }
        
        .column-body {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .column-body.auto-scroll {
            overflow: hidden;
        }
        
        .employee-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            position: relative;
            margin-bottom: 0.75rem;
        }
        
        .employee-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .employee-card.expired {
            border-left: 3px solid #ef4444;
        }
        
        .employee-card.critical {
            border-left: 3px solid #f59e0b;
        }
        
        .employee-card.high {
            border-left: 3px solid #f97316;
        }
        
        .employee-card.medium {
            border-left: 3px solid #eab308;
        }
        
        .deadline-date {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.875rem;
            line-height: 1.5;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .deadline-date i {
            font-size: 0.75rem;
            color: #94a3b8;
            flex-shrink: 0;
        }
        
        .deadline-date-text {
            flex: 1;
        }
        
        .deadline-date-highlight {
            font-weight: 700;
            color: #1e293b;
        }
        
        .employee-card.expired .deadline-date {
            color: #dc2626;
        }
        
        .employee-card.expired .deadline-date i {
            color: #dc2626;
        }
        
        .employee-card.expired .deadline-date-highlight {
            color: #dc2626;
        }
        
        .employee-card.critical .deadline-date {
            color: #d97706;
        }
        
        .employee-card.critical .deadline-date i {
            color: #d97706;
        }
        
        .employee-card.critical .deadline-date-highlight {
            color: #d97706;
        }
        
        .employee-card.high .deadline-date {
            color: #ea580c;
        }
        
        .employee-card.high .deadline-date i {
            color: #ea580c;
        }
        
        .employee-card.high .deadline-date-highlight {
            color: #ea580c;
        }
        
        .employee-card.medium .deadline-date {
            color: #ca8a04;
        }
        
        .employee-card.medium .deadline-date i {
            color: #ca8a04;
        }
        
        .employee-card.medium .deadline-date-highlight {
            color: #ca8a04;
        }
        
        .employee-info {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        
        .employee-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            flex-shrink: 0;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        
        .employee-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
            border: 2px solid #e2e8f0;
        }
        
        .employee-details-main {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .employee-name-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .employee-name {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.375rem;
            cursor: pointer;
            display: block;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .employee-post {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .employee-details-hover {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.875rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            min-width: 220px;
            max-width: 280px;
        }
        
        .employee-details-hover::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 20px;
            width: 12px;
            height: 12px;
            background: #ffffff;
            border-left: 1px solid #e2e8f0;
            border-top: 1px solid #e2e8f0;
            transform: rotate(45deg);
        }
        
        .employee-name-wrapper:hover .employee-details-hover {
            display: block;
        }
        
        .detail-item-hover {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.8125rem;
            line-height: 1.4;
        }
        
        .detail-item-hover:last-child {
            border-bottom: none;
        }
        
        .detail-item-hover i {
            width: 18px;
            color: #94a3b8;
            text-align: center;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }
        
        .detail-item-hover .label {
            color: #64748b;
            font-weight: 500;
            min-width: 70px;
            flex-shrink: 0;
        }
        
        .detail-item-hover .value {
            color: #1e293b;
            font-weight: 600;
            word-wrap: break-word;
            flex: 1;
        }
        
        .days-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .days-badge i {
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }
        
        .days-badge.expired {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .days-badge.critical {
            background: #fef3c7;
            color: #d97706;
        }
        
        .days-badge.high {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .days-badge.medium {
            background: #fef9c3;
            color: #ca8a04;
        }
        
        .status-icon {
            font-size: 1.25rem;
        }
        
        .status-icon.expired {
            color: #ef4444;
        }
        
        .status-icon.critical {
            color: #f59e0b;
        }
        
        .status-icon.high {
            color: #f97316;
        }
        
        .status-icon.medium {
            color: #eab308;
        }
        
        .license-number {
            font-family: 'Courier New', monospace;
            color: #1fb2d5;
            font-weight: 600;
        }
        
        .days-badge.expired {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .days-badge.critical {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .days-badge.high {
            background: #fed7aa;
            color: #f97316;
        }
        
        .days-badge.medium {
            background: #fef9c3;
            color: #eab308;
        }
        
        .empty-column {
            text-align: center;
            padding: 1.5rem 0.75rem;
            color: #94a3b8;
        }
        
        .empty-column i {
            font-size: 1.5rem;
            margin-bottom: 0.375rem;
            opacity: 0.4;
        }
        
        .empty-column p {
            font-size: 0.75rem;
            margin: 0;
            font-weight: 500;
        }
        
        .footer-section {
            background: #ffffff;
            padding: 0.75rem 2rem;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.05);
            z-index: 10;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50px;
        }
        
        .footer-info {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .footer-info i {
            color: #cbd5e1;
            margin-right: 0.375rem;
        }
        
        .no-alerts {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }
        
        .no-alerts i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
            color: #94a3b8;
        }
        
        .no-alerts h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 700;
        }
        
        .no-alerts p {
            color: #64748b;
            font-size: 1rem;
        }
        
        .back-button {
            position: fixed;
            top: 1rem;
            right: 1.5rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            padding: 0.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-button:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #1e293b;
            transform: translateY(-1px);
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }
        
        .back-button i {
            margin: 0;
            font-size: 0.875rem;
        }
        
        @media (max-width: 1400px) {
            .columns-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 1200px) {
            .columns-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header-section {
                padding: 1rem 1.5rem;
            }
            
            .header-section h1 {
                font-size: 1.5rem;
            }
            
            .compliance-summary {
                gap: 0.75rem;
            }
            
            .summary-item {
                min-width: 70px;
                padding: 0.5rem 0.75rem;
            }
        }
        
        @media (max-width: 768px) {
            .header-section {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-section h1 {
                font-size: 1.25rem;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .compliance-summary {
                gap: 0.5rem;
            }
            
            .summary-item {
                min-width: 60px;
                padding: 0.375rem 0.5rem;
            }
            
            .summary-value {
                font-size: 1rem;
            }
            
            .columns-container {
                grid-template-columns: 1fr;
                padding-top: 140px;
                height: calc(100vh - 140px - 50px);
            }
            
            .column {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .footer-section {
                padding: 0.625rem 1rem;
                min-height: 50px;
            }
            
            .back-button {
                top: 0.75rem;
                left: 0.75rem;
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-button" title="Back to Login">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <div class="alerts-container">
        <!-- Fixed Header Section -->
        <div class="header-section">
            <div class="header-left">
                <h1><i class="fas fa-exclamation-triangle me-2"></i>License Expiry Alerts</h1>
                <div class="system-name">Golden Z-5 HR System - Compliance Monitoring</div>
            </div>
            <div class="header-right">
                <div class="header-info">
                    <div class="header-date-time" id="currentTime"><?php echo date('h:i A'); ?></div>
                    <div class="header-date"><?php echo date('l, F d, Y'); ?></div>
                </div>
                <div class="compliance-summary">
                    <div class="summary-item">
                        <div class="summary-label">Expired</div>
                        <div class="summary-value expired"><?php echo count($expiring_employees['expired'] ?? []); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Critical</div>
                        <div class="summary-value critical"><?php echo count($expiring_employees['critical'] ?? []); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">High</div>
                        <div class="summary-value high"><?php echo count($expiring_employees['high'] ?? []); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Medium</div>
                        <div class="summary-value medium"><?php echo count($expiring_employees['medium'] ?? []); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content - Column-based Table Layout -->
        <div class="columns-container" id="columnsContainer">
            <?php if ($total_count == 0): ?>
                <div class="no-alerts" style="grid-column: 1 / -1; width: 100%; padding: 4rem 2rem;">
                    <i class="fas fa-check-circle"></i>
                    <h2>All Clear!</h2>
                    <p>No employees with expiring licenses at this time.</p>
                </div>
            <?php else: ?>
                <?php 
                $column_configs = [
                    'expired' => ['title' => 'Expired Licenses', 'icon' => 'fa-exclamation-circle', 'class' => 'expired'],
                    'critical' => ['title' => 'Critical (≤7 days)', 'icon' => 'fa-exclamation-triangle', 'class' => 'critical'],
                    'high' => ['title' => 'High Priority (8-15 days)', 'icon' => 'fa-clock', 'class' => 'high'],
                    'medium' => ['title' => 'Medium Priority (16-30 days)', 'icon' => 'fa-calendar-alt', 'class' => 'medium']
                ];
                
                foreach ($column_configs as $urgency => $config): 
                    $employees = $expiring_employees[$urgency] ?? [];
                    $count = count($employees);
                ?>
                    <div class="column">
                        <div class="column-header <?php echo htmlspecialchars($config['class']); ?>">
                            <div class="column-title">
                                <i class="fas <?php echo htmlspecialchars($config['icon']); ?>"></i>
                                <?php echo htmlspecialchars($config['title']); ?>
                            </div>
                            <div class="column-count"><?php echo $count; ?></div>
                        </div>
                        <div class="column-body <?php echo $count > 10 ? 'auto-scroll' : ''; ?>">
                            <?php if (empty($employees)): ?>
                                <div class="empty-column">
                                    <i class="fas fa-check-circle"></i>
                                    <p>No employees</p>
                                </div>
                            <?php else: ?>
                                <?php 
                                // Duplicate employees for seamless scrolling if needed
                                $display_employees = $count > 10 ? array_merge($employees, $employees) : $employees;
                                foreach ($display_employees as $employee): 
                                    $full_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['surname'] ?? ''));
                                    $days = $employee['days_diff'];
                                    $exp_date = !empty($employee['license_exp_date']) ? $employee['license_exp_date'] : null;
                                    $employee_no = function_exists('format_employee_no') ? format_employee_no($employee['employee_no']) : $employee['employee_no'];
                                    $employee_id = $employee['id'] ?? $employee_no;
                                    
                                    // Format deadline date in a clean, readable format
                                    $deadline_text = '';
                                    if ($exp_date && $exp_date !== '0000-00-00') {
                                        $exp_timestamp = strtotime($exp_date);
                                        $day = date('j', $exp_timestamp);
                                        $month = date('F', $exp_timestamp);
                                        $year = date('Y', $exp_timestamp);
                                        $current_year = date('Y');
                                        
                                        // Format date elegantly
                                        if ($year == $current_year) {
                                            $formatted_date = $month . ' ' . $day;
                                        } else {
                                            $formatted_date = $month . ' ' . $day . ', ' . $year;
                                        }
                                        
                                        if ($urgency === 'expired') {
                                            $deadline_text = 'Expired on ' . $formatted_date;
                                        } elseif ($urgency === 'critical') {
                                            $deadline_text = 'Expires ' . $formatted_date;
                                        } elseif ($urgency === 'high') {
                                            $deadline_text = 'Expires ' . $formatted_date;
                                        } else {
                                            $deadline_text = 'Expires ' . $formatted_date;
                                        }
                                    }
                                    
                                    // Get employee photo
                                    $photo_path = '';
                                    $photo_exists = false;
                                    if (file_exists(__DIR__ . '/../uploads/employees/' . $employee_id . '.jpg')) {
                                        $photo_path = '../uploads/employees/' . $employee_id . '.jpg';
                                        $photo_exists = true;
                                    } elseif (file_exists(__DIR__ . '/../uploads/employees/' . $employee_id . '.png')) {
                                        $photo_path = '../uploads/employees/' . $employee_id . '.png';
                                        $photo_exists = true;
                                    }
                                    
                                    // Get initials for placeholder
                                    $initials = '';
                                    if (!empty($employee['first_name'])) {
                                        $initials .= strtoupper(substr($employee['first_name'], 0, 1));
                                    }
                                    if (!empty($employee['surname'])) {
                                        $initials .= strtoupper(substr($employee['surname'], 0, 1));
                                    }
                                    if (empty($initials)) {
                                        $initials = '??';
                                    }
                                ?>
                                    <div class="employee-card <?php echo htmlspecialchars($urgency); ?>">
                                        <?php if ($deadline_text): ?>
                                            <div class="deadline-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span class="deadline-date-text">
                                                    <?php 
                                                    // Split the text to highlight the date part
                                                    $parts = explode(' ', $deadline_text, 2);
                                                    if (count($parts) == 2) {
                                                        echo htmlspecialchars($parts[0]) . ' <span class="deadline-date-highlight">' . htmlspecialchars($parts[1]) . '</span>';
                                                    } else {
                                                        echo htmlspecialchars($deadline_text);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="employee-info">
                                            <?php if ($photo_exists): ?>
                                                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($full_name); ?>" class="employee-avatar">
                                            <?php else: ?>
                                                <div class="employee-avatar-placeholder"><?php echo htmlspecialchars($initials); ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="employee-details-main">
                                                <div class="employee-name-wrapper">
                                                    <div class="employee-name">
                                                        <?php echo htmlspecialchars($full_name); ?>
                                                    </div>
                                                    <div class="employee-details-hover">
                                                        <div class="detail-item-hover">
                                                            <i class="fas fa-id-card"></i>
                                                            <span class="label">Employee #:</span>
                                                            <span class="value">#<?php echo htmlspecialchars($employee_no); ?></span>
                                                        </div>
                                                        <div class="detail-item-hover">
                                                            <i class="fas fa-briefcase"></i>
                                                            <span class="label">Post:</span>
                                                            <span class="value"><?php echo htmlspecialchars($employee['post'] ?? 'Unassigned'); ?></span>
                                                        </div>
                                                        <div class="detail-item-hover">
                                                            <i class="fas fa-certificate"></i>
                                                            <span class="label">License #:</span>
                                                            <span class="value license-number"><?php echo htmlspecialchars($employee['license_no'] ?? 'N/A'); ?></span>
                                                        </div>
                                                        <div class="detail-item-hover">
                                                            <i class="fas fa-calendar-alt"></i>
                                                            <span class="label">Expires:</span>
                                                            <span class="value"><?php echo $exp_date ? date('M d, Y', strtotime($exp_date)) : 'N/A'; ?></span>
                                                        </div>
                                                        <div class="detail-item-hover">
                                                            <i class="fas fa-clock"></i>
                                                            <span class="label">Days:</span>
                                                            <span class="value">
                                                                <?php if ($urgency === 'expired'): ?>
                                                                    <?php echo $days; ?> day(s) ago
                                                                <?php else: ?>
                                                                    <?php echo $days; ?> day(s) remaining
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="employee-post"><?php echo htmlspecialchars($employee['post'] ?? 'Unassigned'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Fixed Footer Section -->
        <div class="footer-section">
            <div class="footer-info">
                <i class="fas fa-sync-alt"></i>
                Auto-refreshes every 5 minutes
            </div>
        </div>
    </div>
    
    <script>
        // Update time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto-refresh data every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000); // 5 minutes
        
        // Enable auto-scrolling for columns with many items
        document.querySelectorAll('.column-body.auto-scroll').forEach(column => {
            const content = column.querySelector('.row-content');
            if (!content) return;
            
            let scrollPosition = 0;
            let isPaused = false;
            const scrollSpeed = 1; // pixels per frame
            const scrollDelay = 20; // milliseconds between frames
            
            function scroll() {
                if (isPaused) return;
                
                const maxScroll = content.scrollHeight - column.clientHeight;
                if (maxScroll <= 0) return;
                
                scrollPosition += scrollSpeed;
                
                if (scrollPosition >= maxScroll) {
                    scrollPosition = 0; // Reset to top for seamless loop
                }
                
                column.scrollTop = scrollPosition;
            }
            
            // Pause on hover
            column.addEventListener('mouseenter', () => {
                isPaused = true;
            });
            
            column.addEventListener('mouseleave', () => {
                isPaused = false;
            });
            
            // Start auto-scroll
            setInterval(scroll, scrollDelay);
        });
    </script>
</body>
</html>
