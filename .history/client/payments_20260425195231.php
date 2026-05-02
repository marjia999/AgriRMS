<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get statistics
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id"))['count'];
$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Pending'"))['count'];
$approved_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Approved'"))['count'];
$processing_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Processing'"))['count'];
$delivered_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Delivered'"))['count'];
$returned_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Returned'"))['count'];
$pending_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE user_id = $user_id AND payment_status = 'Pending'"))['count'];

// Get recent approved requests (show approved, processing, delivered, returned)
$recent_requests = mysqli_query($conn, "SELECT sr.*, r.name as resource_name, r.model 
                                        FROM service_requests sr 
                                        LEFT JOIN resources r ON sr.resource_id = r.id 
                                        WHERE sr.user_id = $user_id 
                                        AND sr.request_status IN ('Approved', 'Processing', 'Delivered', 'Returned')
                                        ORDER BY sr.created_at DESC 
                                        LIMIT 5");

// Get available resources count by type
$tractor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE type = 'Tractor' AND status = 'Available'"))['count'];
$harvesting_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE type = 'Harvesting' AND status = 'Available'"))['count'];
$irrigation_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE type = 'Irrigation' AND status = 'Available'"))['count'];
$total_available = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Available'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - AgriRMS</title>
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

        .main-content {
            flex: 1;
            padding: 2rem 5%;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        /* Welcome Hero Section */
        .welcome-hero {
            background: linear-gradient(135deg, #1B4F2B 0%, #0d3b1a 100%);
            padding: 2.5rem;
            border-radius: 28px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .welcome-hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,140,66,0.15), transparent);
            border-radius: 50%;
        }

        .welcome-greeting {
            position: relative;
            z-index: 2;
        }

        .greeting-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,140,66,0.25);
            backdrop-filter: blur(10px);
            color: #FFD966;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .welcome-greeting h1 {
            font-size: 2rem;
            color: white;
            margin-bottom: 0.3rem;
        }

        .welcome-greeting h1 span {
            color: #FFD966;
        }

        .welcome-greeting p {
            color: #c8e6d9;
            font-size: 0.9rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,140,66,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: #FF8C42;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            color: #1B4F2B;
            font-weight: 700;
        }

        .stat-info p {
            color: #666;
            font-size: 0.7rem;
        }

        /* Section Title */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title h2 {
            font-size: 1.3rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title h2 i {
            color: #FF8C42;
        }

        .section-title a {
            color: #FF8C42;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .category-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-decoration: none;
            display: block;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .category-image {
            height: 150px;
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-image i {
            font-size: 4rem;
            color: #FF8C42;
        }

        .category-info {
            padding: 1rem;
            text-align: center;
        }

        .category-info h3 {
            color: #1B4F2B;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .category-info p {
            color: #888;
            font-size: 0.75rem;
        }

        .category-badge {
            display: inline-block;
            background: #e8f0e8;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            color: #1B4F2B;
            margin-top: 0.5rem;
        }

        /* Requests Card */
        .card {
            background: white;
            border-radius: 24px;
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
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h3 {
            color: #1B4F2B;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 i {
            color: #FF8C42;
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
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e8f0e8;
        }

        th {
            background: #f8f9f8;
            color: #1B4F2B;
            font-weight: 600;
            font-size: 0.75rem;
        }

        td {
            color: #444;
            font-size: 0.8rem;
        }

        .status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-returned { background: #d4edda; color: #155724; }

        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 0.2rem 0.7rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-block;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 0.8rem;
        }

        .empty-state p {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.5rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }

        .footer {
            background: #0d2b18;
            color: white;
            padding: 2rem 5%;
            text-align: center;
            margin-top: auto;
        }

        .footer p {
            color: #c0ddc0;
            font-size: 0.8rem;
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
            }
            .categories-grid {
                grid-template-columns: 1fr;
            }
            .welcome-hero {
                padding: 1.5rem;
            }
            .welcome-greeting h1 {
                font-size: 1.3rem;
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
            <a href="dashboard.php">Home</a>
            <a href="resources.php">Resources</a>
            <a href="request_service.php">New Request</a>
            <a href="my_requests.php">My Requests</a>
            <a href="payments.php">Payments</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <!-- Welcome Hero -->
        <div class="welcome-hero">
            <div class="welcome-greeting">
                <div class="greeting-badge">
                    <i class="fas fa-hand-peace"></i> Welcome Back!
                </div>
                <h1>Hello, <span><?php echo htmlspecialchars($user['full_name'] ?? 'Client'); ?></span></h1>
                <p>Ready to manage your agricultural resources today?</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_requests; ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pending_requests; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $approved_requests + $processing_requests + $delivered_requests + $returned_requests; ?></h3>
                    <p>Active/Completed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pending_payments; ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="section-title">
            <h2><i class="fas fa-th-large"></i> Browse Resources</h2>
            <a href="resources.php">View All →</a>
        </div>
        <div class="categories-grid">
            <a href="resources.php?type=Tractor" class="category-card">
                <div class="category-image"><i class="fas fa-tractor"></i></div>
                <div class="category-info">
                    <h3>Tractors</h3>
                    <p>Powerful tractors for farming</p>
                    <span class="category-badge"><?php echo $tractor_count; ?> available</span>
                </div>
            </a>
            <a href="resources.php?type=Harvesting" class="category-card">
                <div class="category-image"><i class="fas fa-cut"></i></div>
                <div class="category-info">
                    <h3>Harvesting</h3>
                    <p>Harvesters & combines</p>
                    <span class="category-badge"><?php echo $harvesting_count; ?> available</span>
                </div>
            </a>
            <a href="resources.php?type=Irrigation" class="category-card">
                <div class="category-image"><i class="fas fa-water"></i></div>
                <div class="category-info">
                    <h3>Irrigation</h3>
                    <p>Pumps & sprinklers</p>
                    <span class="category-badge"><?php echo $irrigation_count; ?> available</span>
                </div>
            </a>
        </div>

        <!-- Recent Approved Requests -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-check-circle"></i> Your Approved Requests</h3>
                <a href="my_requests.php" class="btn-view" style="background: #6c757d;">View All</a>
            </div>
            <div class="table-responsive">
                <?php if($recent_requests && mysqli_num_rows($recent_requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Resource</th>
                            <th>Model</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($recent_requests)): ?>
                        <tr>
                            <td>#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['model'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d', strtotime($row['end_date'])); ?></td>
                            <td>
                                <?php
                                $status_class = 'status-pending';
                                switch($row['request_status']) {
                                    case 'Approved': $status_class = 'status-approved'; break;
                                    case 'Processing': $status_class = 'status-processing'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    case 'Returned': $status_class = 'status-returned'; break;
                                    default: $status_class = 'status-pending';
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $row['request_status']; ?></span>
                            </td>
                            <td><a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-view">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You don't have any approved requests yet.</p>
                    <a href="request_service.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Create New Request
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved.</p>
    </footer>
</body>
</html>