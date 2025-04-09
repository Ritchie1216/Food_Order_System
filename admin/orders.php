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

// Get current page and search parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$per_page = 10;

// Update order status if requested
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    if ($orderModel->updateStatus($order_id, $new_status)) {
        $success_message = "Order status updated successfully";
    } else {
        $error_message = "Failed to update order status";
    }
}

try {
    // Update the SQL query to include payment status check
    $sql = "SELECT o.id, o.table_id, t.table_number, o.total_amount, o.status, o.created_at,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
            GROUP_CONCAT(
                CONCAT(m.name, ' x', oi.quantity) 
                ORDER BY m.name 
                SEPARATOR ', '
            ) as items_list
            FROM orders o
            LEFT JOIN tables t ON o.table_id = t.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE o.status != 'completed' AND o.status != 'cancelled'
            GROUP BY o.id, o.table_id, t.table_number, o.total_amount, o.status, o.created_at
            ORDER BY o.created_at DESC";
    $orders = $orderModel->getOrders($page, $per_page, $search);
    $total_orders = $orderModel->getTotalOrders();
    $total_pages = ceil($total_orders / $per_page);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $orders = [];
    $total_pages = 0;
}

$page_title = "Orders";
ob_start();
?>

<!-- Page content -->
<div class="container-fluid py-4">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-shopping-cart"></i>
            Active Orders
        </h1>
        <div class="header-stats">
            <div class="stat-item">
                <i class="fas fa-clock"></i>
                <span>Pending: <?php echo count(array_filter($orders, fn($order) => $order['status'] == 'pending')); ?></span>
            </div>
            <div class="stat-item">
                <i class="fas fa-spinner"></i>
                <span>Processing: <?php echo count(array_filter($orders, fn($order) => $order['status'] == 'processing')); ?></span>
            </div>
        </div>
    </div>

    <div class="search-container">
        <form class="search-form">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" name="search" placeholder="Search orders..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
                Search
            </button>
        </form>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="orders-grid">
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <h3>No Active Orders</h3>
            <p>There are currently no active orders in the system.</p>
        </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-id">
                        <i class="fas fa-hashtag"></i>
                        #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                    </div>
                    <span class="status-badge <?php echo strtolower($order['status']); ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                
                <div class="order-body">
                    <div class="order-info">
                        <div class="info-item">
                            <i class="fas fa-chair"></i>
                            <span>Table <?php echo htmlspecialchars($order['table_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="order-items">
                        <div class="items-header">
                            <i class="fas fa-utensils"></i>
                            <span><?php echo $order['item_count'] > 0 ? $order['item_count'] . ' items' : 'No items'; ?></span>
                        </div>
                        <div class="items-list">
                            <?php 
                            if ($order['item_count'] > 0 && !empty($order['items_list'])) {
                                echo htmlspecialchars($order['items_list']);
                            } else {
                                echo 'No items';
                            }
                            ?>
                        </div>
                    </div>

                    <?php if (!empty($order['special_instructions'])): ?>
                    <div class="special-instructions">
                        <div class="instructions-header">
                            <i class="fas fa-comment-alt"></i>
                            <span>Special Instructions</span>
                        </div>
                        <?php foreach ($order['special_instructions'] as $instruction): ?>
                            <div class="instruction-item">
                                <strong><?php echo htmlspecialchars($instruction['item']); ?></strong>
                                <span><?php echo htmlspecialchars($instruction['instructions']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="order-footer">
                    <div class="order-amount">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="order-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#statusModal<?php echo $order['id']; ?>">
                            <i class="fas fa-edit"></i>
                            Update
                        </button>
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Update Modal -->
            <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>
                                Update Order Status
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Select New Status</label>
                                    <select name="new_status" class="form-select">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

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

.page-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(44, 62, 80, 0.15);
    color: white;
    position: relative;
    overflow: hidden;
}

.page-header::before {
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

.page-title {
    position: relative;
    z-index: 2;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-stats {
    position: relative;
    z-index: 2;
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    backdrop-filter: blur(5px);
}

.stat-item i {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
}

.search-container {
    margin-bottom: 2rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    max-width: 600px;
    margin: 0 auto;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
}

.search-input-wrapper i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--on-surface-light);
}

.search-input-wrapper .form-control {
    padding-left: 2.5rem;
    border-radius: 12px;
    border: 1px solid rgba(44, 62, 80, 0.1);
    transition: all 0.3s ease;
    background: var(--surface);
}

.search-input-wrapper .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.15);
}

.search-btn {
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(44, 62, 80, 0.15);
}

.search-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2);
}

.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.order-card {
    background: var(--surface);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(44, 62, 80, 0.08);
    border: 1px solid rgba(44, 62, 80, 0.05);
    overflow: hidden;
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 25px rgba(44, 62, 80, 0.1);
}

.order-header {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(44, 62, 80, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-id {
    font-weight: 600;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.875rem;
}

.status-badge i {
    font-size: 0.5rem;
}

.status-badge.pending {
    background: rgba(149, 165, 166, 0.1);
    color: var(--warning);
}

.status-badge.processing {
    background: rgba(16, 185, 129, 0.1);
    color: var(--info);
}

.order-body {
    padding: 1.25rem;
}

.order-info {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--on-surface-medium);
}

.info-item i {
    color: var(--primary);
}

.order-items {
    margin-bottom: 1rem;
}

.items-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: var(--on-surface);
    font-weight: 500;
}

.items-list {
    color: var(--on-surface-medium);
    font-size: 0.9rem;
    line-height: 1.5;
}

.special-instructions {
    background: rgba(44, 62, 80, 0.05);
    padding: 1rem;
    border-radius: 12px;
}

.instructions-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    color: var(--on-surface);
    font-weight: 500;
}

.instruction-item {
    background: var(--surface);
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.instruction-item:last-child {
    margin-bottom: 0;
}

.instruction-item strong {
    color: var(--primary);
    display: block;
    margin-bottom: 0.25rem;
}

.instruction-item span {
    color: var(--on-surface-medium);
    font-size: 0.9rem;
}

.order-footer {
    padding: 1.25rem;
    border-top: 1px solid rgba(44, 62, 80, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-amount {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--on-surface);
}

.order-amount i {
    color: var(--primary);
}

.order-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-outline-primary {
    color: var(--primary);
    border-color: var(--primary);
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.btn-outline-secondary {
    color: var(--on-surface-medium);
    border-color: rgba(44, 62, 80, 0.1);
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: var(--surface-hover);
    color: var(--on-surface);
    transform: translateY(-2px);
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: var(--surface);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(44, 62, 80, 0.08);
    border: 1px solid rgba(44, 62, 80, 0.05);
}

.empty-state i {
    color: var(--on-surface-light);
}

.empty-state h3 {
    color: var(--on-surface);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--on-surface-medium);
}

/* Modal Styles */
.modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(44, 62, 80, 0.1);
}

.modal-header {
    border-bottom: 1px solid rgba(44, 62, 80, 0.05);
    padding: 1.25rem;
}

.modal-title {
    color: var(--on-surface);
    font-weight: 600;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid rgba(44, 62, 80, 0.05);
    padding: 1.25rem;
}

.form-select {
    border: 1px solid rgba(44, 62, 80, 0.1);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: var(--surface);
}

.form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.15);
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
    }

    .header-stats {
        flex-direction: column;
        gap: 1rem;
    }

    .search-form {
        flex-direction: column;
    }
    
    .search-btn {
        width: 100%;
        justify-content: center;
    }
    
    .orders-grid {
        grid-template-columns: 1fr;
    }

    .order-actions {
        flex-direction: column;
    }

    .order-actions .btn {
        width: 100%;
        text-align: center;
    }
}

/* Animations */
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

.order-card {
    animation: fadeIn 0.3s ease forwards;
}

.order-card:nth-child(2) { animation-delay: 0.1s; }
.order-card:nth-child(3) { animation-delay: 0.2s; }
.order-card:nth-child(4) { animation-delay: 0.3s; }
.order-card:nth-child(5) { animation-delay: 0.4s; }
</style>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?> 