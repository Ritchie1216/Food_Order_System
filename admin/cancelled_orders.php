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

// Get cancelled orders
$cancelled_orders = $orderModel->getCancelledOrders($start_date, $end_date);

// Set page title
$page_title = "Cancelled Orders";

// Start output buffering
ob_start();
?>

<!-- Page content -->
<div class="container-fluid py-4">
    <div class="dashboard-header">
        <div class="header-content">
            <h1 class="dashboard-title">
                <i class="fas fa-times-circle"></i>
                Cancelled Orders
            </h1>
            <p class="header-subtitle">View and manage cancelled orders</p>
        </div>
    </div>

    <div class="filter-section">
        <form class="date-filter-form">
            <div class="date-inputs">
                <div class="input-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" class="custom-date-input" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="input-group">
                    <label for="end_date">End Date</label>
                    <input type="date" class="custom-date-input" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
            </div>
            <button type="submit" class="filter-button">
                <span class="button-content">
                    <i class="fas fa-filter"></i>
                    Filter Orders
                </span>
            </button>
        </form>
    </div>

    <div class="orders-section">
        <?php if (empty($cancelled_orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>No Orders Found</h3>
                <p>No cancelled orders found for the selected date range</p>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php 
                $grouped_orders = [];
                // Group orders by table number AND cancellation time
                foreach ($cancelled_orders as $order) {
                    $timestamp = strtotime($order['created_at']);
                    $timeKey = date('Y-m-d H:i', $timestamp);
                    $key = $order['table_number'] . '_' . $timeKey;
                    
                    if (!isset($grouped_orders[$key])) {
                        $grouped_orders[$key] = [
                            'id' => $order['id'],
                            'table_number' => $order['table_number'],
                            'items_list' => $order['items_list'],
                            'item_count' => $order['item_count'],
                            'special_instructions' => $order['special_instructions'],
                            'total_amount' => $order['total_amount'],
                            'created_at' => $order['created_at']
                        ];
                    }
                }

                uasort($grouped_orders, function($a, $b) {
                    $dateCompare = strtotime($b['created_at']) - strtotime($a['created_at']);
                    if ($dateCompare === 0) {
                        return $a['table_number'] - $b['table_number'];
                    }
                    return $dateCompare;
                });

                foreach ($grouped_orders as $order): 
                ?>
                <div class="order-card" data-aos="fade-up">
                    <div class="order-header">
                        <div class="order-id">
                            #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="order-status">
                            <i class="fas fa-times-circle"></i>
                            Cancelled
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-info">
                            <div class="info-group">
                                <span class="info-label">Table</span>
                                <span class="info-value">Table <?php echo htmlspecialchars($order['table_number']); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Items</span>
                                <span class="info-value"><?php echo $order['item_count']; ?> items</span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Total</span>
                                <span class="info-value">RM <?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <div class="items-list">
                                <?php 
                                $items = explode(', ', $order['items_list']);
                                $itemsList = [];
                                foreach($items as $item) {
                                    $itemsList[] = preg_replace('/ - Note:.*$/', '', $item);
                                }
                                echo implode(', ', $itemsList);
                                ?>
                            </div>
                            <?php if (strpos($order['items_list'], ' - Note: ') !== false): ?>
                                <div class="special-instructions-toggle">
                                    <button class="toggle-btn" onclick="toggleInstructions(this)">
                                        <i class="fas fa-info-circle"></i>
                                        Special Instructions
                                    </button>
                                    <div class="instructions-content">
                                        <?php 
                                        $items = explode(', ', $order['items_list']);
                                        $instructions = [];
                                        foreach($items as $item) {
                                            if(strpos($item, ' - Note: ') !== false) {
                                                list($itemName, $note) = explode(' - Note: ', $item);
                                                $instructions[] = "<div class='instruction-item'><strong>" . htmlspecialchars($itemName) . "</strong>: " . htmlspecialchars($note) . "</div>";
                                            }
                                        }
                                        echo implode('', $instructions);
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <div class="order-date">
                            <i class="far fa-clock"></i>
                            <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                        </div>
                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="view-button">
                            <i class="fas fa-eye"></i>
                            View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="orders-summary">
                <div class="total-count">
                    Total Cancelled Orders: <span><?php echo count($cancelled_orders); ?></span>
                </div>
                <a href="export_cancelled_orders.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="export-button" target="_blank">
                    <i class="fas fa-download"></i>
                    Export to Excel
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
:root {
    --color-background: #0a0a0a;
    --color-surface: #1a1a1a;
    --color-surface-hover: #2d2d2d;
    --color-primary: #D4AF37;
    --color-primary-dark: #B3922E;
    --color-accent: #FFD700;
    --color-text: #ffffff;
    --color-text-secondary: rgba(255, 255, 255, 0.7);
    --color-border: rgba(212, 175, 55, 0.2);
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    --gradient-gold: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
}

body {
    background-color: var(--color-background);
    color: var(--color-text);
    font-family: "Inter", sans-serif;
}

.dashboard-header {
    background: var(--color-surface);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--color-border);
    box-shadow: var(--shadow-lg);
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-subtitle {
    color: var(--color-text-secondary);
    margin: 0.5rem 0 0;
}

.filter-section {
    background: var(--color-surface);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--color-border);
    box-shadow: var(--shadow-md);
}

.date-filter-form {
    display: flex;
    gap: 1.5rem;
    align-items: flex-end;
}

.date-inputs {
    display: flex;
    gap: 1.5rem;
    flex: 1;
}

.input-group {
    flex: 1;
}

.input-group label {
    display: block;
    color: var(--color-text-secondary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.custom-date-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-surface-hover);
    color: var(--color-text);
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.custom-date-input:focus {
    border-color: var(--color-primary);
    outline: none;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.filter-button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    background: var(--gradient-gold);
    color: var(--color-surface);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 150px;
    justify-content: center;
}

.filter-button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.order-card {
    background: var(--color-surface);
    border-radius: 15px;
    border: 1px solid var(--color-border);
    overflow: hidden;
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--color-primary);
}

.order-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-id {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-primary);
}

.order-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #FF6B6B;
    font-weight: 500;
}

.order-body {
    padding: 1.5rem;
}

.order-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
}

.info-value {
    color: var(--color-text);
    font-weight: 600;
}

.order-items {
    background: var(--color-surface-hover);
    padding: 1rem;
    border-radius: 8px;
}

.items-list {
    color: var(--color-text-secondary);
    font-size: 0.95rem;
    line-height: 1.5;
}

.special-instructions-toggle {
    margin-top: 1rem;
}

.toggle-btn {
    background: none;
    border: 1px solid var(--color-border);
    color: var(--color-primary);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.toggle-btn:hover {
    background: rgba(212, 175, 55, 0.1);
}

.instructions-content {
    display: none;
    margin-top: 1rem;
    padding: 1rem;
    background: var(--color-surface);
    border-radius: 6px;
    font-size: 0.9rem;
}

.instruction-item {
    margin-bottom: 0.5rem;
}

.order-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-date {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.view-button {
    padding: 0.5rem 1rem;
    background: var(--gradient-gold);
    color: var(--color-surface);
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.view-button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.orders-summary {
    background: var(--color-surface);
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid var(--color-border);
    margin-top: 2rem;
}

.total-count {
    font-size: 1.1rem;
    color: var(--color-text-secondary);
}

.total-count span {
    color: var(--color-primary);
    font-weight: 600;
}

.export-button {
    padding: 0.75rem 1.5rem;
    background: var(--gradient-gold);
    color: var(--color-surface);
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.export-button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--color-surface);
    border-radius: 15px;
    border: 1px solid var(--color-border);
}

.empty-state-icon {
    font-size: 3rem;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--color-text-secondary);
}

@media (max-width: 1200px) {
    .orders-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .date-filter-form {
        flex-direction: column;
    }
    
    .date-inputs {
        flex-direction: column;
    }
    
    .filter-button {
        width: 100%;
    }
    
    .orders-grid {
        grid-template-columns: 1fr;
    }
    
    .orders-summary {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .export-button {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .dashboard-header {
        padding: 1.5rem;
    }
    
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .order-info {
        grid-template-columns: 1fr;
    }
}
</style>';

$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Date filter functionality
    document.querySelector(".date-filter-form").addEventListener("submit", function(e) {
        e.preventDefault();
        const startDate = document.getElementById("start_date").value;
        const endDate = document.getElementById("end_date").value;
        
        // Show loading state
        const filterBtn = document.querySelector(".filter-button");
        const originalBtnContent = filterBtn.innerHTML;
        filterBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Loading...`;
        filterBtn.disabled = true;

        // Make AJAX request
        fetch(`cancelled_orders.php?start_date=${startDate}&end_date=${endDate}&ajax=true`)
            .then(response => response.text())
            .then(html => {
                document.querySelector(".orders-section").innerHTML = html;
                
                // Update URL without reloading
                const newUrl = `cancelled_orders.php?start_date=${startDate}&end_date=${endDate}`;
                window.history.pushState({ path: newUrl }, "", newUrl);
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Error loading orders. Please try again.");
            })
            .finally(() => {
                filterBtn.innerHTML = originalBtnContent;
                filterBtn.disabled = false;
            });
    });
});

function toggleInstructions(button) {
    const content = button.nextElementSibling;
    const isVisible = content.style.display === "block";
    
    // Animate height
    if (isVisible) {
        content.style.maxHeight = "0";
        setTimeout(() => {
            content.style.display = "none";
        }, 300);
    } else {
        content.style.display = "block";
        content.style.maxHeight = content.scrollHeight + "px";
    }
    
    // Rotate icon
    const icon = button.querySelector("i");
    icon.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
}

// Add smooth scroll animation for order cards
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
        }
    });
}, observerOptions);

document.querySelectorAll(".order-card").forEach(card => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";
    observer.observe(card);
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
                    <th>Cancelled At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cancelled_orders)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-inbox fa-2x mb-3 text-muted d-block"></i>
                        No cancelled orders found for the selected date range
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $grouped_orders = [];
                    // Group orders by table number AND cancellation time
                    foreach ($cancelled_orders as $order) {
                        // Create a key using table number AND created_at timestamp (rounded to minutes)
                        $timestamp = strtotime($order['created_at']);
                        $timeKey = date('Y-m-d H:i', $timestamp); // Remove seconds for grouping
                        $key = $order['table_number'] . '_' . $timeKey;
                        
                        if (!isset($grouped_orders[$key])) {
                            $grouped_orders[$key] = [
                                'id' => $order['id'],
                                'table_number' => $order['table_number'],
                                'items_list' => $order['items_list'],
                                'item_count' => $order['item_count'],
                                'special_instructions' => $order['special_instructions'],
                                'total_amount' => $order['total_amount'],
                                'created_at' => $order['created_at']
                            ];
                        }
                    }

                    // Sort by created_at date (newest first) and then by table number
                    uasort($grouped_orders, function($a, $b) {
                        $dateCompare = strtotime($b['created_at']) - strtotime($a['created_at']);
                        if ($dateCompare === 0) {
                            return $a['table_number'] - $b['table_number'];
                        }
                        return $dateCompare;
                    });

                    foreach ($grouped_orders as $order): 
                    ?>
                    <tr>
                        <td>
                            <span class="order-id">
                                #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                            </span>
                        </td>
                        <td>Table <?php echo htmlspecialchars($order['table_number']); ?></td>
                        <td>
                            <div class="order-items">
                                <span class="item-count"><?php echo $order['item_count']; ?> items</span>
                                <div class="item-details small text-muted">
                                    <?php 
                                    $items = explode(', ', $order['items_list']);
                                    $itemsList = [];
                                    foreach($items as $item) {
                                        $itemsList[] = preg_replace('/ - Note:.*$/', '', $item);
                                    }
                                    echo implode(', ', $itemsList);
                                    ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="special-instructions">
                                <?php 
                                $items = explode(', ', $order['items_list']);
                                $instructions = [];
                                foreach($items as $item) {
                                    if(strpos($item, ' - Note: ') !== false) {
                                        list($itemName, $note) = explode(' - Note: ', $item);
                                        $instructions[] = "<div class='instruction-item'><strong>" . htmlspecialchars($itemName) . "</strong>: " . htmlspecialchars($note) . "</div>";
                                    }
                                }
                                echo !empty($instructions) ? implode('', $instructions) : '<span class="text-muted">No special instructions</span>';
                                ?>
                            </div>
                        </td>
                        <td>
                            <span class="order-amount">RM <?php echo number_format($order['total_amount'], 2); ?></span>
                        </td>
                        <td>
                            <span class="status-badge cancelled">
                                <i class="fas fa-times-circle"></i>
                                Cancelled
                            </span>
                        </td>
                        <td>
                            <span class="order-date">
                                <?php 
                                echo date('d M Y, h:i A', strtotime($order['created_at']));
                                ?>
                            </span>
                        </td>
                        <td>
                            <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="total-orders">
            Total Cancelled Orders: <?php echo count($cancelled_orders); ?>
        </div>
        <a href="export_cancelled_orders.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
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