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

// Get date range from query parameters or default to last 30 days
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Get completed orders
$completed_orders = $orderModel->getCompletedOrders($start_date, $end_date);

// Set page title
$page_title = "Completed Orders";

// Start output buffering
ob_start();
?>

<!-- Page content -->
<div class="container-fluid py-4">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-check-circle"></i>
            Completed Orders
        </h1>
    </div>

    <div class="date-filter">
        <form class="date-inputs">
            <input type="date" class="date-input" id="start_date" name="start_date" 
                   value="<?php echo $start_date; ?>">
            <input type="date" class="date-input" id="end_date" name="end_date" 
                   value="<?php echo $end_date; ?>">
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i>
                Filter Orders
            </button>
        </form>
    </div>

    <div class="orders-container">
        <div class="table-responsive">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table</th>
                        <th>Items</th>
                        <th>Special Instructions</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Completed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completed_orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x mb-3 text-muted d-block"></i>
                            No completed orders found for the selected date range
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $grouped_orders = [];
                        // Group orders by table number and completion time
                        foreach ($completed_orders as $order) {
                            $key = $order['table_number'] . '_' . $order['created_at'];
                            if (!isset($grouped_orders[$key])) {
                                $grouped_orders[$key] = [
                                    'id' => [],
                                    'table_number' => $order['table_number'],
                                    'items' => [],
                                    'special_instructions' => [],
                                    'total_amount' => 0,
                                    'created_at' => $order['created_at']
                                ];
                            }
                            $grouped_orders[$key]['id'][] = $order['id'];
                            $grouped_orders[$key]['items'][] = $order['items_list'];
                            if (!empty($order['special_instructions'])) {
                                $grouped_orders[$key]['special_instructions'] = array_merge(
                                    $grouped_orders[$key]['special_instructions'],
                                    $order['special_instructions']
                                );
                            }
                            $grouped_orders[$key]['total_amount'] += $order['total_amount'];
                        }

                        foreach ($grouped_orders as $group): 
                        ?>
                        <tr>
                            <td>
                                <span class="order-id">
                                    #<?php echo implode(', #', array_map(function($id) {
                                        return str_pad($id, 4, '0', STR_PAD_LEFT);
                                    }, $group['id'])); ?>
                                </span>
                            </td>
                            <td>Table <?php echo htmlspecialchars($group['table_number']); ?></td>
                            <td>
                                <div class="order-items">
                                    <span class="item-count"><?php echo count($group['items']); ?> items</span>
                                    <div class="item-details small text-muted">
                                        <?php echo htmlspecialchars(implode(', ', $group['items'])); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="special-instructions">
                                    <?php if (!empty($group['special_instructions'])): ?>
                                        <?php foreach ($group['special_instructions'] as $instruction): ?>
                                            <div class="instruction-item">
                                                <strong><?php echo htmlspecialchars($instruction['item']); ?></strong>
                                                <span><?php echo htmlspecialchars($instruction['instructions']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No special instructions</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="order-amount">RM <?php echo number_format($group['total_amount'], 2); ?></span>
                            </td>
                            <td>
                                <span class="status-badge completed">
                                    <i class="fas fa-check-circle"></i>
                                    Completed
                                </span>
                            </td>
                            <td>
                                <span class="order-date">
                                    <?php 
                                    $date = !empty($group['created_at']) ? 
                                        date('d M Y, h:i A', strtotime($group['created_at'])) : 
                                        'Date not available';
                                    echo $date;
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php foreach ($group['id'] as $orderId): ?>
                                <a href="view_order.php?id=<?php echo $orderId; ?>" 
                                   class="btn btn-sm btn-outline-primary mb-1">
                                    <i class="fas fa-eye"></i> View #<?php echo str_pad($orderId, 4, '0', STR_PAD_LEFT); ?>
                                </a>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="total-orders">
                Total Completed Orders: <?php echo count($completed_orders); ?>
            </div>
            <a href="export_completed_orders.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
               class="export-btn"
               target="_blank">
                <i class="fas fa-download"></i>
                Export to Excel
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add custom CSS
$extra_css = '
<style>
    :root {
        --color-bg: #1E1E2E;
        --color-surface: #282839;
        --color-surface-light: #313145;
        --color-primary: #D4AF37;
        --color-secondary: #C5A028;
        --color-success: #98FB98;
        --color-text: #ffffff;
        --color-text-light: #E8E8E8;
        --color-border: rgba(212, 175, 55, 0.2);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        background-color: var(--color-bg);
        color: var(--color-text);
        font-family: "DM Sans", sans-serif;
    }

    .page-header {
        background: var(--color-surface);
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        border: 1px solid var(--color-border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--color-primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .page-title i {
        color: var(--color-primary);
        font-size: 1.8rem;
    }

    .date-filter {
        background: var(--color-surface);
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        border: 1px solid var(--color-border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
    }

    .date-inputs {
        display: flex;
        gap: 1.5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .date-input {
        padding: 1rem 1.5rem;
        background: var(--color-surface-light);
        border: 1px solid var(--color-border);
        border-radius: 15px;
        color: var(--color-text);
        font-size: 1rem;
        transition: var(--transition);
        flex: 1;
        min-width: 200px;
    }

    .date-input:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
        outline: none;
    }

    .filter-btn {
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: var(--color-bg);
        border: none;
        border-radius: 15px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: var(--transition);
        cursor: pointer;
        min-width: 160px;
    }

    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
    }

    .orders-container {
        background: var(--color-surface);
        border-radius: 20px;
        border: 1px solid var(--color-border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        overflow: hidden;
    }

    .orders-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .orders-table th {
        background: var(--color-surface-light);
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        color: var(--color-primary);
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--color-border);
    }

    .orders-table td {
        padding: 1.5rem;
        border-bottom: 1px solid var(--color-border);
        color: var(--color-text);
        font-size: 1rem;
        transition: var(--transition);
    }

    .orders-table tr:hover td {
        background: rgba(212, 175, 55, 0.05);
    }

    .order-id {
        font-weight: 600;
        color: var(--color-primary);
        font-size: 1.1rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 30px;
        font-weight: 500;
        font-size: 0.95rem;
        background: rgba(152, 251, 152, 0.1);
        color: var(--color-success);
        transition: var(--transition);
    }

    .status-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(152, 251, 152, 0.2);
    }

    .order-amount {
        font-weight: 600;
        color: var(--color-primary);
        font-size: 1.1rem;
    }

    .order-date {
        color: var(--color-text-light);
    }

    .table-footer {
        padding: 1.5rem 2rem;
        background: var(--color-surface-light);
        border-top: 1px solid var(--color-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .total-orders {
        font-weight: 600;
        color: var(--color-primary);
        font-size: 1.1rem;
    }

    .export-btn {
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: var(--color-bg);
        border: none;
        border-radius: 15px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: var(--transition);
        text-decoration: none;
    }

    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        color: var(--color-bg);
    }

    .order-items {
        max-width: 300px;
    }

    .item-count {
        font-weight: 600;
        color: var(--color-primary);
        display: block;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    .item-details {
        color: var(--color-text-light);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .special-instructions {
        max-width: 300px;
    }

    .instruction-item {
        background: rgba(212, 175, 55, 0.1);
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        border: 1px solid var(--color-border);
        transition: var(--transition);
    }

    .instruction-item:hover {
        transform: translateX(5px);
        background: rgba(212, 175, 55, 0.15);
    }

    .instruction-item:last-child {
        margin-bottom: 0;
    }

    .instruction-item strong {
        color: var(--color-primary);
        display: block;
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    .btn-outline-primary {
        color: var(--color-primary);
        border: 1px solid var(--color-primary);
        background: transparent;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        transition: var(--transition);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0.25rem;
    }

    .btn-outline-primary:hover {
        background: var(--color-primary);
        color: var(--color-bg);
        transform: translateY(-2px);
    }

    @media (max-width: 1200px) {
        .orders-table {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .date-inputs {
            flex-direction: column;
        }
        
        .filter-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .page-header, .date-filter {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
        }

        .table-footer {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .export-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .order-items,
        .special-instructions {
            max-width: 200px;
        }

        .item-count {
            font-size: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
    }

    /* Animation classes */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Loading animation */
    .loading {
        position: relative;
        opacity: 0.7;
    }

    .loading::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 30px;
        height: 30px;
        margin: -15px 0 0 -15px;
        border: 3px solid var(--color-primary);
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>';

// Add custom JavaScript
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Date filter functionality with enhanced UI feedback
    const dateForm = document.querySelector(".date-inputs");
    const ordersContainer = document.querySelector(".orders-container");

    dateForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const startDate = document.getElementById("start_date").value;
        const endDate = document.getElementById("end_date").value;
        
        // Show loading state
        const filterBtn = document.querySelector(".filter-btn");
        const originalBtnText = filterBtn.innerHTML;
        filterBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Loading...`;
        filterBtn.disabled = true;
        ordersContainer.classList.add("loading");

        // Make AJAX request
        fetch(`completed_orders.php?start_date=${startDate}&end_date=${endDate}&ajax=true`)
            .then(response => response.text())
            .then(html => {
                // Update table content with animation
                ordersContainer.classList.remove("loading");
                ordersContainer.innerHTML = html;
                ordersContainer.classList.add("fade-in");
                
                // Update URL without reloading
                const newUrl = `completed_orders.php?start_date=${startDate}&end_date=${endDate}`;
                window.history.pushState({ path: newUrl }, "", newUrl);

                // Initialize hover effects for new content
                initializeHoverEffects();
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Error loading orders. Please try again.");
                ordersContainer.classList.remove("loading");
            })
            .finally(() => {
                // Restore button state
                filterBtn.innerHTML = originalBtnText;
                filterBtn.disabled = false;
            });
    });

    // Initialize hover effects
    function initializeHoverEffects() {
        const instructionItems = document.querySelectorAll(".instruction-item");
        instructionItems.forEach(item => {
            item.addEventListener("mouseenter", () => {
                item.style.transform = "translateX(5px)";
            });
            item.addEventListener("mouseleave", () => {
                item.style.transform = "translateX(0)";
            });
        });
    }

    // Initialize effects on page load
    initializeHoverEffects();
});
</script>';

// If this is an AJAX request, only return the table content
if (isset($_GET["ajax"]) && $_GET["ajax"] === "true") {
    ?>
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Table</th>
                    <th>Items</th>
                    <th>Special Instructions</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Completed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($completed_orders)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-inbox fa-2x mb-3 text-muted d-block"></i>
                        No completed orders found for the selected date range
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $grouped_orders = [];
                    // Group orders by table number and completion time
                    foreach ($completed_orders as $order) {
                        $key = $order['table_number'] . '_' . $order['created_at'];
                        if (!isset($grouped_orders[$key])) {
                            $grouped_orders[$key] = [
                                'id' => [],
                                'table_number' => $order['table_number'],
                                'items' => [],
                                'special_instructions' => [],
                                'total_amount' => 0,
                                'created_at' => $order['created_at']
                            ];
                        }
                        $grouped_orders[$key]['id'][] = $order['id'];
                        $grouped_orders[$key]['items'][] = $order['items_list'];
                        if (!empty($order['special_instructions'])) {
                            $grouped_orders[$key]['special_instructions'] = array_merge(
                                $grouped_orders[$key]['special_instructions'],
                                $order['special_instructions']
                            );
                        }
                        $grouped_orders[$key]['total_amount'] += $order['total_amount'];
                    }

                    foreach ($grouped_orders as $group): 
                    ?>
                    <tr>
                        <td>
                            <span class="order-id">
                                #<?php echo implode(', #', array_map(function($id) {
                                    return str_pad($id, 4, '0', STR_PAD_LEFT);
                                }, $group['id'])); ?>
                            </span>
                        </td>
                        <td>Table <?php echo htmlspecialchars($group['table_number']); ?></td>
                        <td>
                            <div class="order-items">
                                <span class="item-count"><?php echo count($group['items']); ?> items</span>
                                <div class="item-details small text-muted">
                                    <?php echo htmlspecialchars(implode(', ', $group['items'])); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="special-instructions">
                                <?php if (!empty($group['special_instructions'])): ?>
                                    <?php foreach ($group['special_instructions'] as $instruction): ?>
                                        <div class="instruction-item">
                                            <strong><?php echo htmlspecialchars($instruction['item']); ?></strong>
                                            <span><?php echo htmlspecialchars($instruction['instructions']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No special instructions</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="order-amount">RM <?php echo number_format($group['total_amount'], 2); ?></span>
                        </td>
                        <td>
                            <span class="status-badge completed">
                                <i class="fas fa-check-circle"></i>
                                Completed
                            </span>
                        </td>
                        <td>
                            <span class="order-date">
                                <?php 
                                $date = !empty($group['created_at']) ? 
                                    date('d M Y, h:i A', strtotime($group['created_at'])) : 
                                    'Date not available';
                                echo $date;
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php foreach ($group['id'] as $orderId): ?>
                            <a href="view_order.php?id=<?php echo $orderId; ?>" 
                               class="btn btn-sm btn-outline-primary mb-1">
                                <i class="fas fa-eye"></i> View #<?php echo str_pad($orderId, 4, '0', STR_PAD_LEFT); ?>
                            </a>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="total-orders">
            Total Completed Orders: <?php echo count($completed_orders); ?>
        </div>
        <a href="export_completed_orders.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
           class="export-btn"
           target="_blank">
            <i class="fas fa-download"></i>
            Export to Excel
        </a>
    </div>
    <?php
    exit;
}

// Include the layout
include 'includes/layout.php';
?> 