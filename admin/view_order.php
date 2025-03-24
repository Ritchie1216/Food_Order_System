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

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get order details
$order = $orderModel->getOrder($order_id);

// Redirect if order not found
if (!$order) {
    header('Location: completed_orders.php');
    exit();
}

// Set page title
$page_title = "Order #" . str_pad($order_id, 4, '0', STR_PAD_LEFT);

// Start output buffering
ob_start();

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];
?>

<div class="container-fluid py-4">
    <!-- Back button and header -->
    <div class="d-flex align-items-center mb-4">
        <a href="completed_orders.php" class="btn btn-link text-muted p-0 me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="page-title mb-0">
            Order Details #<?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?>
        </h1>
    </div>

    <div class="row">
        <!-- Order Summary Card -->
        <div class="col-12 col-lg-4 mb-4">
            <div class="card order-summary">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <div class="summary-item">
                        <span class="label">Status</span>
                        <span class="status-badge <?php echo strtolower($order['status']); ?>">
                            <i class="fas fa-<?php echo $order['status'] === 'cancelled' ? 'times' : 'check'; ?>-circle"></i>
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Table Number</span>
                        <span class="value">Table <?php echo htmlspecialchars($order['table_number']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Order Date</span>
                        <span class="value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Total Amount</span>
                        <span class="value amount">RM <?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items Card -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card order-items">
                <div class="card-body">
                    <h5 class="card-title">Order Items</h5>
                    <div class="table-responsive">
                        <table class="table items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                foreach ($order['items'] as $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                <tr>
                                    <td>
                                        <div class="item-info">
                                            <div class="item-image">
                                                <?php if (!empty($item['image_path'])): 
                                                    $image_path = str_replace('uploads/menu_items/', '', $item['image_path']);
                                                ?>
                                                <img src="../uploads/menu_items/<?php echo htmlspecialchars($image_path); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                <i class="fas fa-utensils"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-details">
                                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">RM <?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-end">RM <?php echo number_format($item_total, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <?php
                                // Get current tax settings
                                $settings_query = "SELECT tax_rate FROM settings LIMIT 1";
                                $settings_stmt = $db->prepare($settings_query);
                                $settings_stmt->execute();
                                $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
                                
                                // Get tax rate from settings or use default
                                $tax_rate = floatval($settings['tax_rate'] ?? 9);
                                
                                // Calculate SST and total
                                $sst_amount = $subtotal * ($tax_rate / 100);
                                $total_with_sst = $subtotal + $sst_amount;
                                ?>
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal:</td>
                                    <td class="text-end">RM <?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">SST (<?php echo number_format($tax_rate, 1); ?>%):</td>
                                    <td class="text-end">RM <?php echo number_format($sst_amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total Amount:</td>
                                    <td class="text-end fw-bold">RM <?php echo number_format($total_with_sst, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
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
        --color-warning: #FFB6C1;
        --color-danger: #FF6B6B;
        --color-info: #87CEEB;
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

    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--color-text-light);
        text-decoration: none;
        padding: 0.75rem 1.5rem;
        border-radius: 15px;
        background: var(--color-surface-light);
        border: 1px solid var(--color-border);
        transition: var(--transition);
        font-weight: 500;
    }

    .back-link:hover {
        transform: translateX(-5px);
        background: var(--color-surface);
        color: var(--color-primary);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--color-primary);
        margin: 0;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 2s infinite linear;
    }

    @keyframes shimmer {
        0% {
            background-position: -200% center;
        }
        100% {
            background-position: 200% center;
        }
    }

    .card {
        background: var(--color-surface);
        border-radius: 20px;
        border: 1px solid var(--color-border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        overflow: hidden;
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
    }

    .card-body {
        padding: 2rem;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--color-primary);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .card-title::before {
        content: "";
        width: 4px;
        height: 24px;
        background: linear-gradient(to bottom, var(--color-primary), var(--color-secondary));
        border-radius: 2px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem;
        background: var(--color-surface-light);
        border-radius: 15px;
        margin-bottom: 1rem;
        border: 1px solid var(--color-border);
        transition: var(--transition);
    }

    .summary-item:hover {
        transform: translateX(5px);
        background: rgba(212, 175, 55, 0.1);
    }

    .summary-item .label {
        color: var(--color-text-light);
        font-weight: 500;
    }

    .summary-item .value {
        font-weight: 600;
        color: var(--color-primary);
    }

    .summary-item .amount {
        font-size: 1.25rem;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        font-size: 1rem;
        transition: var(--transition);
    }

    .status-badge.completed {
        background: rgba(152, 251, 152, 0.1);
        color: var(--color-success);
    }

    .status-badge.pending {
        background: rgba(255, 182, 193, 0.1);
        color: var(--color-warning);
    }

    .status-badge.processing {
        background: rgba(135, 206, 235, 0.1);
        color: var(--color-info);
    }

    .status-badge.cancelled {
        background: rgba(255, 107, 107, 0.1);
        color: var(--color-danger);
    }

    .status-badge:hover {
        transform: translateY(-2px);
        filter: brightness(1.2);
    }

    .items-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .items-table th {
        background: var(--color-surface-light);
        padding: 1.25rem;
        font-weight: 600;
        color: var(--color-primary);
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--color-border);
    }

    .items-table td {
        padding: 1.25rem;
        border-bottom: 1px solid var(--color-border);
        color: var(--color-text);
    }

    .items-table tr:hover td {
        background: rgba(212, 175, 55, 0.05);
    }

    .item-info {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .item-image {
        width: 100px;
        height: 100px;
        border-radius: 15px;
        background: var(--color-surface-light);
        overflow: hidden;
        border: 1px solid var(--color-border);
        transition: var(--transition);
    }

    .item-image:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-image i {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--color-primary);
    }

    .item-name {
        font-weight: 600;
        color: var(--color-text);
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    tfoot tr:not(:last-child) td {
        color: var(--color-text-light);
        font-size: 0.95rem;
    }

    tfoot tr:last-child td {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--color-primary);
        border-top: 2px solid var(--color-border);
    }

    @media (max-width: 1200px) {
        .card-body {
            padding: 1.5rem;
        }

        .item-image {
            width: 80px;
            height: 80px;
        }
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .items-table {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .item-image {
            width: 60px;
            height: 60px;
        }

        .summary-item {
            padding: 1rem;
        }
    }

    @media (max-width: 480px) {
        .item-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
    }

    /* Animation classes */
    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }

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

    .slide-in {
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
</style>';

// Add custom JavaScript
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Add animation classes on load
    document.querySelectorAll(".card").forEach((card, index) => {
        setTimeout(() => {
            card.classList.add("fade-in");
        }, index * 100);
    });

    // Add hover effects for summary items
    document.querySelectorAll(".summary-item").forEach(item => {
        item.addEventListener("mouseenter", () => {
            item.style.transform = "translateX(5px)";
        });
        item.addEventListener("mouseleave", () => {
            item.style.transform = "translateX(0)";
        });
    });

    // Add hover effects for item images
    document.querySelectorAll(".item-image").forEach(image => {
        image.addEventListener("mouseenter", () => {
            image.style.transform = "scale(1.05)";
        });
        item.addEventListener("mouseleave", () => {
            image.style.transform = "scale(1)";
        });
    });
});
</script>';

// Include the layout
include 'includes/layout.php';
?> 