<?php
// Admin Template Functions
session_start();
include __DIR__ . '/../database.php';

// Check if user is logged in and is admin
function checkAdminAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
        header("Location: ../login.php");
        exit();
    }
}

// Render Admin Header
function renderAdminHeader($page_title = "Admin Dashboard") {
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?> - AgriRMS</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', sans-serif;
                background: #f5f7f5;
                color: #1a2e1f;
                line-height: 1.5;
            }

            /* Header */
            .header {
                background: #1B4F2B;
                padding: 1rem 5%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: sticky;
                top: 0;
                z-index: 1000;
                box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            }

            .logo h2 {
                color: #FF8C42;
                font-size: 1.5rem;
            }

            .logo p {
                color: #f0f7f0;
                font-size: 0.75rem;
            }

            .nav-links {
                display: flex;
                gap: 1.5rem;
                align-items: center;
                flex-wrap: wrap;
            }

            .nav-links a {
                color: white;
                text-decoration: none;
                font-weight: 500;
                transition: 0.3s;
                padding: 0.5rem 0;
            }

            .nav-links a:hover {
                color: #FFD966 !important;
            }

            .nav-links a.active {
                color: #FFD966 !important;
                border-bottom: 2px solid #FFD966;
            }

            .user-info {
                background: #0d3b1a;
                padding: 0.5rem 1rem;
                border-radius: 25px;
                font-size: 0.85rem;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .user-info i {
                color: #FFD966;
            }

            .btn-logout {
                background: #dc3545;
                padding: 0.4rem 1rem;
                border-radius: 8px;
                color: white !important;
            }

            .btn-logout:hover {
                background: #c82333;
                color: white !important;
            }

            /* Container */
            .container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 2rem;
            }

            /* Dashboard Header */
            .dashboard-header {
                margin-bottom: 2rem;
            }

            .dashboard-header h1 {
                font-size: 1.8rem;
                color: #1B4F2B;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .dashboard-header h1 i {
                color: #FF8C42;
            }

            .dashboard-header p {
                color: #666;
                margin-top: 0.3rem;
            }

            /* Stats Grid */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .stat-card {
                background: white;
                border-radius: 20px;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
                transition: 0.3s;
                border: 1px solid #e8f0e8;
            }

            .stat-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                border-color: #FF8C42;
            }

            .stat-icon {
                width: 55px;
                height: 55px;
                background: rgba(255,140,66,0.1);
                border-radius: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .stat-icon i {
                font-size: 1.6rem;
                color: #FF8C42;
            }

            .stat-info h3 {
                font-size: 1.8rem;
                color: #1B4F2B;
                font-weight: 700;
            }

            .stat-info p {
                color: #666;
                font-size: 0.85rem;
            }

            .stat-info small {
                color: #888;
                font-size: 0.7rem;
            }

            /* Cards */
            .card {
                background: white;
                border-radius: 20px;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
                border: 1px solid #e8f0e8;
            }

            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.2rem;
                padding-bottom: 0.8rem;
                border-bottom: 2px solid #f0f4f0;
            }

            .card-header h3 {
                color: #1B4F2B;
                font-size: 1.2rem;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .card-header h3 i {
                color: #FF8C42;
            }

            .card-header a {
                color: #FF8C42;
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 500;
                transition: 0.3s;
            }

            .card-header a:hover {
                color: #e67e22;
                text-decoration: underline;
            }

            /* Tables */
            .table-responsive {
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th, td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #e8f0e8;
            }

            th {
                background: #f8f9f8;
                color: #1B4F2B;
                font-weight: 600;
                font-size: 0.85rem;
            }

            td {
                color: #444;
                font-size: 0.85rem;
            }

            tr:hover {
                background: #f0f7f0;
            }

            /* Status Badges */
            .status {
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.7rem;
                font-weight: 600;
                display: inline-block;
            }

            .status-available, .status-approved, .status-paid, .status-returned, .status-completed {
                background: #d4edda;
                color: #155724;
            }

            .status-pending {
                background: #fff3cd;
                color: #856404;
            }

            .status-rented, .status-processing, .status-delivered {
                background: #cce5ff;
                color: #004085;
            }

            .status-under_maintenance, .status-cancelled {
                background: #f8d7da;
                color: #721c24;
            }

            /* Buttons */
            .btn-view, .btn-primary {
                background: #FF8C42;
                color: white;
                padding: 0.3rem 0.8rem;
                border-radius: 6px;
                text-decoration: none;
                font-size: 0.75rem;
                display: inline-block;
                border: none;
                cursor: pointer;
            }

            .btn-view:hover, .btn-primary:hover {
                background: #e67e22;
            }

            .btn-edit {
                background: #1B4F2B;
                color: white;
                padding: 0.3rem 0.8rem;
                border-radius: 6px;
                text-decoration: none;
                font-size: 0.75rem;
                display: inline-block;
            }

            .btn-edit:hover {
                background: #0d3b1a;
            }

            .btn-danger {
                background: #dc3545;
                color: white;
                padding: 0.3rem 0.8rem;
                border-radius: 6px;
                text-decoration: none;
                font-size: 0.75rem;
                display: inline-block;
            }

            .btn-danger:hover {
                background: #c82333;
            }

            /* Two Column Layout */
            .two-columns {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }

            /* Forms */
            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: #1B4F2B;
                font-size: 0.85rem;
            }

            .form-group input, .form-group select, .form-group textarea {
                width: 100%;
                padding: 0.8rem 1rem;
                border: 2px solid #e8f0e8;
                border-radius: 12px;
                font-size: 0.9rem;
                font-family: 'Inter', sans-serif;
            }

            .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
                outline: none;
                border-color: #FF8C42;
            }

            /* Alerts */
            .alert {
                padding: 1rem;
                border-radius: 12px;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .alert-success {
                background: #d4edda;
                color: #155724;
                border-left: 4px solid #28a745;
            }

            .alert-danger {
                background: #f8d7da;
                color: #721c24;
                border-left: 4px solid #dc3545;
            }

            .alert-warning {
                background: #fff3cd;
                color: #856404;
                border-left: 4px solid #ffc107;
            }

            .alert-info {
                background: #d1ecf1;
                color: #0c5460;
                border-left: 4px solid #17a2b8;
            }

            /* Footer */
            .footer {
                background: #0d2b18;
                color: white;
                padding: 1.5rem;
                text-align: center;
                margin-top: 3rem;
            }

            .footer p {
                color: #c0ddc0;
                font-size: 0.8rem;
            }

            /* Responsive */
            @media (max-width: 1024px) {
                .two-columns {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 768px) {
                .header {
                    flex-direction: column;
                    gap: 1rem;
                }
                .nav-links {
                    justify-content: center;
                }
                .container {
                    padding: 1rem;
                }
                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 1rem;
                }
            }

            @media (max-width: 480px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <header class="header">
            <div class="logo">
                <h2><i class="fas fa-leaf"></i> AgriRMS</h2>
                <p>Agricultural Resource Management System</p>
            </div>
            <nav class="nav-links">
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="resources.php" class="<?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">Resources</a>
                <a href="service_requests.php" class="<?php echo $current_page == 'service_requests.php' ? 'active' : ''; ?>">Requests</a>
                <a href="logistics.php" class="<?php echo $current_page == 'logistics.php' ? 'active' : ''; ?>">Logistics</a>
                <a href="billing.php" class="<?php echo $current_page == 'billing.php' ? 'active' : ''; ?>">Billing</a>
                <a href="clients.php" class="<?php echo $current_page == 'clients.php' ? 'active' : ''; ?>">Clients</a>
                <a href="maintenance.php" class="<?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?>">Maintenance</a>
                <div class="user-info">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </nav>
        </header>
        <div class="container">
    <?php
}

// Render Admin Footer
function renderAdminFooter() {
    ?>
        </div>
        <footer class="footer">
            <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved.</p>
        </footer>
    </body>
    </html>
    <?php
}
?>