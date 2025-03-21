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

// Update order status if requested
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Add special handling for cancelled status
    if ($new_status === 'cancelled') {
        if ($orderModel->updateStatus($order_id, 'cancelled')) {
            $success_message = "Order cancelled successfully";
        } else {
            $error_message = "Failed to cancel order";
        }
    } else {
        // Check if this is a combined order (multiple IDs separated by commas)
        if (strpos($order_id, ',') !== false) {
            $order_ids = explode(',', $order_id);
            $success = true;
            
            // Update status for each order in the group
            foreach ($order_ids as $id) {
                if (!$orderModel->updateStatus($id, $new_status)) {
                    $success = false;
                }
            }
            
            if ($success) {
                $success_message = "All orders status updated successfully";
            } else {
                $error_message = "Failed to update some order statuses";
            }
        } else {
            // Single order update
            if ($orderModel->updateStatus($order_id, $new_status)) {
                $success_message = "Order status updated successfully";
            } else {
                $error_message = "Failed to update order status";
            }
        }
    }
}

// Get orders grouped by status
try {
    $pending_orders = $orderModel->getOrdersByStatus('pending');
    $processing_orders = $orderModel->getOrdersByStatus('processing');
    $all_orders = array_merge($pending_orders ?? [], $processing_orders ?? []);
    usort($all_orders, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $pending_orders = [];
    $processing_orders = [];
    $all_orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --color-bg: #f8f9fa;
        --color-surface: #ffffff;
        --color-surface-hover: #f1f3f5;
        --color-primary: #212529;
        --color-secondary: #495057;
        --color-pending: #868e96;
        --color-cooking: #495057;
        --color-completed: #212529;
        --color-warning: #dc3545;
        --color-text: #212529;
        --color-text-light: #868e96;
        --color-border: #dee2e6;
        --border-radius: 8px;
        --box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        --box-shadow-lg: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --gradient-blue: linear-gradient(135deg, #212529 0%, #343a40 100%);
        --gradient-purple: linear-gradient(135deg, #495057 0%, #343a40 100%);
        --gradient-orange: linear-gradient(135deg, #868e96 0%, #495057 100%);
        --font-primary: 'Poppins', sans-serif;
        --font-secondary: 'DM Sans', sans-serif;
    }

    body {
        background: var(--color-bg);
        color: var(--color-text);
        font-family: var(--font-secondary);
        min-height: 100vh;
        line-height: 1.6;
        letter-spacing: -0.01em;
    }

    .kitchen-display {
        padding: 1.5rem;
        max-width: 2200px;
        margin: 0 auto;
    }

    .header {
        background: var(--color-surface);
        padding: 1.25rem 1.75rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 1rem;
        z-index: 100;
        box-shadow: var(--box-shadow);
        border: 1px solid var(--color-border);
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-title i {
        font-size: 1.5rem;
        color: var(--color-primary);
        background: var(--color-surface-hover);
        padding: 0.75rem;
        border-radius: var(--border-radius);
        transition: all 0.2s ease;
    }

    .header-title i:hover {
        transform: scale(1.05);
        background: var(--color-border);
    }

    .page-title {
        font-family: var(--font-primary);
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--color-text);
        margin: 0;
        letter-spacing: -0.02em;
    }

    .header-stats {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .stat-box {
        padding: 0.625rem 1rem;
        border-radius: var(--border-radius);
        background: var(--color-surface);
        display: flex;
        align-items: center;
        gap: 0.625rem;
        font-weight: 500;
        color: var(--color-text);
        transition: all 0.2s ease;
        font-family: var(--font-secondary);
        letter-spacing: -0.01em;
        border: 1px solid var(--color-border);
    }

    .stat-box:hover {
        background: var(--color-surface-hover);
        transform: translateY(-1px);
    }

    .stat-box i {
        font-size: 1rem;
        color: var(--color-primary);
    }

    .orders-table-container {
        background: var(--color-surface);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    .orders-table th {
        background: var(--color-surface-hover);
        padding: 1rem;
        text-align: left;
        font-family: var(--font-primary);
        font-weight: 600;
        color: var(--color-text);
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--color-border);
    }

    .orders-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--color-border);
        font-size: 0.875rem;
    }

    .orders-table tr:last-child td {
        border-bottom: none;
    }

    .orders-table tr:hover {
        background: var(--color-surface-hover);
    }

    .order-number {
        font-family: var(--font-primary);
        font-weight: 600;
        color: var(--color-primary);
    }

    .table-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table-number {
        font-weight: 600;
        color: var(--color-text);
    }

    .time-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        font-size: 0.75rem;
        font-weight: 500;
        background: var(--color-surface-hover);
        color: var(--color-text);
    }

    .time-badge.warning {
        background: #fff5f5;
        color: var(--color-warning);
    }

    .items-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        background: var(--color-surface-hover);
        border-radius: var(--border-radius);
    }

    .quantity {
        font-weight: 600;
        color: var(--color-primary);
        min-width: 40px;
    }

    .item-name {
        flex: 1;
    }

    .item-btn {
        width: 32px;
        height: 32px;
        border-radius: var(--border-radius);
        border: 1px solid var(--color-border);
        background: var(--color-surface);
        color: var(--color-text-light);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.75rem;
    }

    .item-btn:hover {
        background: var(--color-primary);
        color: white;
        border-color: transparent;
    }

    .item-btn.complete {
        background: var(--color-secondary);
        color: white;
        border-color: transparent;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: var(--color-surface-hover);
        color: var(--color-text);
    }

    .pending .status-badge {
        background: var(--color-surface-hover);
        color: var(--color-text);
    }

    .cooking .status-badge {
        background: var(--color-surface-hover);
        color: var(--color-text);
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn, .cancel-btn {
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius);
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: white;
        font-family: var(--font-primary);
    }

    .pending .action-btn {
        background: var(--color-primary);
    }

    .cooking .action-btn {
        background: var(--color-secondary);
    }

    .cancel-btn {
        background: var(--color-pending);
    }

    .action-btn:hover, .cancel-btn:hover {
        transform: translateY(-1px);
        filter: brightness(105%);
    }

    .no-orders {
        text-align: center;
        padding: 3rem 2rem;
        background: var(--color-surface);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        border: 1px solid var(--color-border);
    }

    .no-orders i {
        font-size: 2.5rem;
        color: var(--color-primary);
        margin-bottom: 1rem;
    }

    .no-orders h2 {
        font-family: var(--font-primary);
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        margin-bottom: 0.5rem;
        color: var(--color-text);
    }

    .no-orders p {
        font-family: var(--font-secondary);
        font-size: 1rem;
        letter-spacing: -0.01em;
        color: var(--color-text-light);
    }

    @media (max-width: 768px) {
        .kitchen-display {
            padding: 1rem;
        }

        .header {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }

        .header-stats {
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
        }

        .stat-box {
            flex: 1;
            min-width: 120px;
            justify-content: center;
        }

        .orders-table {
            display: block;
            overflow-x: auto;
        }

        .orders-table th,
        .orders-table td {
            padding: 0.75rem;
            white-space: nowrap;
        }

        .items-list {
            min-width: 200px;
        }

        .action-buttons {
            flex-direction: column;
            gap: 0.375rem;
        }

        .action-btn, .cancel-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .header-title {
            flex-direction: column;
            text-align: center;
            gap: 0.75rem;
        }

        .stat-box {
            min-width: 100%;
        }

        .orders-table th,
        .orders-table td {
            padding: 0.5rem;
        }

        .item {
            padding: 0.375rem;
        }

        .quantity {
            min-width: 32px;
        }

        .item-name {
            font-size: 0.75rem;
        }
    }
    </style>
</head>
<body>
    <div class="kitchen-display">
        <header class="header">
            <div class="header-title">
                <i class="fas fa-utensils fa-lg"></i>
                <h1 class="page-title">Kitchen Display</h1>
            </div>
            <div class="header-stats">
                <div class="stat-box">
                    <i class="fas fa-clock"></i>
                    <span id="currentTime"></span>
                </div>
                <div class="stat-box">
                    <i class="fas fa-hourglass-start"></i>
                    <span>Pending: <?php echo count($pending_orders); ?></span>
                </div>
                <div class="stat-box">
                    <i class="fas fa-fire"></i>
                    <span>Cooking: <?php echo count($processing_orders); ?></span>
                </div>
                <a href="dashboard.php" class="stat-box">
                    <i class="fas fa-times"></i>
                    <span>Exit</span>
                </a>
            </div>
        </header>

        <?php if (empty($all_orders)): ?>
        <div class="no-orders">
            <i class="fas fa-check-circle"></i>
            <h2>All Caught Up!</h2>
            <p>No pending orders at the moment</p>
        </div>
        <?php else: ?>
        <div class="orders-table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Table</th>
                        <th>Time</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_orders as $order): 
                        $is_pending = $order['status'] === 'pending';
                        $order_time = strtotime($order['created_at']);
                        $time_diff = time() - $order_time;
                        $minutes = round($time_diff / 60);
                        $time_warning = ($is_pending && $minutes > 30) || (!$is_pending && $minutes > 45);
                    ?>
                    <tr class="<?php echo $is_pending ? 'pending' : 'cooking'; ?>" 
                        data-order-id="<?php echo $order['id']; ?>"
                        data-status="<?php echo $is_pending ? 'pending' : 'processing'; ?>">
                        <td>
                            <div class="order-number">
                                #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                            </div>
                        </td>
                        <td>
                            <div class="table-info">
                                <i class="fas fa-chair"></i>
                                <span class="table-number"><?php echo htmlspecialchars($order['table_number']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="time-badge <?php echo $time_warning ? 'warning' : ''; ?>">
                                <i class="fas fa-clock"></i>
                                <?php echo $minutes; ?> min
                            </div>
                        </td>
                        <td>
                            <div class="items-list">
                                <?php 
                                $items = explode(', ', $order['items']);
                                foreach ($items as $index => $item):
                                    $item_parts = explode(' (', $item);
                                    $item_name = $item_parts[0];
                                    $quantity = rtrim($item_parts[1], ')');

                                    $item_instruction = '';
                                    if (!empty($order['special_instructions'])) {
                                        foreach ($order['special_instructions'] as $instruction) {
                                            if ($instruction['item'] === $item_name) {
                                                $item_instruction = $instruction['instructions'];
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <div class="item" data-item-id="<?php echo $index; ?>">
                                    <div class="quantity"><?php echo $quantity; ?>×</div>
                                    <div class="item-name"><?php echo htmlspecialchars($item_name); ?></div>
                                    <button class="item-btn" onclick="toggleItemComplete(this)" title="Mark as complete">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div class="status-badge">
                                <?php if ($is_pending): ?>
                                <i class="fas fa-hourglass-start"></i> PENDING
                                <?php else: ?>
                                <i class="fas fa-fire"></i> COOKING
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST" class="order-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="new_status" 
                                           value="<?php echo $is_pending ? 'processing' : 'completed'; ?>">
                                    <button type="submit" name="update_status" class="action-btn">
                                        <?php if ($is_pending): ?>
                                        <i class="fas fa-fire"></i> START
                                        <?php else: ?>
                                        <i class="fas fa-check"></i> COMPLETE
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <form method="POST" class="cancel-form" onsubmit="return confirmCancel()">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="new_status" value="cancelled">
                                    <button type="submit" name="update_status" class="cancel-btn">
                                        <i class="fas fa-times"></i> CANCEL
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Update current time
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
        document.getElementById('currentTime').textContent = timeString;
    }

    setInterval(updateTime, 1000);
    updateTime();

    // Item completion functionality
    function toggleItemComplete(button) {
        const item = button.closest('.item');
        const orderCard = item.closest('.order-card');
        const itemsList = orderCard.querySelector('.items-list');
        const items = itemsList.querySelectorAll('.item');
        const progressBar = itemsList.querySelector('.progress-fill');
        
        item.classList.toggle('completed');
        button.classList.toggle('complete');
        
        // Update progress bar
        const completedItems = itemsList.querySelectorAll('.item.completed').length;
        const totalItems = items.length;
        const progress = (completedItems / totalItems) * 100;
        progressBar.style.width = progress + '%';
        
        // If all items are completed, enable the complete order button
        const actionBtn = orderCard.querySelector('.action-btn');
        if (completedItems === totalItems) {
            actionBtn.disabled = false;
            actionBtn.style.opacity = '1';
        }
    }

    // Auto refresh every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);

    // Play sound for new orders
    document.addEventListener('DOMContentLoaded', function() {
        let currentPendingCount = <?php echo count($pending_orders); ?>;
        
        setInterval(function() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPendingCount = doc.querySelectorAll('.order-card.pending').length;
                    
                    if (newPendingCount > currentPendingCount) {
                        const audio = new Audio('assets/notification.mp3');
                        audio.play();
                    }
                    
                    currentPendingCount = newPendingCount;
                });
        }, 30000);

        // Request fullscreen on load
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    });

    function confirmCancel() {
        return confirm('Are you sure you want to cancel this order?');
    }
    </script>
</body>
</html> 