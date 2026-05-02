<?php
/**
 * Calculate rental cost for agricultural equipment
 * 
 * @param float $daily_rate Daily rental rate in BDT
 * @param int $days Number of days rented
 * @param string $duration_type 'Daily', 'Weekly', 'Monthly'
 * @param int $quantity Number of units
 * @return float Total cost
 */
function calculateRentalCost($daily_rate, $days, $duration_type = 'Daily', $quantity = 1) {
    $base_cost = 0;
    
    if ($duration_type == 'Daily') {
        $base_cost = $daily_rate * $days * $quantity;
    } elseif ($duration_type == 'Weekly') {
        $weeks = ceil($days / 7);
        $weekly_rate = $daily_rate * 7;
        $base_cost = $weekly_rate * $weeks * $quantity;
    } elseif ($duration_type == 'Monthly') {
        $months = ceil($days / 30);
        $monthly_rate = $daily_rate * 30;
        $base_cost = $monthly_rate * $months * $quantity;
    }
    
    return round($base_cost, 2);
}

/**
 * Calculate total bill with all charges
 * 
 * @param float $daily_rate Daily rate in BDT
 * @param int $days Number of days
 * @param int $quantity Number of units
 * @param string $duration_type Rental duration type (Daily/Weekly/Monthly)
 * @param string $delivery_division Delivery division (Dhaka or other)
 * @param bool $include_operator Whether operator is included
 * @param int $late_days Late return days (if any)
 * @return array Breakdown of charges
 */
function calculateTotalBill($daily_rate, $days, $quantity = 1, $duration_type = 'Daily', $delivery_division = 'Outside Dhaka', $include_operator = false, $late_days = 0) {
    // Base rental cost
    $rental_cost = calculateRentalCost($daily_rate, $days, $duration_type, $quantity);
    
    // Delivery fee (1000 for Dhaka, 2500 for others)
    $delivery_fee = ($delivery_division == 'Dhaka') ? 1000 : 2500;
    
    // Operator fee (BDT 1000 per day for operator)
    $operator_fee = $include_operator ? ($days * 1000) : 0;
    
    // Late return penalty (2x daily rate per extra day per unit)
    $late_penalty = $late_days * ($daily_rate * 2) * $quantity;
    
    // Fuel surcharge (5% of rental cost, only for fuel-operated equipment)
    // This should be conditionally applied based on fuel_type
    $fuel_surcharge = $rental_cost * 0.05;
    
    // Total
    $total = $rental_cost + $operator_fee + $delivery_fee + $late_penalty + $fuel_surcharge;
    
    return [
        'rental_cost' => $rental_cost,
        'operator_fee' => $operator_fee,
        'delivery_fee' => $delivery_fee,
        'late_penalty' => $late_penalty,
        'fuel_surcharge' => $fuel_surcharge,
        'total' => $total
    ];
}

/**
 * Calculate number of days between two dates
 * 
 * @param string $start_date Start date (Y-m-d)
 * @param string $end_date End date (Y-m-d)
 * @return int Number of days
 */
function calculateDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    return $start->diff($end)->days + 1;
}

/**
 * Get delivery fee based on division
 * 
 * @param string $division Division name
 * @return float Delivery fee
 */
function getDeliveryFee($division) {
    return ($division == 'Dhaka') ? 1000.00 : 2500.00;
}

/**
 * Get status badge class for HTML
 * 
 * @param string $status Status value
 * @return string CSS class name
 */
function getStatusClass($status) {
    switch($status) {
        case 'Pending': return 'status-pending';
        case 'Approved': return 'status-approved';
        case 'Processing': return 'status-processing';
        case 'Delivered': return 'status-delivered';
        case 'Returned': return 'status-returned';
        case 'Cancelled': return 'status-cancelled';
        default: return 'status-pending';
    }
}

/**
 * Get payment status badge class
 * 
 * @param string $status Payment status
 * @return string CSS class name
 */
function getPaymentStatusClass($status) {
    switch($status) {
        case 'Paid': return 'status-paid';
        case 'Partial': return 'status-partial';
        case 'Pending': return 'status-pending';
        case 'Failed': return 'status-failed';
        default: return 'status-pending';
    }
}

/**
 * Format currency in BDT
 * 
 * @param float $amount Amount
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '৳ ' . number_format($amount, 2);
}

/**
 * Calculate rental period text
 * 
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return string Formatted period text
 */
function formatRentalPeriod($start_date, $end_date) {
    return date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
}

// Example usage:
// Tractor daily rate = 2500 BDT, rented for 5 days, quantity 1, daily duration, delivery to Dhaka
/*
$bill = calculateTotalBill(2500, 5, 1, 'Daily', 'Dhaka', false, 0);
echo "Rental Cost: " . formatCurrency($bill['rental_cost']) . "\n";
echo "Delivery Fee: " . formatCurrency($bill['delivery_fee']) . "\n";
echo "Fuel Surcharge: " . formatCurrency($bill['fuel_surcharge']) . "\n";
echo "Total: " . formatCurrency($bill['total']) . "\n";
*/
?>