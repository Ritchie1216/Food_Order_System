<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../classes/Order.php');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$orderModel = new Order($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Process payment if submitted
if (isset($_POST['process_payment'])) {
    $order_ids = explode(',', $_POST['order_ids']);
    $total_amount = $_POST['amount'];  // This is the total amount for all orders combined
    $cash_received = $_POST['cash_received'];
    $change = $cash_received - $total_amount;
  
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Process each order
        foreach ($order_ids as $order_id) {
            // Get individual order amount
            $order_amount_sql = "SELECT total_amount FROM orders WHERE table_id = ?";
            $order_amount_stmt = $db->prepare($order_amount_sql);
            $order_amount_stmt->execute([$order_id]);
            $order_amount = $order_amount_stmt->fetchColumn();
            
            // Insert into payments table with individual order amount
            $payment_sql = "INSERT INTO payments (order_id, amount, payment_status, payment_date, cash_received, change_amount) 
                           VALUES (?, ?, 'completed', CURRENT_TIMESTAMP, ?, ?)";
            $payment_stmt = $db->prepare($payment_sql);
            $payment_success = $payment_stmt->execute([$order_id, $order_amount, $cash_received, $change]);
            
            // Get payment details for receipt
            $payment_id = $db->lastInsertId();
            
            // Update order status
            $update_sql = "UPDATE orders SET status = 'completed' WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_success = $update_stmt->execute([$order_id]);
            
            if (!$payment_success || !$update_success) {
                throw new Exception("Failed to process payment for order #" . $order_id);
            }
            
            // Get table ID for this order
            $table_query = "SELECT table_id FROM orders WHERE id = ?";
            $table_stmt = $db->prepare($table_query);
            $table_stmt->execute([$order_id]);
            $table_id = $table_stmt->fetchColumn();
            
            // Delete QR code if table_id exists
            if ($table_id) {
                try {
                    // First get the QR code image path before deleting the record
                    $qr_path_query = "SELECT image_path FROM qr_codes WHERE table_id = ?";
                    $qr_path_stmt = $db->prepare($qr_path_query);
                    $qr_path_stmt->execute([$table_id]);
                    $image_path = $qr_path_stmt->fetchColumn();
                    
                    // Delete from qr_codes table
                    $delete_token_sql = "DELETE FROM qr_codes WHERE table_id = ?";
                    $delete_token_stmt = $db->prepare($delete_token_sql);
                    $delete_token_stmt->execute([$table_id]);
                    
                    // Delete the physical QR code image file
                    if ($image_path) {
                        // Try multiple paths to ensure file deletion
                        $possible_paths = [
                            __DIR__ . '/../' . $image_path,
                            $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($image_path, '/'),
                            __DIR__ . '/../uploads/qrcodes/' . basename($image_path),
                            $_SERVER['DOCUMENT_ROOT'] . '/uploads/qrcodes/' . basename($image_path)
                        ];
                        
                        // Try to delete using different path variations
                        $deleted = false;
                        foreach ($possible_paths as $path) {
                            if (file_exists($path)) {
                                unlink($path);
                                error_log("QR code image deleted: " . $path);
                                $deleted = true;
                                break;
                            }
                        }
                        
                        // Final attempt with just the filename
                        if (!$deleted) {
                            $filename = basename($image_path);
                            $qr_directory = __DIR__ . '/../uploads/qrcodes/';
                            if (file_exists($qr_directory . $filename)) {
                                unlink($qr_directory . $filename);
                                error_log("QR code image deleted using filename only: " . $qr_directory . $filename);
                            }
                        }
                    }
                } catch (Exception $tableEx) {
                    error_log("Warning: Could not delete QR code: " . $tableEx->getMessage());
                }
            }
        }
        
        $db->commit();
        
        // Get combined order details for receipt
        $receipt_sql = "SELECT o.*, t.table_number,
                      GROUP_CONCAT(CONCAT(m.name, ':', oi.quantity, ':', m.price) SEPARATOR '||') as item_details
                      FROM orders o 
                      LEFT JOIN tables t ON o.table_id = t.id
                      LEFT JOIN order_items oi ON o.id = oi.order_id
                      LEFT JOIN menu_items m ON oi.menu_item_id = m.id
                      WHERE o.id IN (" . implode(',', array_fill(0, count($order_ids), '?')) . ")
                      GROUP BY o.id";
        $receipt_stmt = $db->prepare($receipt_sql);
        $receipt_stmt->execute($order_ids);
        $orders_details = $receipt_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process items and calculate totals correctly
        $items_array = [];
        $subtotal = 0;
        
        foreach ($orders_details as $order_detail) {
            $item_details = explode('||', $order_detail['item_details']);
            foreach ($item_details as $item) {
                if (empty(trim($item))) continue;
                list($name, $quantity, $price) = explode(':', $item);
                $item_total = $quantity * $price;
                $subtotal += $item_total;
                
                $items_array[] = [
                    'name' => $name,
                    'quantity' => (int)$quantity,
                    'price' => (float)$price,
                    'total' => $item_total
                ];
            }
        }
        
        // Calculate SST (6%)
        $sst_amount = $subtotal * 0.06;
        $total_with_sst = $subtotal + $sst_amount;
        
        // Store receipt data in session for printing
        $_SESSION['receipt_data'] = [
            'payment_id' => $payment_id,
            'order_ids' => $order_ids,
            'table_number' => $orders_details[0]['table_number'],
            'items' => $items_array,
            'subtotal' => $subtotal,
            'sst_amount' => $sst_amount,
            'total_amount' => $total_with_sst,
            'cash_received' => $cash_received,
            'change_amount' => $change,
            'payment_date' => date('Y-m-d H:i:s')
        ];
        
        $success_message = "Payment processed successfully.";
        echo "<script>
                window.location.href = 'print_receipt.php?payment_id=" . $payment_id . "';
              </script>";
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Error processing payment: " . $e->getMessage();
        error_log($error_message);
    }
}

// Get table filter from URL
$table_filter = isset($_GET['table']) ? $_GET['table'] : null;

try {
    // Base query: Fetch only completed orders that need payment processing
    $query = "SELECT o.*, 
              t.table_number,
              (SELECT CONCAT('[', GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', oi.id,
                        'name', m.name,
                        'price', m.price,
                        'quantity', oi.quantity,
                        'instructions', oi.special_instructions
                    )
                ), ']')
              FROM order_items oi 
              JOIN menu_items m ON oi.menu_item_id = m.id 
              WHERE oi.order_id = o.id) as items
              FROM orders o
              JOIN tables t ON o.table_id = t.id
              LEFT JOIN payments p ON o.id = p.order_id
              WHERE o.status = 'completed' 
              AND (p.payment_status IS NULL OR p.payment_status != 'completed')";

    // Add table filter if specified
    $params = [];
    if ($table_filter) {
        $query .= " AND t.table_number = ?";
        $params[] = $table_filter;
    }

    // Add sorting to ensure consistent order
    $query .= " ORDER BY t.table_number ASC, o.created_at ASC"; 

    // Debugging: Output query and parameters
    // var_dump($query, $params);

    // Execute query
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $payment_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // // Debugging: Check if orders are fetched correctly
    // var_dump($payment_orders);

    // Group orders by table
    $tables_with_orders = [];
    foreach ($payment_orders as $order) {
        $table_number = $order['table_number'];
        if (!isset($tables_with_orders[$table_number])) {
            $tables_with_orders[$table_number] = [];
        }
        $tables_with_orders[$table_number][] = $order;
    }
} catch (Exception $e) {
    // Log error and display it for debugging
    error_log("Error in payment_counter.php: " . $e->getMessage());
    echo "Error: " . $e->getMessage(); // Debugging only, remove in production
}
// Get all available table numbers for the dropdown
try {
    $tables_sql = "SELECT DISTINCT t.table_number 
                   FROM tables t 
                   INNER JOIN orders o ON t.id = o.table_id 
                   WHERE o.status = 'completed' 
                   AND t.status = 'active'
                   AND NOT EXISTS (
                       SELECT 1 FROM payments p 
                       WHERE p.order_id = o.id
                   )
                   ORDER BY t.table_number";
    $tables_stmt = $db->prepare($tables_sql);
    $tables_stmt->execute();
    $available_tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $available_tables = [];
}

$page_title = "Payment Counter";




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Payment Counter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C3E50;
            --primary-dark: #1A252F;
            --primary-light: #34495E;
            --success: #10B981;
            --warning: #95A5A6;
            --danger: #FF5252;
            --info: #10B981;
            --surface: #FFFFFF;
            --surface-hover: #F8F9FA;
            --on-surface: #2C3E50;
            --on-surface-medium: #5D6D7E;
            --on-surface-light: #95A5A6;
            --background: #F5F6F7;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            color: var(--on-surface);
        }

        .payment-counter {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .restaurant-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.15);
            position: relative;
            overflow: hidden;
        }

        .restaurant-header::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: url("data:image/svg+xml,%3Csvg width='300' height='300' viewBox='0 0 300 300' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='150' cy='150' r='150' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E") no-repeat;
            transform: translate(50%, -50%);
            z-index: 1;
        }

        .restaurant-header h1 {
            position: relative;
            z-index: 2;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-section {
            position: relative;
            z-index: 2;
            margin-top: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(5px);
        }

        .search-form {
            display: flex;
            gap: 1rem;
        }

        .input-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .table-select {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 1rem;
            min-width: 200px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--surface);
            transition: all 0.3s ease;
        }

        .table-select:focus {
            border-color: rgba(255, 255, 255, 0.4);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }

        .table-select option {
            background: var(--primary);
            color: var(--surface);
        }

        .search-btn, .clear-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .search-btn {
            background: var(--success);
            color: var(--surface);
        }

        .search-btn:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .clear-btn {
            background: var(--danger);
            color: var(--surface);
        }

        .clear-btn:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .table-card {
            background: var(--surface);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.08);
            border: 1px solid rgba(44, 62, 80, 0.05);
            transition: all 0.3s ease;
        }

        .table-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--surface);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-number {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-status {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(5px);
        }

        .order-details {
            padding: 1.5rem;
        }

        .order-time {
            background: var(--surface-hover);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .order-time > div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--on-surface-medium);
        }

        .order-time i {
            color: var(--primary);
        }

        .items-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .item-card {
            background: var(--surface-hover);
            padding: 1rem;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
            transition: all 0.3s ease;
        }

        .item-card:hover {
            transform: translateX(4px);
            background: var(--surface);
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.05);
        }

        .item-card.header {
            background: var(--primary);
            color: var(--surface);
            font-weight: bold;
        }

        .item-name {
            font-weight: 600;
            color: var(--on-surface);
        }

        .item-quantity {
            text-align: center;
            color: var(--on-surface-medium);
        }

        .item-price, .item-total {
            text-align: right;
            color: var(--on-surface);
            font-weight: 500;
        }

        .payment-section {
            background: var(--surface-hover);
            padding: 1.5rem;
            border-top: 1px solid rgba(44, 62, 80, 0.05);
        }

        .amount-breakdown {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.05);
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            font-size: 1.1rem;
            color: var(--on-surface-medium);
        }

        .amount-row:not(:last-child) {
            border-bottom: 1px solid rgba(44, 62, 80, 0.05);
        }

        .total-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(44, 62, 80, 0.1);
        }

        .payment-form {
            display: grid;
            gap: 1rem;
        }

        .cash-input {
            font-size: 1.5rem;
            padding: 1rem;
            border: 2px solid rgba(44, 62, 80, 0.1);
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            background: var(--surface);
        }

        .cash-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.15);
        }

        .change-display {
            background: var(--success);
            color: var(--surface);
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .process-btn {
            background: var(--primary);
            color: var(--surface);
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .process-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2);
        }

        .process-btn:disabled {
            background: var(--on-surface-light);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
        }

        .table-summary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.15);
        }

        .table-count {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.1);
            color: var(--surface);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-card {
            animation: fadeIn 0.3s ease forwards;
        }

        .table-card:nth-child(2) { animation-delay: 0.1s; }
        .table-card:nth-child(3) { animation-delay: 0.2s; }
        .table-card:nth-child(4) { animation-delay: 0.3s; }
        .table-card:nth-child(5) { animation-delay: 0.4s; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .payment-counter {
                margin: 1rem;
            }

            .restaurant-header {
                padding: 1.5rem;
            }

            .search-form {
                flex-direction: column;
            }

            .input-group {
                flex-direction: column;
            }

            .table-select {
                width: 100%;
            }

            .search-btn, .clear-btn {
                width: 100%;
                justify-content: center;
            }

            .tables-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .item-card {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .item-quantity, .item-price, .item-total {
                text-align: left;
            }

            .table-summary {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .btn-light {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="payment-counter">
        <div class="restaurant-header">
            <h1><i class="fas fa-cash-register"></i> Restaurant Payment Counter</h1>
            
            <!-- Add search form -->
            <div class="search-section">
                <div class="search-form">
                    <div class="input-group">
                        <select id="tableSelect" class="form-select table-select">
                            <option value="">All Tables</option>
                            <?php foreach ($available_tables as $table_num): ?>
                            <option value="<?php echo $table_num; ?>" <?php echo $table_filter == $table_num ? 'selected' : ''; ?>>
                                Table <?php echo $table_num; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="searchBtn" class="btn search-btn">
                            <i class="fas fa-search"></i> Find Table
                        </button>
                        <button type="button" id="clearBtn" class="btn clear-btn" style="display: <?php echo $table_filter ? 'inline-flex' : 'none'; ?>">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($tables_with_orders)): ?>
        <div class="alert alert-info">
            <?php if ($table_filter): ?>
                <i class="fas fa-info-circle"></i> Table <?php echo $table_filter; ?> has no pending payments.
            <?php else: ?>
                <i class="fas fa-info-circle"></i> No tables waiting for payment.
            <?php endif; ?>
        </div>
        <?php else: ?>
            <!-- Display table count summary -->
            <div class="table-summary">
                <div class="table-count">
                    <i class="fas fa-table"></i> Tables Waiting for Payment: <?php echo count($tables_with_orders); ?>
                </div>
                <?php if ($table_filter): ?>
                <a href="payment_counter.php" class="btn btn-light">
                    <i class="fas fa-list"></i> Show All Tables
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Display all tables in a grid -->
            <div class="tables-grid">
                <?php foreach ($tables_with_orders as $table_number => $table_orders): ?>
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-number">
                            <i class="fas fa-table"></i> Table <?php echo htmlspecialchars($table_number); ?>
                        </div>
                        <div class="table-status">
                            <i class="fas fa-clock"></i> Waiting for Payment
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="order-time">
                            <div><i class="fas fa-hourglass-start"></i> First Order: <?php echo date('h:i A', strtotime($table_orders[0]['created_at'])); ?></div>
                            <div><i class="fas fa-receipt"></i> Orders: <?php echo count($table_orders); ?></div>
                        </div>

                        <div class="items-grid">
                            <div class="item-card header">
                                <div class="item-name">Item</div>
                                <div class="item-quantity">Qty</div>
                                <div class="item-price">Price</div>
                                <div class="item-total">Total</div>
                            </div>
                            <?php 
                            $subtotal = 0;
                            foreach ($table_orders as $order):
                                $items = json_decode($order['items'], true);
                                foreach ($items as $item): 
                                    $item_total = $item['quantity'] * $item['price'];
                                    $subtotal += $item_total;
                            ?>
                            <div class="item-card">
                                <div class="item-name">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <?php if (!empty($item['instructions'])): ?>
                                        <br><small class="text-muted">Note: <?php echo htmlspecialchars($item['instructions']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="item-quantity"><?php echo $item['quantity']; ?></div>
                                <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                <div class="item-total">RM <?php echo number_format($item_total, 2); ?></div>
                            </div>
                            <?php 
                                endforeach;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <div class="payment-section">
                        <?php
                        // Calculate SST and total
                        $sst_amount = $subtotal * 0.06;  // Calculate 6% SST
                        $total_with_sst = $subtotal + $sst_amount; // Final total with SST
                        ?>
                        <div class="amount-breakdown">
                            <div class="amount-row">
                                <span>Subtotal:</span>
                                <span>RM <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="amount-row">
                                <span>SST (6%):</span>
                                <span>RM <?php echo number_format($sst_amount, 2); ?></span>
                            </div>
                            <div class="total-amount">
                                Total: RM <?php echo number_format($total_with_sst, 2); ?>
                            </div>
                        </div>

                        <form method="POST" onsubmit="return validatePayment(this)" class="payment-form">
                            <input type="hidden" name="order_ids" value="<?php 
                                // Get all order IDs for this table
                                $table_order_ids = array_column($table_orders, 'id');
                                echo implode(',', $table_order_ids); 
                            ?>">
                            <input type="hidden" name="amount" value="<?php echo $total_with_sst; ?>">
                            
                            <div class="form-group">
                                <input type="number" 
                                       name="cash_received" 
                                       class="cash-input" 
                                       step="0.01" 
                                       min="<?php echo $total_with_sst; ?>"
                                       placeholder="Enter cash amount"
                                       onkeyup="calculateChange(this, <?php echo $total_with_sst; ?>)"
                                       required>
                            </div>
                            
                            <div class="change-display" style="display: none;">
                                Change: RM <span class="change-amount">0.00</span>
                            </div>

                            <button type="submit" name="process_payment" class="process-btn" disabled>
                                <i class="fas fa-check-circle"></i> Process Payment & Print Receipt
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Add this to your existing script
    document.addEventListener('DOMContentLoaded', function() {
        const tableSelect = document.getElementById('tableSelect');
        const searchBtn = document.getElementById('searchBtn');
        const clearBtn = document.getElementById('clearBtn');
        
        // Search button click handler
        searchBtn.addEventListener('click', function() {
            const selectedTable = tableSelect.value;
            if (selectedTable) {
                window.location.href = 'payment_counter.php?table=' + selectedTable;
            } else {
                window.location.href = 'payment_counter.php';
            }
        });
        
        // Clear button click handler
        clearBtn.addEventListener('click', function() {
            window.location.href = 'payment_counter.php';
        });
        
        // Also allow searching by pressing Enter on the select
        tableSelect.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchBtn.click();
            }
        });
        
        // Show/hide table cards based on selection without page reload
        tableSelect.addEventListener('change', function() {
            const selectedTable = this.value;
            const tableCards = document.querySelectorAll('.table-card');
            
            if (selectedTable === '') {
                // Show all tables
                tableCards.forEach(card => {
                    card.style.display = 'flex';
                });
                clearBtn.style.display = 'none';
            } else {
                // Show only the selected table
                tableCards.forEach(card => {
                    const tableNumber = card.querySelector('.table-number').textContent.trim().replace('Table ', '');
                    if (tableNumber === selectedTable) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
                clearBtn.style.display = 'inline-flex';
            }
        });
    });

    function calculateChange(input, totalAmount) {
        const form = input.closest('form');
        const cashReceived = parseFloat(input.value) || 0;
        const changeDisplay = form.querySelector('.change-display');
        const changeAmount = changeDisplay.querySelector('.change-amount');
        const submitBtn = form.querySelector('.process-btn');
        
        if (cashReceived >= totalAmount) {
            const change = (cashReceived - totalAmount).toFixed(2);
            changeAmount.textContent = change;
            changeDisplay.style.display = 'block';
            submitBtn.disabled = false;
        } else {
            changeDisplay.style.display = 'none';
            submitBtn.disabled = true;
        }
    }

    function validatePayment(form) {
        const cashReceived = parseFloat(form.cash_received.value);
        const totalAmount = parseFloat(form.amount.value);
        return cashReceived >= totalAmount;
    }

    // Auto refresh every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);
    </script>
</body>
</html> 