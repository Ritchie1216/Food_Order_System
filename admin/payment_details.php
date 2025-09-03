<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get date range from query parameters or default to last 30 days
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Get payments with order details
try {
    // First, get all payments within the date range
    $sql = "SELECT p.payment_id, p.order_id, p.amount, p.payment_status, 
            p.payment_date, p.cash_received, p.change_amount,
            o.id as order_id, t.table_number, o.created_at as order_date,
            GROUP_CONCAT(CONCAT(m.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_list,
            SUM(oi.quantity) as item_count,
            SUM(oi.quantity * oi.price) as order_total,
            o.total_amount as order_total_with_sst
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            LEFT JOIN tables t ON o.table_id = t.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE p.payment_date BETWEEN ? AND ?
            GROUP BY p.payment_id
            ORDER BY p.payment_date DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group payments by date, time (to the minute) and table
    $grouped_payments = [];
    foreach ($all_payments as $payment) {
        // Create a key using date, time (minutes only), and table number
        $timestamp = strtotime($payment['payment_date']);
        $timeKey = date('Y-m-d H:i', $timestamp); // Format to minutes only
        $key = $timeKey . '_' . $payment['table_number'];
        
        if (!isset($grouped_payments[$key])) {
            $grouped_payments[$key] = [
                'payment_day' => date('Y-m-d', $timestamp),
                'payment_time' => date('H:i:s', $timestamp),
                'table_number' => $payment['table_number'],
                'payment_count' => 0,
                'payment_ids' => [],
                'order_ids' => [],
                'payment_amounts' => [],
                'total_amount' => 0,
                'cash_received' => $payment['cash_received'],
                'change_amount' => $payment['change_amount'],
                'payment_statuses' => [],
                'latest_payment_date' => $payment['payment_date'],
                'items_list' => [],
                'item_count' => 0
            ];
        }
        
        // Add payment details
        $grouped_payments[$key]['payment_count']++;
        $grouped_payments[$key]['payment_ids'][] = $payment['payment_id'];
        $grouped_payments[$key]['order_ids'][] = $payment['order_id'];
        $grouped_payments[$key]['payment_amounts'][] = $payment['amount'];
        $grouped_payments[$key]['payment_statuses'][] = $payment['payment_status'];
        
        // Calculate total amount - prioritize payment amount, then order total with SST, then calculated order total
        $amount = floatval($payment['amount']);
        if ($amount > 0) {
            $grouped_payments[$key]['total_amount'] += $amount;
        } else {
            // If payment amount is 0, use the order total with SST (includes tax)
            $order_total_with_sst = floatval($payment['order_total_with_sst']);
            if ($order_total_with_sst > 0) {
                $grouped_payments[$key]['total_amount'] += $order_total_with_sst;
            } else {
                // Fallback to calculated order total without tax
                $order_total = floatval($payment['order_total']);
                if ($order_total > 0) {
                    $grouped_payments[$key]['total_amount'] += $order_total;
                }
            }
        }
        
        // Add items to the items list without duplicates
        $items = explode(', ', $payment['items_list']);
        foreach ($items as $item) {
            if (!in_array($item, $grouped_payments[$key]['items_list'])) {
                $grouped_payments[$key]['items_list'][] = $item;
            }
        }
        $grouped_payments[$key]['item_count'] += $payment['item_count'];
        
        // Update latest payment date if this one is newer
        if (strtotime($payment['payment_date']) > strtotime($grouped_payments[$key]['latest_payment_date'])) {
            $grouped_payments[$key]['latest_payment_date'] = $payment['payment_date'];
            $grouped_payments[$key]['cash_received'] = $payment['cash_received'];
            $grouped_payments[$key]['change_amount'] = $payment['change_amount'];
        }
    }
    
    // Convert to indexed array and prepare for display
    $payments = [];
    foreach ($grouped_payments as $group) {
        // Convert arrays to strings for display
        $group['payment_ids'] = implode(',', $group['payment_ids']);
        $group['order_ids'] = implode(',', $group['order_ids']);
        $group['payment_amounts_array'] = $group['payment_amounts'];
        $group['items_list'] = implode(', ', $group['items_list']);
        
        // Ensure change amount is calculated correctly for grouped payments
        if (count($group['payment_amounts_array']) > 1) {
            $group['change_amount'] = floatval($group['cash_received']) - $group['total_amount'];
        }
        
        // Ensure total_amount is properly calculated
        if (!isset($group['total_amount']) || $group['total_amount'] <= 0) {
            // Fallback: calculate from payment amounts array
            $group['total_amount'] = array_sum(array_map('floatval', $group['payment_amounts']));
        }
        
        $payments[] = $group;
    }
    
    // Sort by latest payment date (newest first)
    usort($payments, function($a, $b) {
        return strtotime($b['latest_payment_date']) - strtotime($a['latest_payment_date']);
    });
    
    // Calculate overall totals
    $total_payments = 0;
    $total_amount = 0;
    foreach ($payments as $payment) {
        $total_payments += $payment['payment_count'];
        $total_amount += $payment['total_amount'];
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $payments = [];
    $total_payments = 0;
    $total_amount = 0;
}

// Set page title
$page_title = "Payment Details (Newest to Oldest)";

// Start output buffering
ob_start();
?>

<!-- Page content -->
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="header-text">
                <h1>Payment Management</h1>
                <p class="subtitle">Track and manage all payment transactions</p>
            </div>
        </div>
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Payments</h3>
                    <p class="stat-value"><?php echo $total_payments; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">RM <?php echo number_format($total_amount, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-section">
        <form class="date-range-form">
            <div class="date-inputs">
                <div class="input-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="input-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
            </div>
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i>
                <span>Apply Filter</span>
            </button>
        </form>
        
    </div>

    <div class="payments-grid">
        <?php if (empty($payments)): ?>
        <div class="empty-state">
            <i class="fas fa-receipt empty-icon"></i>
            <h3>No Payments Found</h3>
            <p>No payment records found for the selected date range</p>
        </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
            <div class="payment-card" data-payment-id="<?php echo explode(',', $payment['payment_ids'])[0]; ?>">
                <div class="payment-header">
                    <div class="payment-main-info">
                        <div class="payment-id-section">
                            <?php 
                            $payment_ids = explode(',', $payment['payment_ids']);
                            if (count($payment_ids) > 1): ?>
                                <span class="payment-count"><?php echo count($payment_ids); ?> payments</span>
                                <div class="payment-ids">
                                    <?php 
                                    $formatted_ids = array_map(function($id) {
                                        return '#' . str_pad($id, 4, '0', STR_PAD_LEFT);
                                    }, $payment_ids);
                                    echo implode(', ', array_slice($formatted_ids, 0, 3));
                                    if (count($formatted_ids) > 3) echo ', ...';
                                    ?>
                                </div>
                            <?php else: ?>
                                <span class="payment-id">#<?php echo str_pad($payment_ids[0], 4, '0', STR_PAD_LEFT); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="payment-status">
                            <?php 
                            $all_completed = true;
                            foreach ($payment['payment_statuses'] as $status) {
                                if ($status !== 'completed') {
                                    $all_completed = false;
                                    break;
                                }
                            }
                            if ($all_completed): ?>
                            <span class="status-badge status-completed">
                                <i class="fas fa-check-circle"></i>
                                Completed
                            </span>
                            <?php else: ?>
                            <span class="status-badge status-mixed">
                                <i class="fas fa-clock"></i>
                                Mixed Status
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="payment-time">
                        <i class="far fa-clock"></i>
                        <?php echo date('d M Y, h:i A', strtotime($payment['latest_payment_date'])); ?>
                    </div>
                </div>

                <div class="payment-details">
                    <div class="detail-row">
                        <div class="detail-group">
                            <label>Table</label>
                            <span class="table-number">Table <?php echo htmlspecialchars($payment['table_number']); ?></span>
                        </div>
                        <div class="detail-group">
                            <label>Orders</label>
                            <?php 
                            $order_ids = explode(',', $payment['order_ids']);
                            if (count($order_ids) > 1): ?>
                                <div class="orders-info">
                                    <span class="order-count"><?php echo count($order_ids); ?> orders</span>
                                    <div class="order-links">
                                        <?php 
                                        $formatted_order_ids = array_map(function($id) {
                                            return '<a href="view_order.php?id=' . $id . '" class="order-link">#' . 
                                                   str_pad($id, 4, '0', STR_PAD_LEFT) . '</a>';
                                        }, $order_ids);
                                        echo implode(', ', array_slice($formatted_order_ids, 0, 3));
                                        if (count($formatted_order_ids) > 3) echo ', ...';
                                        ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="view_order.php?id=<?php echo $order_ids[0]; ?>" class="order-link">
                                    #<?php echo str_pad($order_ids[0], 4, '0', STR_PAD_LEFT); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="items-section">
                        <label>Items (<?php echo intval($payment['item_count']); ?>)</label>
                        <div class="items-list">
                            <?php echo !empty($payment['items_list']) ? htmlspecialchars($payment['items_list']) : 'No items'; ?>
                        </div>
                    </div>

                    <div class="payment-amounts">
                        <div class="amount-row">
                            <div class="amount-group">
                                <label>Total Amount</label>
                                <span class="amount total">RM <?php echo number_format($payment['total_amount'], 2); ?></span>
                            </div>
                            <div class="amount-group">
                                <label>Cash Received</label>
                                <span class="amount received">RM <?php echo number_format($payment['cash_received'], 2); ?></span>
                            </div>
                            <div class="amount-group">
                                <label>Change</label>
                                <span class="amount change">RM <?php echo number_format($payment['change_amount'], 2); ?></span>
                            </div>
                        </div>
                        <?php if (count($payment_ids) > 1): ?>
                        <div class="amount-details">
                            <?php 
                            $amount_details = array_map(function($amt) {
                                return 'RM ' . number_format(floatval($amt), 2);
                            }, $payment['payment_amounts_array']);
                            echo implode(', ', array_slice($amount_details, 0, 3));
                            if (count($amount_details) > 3) echo ', ...';
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="payment-actions">
                    <button type="button" class="action-btn print-receipt" data-payment-id="<?php echo $payment_ids[0]; ?>">
                        <i class="fas fa-print"></i>
                        <span>Print Receipt</span>
                    </button>
                    
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="pagination-info">
        Showing <?php echo count($payments); ?> grouped entries (<?php echo $total_payments; ?> total payments)
    </div>
</div>

<?php
$content = ob_get_clean();

// Add custom CSS
$extra_css = '
<style>
:root {
    --primary: #d4af37;
    --primary-dark: #b8860b;
    --success: #2d6a4f;
    --warning: #92400e;
    --danger: #991b1b;
    --background: #121212;
    --surface: #1a1a1a;
    --surface-2: #242424;
    --surface-3: #2d2d2d;
    --text-primary: #d4d4d4;
    --text-secondary: #a6a6a6;
    --text-tertiary: #808080;
    --border: rgba(212, 175, 55, 0.1);
    --shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.dashboard-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    margin-bottom: 2rem;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.logo {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo i {
    font-size: 28px;
    color: var(--background);
}

.logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.header-text h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.subtitle {
    color: var(--text-secondary);
    margin: 0.5rem 0 0;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.stat-card {
    background: var(--surface);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    border-color: rgba(212, 175, 55, 0.2);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--background);
}

.stat-details h3 {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0 0 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.filter-section {
    background: var(--surface);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.date-range-form {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.date-inputs {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.input-group {
    flex: 1;
}

.input-group label {
    display: block;
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.input-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface-2);
    color: var(--text-primary);
    font-size: 0.95rem;
}

.filter-btn, .export-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.filter-btn {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: var(--background);
    border: none;
}

.export-btn {
    background: var(--surface-2);
    color: var(--text-primary);
    border: 1px solid var(--border);
    text-decoration: none;
}

.filter-btn:hover, .export-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.payments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.payment-card {
    background: var(--surface);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.3s ease;
}

.payment-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    border-color: rgba(212, 175, 55, 0.2);
}

.payment-header {
    background: var(--surface-2);
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
}

.payment-main-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.payment-id-section {
    display: flex;
    flex-direction: column;
}

.payment-count {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--primary);
}

.payment-ids {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.payment-time {
    font-size: 0.875rem;
    color: var(--text-tertiary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-completed {
    background: rgba(45, 106, 79, 0.2);
    color: #4ade80;
    border: 1px solid rgba(74, 222, 128, 0.2);
}

.status-mixed {
    background: rgba(146, 64, 14, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.2);
}

.payment-details {
    padding: 1.25rem;
}

.detail-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.detail-group label {
    display: block;
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.table-number {
    font-size: 1rem;
    color: var(--text-primary);
    font-weight: 500;
}

.orders-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-count {
    color: var(--text-primary);
}

.order-links {
    font-size: 0.875rem;
}

.order-link {
    color: var(--primary);
    text-decoration: none;
    transition: all 0.3s ease;
}

.order-link:hover {
    text-decoration: underline;
}

.items-section {
    background: var(--surface-2);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.25rem;
}

.items-section label {
    display: block;
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.items-list {
    color: var(--text-primary);
    font-size: 0.95rem;
    line-height: 1.5;
}

.payment-amounts {
    background: var(--surface-2);
    padding: 1rem;
    border-radius: 8px;
}

.amount-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
}

.amount-group label {
    display: block;
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.amount {
    display: block;
    font-size: 1rem;
    font-weight: 600;
}

.amount.total {
    color: var(--primary);
}

.amount.received {
    color: var(--text-primary);
}

.amount.change {
    color: var(--text-secondary);
}

.amount-details {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
    font-size: 0.875rem;
    color: var(--text-tertiary);
}

.payment-actions {
    padding: 1.25rem;
    background: var(--surface-2);
    border-top: 1px solid var(--border);
    display: flex;
    gap: 1rem;
}

.action-btn {
    flex: 1;
    padding: 0.75rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
}

.action-btn.print-receipt {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: var(--background);
    border: none;
}

.action-btn.view-details {
    background: var(--surface-3);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: var(--surface);
    border-radius: 12px;
    border: 1px solid var(--border);
}

.empty-icon {
    font-size: 3rem;
    color: var(--text-tertiary);
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: var(--text-primary);
    margin: 0 0 0.5rem;
}

.empty-state p {
    color: var(--text-secondary);
    margin: 0;
}

.pagination-info {
    text-align: center;
    color: var(--text-secondary);
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }

    .logo-section {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }

    .filter-section {
        flex-direction: column;
    }

    .date-range-form {
        flex-direction: column;
        width: 100%;
    }

    .date-inputs {
        flex-direction: column;
        width: 100%;
    }

    .filter-btn, .export-btn {
        width: 100%;
        justify-content: center;
    }

    .payments-grid {
        grid-template-columns: 1fr;
    }

    .payment-main-info {
        flex-direction: column;
        gap: 1rem;
    }

    .amount-row {
        grid-template-columns: 1fr;
    }

    .payment-actions {
        flex-direction: column;
    }
}
</style>';

// Add custom JavaScript
$extra_js = '
<script>
document.querySelector(".date-range-form").addEventListener("submit", function(e) {
    e.preventDefault();
    const startDate = document.getElementById("start_date").value;
    const endDate = document.getElementById("end_date").value;
    
    const filterBtn = document.querySelector(".filter-btn");
    const originalBtnText = filterBtn.innerHTML;
    filterBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i><span>Loading...</span>`;
    filterBtn.disabled = true;

    window.location.href = `payment_details.php?start_date=${startDate}&end_date=${endDate}`;
});

document.addEventListener("click", function(e) {
    if (e.target.classList.contains("print-receipt") || e.target.closest(".print-receipt")) {
        const button = e.target.classList.contains("print-receipt") ? e.target : e.target.closest(".print-receipt");
        const paymentId = button.getAttribute("data-payment-id");
        
        const originalBtnText = button.innerHTML;
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i><span>Printing...</span>`;
        button.disabled = true;
        
        printPaymentReceipt(paymentId);
        
        setTimeout(function() {
            button.innerHTML = originalBtnText;
            button.disabled = false;
        }, 1500);
    }
});

function printPaymentReceipt(paymentId) {
    const iframe = document.createElement("iframe");
    iframe.style.display = "none";
    
    iframe.onload = function() {
        try {
            setTimeout(function() {
                iframe.contentWindow.print();
                setTimeout(function() {
                    document.body.removeChild(iframe);
                }, 3000);
            }, 500);
        } catch (error) {
            console.error("Error printing receipt:", error);
            alert("There was an error printing the receipt. Please try again.");
            document.body.removeChild(iframe);
        }
    };
    
    iframe.src = "print_receipt.php?payment_id=" + paymentId;
    document.body.appendChild(iframe);
}

// Add card hover animations
document.querySelectorAll(".payment-card").forEach(card => {
    card.addEventListener("mouseenter", function() {
        this.style.transform = "translateY(-5px)";
    });
    
    card.addEventListener("mouseleave", function() {
        this.style.transform = "translateY(0)";
    });
});

// Add scroll animations
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add("fade-in");
        }
    });
}, {
    threshold: 0.1
});

document.querySelectorAll(".payment-card").forEach(card => {
    observer.observe(card);
});

// Add date range validation
const startDateInput = document.getElementById("start_date");
const endDateInput = document.getElementById("end_date");

function validateDateRange() {
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    const today = new Date();
    
    if (startDate > endDate) {
        endDateInput.value = startDateInput.value;
    }
    
    if (endDate > today) {
        endDateInput.value = today.toISOString().split("T")[0];
    }
}

startDateInput.addEventListener("change", validateDateRange);
endDateInput.addEventListener("change", validateDateRange);

// Add search functionality
const searchInput = document.createElement("input");
searchInput.type = "text";
searchInput.className = "search-input";
searchInput.placeholder = "Search payments...";
document.querySelector(".filter-section").insertBefore(searchInput, document.querySelector(".export-section"));

searchInput.addEventListener("input", function(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    document.querySelectorAll(".payment-card").forEach(card => {
        const cardText = card.textContent.toLowerCase();
        const shouldShow = cardText.includes(searchTerm);
        
        card.style.display = shouldShow ? "block" : "none";
        
        if (shouldShow) {
            card.classList.add("fade-in");
        }
    });
    
    // Update pagination info
    const visibleCards = document.querySelectorAll(".payment-card[style*=\'display: block\']").length;
    document.querySelector(".pagination-info").textContent = 
        `Showing ${visibleCards} filtered entries`;
});

// Add keyboard shortcuts
document.addEventListener("keydown", function(e) {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === "f") {
        e.preventDefault();
        searchInput.focus();
    }
    
    // Esc to clear search
    if (e.key === "Escape") {
        searchInput.value = "";
        searchInput.dispatchEvent(new Event("input"));
    }
});

// Add loading states for interactive elements
document.querySelectorAll(".action-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        if (!this.classList.contains("print-receipt")) {
            const originalText = this.innerHTML;
            this.innerHTML = `<i class="fas fa-spinner fa-spin"></i><span>Loading...</span>`;
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 1500);
        }
    });
});

// Add tooltip functionality
const tooltip = document.createElement("div");
tooltip.className = "tooltip";
document.body.appendChild(tooltip);

document.querySelectorAll("[data-tooltip]").forEach(element => {
    element.addEventListener("mouseenter", function(e) {
        const text = this.getAttribute("data-tooltip");
        tooltip.textContent = text;
        tooltip.style.display = "block";
        
        const rect = this.getBoundingClientRect();
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + "px";
        tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + "px";
    });
    
    element.addEventListener("mouseleave", function() {
        tooltip.style.display = "none";
    });
});
</script>';

// Include the layout
include 'includes/layout.php';
?> 