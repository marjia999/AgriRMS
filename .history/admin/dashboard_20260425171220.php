<?php
include '../includes/admin_template.php';

// Check admin access
checkAdminAccess();

// Get statistics with correct column names
$total_resources_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM resources");
$total_resources = $total_resources_query ? mysqli_fetch_assoc($total_resources_query)['count'] : 0;

$total_clients_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Client'");
$total_clients = $total_clients_query ? mysqli_fetch_assoc($total_clients_query)['count'] : 0;

$total_requests_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests");
$total_requests = $total_requests_query ? mysqli_fetch_assoc($total_requests_query)['count'] : 0;

$pending_requests_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE request_status = 'Pending'");
$pending_requests = $pending_requests_query ? mysqli_fetch_assoc($pending_requests_query)['count'] : 0;

$available_resources_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Available'");
$available_resources = $available_resources_query ? mysqli_fetch_assoc($available_resources_query)['count'] : 0;

$in_use_resources_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Rented'");
$in_use_resources = $in_use_resources_query ? mysqli_fetch_assoc($in_use_resources_query)['count'] : 0;

$maintenance_resources_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Under Maintenance'");
$maintenance_resources = $maintenance_resources_query ? mysqli_fetch_assoc($maintenance_resources_query)['count'] : 0;

$completed_requests_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE request_status = 'Returned'");
$completed_requests = $completed_requests_query ? mysqli_fetch_assoc($completed_requests_query)['count'] : 0;

$pending_payments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Pending'");
$pending_payments = $pending_payments_query ? mysqli_fetch_assoc($pending_payments_query)['count'] : 0;

$paid_payments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Paid'");
$paid_payments = $paid_payments_query ? mysqli_fetch_assoc($paid_payments_query)['count'] : 0;

// Recent requests with correct column names (user_id instead of client_id, full_name instead of first_name/last_name)
$recent_requests_query = "SELECT sr.*, u.full_name, u.email 
                          FROM service_requests sr 
                          JOIN users u ON sr.user_id = u.id 
                          ORDER BY sr.created_at DESC 
                          LIMIT 5";
$recent_requests = mysqli_query($conn, $recent_requests_query);

// Recent resources with correct column names
$recent_resources_query = "SELECT * FROM resources ORDER BY created_at DESC LIMIT 5";
$recent_resources = mysqli_query($conn, $recent_resources_query);

// Render header
renderAdminHeader("Dashboard");
?>

<div class="dashboard-header">
    <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-tractor"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_resources; ?></h3>
            <p>Total Resources</p>
            <small><?php echo $available_resources; ?> Available • <?php echo $in_use_resources; ?> In Use</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_clients; ?></h3>
            <p>Total Clients</p>
            <small>Active registered users</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_requests; ?></h3>
            <p>Service Requests</p>
            <small><?php echo $pending_requests; ?> Pending • <?php echo $completed_requests; ?> Completed</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-info">
            <h3><?php echo $pending_payments; ?></h3>
            <p>Pending Payments</p>
            <small><?php echo $paid_payments; ?> Paid</small>
        </div>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="quick-actions">
    <div class="quick-actions-header">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        <p>Manage your agricultural resources efficiently</p>
    </div>
    <div class="actions-grid">
        <a href="add_resource.php" class="action-item">
            <div class="action-icon"><i class="fas fa-plus"></i></div>
            <h4>Add Resource</h4>
            <p>Add new machinery or equipment</p>
        </a>
        <a href="service_requests.php" class="action-item">
            <div class="action-icon"><i class="fas fa-clipboard-list"></i></div>
            <h4>View Requests</h4>
            <p>Manage service requests</p>
        </a>
        <a href="logistics.php" class="action-item">
            <div class="action-icon"><i class="fas fa-truck"></i></div>
            <h4>Logistics</h4>
            <p>Schedule deliveries</p>
        </a>
        <a href="billing.php" class="action-item">
            <div class="action-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4>Billing</h4>
            <p>Manage invoices & payments</p>
        </a>
        <a href="clients.php" class="action-item">
            <div class="action-icon"><i class="fas fa-users"></i></div>
            <h4>Clients</h4>
            <p>View all registered clients</p>
        </a>
        <a href="maintenance.php" class="action-item">
            <div class="action-icon"><i class="fas fa-tools"></i></div>
            <h4>Maintenance</h4>
            <p>Track resource maintenance</p>
        </a>
    </div>
</div>

<!-- Two Column Layout for Recent Data -->
<div class="two-columns">
    <!-- Recent Service Requests -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Recent Service Requests</h3>
            <a href="service_requests.php">View All →</a>
        </div>
        <div class="table-responsive">
            <?php if ($recent_requests && mysqli_num_rows($recent_requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Resource</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($recent_requests)): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                            <br><small style="color:#888;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php 
                            $resource_query = mysqli_query($conn, "SELECT name FROM resources WHERE id = " . $row['resource_id']);
                            $resource = mysqli_fetch_assoc($resource_query);
                            echo htmlspecialchars($resource['name'] ?? 'N/A'); 
                        ?></td>
                        <td>
                            <?php
                            $status_class = 'status-pending';
                            switch($row['request_status']) {
                                case 'Pending': $status_class = 'status-pending'; break;
                                case 'Approved': $status_class = 'status-approved'; break;
                                case 'Processing': $status_class = 'status-processing'; break;
                                case 'Delivered': $status_class = 'status-available'; break;
                                case 'Returned': $status_class = 'status-returned'; break;
                                case 'Cancelled': $status_class = 'status-cancelled'; break;
                                default: $status_class = 'status-pending';
                            }
                            ?>
                            <span class="status <?php echo $status_class; ?>"><?php echo $row['request_status']; ?></span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="tooltip">
                                <a href="service_requests.php?id=<?php echo $row['id']; ?>" class="btn-view">View Details</a>
                                <span class="tooltip-text">View full request</span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-inbox" style="font-size: 2rem; color: #ccc;"></i>
                <p style="margin-top: 0.5rem; color: #888;">No requests found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Resources -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Recently Added Resources</h3>
            <a href="resources.php">View All →</a>
        </div>
        <div class="table-responsive">
            <?php if ($recent_resources && mysqli_num_rows($recent_resources) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($recent_resources)): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                            <br><small style="color:#888;">Model: <?php echo htmlspecialchars($row['model']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td>
                            <?php
                            $status_class = 'status-available';
                            switch($row['status']) {
                                case 'Available': $status_class = 'status-available'; break;
                                case 'Rented': $status_class = 'status-rented'; break;
                                case 'Under Maintenance': $status_class = 'status-under_maintenance'; break;
                                default: $status_class = 'status-available';
                            }
                            ?>
                            <span class="status <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="tooltip">
                                <a href="resources.php?edit=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                <span class="tooltip-text">Edit resource details</span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-tractor" style="font-size: 2rem; color: #ccc;"></i>
                <p style="margin-top: 0.5rem; color: #888;">No resources found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Render footer
renderAdminFooter();
?>