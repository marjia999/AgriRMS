<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_payment'])) {
    $payment_id = $_POST['payment_id'];
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $paid_amount = mysqli_real_escape_string($conn, $_POST['paid_amount']);
    
    // Get payment details
    $payment_query = mysqli_query($conn, "SELECT * FROM payments WHERE id = $payment_id AND user_id = $user_id");
    $payment = mysqli_fetch_assoc($payment_query);
    
    if ($payment) {
        $new_paid_amount = $payment['paid_amount'] + $paid_amount;
        $new_due_amount = $payment['total_amount'] - $new_paid_amount;
        
        if ($new_due_amount <= 0) {
            $payment_status = 'Paid';
            $new_due_amount = 0;
        } else {
            $payment_status = 'Partial';
        }
        
        $update_query = "UPDATE payments SET 
                         paid_amount = '$new_paid_amount',
                         due_amount = '$new_due_amount',
                         payment_status = '$payment_status',
                         payment_method = '$payment_method',
                         transaction_id = '$transaction_id',
                         payment_date = NOW()
                         WHERE id = $payment_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Update service request payment status
            mysqli_query($conn, "UPDATE service_requests SET payment_status = '$payment_status' WHERE id = " . $payment['booking_id']);
            
            $success = "Payment of ৳ " . number_format($paid_amount, 2) . " has been recorded successfully!";
        } else {
            $error = "Payment failed. Please try again.";
        }
    } else {
        $error = "Invalid payment record.";
    }
}

// Get all payments for this user
$payments_query = "SELECT p.*, sr.resource_id, r.name as resource_name, r.model,
                  sr.start_date, sr.end_date, sr.rental_duration, sr.quantity
                  FROM payments p
                  LEFT JOIN service_requests sr ON p.booking_id = sr.id
                  LEFT JOIN resources r ON p.resource_id = r.id
                  WHERE p.user_id = $user_id
                  ORDER BY p.created_at DESC";
$payments = mysqli_query($conn, $payments_query);

// Get pending payments (for quick payment section)
$pending_payments_query = "SELECT p.*, sr.resource_id, r.name as resource_name, r.model,
                          sr.start_date, sr.end_date
                          FROM payments p
                          LEFT JOIN service_requests sr ON p.booking_id = sr.id
                          LEFT JOIN resources r ON p.resource_id = r.id
                          WHERE p.user_id = $user_id AND p.payment_status != 'Paid'
                          ORDER BY p.created_at DESC";
$pending_payments = mysqli_query($conn, $pending_payments_query);

// Calculate statistics
$total_paid_query = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM payments WHERE user_id = $user_id AND payment_status = 'Paid'");
$total_paid = mysqli_fetch_assoc($total_paid_query)['total'] ?? 0;

$total_due_query = mysqli_query($conn, "SELECT SUM(due_amount) as total FROM payments WHERE user_id = $user_id AND payment_status != 'Paid'");
$total_due = mysqli_fetch_assoc($total_due_query)['total'] ?? 0;

$total_transactions = mysqli_num_rows($payments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - AgriRMS</title>
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
        }

        .btn-logout {
            background: #dc3545;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
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
        }

        .page-header p {
            color: #666;
            margin-top: 0.3rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
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
        }

        .stat-info p {
            color: #666;
            font-size: 0.7rem;
        }

        /* Cards */
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

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-partial {
            background: #cce5ff;
            color: #004085;
        }

        .btn-pay {
            background: #28a745;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-block;
            cursor: pointer;
            border: none;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
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
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
        }

        .modal-content h3 {
            color: #1B4F2B;
            margin-bottom: 1rem;
        }

        .modal-content .form-group {
            margin-bottom: 1rem;
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.8rem;
        }

        .modal-content input, .modal-content select {
            width: 100%;
            padding: 0.7rem;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            padding: 0.6rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            flex: 1;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.6rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            flex: 1;
        }

        .footer {
            background: #0d2b18;
            color: white;
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
        }

        .alert {
            padding: 0.8rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                justify-content: center;
            }
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
            <a href="dashboard.php">Dashboard</a>
            <a href="resources.php">Resources</a>
            <a href="request_service.php">New Request</a>
            <a href="my_requests.php">My Requests</a>
            <a href="payments.php">Payments</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Payments</h1>
            <p>Manage your payments and transaction history</p>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h3>৳ <?php echo number_format($total_paid, 2); ?></h3>
                    <p>Total Paid</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h3>৳ <?php echo number_format($total_due, 2); ?></h3>
                    <p>Total Due</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-history"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_transactions; ?></h3>
                    <p>Transactions</p>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Payment History</h3>
            </div>
            <div class="table-responsive">
                <?php if($payments && mysqli_num_rows($payments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Resource</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td>#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?></td>
                            <td>৳ <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['paid_amount'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['due_amount'], 2); ?></td>
                            <td><?php echo $row['payment_method'] ?? '—'; ?></td>
                            <td>
                                <?php
                                $status_class = $row['payment_status'] == 'Paid' ? 'status-paid' : ($row['payment_status'] == 'Partial' ? 'status-partial' : 'status-pending');
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $row['payment_status'] ?? 'Pending'; ?></span>
                            </td>
                            <td><?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '—'; ?></td>
                            <td>
                                <?php if($row['payment_status'] != 'Paid'): ?>
                                    <button class="btn-pay" onclick="openPaymentModal(<?php echo $row['id']; ?>, <?php echo $row['due_amount']; ?>)">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </button>
                                <?php else: ?>
                                    <span style="color: #28a745;"><i class="fas fa-check"></i> Paid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>No payment records found.</p>
                    <a href="request_service.php" class="btn-view" style="background: #FF8C42; margin-top: 1rem; display: inline-block;">Make Your First Request</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Payments Section -->
        <?php if($pending_payments && mysqli_num_rows($pending_payments) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Pending Payments</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Resource</th>
                            <th>Due Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($pending_payments)): ?>
                        <tr>
                            <td>#<?php echo str_pad($row['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?></td>
                            <td>৳ <?php echo number_format($row['due_amount'], 2); ?></td>
                            <td>
                                <button class="btn-pay" onclick="openPaymentModal(<?php echo $row['id']; ?>, <?php echo $row['due_amount']; ?>)">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-credit-card"></i> Make Payment</h3>
            <form method="POST">
                <input type="hidden" name="payment_id" id="payment_id">
                <input type="hidden" name="paid_amount" id="paid_amount">
                
                <div class="form-group">
                    <label>Due Amount</label>
                    <input type="text" id="due_amount_display" readonly style="background: #f0f7f0; font-weight: bold;">
                </div>
                
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method" required>
                        <option value="">Select Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Bkash">Bkash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Rocket">Rocket</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Transaction ID (for mobile banking)</label>
                    <input type="text" name="transaction_id" placeholder="Enter transaction ID">
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="make_payment" class="btn-submit">
                        <i class="fas fa-check"></i> Confirm Payment
                    </button>
                    <button type="button" class="btn-cancel" onclick="closePaymentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System</p>
    </footer>

    <script>
        function openPaymentModal(paymentId, dueAmount) {
            document.getElementById('payment_id').value = paymentId;
            document.getElementById('paid_amount').value = dueAmount;
            document.getElementById('due_amount_display').value = '৳ ' + dueAmount.toLocaleString();
            document.getElementById('paymentModal').classList.add('active');
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                closePaymentModal();
            }
        }
    </script>
</body>
</html>