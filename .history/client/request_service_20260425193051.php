<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

// Check if resource_id is passed from resources page
$pre_selected_resource = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;

// Get all resources for dropdown (only available)
$resources_query = mysqli_query($conn, "SELECT * FROM resources WHERE status = 'Available' ORDER BY type, name");

// Get delivery options
$delivery_query = mysqli_query($conn, "SELECT * FROM delivery WHERE is_active = 1");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $resource_id = mysqli_real_escape_string($conn, $_POST['resource_id']);
    $quantity = (int)$_POST['quantity'];
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $delivery_district = mysqli_real_escape_string($conn, $_POST['delivery_district']);
    $delivery_upazila = mysqli_real_escape_string($conn, $_POST['delivery_upazila']);
    $rental_duration = mysqli_real_escape_string($conn, $_POST['rental_duration']);
    $delivery_fee = mysqli_real_escape_string($conn, $_POST['delivery_fee']);
    $location_type = mysqli_real_escape_string($conn, $_POST['location_type']);
    
    // Get resource details
    $resource_query = mysqli_query($conn, "SELECT * FROM resources WHERE id = $resource_id");
    $resource = mysqli_fetch_assoc($resource_query);
    
    // Check if requested quantity is available
    if ($quantity > $resource['quantity']) {
        $error = "Sorry, only {$resource['quantity']} unit(s) of this resource are available. Please reduce the quantity.";
    } else {
        // Calculate total based on rental duration type
        $daily_rate = $resource['daily_rate'];
        if ($rental_duration == 'Weekly') {
            $rental_cost = ($resource['weekly_rate'] ?? $daily_rate * 7) * $quantity;
        } elseif ($rental_duration == 'Monthly') {
            $rental_cost = ($resource['monthly_rate'] ?? $daily_rate * 30) * $quantity;
        } else {
            $rental_cost = $daily_rate * $quantity;
        }
        
        $total_cost = $rental_cost + $delivery_fee;
        
        $query = "INSERT INTO service_requests (user_id, resource_id, rental_duration, quantity, start_date, end_date, total_rental_cost, delivery_cost, total_cost, delivery_address, delivery_district, delivery_upazila, request_status, payment_status, created_at) 
                  VALUES ('$user_id', '$resource_id', '$rental_duration', '$quantity', '$start_date', '$end_date', '$rental_cost', '$delivery_fee', '$total_cost', '$delivery_address', '$delivery_district', '$delivery_upazila', 'Pending', 'Pending', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $request_id = mysqli_insert_id($conn);
            
            // Create payment record
            $payment_query = "INSERT INTO payments (user_id, booking_id, resource_id, payment_type, resource_cost, delivery_cost, total_amount, paid_amount, payment_status, created_at) 
                              VALUES ('$user_id', '$request_id', '$resource_id', 'Rental', '$rental_cost', '$delivery_fee', '$total_cost', '0', 'Pending', NOW())";
            mysqli_query($conn, $payment_query);
            
            $success = "Request submitted successfully! Your request ID is #$request_id. We'll contact you soon.";
        } else {
            $error = "Failed to submit request. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - AgriRMS</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .form-card {
            background: white;
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e8f0e8;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        .form-group label i {
            color: #FF8C42;
            width: 20px;
            margin-right: 5px;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e8f0e8;
            border-radius: 16px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            background: #fefefe;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255,140,66,0.1);
        }

        .form-group input:read-only {
            background: #f0f7f0;
            cursor: not-allowed;
            color: #1B4F2B;
            font-weight: 600;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            background: #f0f7f0;
            border: 1px solid #e8f0e8;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            color: #1B4F2B;
        }

        .quantity-btn:hover:not(:disabled) {
            background: #FF8C42;
            color: white;
            border-color: #FF8C42;
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
        }

        .available-stock {
            font-size: 0.7rem;
            color: #28a745;
            margin-top: 0.3rem;
            display: block;
        }

        .available-stock.warning {
            color: #dc3545;
        }

        .duration-hint {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.3rem;
            display: block;
        }

        .delivery-hint {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.3rem;
            display: block;
        }

        .price-summary {
            background: linear-gradient(135deg, #f8f9f8, #ffffff);
            border-radius: 20px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 2px solid #FF8C42;
            box-shadow: 0 4px 15px rgba(255,140,66,0.15);
        }

        .price-summary h4 {
            color: #1B4F2B;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-summary h4 i {
            color: #FF8C42;
        }

        .price-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .price-item {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .price-item .label {
            font-size: 0.7rem;
            color: #888;
            margin-bottom: 0.3rem;
        }

        .price-item .value {
            font-size: 1rem;
            font-weight: 700;
            color: #1B4F2B;
        }

        .total-amount {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            text-align: center;
            min-width: 150px;
        }

        .total-amount .label {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .total-amount .value {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .calculation-formula {
            margin-top: 0.8rem;
            padding-top: 0.8rem;
            border-top: 1px dashed #ddd;
            text-align: center;
            font-size: 0.7rem;
            color: #888;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-submit {
            flex: 1;
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.9rem;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,140,66,0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back {
            flex: 1;
            background: #1B4F2B;
            color: white;
            padding: 0.9rem;
            border-radius: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #0d3b1a;
            transform: translateY(-2px);
        }

        .alert {
            padding: 0.9rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #fee2e2;
            color: #c0392b;
            border-left: 4px solid #c0392b;
        }

        .footer {
            background: #0d2b18;
            color: white;
            padding: 2rem 5%;
            text-align: center;
            margin-top: auto;
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
            .form-card {
                padding: 1.5rem;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .button-group {
                flex-direction: column;
            }
            .price-details {
                flex-direction: column;
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
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-handshake"></i> Request Service</h1>
                <p>Fill in the details below to request agricultural resources</p>
            </div>

            <div class="form-card">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="requestForm">
                    <!-- ROW 1: Select Resource -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tractor"></i> Select Resource <span class="required">*</span></label>
                            <select name="resource_id" id="resource_id" required onchange="updateResourceDetails()">
                                <option value="">-- Select a resource --</option>
                                <?php 
                                $current_type = '';
                                $resources_query = mysqli_query($conn, "SELECT * FROM resources WHERE status = 'Available' ORDER BY type, name");
                                while($resource = mysqli_fetch_assoc($resources_query)): 
                                    if($current_type != $resource['type']):
                                        if($current_type != '') echo '</optgroup>';
                                        $current_type = $resource['type'];
                                        echo '<optgroup label="' . $resource['type'] . '">';
                                    endif;
                                ?>
                                    <option value="<?php echo $resource['id']; ?>" 
                                            data-daily="<?php echo $resource['daily_rate']; ?>"
                                            data-weekly="<?php echo $resource['weekly_rate']; ?>"
                                            data-monthly="<?php echo $resource['monthly_rate']; ?>"
                                            data-name="<?php echo htmlspecialchars($resource['name']); ?>"
                                            data-quantity="<?php echo $resource['quantity']; ?>"
                                            <?php echo ($pre_selected_resource == $resource['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($resource['name']); ?> (Model: <?php echo htmlspecialchars($resource['model']); ?>) - <?php echo $resource['quantity']; ?> available
                                    </option>
                                <?php endwhile; ?>
                                <?php if($current_type != '') echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Rental Duration <span class="required">*</span></label>
                            <select name="rental_duration" id="rental_duration" required onchange="updateEndDate()">
                                <option value="Daily">Daily (1 day)</option>
                                <option value="Weekly">Weekly (7 days)</option>
                                <option value="Monthly">Monthly (30 days)</option>
                            </select>
                            <div class="duration-hint"><i class="fas fa-info-circle"></i> End date will be auto-calculated based on start date</div>
                        </div>
                    </div>

                    <!-- ROW 2: Quantity + Delivery Location -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-boxes"></i> Quantity <span class="required">*</span></label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" id="decrementBtn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" name="quantity" id="quantity" class="quantity-input" value="1" min="1" required onchange="validateQuantity()">
                                <button type="button" class="quantity-btn" id="incrementBtn" onclick="changeQuantity(1)">+</button>
                            </div>
                            <span class="available-stock" id="stockInfo"></span>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Delivery Location <span class="required">*</span></label>
                            <select name="location_type" id="location_type" required onchange="updateDeliveryFee()">
                                <option value="">-- Select location --</option>
                                <option value="Inside Dhaka">Inside Dhaka (Delivery Fee: ৳1,000)</option>
                                <option value="Outside Dhaka">Outside Dhaka (Delivery Fee: ৳2,500)</option>
                            </select>
                            <input type="hidden" name="delivery_fee" id="delivery_fee" value="0">
                            <div class="delivery-hint"><i class="fas fa-truck"></i> Delivery fee varies based on your location</div>
                        </div>
                    </div>

                    <!-- ROW 3: Upazila + Full Address -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-location-dot"></i> Upazila/Thana</label>
                            <input type="text" name="delivery_upazila" id="delivery_upazila" placeholder="e.g., Gulshan, Motijheel" oninput="calculateTotal()">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-address-card"></i> Full Delivery Address <span class="required">*</span></label>
                            <textarea name="delivery_address" id="delivery_address" rows="2" required placeholder="House no, Road, Village, etc." oninput="calculateTotal()"></textarea>
                        </div>
                    </div>

                    <!-- ROW 4: Start Date + End Date (End Date is read-only) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Start Date <span class="required">*</span></label>
                            <input type="date" name="start_date" id="start_date" required onchange="updateEndDate()" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> End Date <span class="required">*</span></label>
                            <input type="date" name="end_date" id="end_date" required readonly style="background: #f0f7f0; cursor: pointer;">
                        </div>
                    </div>

                    <!-- Price Summary Section -->
                    <div class="price-summary" id="priceSummary" style="display: none;">
                        <h4><i class="fas fa-calculator"></i> Price Calculation</h4>
                        <div class="price-details">
                            <div class="price-item">
                                <div class="label">Rental Cost</div>
                                <div class="value" id="rentalCost">—</div>
                            </div>
                            <div class="price-item">
                                <div class="label">Delivery Fee</div>
                                <div class="value" id="deliveryFee">—</div>
                            </div>
                            <div class="price-item">
                                <div class="label">Quantity</div>
                                <div class="value" id="displayQuantity">0</div>
                            </div>
                            <div class="price-item">
                                <div class="label">Rental Period</div>
                                <div class="value" id="periodDisplay">—</div>
                            </div>
                            <div class="total-amount">
                                <div class="label">Total Amount</div>
                                <div class="value" id="totalAmount">৳ 0</div>
                            </div>
                        </div>
                        <div class="calculation-formula" id="calculationFormula">
                            <i class="fas fa-chart-line"></i> Select resource and start date to see calculation
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-submit" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                        <a href="dashboard.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System</p>
    </footer>

    <script>
        let selectedResourceDaily = 0;
        let selectedResourceWeekly = 0;
        let selectedResourceMonthly = 0;
        let selectedResourceName = '';
        let selectedResourceQuantity = 0;
        let selectedDeliveryFee = 0;
        
        function changeQuantity(delta) {
            const quantityInput = document.getElementById('quantity');
            let newValue = parseInt(quantityInput.value) + delta;
            if (newValue < 1) newValue = 1;
            if (newValue > selectedResourceQuantity) newValue = selectedResourceQuantity;
            quantityInput.value = newValue;
            validateQuantity();
            calculateTotal();
        }
        
        function validateQuantity() {
            const quantityInput = document.getElementById('quantity');
            let quantity = parseInt(quantityInput.value);
            const stockInfo = document.getElementById('stockInfo');
            const incrementBtn = document.getElementById('incrementBtn');
            const decrementBtn = document.getElementById('decrementBtn');
            
            if (quantity > selectedResourceQuantity) {
                quantityInput.value = selectedResourceQuantity;
                quantity = selectedResourceQuantity;
                stockInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Only ' + selectedResourceQuantity + ' unit(s) available!';
                stockInfo.className = 'available-stock warning';
            } else if (quantity < 1) {
                quantityInput.value = 1;
                quantity = 1;
                stockInfo.innerHTML = '<i class="fas fa-check-circle"></i> ' + selectedResourceQuantity + ' unit(s) available';
                stockInfo.className = 'available-stock';
            } else {
                stockInfo.innerHTML = '<i class="fas fa-check-circle"></i> ' + selectedResourceQuantity + ' unit(s) available';
                stockInfo.className = 'available-stock';
            }
            
            if (quantity >= selectedResourceQuantity) {
                incrementBtn.disabled = true;
            } else {
                incrementBtn.disabled = false;
            }
            
            if (quantity <= 1) {
                decrementBtn.disabled = true;
            } else {
                decrementBtn.disabled = false;
            }
        }
        
        function updateDeliveryFee() {
            const locationType = document.getElementById('location_type').value;
            const deliveryFeeInput = document.getElementById('delivery_fee');
            const deliveryFeeDisplay = document.getElementById('deliveryFee');
            
            if (locationType === 'Inside Dhaka') {
                selectedDeliveryFee = 1000;
                deliveryFeeInput.value = 1000;
                deliveryFeeDisplay.innerHTML = '৳ 1,000';
            } else if (locationType === 'Outside Dhaka') {
                selectedDeliveryFee = 2500;
                deliveryFeeInput.value = 2500;
                deliveryFeeDisplay.innerHTML = '৳ 2,500';
            } else {
                selectedDeliveryFee = 0;
                deliveryFeeInput.value = 0;
                deliveryFeeDisplay.innerHTML = '—';
            }
            calculateTotal();
        }
        
        function updateEndDate() {
            const startDate = document.getElementById('start_date').value;
            const rentalDuration = document.getElementById('rental_duration').value;
            const endDateInput = document.getElementById('end_date');
            
            if (!startDate) {
                endDateInput.value = '';
                calculateTotal();
                return;
            }
            
            const start = new Date(startDate);
            let daysToAdd = 0;
            
            if (rentalDuration === 'Daily') {
                daysToAdd = 0;
            } else if (rentalDuration === 'Weekly') {
                daysToAdd = 6;
            } else if (rentalDuration === 'Monthly') {
                daysToAdd = 29;
            }
            
            const end = new Date(start);
            end.setDate(start.getDate() + daysToAdd);
            
            const year = end.getFullYear();
            const month = String(end.getMonth() + 1).padStart(2, '0');
            const day = String(end.getDate()).padStart(2, '0');
            
            endDateInput.value = year + '-' + month + '-' + day;
            calculateTotal();
        }
        
        function updateResourceDetails() {
            const resourceSelect = document.getElementById('resource_id');
            const selectedOption = resourceSelect.options[resourceSelect.selectedIndex];
            
            if (resourceSelect.value && selectedOption) {
                selectedResourceDaily = parseFloat(selectedOption.dataset.daily) || 0;
                selectedResourceWeekly = parseFloat(selectedOption.dataset.weekly) || selectedResourceDaily * 7;
                selectedResourceMonthly = parseFloat(selectedOption.dataset.monthly) || selectedResourceDaily * 30;
                selectedResourceName = selectedOption.dataset.name || '';
                selectedResourceQuantity = parseInt(selectedOption.dataset.quantity) || 0;
                
                const quantityInput = document.getElementById('quantity');
                quantityInput.max = selectedResourceQuantity;
                quantityInput.value = 1;
                validateQuantity();
            } else {
                selectedResourceDaily = 0;
                selectedResourceWeekly = 0;
                selectedResourceMonthly = 0;
                selectedResourceName = '';
                selectedResourceQuantity = 0;
                document.getElementById('stockInfo').innerHTML = '';
            }
            updateEndDate();
        }
        
        function calculateTotal() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const deliveryAddress = document.getElementById('delivery_address').value;
            const locationType = document.getElementById('location_type').value;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const rentalDuration = document.getElementById('rental_duration').value;
            const priceSummary = document.getElementById('priceSummary');
            const submitBtn = document.getElementById('submitBtn');
            
            document.getElementById('displayQuantity').innerHTML = quantity;
            
            let rentalCost = 0;
            let periodText = '';
            
            if (rentalDuration === 'Daily') {
                rentalCost = selectedResourceDaily * quantity;
                periodText = '1 Day';
            } else if (rentalDuration === 'Weekly') {
                rentalCost = selectedResourceWeekly * quantity;
                periodText = '7 Days (1 Week)';
            } else {
                rentalCost = selectedResourceMonthly * quantity;
                periodText = '30 Days (1 Month)';
            }
            
            document.getElementById('rentalCost').innerHTML = '৳ ' + rentalCost.toLocaleString();
            document.getElementById('periodDisplay').innerHTML = periodText;
            
            let total = rentalCost + selectedDeliveryFee;
            
            if (startDate && endDate && selectedResourceDaily > 0 && quantity <= selectedResourceQuantity && locationType) {
                document.getElementById('totalAmount').innerHTML = '৳ ' + total.toLocaleString();
                document.getElementById('calculationFormula').innerHTML = '<i class="fas fa-chart-line"></i> Rental (৳ ' + rentalCost.toLocaleString() + ') + Delivery (৳ ' + selectedDeliveryFee.toLocaleString() + ') = ৳ ' + total.toLocaleString();
                
                if (deliveryAddress.trim() !== '' && locationType) {
                    priceSummary.style.display = 'block';
                    submitBtn.disabled = false;
                } else {
                    priceSummary.style.display = 'block';
                    submitBtn.disabled = true;
                }
                return;
            }
            
            if (!startDate || !endDate || selectedResourceDaily === 0 || !locationType) {
                priceSummary.style.display = 'none';
                submitBtn.disabled = true;
            } else if (quantity > selectedResourceQuantity) {
                priceSummary.style.display = 'block';
                submitBtn.disabled = true;
            }
        }
        
        // Set min dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;
        
        document.getElementById('start_date').addEventListener('change', function() {
            updateEndDate();
        });
        
        // Add event listeners
        document.getElementById('resource_id').addEventListener('change', updateResourceDetails);
        document.getElementById('rental_duration').addEventListener('change', updateEndDate);
        document.getElementById('location_type').addEventListener('change', updateDeliveryFee);
        document.getElementById('delivery_address').addEventListener('input', calculateTotal);
        document.getElementById('quantity').addEventListener('change', function() {
            validateQuantity();
            calculateTotal();
        });
        
        // Initialize
        updateResourceDetails();
        
        <?php if($pre_selected_resource > 0): ?>
        setTimeout(function() { updateResourceDetails(); }, 100);
        <?php endif; ?>
    </script>
</body>
</html>