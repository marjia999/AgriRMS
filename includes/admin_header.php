<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - AgriRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Inter, sans-serif; margin: 0; background: #f5f7f5; color: #1a2e1f; }
        .header { background: #1B4F2B; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .logo h2 { margin: 0; color: #FF8C42; font-size: 1.4rem; }
        .logo p { margin: 0; color: #f0f7f0; font-size: .75rem; }
        .nav-links { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { color: #fff; text-decoration: none; font-weight: 500; }
        .nav-links a.active, .nav-links a:hover { color: #FFD966; }
        .admin-dropdown { background: #0d3b1a; color: #fff; border-radius: 999px; padding: .45rem .9rem; font-size: .82rem; display: flex; align-items: center; gap: .4rem; }
        .btn-logout { background: #dc3545; border-radius: 8px; padding: .45rem .9rem; }
        .admin-main { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
        @media (max-width: 768px) { .header { padding: 1rem; } .admin-main { padding: 1rem; } }
    </style>
</head>
<body>
<header class="header">
    <div class="logo">
        <h2><i class="fas fa-leaf"></i> AgriRMS</h2>
        <p>Agricultural Resource Management System</p>
    </div>
    <nav class="nav-links" aria-label="Admin navigation">
        <a class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
        <a class="<?php echo $current_page === 'resources.php' ? 'active' : ''; ?>" href="resources.php">Resources</a>
        <a class="<?php echo $current_page === 'service_requests.php' ? 'active' : ''; ?>" href="service_requests.php">Requests</a>
        <a class="<?php echo $current_page === 'logistics.php' ? 'active' : ''; ?>" href="logistics.php">Logistics</a>
        <a class="<?php echo $current_page === 'billing.php' ? 'active' : ''; ?>" href="billing.php">Billing</a>
        <a class="<?php echo $current_page === 'clients.php' ? 'active' : ''; ?>" href="clients.php">Clients</a>
        <a class="<?php echo $current_page === 'maintenance.php' ? 'active' : ''; ?>" href="maintenance.php">Maintenance</a>
        <span class="admin-dropdown"><i class="fas fa-user-shield"></i><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
        <a class="btn-logout" href="../logout.php">Logout</a>
    </nav>
</header>
<main class="admin-main">
