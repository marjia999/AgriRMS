<?php
session_start();
include '../database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Get all clients with their request counts
$clients_query = "SELECT u.*, 
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id) as total_requests,
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id AND request_status = 'Pending') as pending_requests,
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id AND request_status = 'Processing') as processing_requests,
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id AND request_status IN ('Delivered', 'Returned')) as completed_requests,
                  (SELECT SUM(total_amount) FROM payments WHERE user_id = u.id AND payment_status = 'Paid') as total_spent,
                  (SELECT SUM(due_amount) FROM payments WHERE user_id = u.id AND payment_status != 'Paid') as total_due
                  FROM users u 
                  WHERE u.role = 'Client' 
                  ORDER BY u.created_at DESC";
$clients = mysqli_query($conn, $clients_query);

// Calculate statistics
$total_clients_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'Client'");
$total_clients = $total_clients_query ? mysqli_fetch_assoc($total_clients_query)['total'] : 0;

$new_clients_query = mysqli_query($conn, "SELECT COUNT(*) as new FROM users WHERE role = 'Client' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$new_clients = $new_clients_query ? mysqli_fetch_assoc($new_clients_query)['new'] : 0;

$active_clients_query = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as active FROM service_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$active_clients = $active_clients_query ? mysqli_fetch_assoc($active_clients_query)['active'] : 0;

$total_requests_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM service_requests sr JOIN users u ON sr.user_id = u.id WHERE u.role = 'Client'");
$total_requests = $total_requests_query ? mysqli_fetch_assoc($total_requests_query)['total'] : 0;

$total_spent_query = mysqli_query($conn, "SELECT SUM(p.total_amount) as total FROM payments p JOIN users u ON p.user_id = u.id WHERE u.role = 'Client' AND p.payment_status = 'Paid'");
$total_spent = $total_spent_query ? mysqli_fetch_assoc($total_spent_query)['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - AgriRMS</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            font-weight: 400;
            letter-spacing: 0.3px;
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
        }

        .nav-links a:hover {
            color: #FFD966 !important;
            transform: translateY(-1px);
        }

        .btn-logout {
            background: #dc3545;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
            transition: 0.3s;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 5%;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #FF8C42;
            font-size: 2rem;
        }

        .page-header p {
            color: #666;
            margin-top: 0.5rem;
        }

        /* Stats Cards - Premium Design */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF8C42, #FFD966);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,140,66,0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: #FF8C42;
        }

        .stat-trend {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 20px;
            background: #e8f0e8;
            color: #1B4F2B;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1B4F2B;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .stat-sub {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.5rem;
        }

        /* Client Grid - Card Layout */
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .client-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .client-header {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            padding: 1.5rem;
            position: relative;
            color: white;
        }

        .client-avatar {
            width: 70px;
            height: 70px;
            background: #FF8C42;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 2rem;
            font-weight: 700;
            color: #1B4F2B;
            border: 3px solid #FFD966;
        }

        .client-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .client-email {
            font-size: 0.8rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .client-phone {
            font-size: 0.8rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 0.3rem;
        }

        .client-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .client-body {
            padding: 1.5rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e8f0e8;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1B4F2B;
        }

        .stat-item .stat-text {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.2rem;
        }

        .financial-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .financial-item {
            background: #f8f9f8;
            padding: 0.8rem;
            border-radius: 16px;
            text-align: center;
        }

        .financial-label {
            font-size: 0.7rem;
            color: #888;
            margin-bottom: 0.3rem;
        }

        .financial-amount {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .financial-amount.spent {
            color: #28a745;
        }

        .financial-amount.due {
            color: #dc3545;
        }

        .btn-view-client {
            display: block;
            text-align: center;
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.8rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-view-client:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            font-size: 1.3rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header h2 i {
            color: #FF8C42;
        }

        .client-count {
            background: #1B4F2B;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 24px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #888;
            font-size: 0.9rem;
        }

        /* Footer */
        .footer {
            background: #0d2b18;
            color: white;
            padding: 2rem 5%;
            text-align: center;
            margin-top: auto;
        }

        .footer p {
            color: #c0ddc0;
            font-size: 0.85rem;
        }

        .footer p i {
            color: #FFD966;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .clients-grid {
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
            .main-content {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .financial-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .client-header {
                padding: 1rem;
            }
            .client-body {
                padding: 1rem;
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
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="resources.php"><i class="fas fa-tractor"></i> Resources</a>
            <a href="service_requests.php"><i class="fas fa-clipboard-list"></i> Requests</a>
            <a href="logistics.php"><i class="fas fa-truck"></i> Logistics</a>
            <a href="billing.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
            <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-users"></i>
                Client Management
            </h1>
            <p>Manage and monitor all registered clients, their activities, and financial status</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-user-plus"></i> +<?php echo $new_clients; ?> new
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_clients; ?></div>
                <div class="stat-label">Total Clients</div>
                <div class="stat-sub">Registered users</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $active_clients; ?></div>
                <div class="stat-label">Active Clients</div>
                <div class="stat-sub">Activity in last 30 days</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_requests; ?></div>
                <div class="stat-label">Total Requests</div>
                <div class="stat-sub">All service requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="stat-value">৳ <?php echo number_format($total_spent, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-sub">From all clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo round($total_spent / max($total_clients, 1), 0); ?></div>
                <div class="stat-label">Avg per Client</div>
                <div class="stat-sub">Average spending</div>
            </div>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h2>
                <i class="fas fa-user-friends"></i>
                Registered Clients
            </h2>
            <div class="client-count">
                <i class="fas fa-users"></i> <?php echo $total_clients; ?> Clients
            </div>
        </div>

        <!-- Clients Grid - Card Layout -->
        <?php if($clients && mysqli_num_rows($clients) > 0): ?>
        <div class="clients-grid">
            <?php while($row = mysqli_fetch_assoc($clients)): 
                $initials = strtoupper(substr($row['full_name'], 0, 1));
                $avatar_color = ['#FF8C42', '#1B4F2B', '#FFD966', '#17a2b8', '#dc3545'][rand(0,4)];
            ?>
            <div class="client-card">
                <div class="client-header">
                    <div class="client-avatar" style="background: <?php echo $avatar_color; ?>">
                        <?php echo $initials; ?>
                    </div>
                    <div class="client-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                    <div class="client-email">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
                    </div>
                    <?php if($row['phone']): ?>
                    <div class="client-phone">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="client-badge">
                        <i class="fas fa-calendar-alt"></i> Joined <?php echo date('M Y', strtotime($row['created_at'])); ?>
                    </div>
                </div>
                <div class="client-body">
                    <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $row['total_requests']; ?></div>
                            <div class="stat-text">Total <br>Requests</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" style="color: #856404;"><?php echo $row['pending_requests']; ?></div>
                            <div class="stat-text">Pending</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" style="color: #28a745;"><?php echo $row['completed_requests']; ?></div>
                            <div class="stat-text">Completed</div>
                        </div>
                    </div>
                    <div class="financial-row">
                        <div class="financial-item">
                            <div class="financial-label">Total Spent</div>
                            <div class="financial-amount spent">৳ <?php echo number_format($row['total_spent'] ?? 0, 2); ?></div>
                        </div>
                        <div class="financial-item">
                            <div class="financial-label">Due Amount</div>
                            <div class="financial-amount due">৳ <?php echo number_format($row['total_due'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                    <a href="view_client.php?id=<?php echo $row['id']; ?>" class="btn-view-client">
                        <i class="fas fa-eye"></i> View Full Details
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>No clients registered yet.</p>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>