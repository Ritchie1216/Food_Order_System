<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../classes/Dashboard.php');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$dashboard = new Dashboard($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user info from session or database
$username = $_SESSION['username'] ?? 'Admin';
$page_title = 'Dashboard';

try {
    // Fetch all dashboard data in one go
    $dashboardData = $dashboard->getDashboardData();
    $stats = $dashboardData['stats'];
    $recentOrders = $dashboardData['recentOrders'];
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Custom CSS for modern design
$extra_css = '
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    font-family: "Inter", sans-serif;
    background: var(--background);
}

.dashboard-wrapper {
    padding: 1.5rem;
}

.welcome-section {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    box-shadow: 0 4px 20px rgba(44, 62, 80, 0.15);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    color: white;
}

.welcome-section::before {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: url("data:image/svg+xml,%3Csvg width=\'300\' height=\'300\' viewBox=\'0 0 300 300\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Ccircle cx=\'150\' cy=\'150\' r=\'150\' fill=\'rgba(255,255,255,0.1)\'/%3E%3C/svg%3E") no-repeat;
    transform: translate(50%, -50%);
    z-index: 1;
}

.welcome-content {
    position: relative;
    z-index: 2;
}

.welcome-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface);
    box-shadow: 0 2px 12px rgba(44, 62, 80, 0.08);
    border: 1px solid rgba(44, 62, 80, 0.05);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 25px rgba(0,0,0,0.1);
}

.stat-card::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: currentColor;
    opacity: 0.05;
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    background: var(--primary);
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(44, 62, 80, 0.15);
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(10deg);
}

.stat-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--on-surface-medium);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--on-surface);
    margin-bottom: 0.5rem;
}

.stat-change {
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stat-change.positive {
    color: var(--success);
}

.stat-change.negative {
    color: var(--danger);
}

.recent-orders-section {
    background: var(--surface);
    box-shadow: 0 2px 12px rgba(44, 62, 80, 0.08);
    border: 1px solid rgba(44, 62, 80, 0.05);
    border-radius: 1rem;
    padding: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--background);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--on-surface);
}

.view-all-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: var(--primary);
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(44, 62, 80, 0.15);
}

.view-all-btn:hover {
    background: var(--primary-dark);
    transform: translateX(5px);
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.orders-table th {
    padding: 1rem;
    font-weight: 600;
    color: var(--on-surface);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    background: var(--surface-hover);
}

.orders-table td {
    padding: 1rem;
    color: var(--on-surface);
    border-bottom: 1px solid var(--background);
}

.orders-table tbody tr {
    transition: all 0.3s ease;
}

.orders-table tbody tr:hover {
    background: var(--surface-hover);
    transform: scale(1.01);
}

.order-id {
    font-weight: 600;
    color: var(--primary);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.completed {
    background: rgba(29, 233, 182, 0.1);
    color: var(--success);
}

.status-badge.processing {
    background: rgba(29, 233, 182, 0.1);
    color: var(--info);
}

.status-badge.pending {
    background: rgba(149, 165, 166, 0.1);
    color: var(--warning);
}

@media (max-width: 768px) {
    .dashboard-wrapper {
        padding: 1rem;
    }

    .welcome-section {
        padding: 1.5rem;
    }

    .welcome-title {
        font-size: 1.5rem;
    }

    .stat-card {
        padding: 1.25rem;
    }

    .orders-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .status-badge {
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .view-all-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>';

// Start output buffering
ob_start();
?>

<div class="dashboard-wrapper">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($username); ?></h1>
            <p class="welcome-subtitle"><?php echo date("l, F j, Y"); ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h6 class="stat-title">Today's Orders</h6>
            <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>12.5% vs yesterday</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h6 class="stat-title">Today's Revenue</h6>
            <div class="stat-value">RM <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>8.2% vs yesterday</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chair"></i>
            </div>
            <h6 class="stat-title">Active Tables</h6>
            <div class="stat-value"><?php echo $stats['active_tables'] ?? 0; ?></div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>4.3% vs last hour</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <h6 class="stat-title">Menu Items</h6>
            <div class="stat-value"><?php echo $stats['total_items'] ?? 0; ?></div>
            <div class="stat-change negative">
                <i class="fas fa-arrow-down"></i>
                <span>2.1% vs last week</span>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="recent-orders-section">
        <div class="section-header">
            <h5 class="section-title">Recent Orders</h5>
            <a href="orders.php" class="view-all-btn">
                View All Orders
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>
                            <span class="order-id">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </td>
                        <td>Table <?php echo htmlspecialchars($order['table_name']); ?></td>
                        <td><?php echo $order['item_count']; ?> items</td>
                        <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-badge <?php echo $order['status']; ?>">
                                <i class="fas fa-<?php 
                                    echo match($order['status']) {
                                        'completed' => 'check-circle',
                                        'processing' => 'clock',
                                        'pending' => 'hourglass',
                                        default => 'info-circle'
                                    };
                                ?>"></i>
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for animations and interactivity
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Animate stats cards on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.transform = "translateY(0)";
                entry.target.style.opacity = "1";
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll(".stat-card").forEach(card => {
        card.style.transform = "translateY(20px)";
        card.style.opacity = "0";
        card.style.transition = "all 0.5s ease";
        observer.observe(card);
    });

    // Add hover effect to table rows
    document.querySelectorAll(".orders-table tbody tr").forEach(row => {
        row.addEventListener("mouseenter", function() {
            this.style.transform = "scale(1.01)";
            this.style.transition = "all 0.3s ease";
        });

        row.addEventListener("mouseleave", function() {
            this.style.transform = "scale(1)";
        });
    });

    // Add click effect to status badges
    document.querySelectorAll(".status-badge").forEach(badge => {
        badge.addEventListener("click", function() {
            this.style.transform = "scale(0.95)";
            setTimeout(() => {
                this.style.transform = "scale(1)";
            }, 100);
        });
    });
});
</script>';

include 'includes/layout.php';
?> 