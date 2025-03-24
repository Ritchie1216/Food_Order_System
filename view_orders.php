<?php
session_start();
require_once(__DIR__ . '/config/Database.php');
require_once(__DIR__ . '/classes/Order.php');

$database = new Database();
$db = $database->getConnection();
$orderModel = new Order($db);

// Get table number and token from URL
$table_number = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : null;
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : null;

// Get current tax rate from settings
try {
    $tax_rate_query = "SELECT tax_rate FROM settings LIMIT 1";
    $tax_rate_stmt = $db->prepare($tax_rate_query);
    $tax_rate_stmt->execute();
    $settings = $tax_rate_stmt->fetch(PDO::FETCH_ASSOC);
    $tax_rate = floatval($settings['tax_rate'] ?? 9); // Use default 9% if no setting found
} catch (Exception $e) {
    error_log("Error fetching tax rate: " . $e->getMessage());
    $tax_rate = 9; // Default to 9% if there's an error
}

// Validate token and get table orders
$table_orders = [];
$error_message = '';

if ($table_number && $token) {
    try {
        // Validate token
        $validate_query = "SELECT t.id, t.table_number, qc.token 
                          FROM tables t 
                          JOIN qr_codes qc ON t.id = qc.table_id 
                          WHERE t.table_number = ? 
                          AND qc.token = ? 
                          AND qc.is_active = 1 
                          AND (qc.expires_at IS NULL OR qc.expires_at > NOW())";
        $stmt = $db->prepare($validate_query);
        $stmt->execute([$table_number, $token]);
        $table_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($table_data) {
            // Get orders for the table
            $orders_query = "SELECT o.id, o.status, o.total_amount, o.created_at 
                           FROM orders o
                           JOIN tables t ON o.table_id = t.id
                           LEFT JOIN payments p ON o.id = p.order_id
                           WHERE t.table_number = ?
                           AND o.status IN ('pending', 'processing', 'completed')
                           AND (p.payment_status IS NULL OR p.payment_status != 'completed')
                           AND DATE(o.created_at) = CURDATE()
                           ORDER BY o.created_at DESC";
            
            $orders_stmt = $db->prepare($orders_query);
            $orders_stmt->execute([$table_number]);
            $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get order items for each order
            foreach ($orders as &$order) {
                $items_query = "SELECT oi.id, m.name, m.price, oi.quantity, oi.special_instructions
                               FROM order_items oi
                               JOIN menu_items m ON oi.menu_item_id = m.id
                               WHERE oi.order_id = ?";
                
                $items_stmt = $db->prepare($items_query);
                $items_stmt->execute([$order['id']]);
                $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format items as JSON
                $formatted_items = array_map(function($item) {
                    return [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'instructions' => $item['special_instructions']
                    ];
                }, $items);
                
                $order['items'] = json_encode($formatted_items);
            }
            
            $table_orders = $orders;
        } else {
            $error_message = "Invalid or expired QR code. Please scan again.";
            $table_number = null;
            $token = null;
        }
    } catch (Exception $e) {
        error_log("Error in view_orders.php: " . $e->getMessage());
        $error_message = "Error retrieving orders. Please try again.";
    }
} else {
    $error_message = "Please scan your table's QR code to view orders.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Table <?php echo $table_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --color-bg: #1a1a1a;
            --color-surface: #2d2d2d;
            --color-primary: #c8a165;
            --color-secondary: #b38b4d;
            --color-pending: #ff9800;
            --color-processing: #c8a165;
            --color-completed: #4caf50;
            --color-cancelled: #f44336;
            --color-text: #ffffff;
            --color-text-light: #cccccc;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            --box-shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.3);
            --border-radius: 15px;
            --border-color: rgba(200, 161, 101, 0.2);
        }

        body {
            background: var(--color-bg);
            color: var(--color-text);
            font-family: 'DM Sans', sans-serif;
            padding-top: 80px;
            line-height: 1.6;
        }

        .navbar {
            background: var(--color-surface);
            box-shadow: var(--box-shadow);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--color-primary) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            font-size: 1.5rem;
        }

        .back-button {
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            background: linear-gradient(145deg, #8B0000, #800000);
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .back-button:hover {
            background: linear-gradient(145deg, #c8a165, #b38b4d);
            color: white;
            transform: translateY(-2px);
        }

        .table-info {
            background: var(--color-surface);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .table-info h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .table-info p {
            color: var(--color-text-light);
            margin: 0;
        }

        .order-card {
            background: var(--color-surface);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid var(--border-color);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
            border-color: var(--color-primary);
        }

        .order-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(200, 161, 101, 0.1);
        }

        .order-id {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--color-primary);
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
        }

        .status-processing {
            background: rgba(200, 161, 101, 0.2);
            color: #c8a165;
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .order-body {
            padding: 1.25rem;
        }

        .order-items {
            margin-bottom: 1.25rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-quantity {
            background: var(--color-primary);
            color: var(--color-bg);
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .item-name {
            font-weight: 500;
            color: var(--color-text);
        }

        .item-price {
            color: var(--color-primary);
            font-weight: 500;
        }

        .special-instructions {
            margin-top: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(200, 161, 101, 0.1);
            border-left: 3px solid var(--color-primary);
            border-radius: 4px;
            font-size: 0.875rem;
            color: var(--color-text-light);
        }

        .special-instructions i {
            color: var(--color-primary);
            margin-right: 0.5rem;
        }

        .order-footer {
            padding: 1.25rem;
            background: rgba(200, 161, 101, 0.05);
            border-top: 1px solid var(--border-color);
        }

        .order-subtotal, .order-sst {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: var(--color-text-light);
            margin-bottom: 0.5rem;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.125rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed var(--border-color);
            color: var(--color-primary);
        }

        .order-time {
            color: var(--color-text-light);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            background: var(--color-surface);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
        }

        .no-orders i {
            font-size: 3rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
        }

        .no-orders h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 0.5rem;
        }

        .no-orders p {
            color: var(--color-text-light);
            margin: 0;
        }

        /* Status Timeline Styles */
        .status-timeline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0;
            position: relative;
            padding: 0 1rem;
        }

        .status-timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-color);
            transform: translateY(-50%);
            z-index: 1;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            z-index: 2;
            background: var(--color-surface);
            padding: 0 1rem;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-surface);
            border: 2px solid var(--border-color);
            color: var(--color-text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .step-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--color-text-light);
            white-space: nowrap;
        }

        .timeline-step.active .step-icon {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: var(--color-bg);
            box-shadow: 0 0 0 4px rgba(200, 161, 101, 0.2);
        }

        .timeline-step.active .step-label {
            color: var(--color-primary);
            font-weight: 600;
        }

        .timeline-step.completed .step-icon {
            background: var(--color-completed);
            border-color: var(--color-completed);
            color: var(--color-bg);
        }

        .timeline-step.completed .step-label {
            color: var(--color-completed);
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            height: 2px;
            background: var(--color-primary);
            transform: translateY(-50%);
            transition: width 0.3s ease;
            z-index: 1;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .table-info {
                padding: 1.5rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .order-status {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils"></i>
                Gourmet Delights
            </a>
            <a href="index.php<?php echo ($table_number && $token) ? '?table=' . $table_number . '&token=' . $token : ''; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Menu
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="table-info">
            <h1>Table <?php echo $table_number; ?></h1>
            <p>View your orders for today</p>
        </div>

        <?php if (empty($table_orders)): ?>
        <div class="no-orders">
            <i class="fas fa-receipt"></i>
            <h2>No Orders Found</h2>
            <p>You don't have any orders for today.</p>
        </div>
        <?php else: ?>
            <?php foreach ($table_orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <?php if (isset($order['is_combined']) && $order['is_combined']): ?>
                    <div class="order-id">
                        <?php echo ucfirst($order['status']); ?> Orders (<?php echo $order['order_count']; ?> orders)
                        <div class="small text-muted">
                            <?php 
                            $order_ids = explode(',', $order['id']);
                            $formatted_ids = array_map(function($id) {
                                return '#' . str_pad($id, 4, '0', STR_PAD_LEFT);
                            }, $order_ids);
                            echo implode(', ', $formatted_ids);
                            ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="order-id">Order #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></div>
                    <?php endif; ?>
                    <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </div>
                </div>

                <!-- Add Status Timeline -->
                <div class="status-timeline">
                    <?php
                    $statuses = ['pending', 'processing', 'completed'];
                    $currentStatus = strtolower($order['status']);
                    $progress = 0;
                    
                    switch($currentStatus) {
                        case 'completed':
                            $progress = 100;
                            break;
                        case 'processing':
                            $progress = 50;
                            break;
                        default:
                            $progress = 0;
                    }
                    ?>
                    <div class="progress-line" style="width: <?php echo $progress; ?>%"></div>
                    
                    <?php foreach ($statuses as $index => $status): 
                        $isActive = $currentStatus === $status;
                        $isCompleted = array_search($currentStatus, $statuses) > array_search($status, $statuses);
                        $stepClass = $isActive ? 'active' : ($isCompleted ? 'completed' : '');
                    ?>
                    <div class="timeline-step <?php echo $stepClass; ?>">
                        <div class="step-icon">
                            <?php if ($isCompleted): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php
                                $icon = 'clock';
                                switch($status) {
                                    case 'processing':
                                        $icon = 'fire';
                                        break;
                                    case 'completed':
                                        $icon = 'check';
                                        break;
                                }
                                ?>
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="step-label"><?php echo ucfirst($status); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-body">
                    <div class="order-items">
                        <?php 
                        $items = json_decode($order['items'], true);
                        if (is_array($items)) {
                            foreach ($items as $item): 
                                if (is_array($item)):
                        ?>
                        <div class="order-item">
                            <div class="item-details">
                                <span class="item-quantity"><?php echo htmlspecialchars($item['quantity']); ?>×</span>
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            </div>
                            <span class="item-price">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php if (!empty($item['instructions'])): ?>
                        <div class="special-instructions">
                            <i class="fas fa-comment-alt"></i>
                            <?php echo htmlspecialchars($item['instructions']); ?>
                        </div>
                        <?php 
                                endif;
                            endif;
                            endforeach; 
                        }
                        ?>
                    </div>
                </div>
                <div class="order-footer">
                    <?php
                    // Calculate SST using current tax rate
                    $sst_rate = $tax_rate / 100;
                    $subtotal = $order['total_amount'] / (1 + $sst_rate);
                    $sst_amount = $order['total_amount'] - $subtotal;
                    ?>
                    <div class="order-subtotal">
                        <span>Subtotal</span>
                        <span>RM <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="order-sst">
                        <span>SST (<?php echo number_format($tax_rate, 1); ?>%)</span>
                        <span>RM <?php echo number_format($sst_amount, 2); ?></span>
                    </div>
                    <div class="order-total">
                        <span>Total</span>
                        <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="order-time">
                        Ordered: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 