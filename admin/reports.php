<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get date range from query parameters or default to current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');

// Get view mode (daily, weekly, monthly)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'daily';

try {
    // Function to get sales summary
    function getSalesSummary($db, $start_date, $end_date) {
        $sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(oi.quantity * mi.price), 0) as total_sales,
                COALESCE(AVG(oi.quantity * mi.price), 0) as average_sale,
                COALESCE(MAX(oi.quantity * mi.price), 0) as highest_sale
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE DATE(o.created_at) >= :start_date 
            AND DATE(o.created_at) <= :end_date
            AND o.status IN ('completed', 'paid')";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Initialize values if null
        $total_orders = $result['total_orders'] ?? 0;
        $total_sales = $result['total_sales'] ?? 0;
        $average_sale = $result['average_sale'] ?? 0;
        $highest_sale = $result['highest_sale'] ?? 0;
        
        // Format values
        return [
            'total_orders' => (int)$total_orders,
            'total_sales' => number_format((float)$total_sales, 2),
            'average_sale' => number_format((float)$average_sale, 2),
            'highest_sale' => number_format((float)$highest_sale, 2)
        ];
    }

    // Function to get top selling items
    function getTopSellingItems($db, $start_date, $end_date, $limit = 10) {
        $sql = "SELECT 
                mi.name,
                        SUM(oi.quantity) as quantity_sold,
                SUM(oi.quantity * mi.price) as total_sales
                      FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
                      JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
            AND o.status IN ('completed', 'paid')
            GROUP BY mi.id, mi.name
                      ORDER BY quantity_sold DESC
            LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get sales data for chart
    function getSalesData($db, $start_date, $end_date, $view_mode) {
        $group_by = '';
        $date_format = '';
        
        switch ($view_mode) {
            case 'weekly':
                $group_by = 'YEARWEEK(o.created_at, 1)';
                $date_format = "CONCAT('Week ', WEEK(o.created_at), ' ', DATE_FORMAT(o.created_at, '%b %Y'))";
                break;
            case 'monthly':
                $group_by = "DATE_FORMAT(o.created_at, '%Y-%m')";
                $date_format = "DATE_FORMAT(o.created_at, '%b %Y')";
                break;
            default: // daily
                $group_by = "DATE(o.created_at)";
                $date_format = "DATE_FORMAT(o.created_at, '%d %b')";
                break;
        }

        $sql = "SELECT 
                {$date_format} as period,
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(oi.quantity * mi.price), 0) as total_sales
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE DATE(o.created_at) >= :start_date 
            AND DATE(o.created_at) <= :end_date
            AND o.status IN ('completed', 'paid')
            GROUP BY {$group_by}
            ORDER BY o.created_at";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no results, return empty array with default structure
        if (empty($results)) {
            return [[
                'period' => $view_mode == 'daily' ? date('d M') : ($view_mode == 'weekly' ? 'Week ' . date('W M Y') : date('M Y')),
                'order_count' => 0,
                'total_sales' => 0
            ]];
        }
        
        return $results;
    }

    // Get all required data
    $sales_summary = getSalesSummary($db, $start_date, $end_date);
    $top_items = getTopSellingItems($db, $start_date, $end_date);
    $sales_data = getSalesData($db, $start_date, $end_date, $view_mode);

    // Prepare chart data
    $chart_labels = array_column($sales_data, 'period');
    $chart_values = array_column($sales_data, 'total_sales');
    $chart_orders = array_column($sales_data, 'order_count');

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Set page title
$page_title = "Sales Reports";

// Start output buffering
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Sales Reports
                    </h2>
                </div>
                <div class="card-body">
                    <form class="row g-3 align-items-center mb-4">
                        <div class="col-auto">
                            <label class="form-label">Date Range:</label>
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-auto">
                            <label class="form-label">View:</label>
    </div>
                        <div class="col-auto">
                <div class="btn-group" role="group">
                    <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&view=daily" 
                                   class="btn <?php echo $view_mode == 'daily' ? 'btn-primary' : 'btn-outline-primary'; ?>">Daily</a>
                    <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&view=weekly" 
                                   class="btn <?php echo $view_mode == 'weekly' ? 'btn-primary' : 'btn-outline-primary'; ?>">Weekly</a>
                    <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&view=monthly" 
                                   class="btn <?php echo $view_mode == 'monthly' ? 'btn-primary' : 'btn-outline-primary'; ?>">Monthly</a>
                </div>
            </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-2"></i>Generate Report
            </button>
                        </div>
        </form>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
    </div>
    <?php else: ?>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Total Sales</h6>
                                        <h3 class="card-title mb-0">RM <?php echo $sales_summary['total_sales']; ?></h3>
            </div>
            </div>
        </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Total Orders</h6>
                                        <h3 class="card-title mb-0"><?php echo $sales_summary['total_orders']; ?></h3>
            </div>
            </div>
        </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Average Sale</h6>
                                        <h3 class="card-title mb-0">RM <?php echo $sales_summary['average_sale']; ?></h3>
            </div>
            </div>
        </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2">Highest Sale</h6>
                                        <h3 class="card-title mb-0">RM <?php echo $sales_summary['highest_sale']; ?></h3>
            </div>
            </div>
        </div>
    </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Sales Trend</h5>
            </div>
                            <div class="card-body">
                                <canvas id="salesChart" height="300"></canvas>
        </div>
    </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Top Selling Items</h5>
                </div>
                                    <div class="card-body">
            <div class="table-responsive">
                                            <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($top_items as $item): ?>
                            <tr>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo number_format($item['quantity_sold']); ?></td>
                                <td>RM <?php echo number_format($item['total_sales'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
    --gold: #c8a165;
    --gold-light: #d4b483;
    --gold-dark: #a67c3d;
    --black: #1a1a1a;
    --black-light: #2d2d2d;
    --text-light: #ffffff;
    --text-muted: #cccccc;
    }

    body {
    background: var(--black);
    color: var(--text-light);
}

.card {
    background: var(--black-light);
    border: 1px solid rgba(200, 161, 101, 0.2);
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.card-header {
    background: linear-gradient(145deg, var(--black-light), var(--black));
    border-bottom: 1px solid rgba(200, 161, 101, 0.2);
    padding: 1.5rem;
}

.card-title {
    color: var(--gold);
    font-weight: 600;
}

.card-body {
        padding: 1.5rem;
}

.form-control {
    background: var(--black);
    border: 1px solid rgba(200, 161, 101, 0.2);
    border-radius: 8px;
    color: var(--text-light);
}

.form-control:focus {
    background: var(--black);
    border-color: var(--gold);
    color: var(--text-light);
    box-shadow: 0 0 0 0.2rem rgba(200, 161, 101, 0.25);
}

.form-label {
    color: var(--gold);
    font-weight: 500;
}

.btn {
    border-radius: 8px;
    padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

.btn-primary {
    background: linear-gradient(145deg, var(--gold), var(--gold-dark));
    border: none;
    color: var(--black);
}

.btn-primary:hover {
    background: linear-gradient(145deg, var(--gold-light), var(--gold));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(200, 161, 101, 0.3);
    }

.btn-outline-primary {
    border: 1px solid var(--gold);
    color: var(--gold);
    background: transparent;
}

.btn-outline-primary:hover {
    background: var(--gold);
    color: var(--black);
}

.table {
    color: var(--text-light);
}

.table th {
        background: rgba(200, 161, 101, 0.1);
    border-top: none;
    color: var(--gold);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.05em;
}

.table td {
    border-color: rgba(200, 161, 101, 0.1);
    color: var(--text-muted);
}

.table tr:hover td {
        background: rgba(200, 161, 101, 0.05);
    color: var(--text-light);
}

/* Card color variations */
.bg-primary {
    background: linear-gradient(145deg, var(--gold), var(--gold-dark)) !important;
}

.bg-success {
    background: linear-gradient(145deg, #2d3436, #1a1a1a) !important;
    border: 1px solid var(--gold) !important;
}

.bg-info {
    background: linear-gradient(145deg, #2d3436, #1a1a1a) !important;
    border: 1px solid var(--gold) !important;
}

.bg-warning {
    background: linear-gradient(145deg, #2d3436, #1a1a1a) !important;
    border: 1px solid var(--gold) !important;
}

.alert-danger {
    background: var(--black-light);
    border: 1px solid #dc3545;
    color: #dc3545;
}

/* Chart customization */
.chart-container {
    background: var(--black-light);
    border-radius: 10px;
    padding: 1rem;
    }

    @media (max-width: 768px) {
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
        }
        
    .btn-group {
        display: flex;
            width: 100%;
        }
        
    .btn-group .btn {
        flex: 1;
        margin-bottom: 0;
        }
    }
</style>';

// Add Chart.js and custom JavaScript
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("salesChart").getContext("2d");
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, "rgba(200, 161, 101, 0.4)");
    gradient.addColorStop(1, "rgba(200, 161, 101, 0.0)");
    
    // Set chart defaults
        Chart.defaults.color = "#cccccc";
        Chart.defaults.borderColor = "rgba(200, 161, 101, 0.1)";
        
    new Chart(ctx, {
            type: "line",
            data: {
            labels: ' . json_encode($chart_labels) . ',
            datasets: [{
                        label: "Sales (RM)",
                data: ' . json_encode($chart_values) . ',
                borderColor: "#c8a165",
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointBackgroundColor: "#1a1a1a",
                pointBorderColor: "#c8a165",
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }, {
                label: "Orders",
                data: ' . json_encode($chart_orders) . ',
                borderColor: "#a67c3d",
                backgroundColor: "rgba(166, 124, 61, 0.1)",
                        fill: true,
                        tension: 0.4,
                borderWidth: 2,
                pointBackgroundColor: "#1a1a1a",
                pointBorderColor: "#a67c3d",
                        pointBorderWidth: 2,
                        pointRadius: 4,
                pointHoverRadius: 6
            }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "top",
                        labels: {
                        color: "#cccccc",
                            font: {
                                size: 12
                        },
                        usePointStyle: true,
                        padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: "rgba(45, 45, 45, 0.9)",
                        titleColor: "#ffffff",
                        bodyColor: "#ffffff",
                        borderColor: "rgba(200, 161, 101, 0.3)",
                        borderWidth: 1,
                    padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || "";
                                if (label) {
                                    label += ": ";
                                }
                            if (context.dataset.label.includes("Sales")) {
                                    label += "RM " + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(200, 161, 101, 0.1)",
                            drawBorder: false
                        },
                        ticks: {
                        color: "#cccccc",
                            padding: 10,
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: "rgba(200, 161, 101, 0.1)",
                            drawBorder: false
                        },
                        ticks: {
                        color: "#cccccc",
                            padding: 10,
                            font: {
                                size: 11
                        }
                    }
                }
            }
        }
    });
    });
</script>';

// Include the layout
include 'includes/layout.php';
?> 