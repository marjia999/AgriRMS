<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter type from URL
$filter_type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query - Client can only see Available resources
$query = "SELECT * FROM resources WHERE status = 'Available'";
if ($filter_type && $filter_type != 'All') {
    $query .= " AND type = '$filter_type'";
}
$query .= " ORDER BY type, name ASC";
$resources = mysqli_query($conn, $query);

// Get unique types for filter dropdown (only from available resources)
$types_query = mysqli_query($conn, "SELECT DISTINCT type FROM resources WHERE status = 'Available' ORDER BY type");
$all_types = [];
while($row = mysqli_fetch_assoc($types_query)) {
    $all_types[] = $row['type'];
}

// Get counts for filters
$total_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Available'"))['count'];

$type_counts = [];
foreach($all_types as $type) {
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Available' AND type = '$type'");
    $type_counts[$type] = mysqli_fetch_assoc($count_query)['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Resources - AgriRMS</title>
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

        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid #e8f0e8;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-label {
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            background: white;
            cursor: pointer;
            min-width: 180px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #FF8C42;
        }

        .active-filters {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .filter-badge {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-clear-filter {
            background: #6c757d;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }

        .btn-clear-filter:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Resources Grid */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .resource-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .resource-header {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            padding: 1.2rem;
            color: white;
            position: relative;
        }

        .resource-header h3 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .resource-header .model {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .resource-type-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .resource-body {
            padding: 1.2rem;
        }

        .resource-info {
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f0f4f0;
        }

        .info-row .label {
            color: #888;
            font-size: 0.75rem;
        }

        .info-row .value {
            font-weight: 600;
            color: #1B4F2B;
        }

        .price {
            font-size: 1.3rem;
            color: #FF8C42;
            font-weight: 700;
        }

        .price small {
            font-size: 0.7rem;
            font-weight: 400;
        }

        .btn-request {
            width: 100%;
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.7rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
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

        @media (max-width: 900px) {
            .resources-grid {
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
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                justify-content: space-between;
            }
            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-select {
                width: 100%;
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
            <a href="request_service.php"><i class="fas fa-plus-circle"></i> New Request</a>
            <a href="my_requests.php"><i class="fas fa-list"></i> My Requests</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-tractor"></i>
                Available Resources
            </h1>
            <p>Browse and rent agricultural equipment, machinery, and storage solutions</p>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label"><i class="fas fa-filter"></i> Filter By Type:</span>
                <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <select name="type" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach($all_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type == $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?> (<?php echo $type_counts[$type]; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if($filter_type): ?>
            <div class="active-filters">
                <span class="filter-badge">
                    <i class="fas fa-tag"></i> Type: <?php echo htmlspecialchars($filter_type); ?>
                </span>
                <a href="resources.php" class="btn-clear-filter">
                    <i class="fas fa-times"></i> Clear Filter
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Resources Grid -->
        <?php if($resources && mysqli_num_rows($resources) > 0): ?>
        <div class="resources-grid">
            <?php while($resource = mysqli_fetch_assoc($resources)): 
                $type_icon = '';
                switch($resource['type']) {
                    case 'Tractor': $type_icon = 'fas fa-tractor'; break;
                    case 'Soil Cultivation': $type_icon = 'fas fa-seedling'; break;
                    case 'Planting': $type_icon = 'fas fa-leaf'; break;
                    case 'Irrigation': $type_icon = 'fas fa-water'; break;
                    case 'Harvesting': $type_icon = 'fas fa-cut'; break;
                    case 'Hay Making': $type_icon = 'fas fa-tree'; break;
                    case 'Loading': $type_icon = 'fas fa-crane'; break;
                    case 'Fertilizer Dispenser': $type_icon = 'fas fa-tachometer-alt'; break;
                    case 'Produce Sorter': $type_icon = 'fas fa-sort-amount-down'; break;
                    case 'Post Harvest': $type_icon = 'fas fa-warehouse'; break;
                    default: $type_icon = 'fas fa-tractor';
                }
            ?>
            <div class="resource-card">
                <div class="resource-header">
                    <div class="resource-icon" style="margin-bottom: 0.5rem;">
                        <i class="<?php echo $type_icon; ?>" style="font-size: 1.5rem;"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($resource['name']); ?></h3>
                    <div class="model">Model: <?php echo htmlspecialchars($resource['model']); ?></div>
                    <div class="resource-type-badge"><?php echo htmlspecialchars($resource['type']); ?></div>
                </div>
                <div class="resource-body">
                    <div class="resource-info">
                        <div class="info-row">
                            <span class="label"><i class="fas fa-industry"></i> Category</span>
                            <span class="value"><?php echo htmlspecialchars($resource['category']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-building"></i> Manufacturer</span>
                            <span class="value"><?php echo htmlspecialchars($resource['manufacturer'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if($resource['horsepower']): ?>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-tachometer-alt"></i> Horsepower</span>
                            <span class="value"><?php echo $resource['horsepower']; ?> HP</span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-gas-pump"></i> Fuel Type</span>
                            <span class="value"><?php echo $resource['fuel_type']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-money-bill-wave"></i> Daily Rate</span>
                            <span class="price">৳ <?php echo number_format($resource['daily_rate'], 2); ?><small>/day</small></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-chart-line"></i> Status</span>
                            <span class="status-badge"><i class="fas fa-check-circle"></i> Available</span>
                        </div>
                        <?php if($resource['quantity'] > 1): ?>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-cubes"></i> Available Units</span>
                            <span class="value"><?php echo $resource['quantity']; ?> units</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="request_service.php?resource_id=<?php echo $resource['id']; ?>" class="btn-request">
                        <i class="fas fa-calendar-alt"></i> Request This Resource
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tractor"></i>
            <p>No resources available at the moment. Please check back later.</p>
            <?php if($filter_type): ?>
            <a href="resources.php" style="margin-top: 1rem; display: inline-block; color: #FF8C42; text-decoration: none;">Clear Filter</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>