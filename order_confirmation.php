<?php
session_start();
require_once(__DIR__ . '/config/Database.php');
require_once(__DIR__ . '/classes/Order.php');

$database = new Database();
$db = $database->getConnection();
$orderModel = new Order($db);

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$order = $order_id ? $orderModel->getOrder($order_id) : null;

// Get table number from URL
$table_number = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : null;

// Get token from URL
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #c8a165;
            --secondary-color: #b38b4d;
            --success-color: #c8a165;
            --background-color: #1a1a1a;
            --text-color: #ffffff;
            --text-muted: #cccccc;
            --card-background: #2d2d2d;
            --border-color: rgba(200, 161, 101, 0.2);
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            --box-shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            padding-top: 80px;
            color: var(--text-color);
            line-height: 1.6;
        }

        .navbar {
            background: rgba(45, 45, 45, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--box-shadow);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: translateY(-2px);
        }

        .navbar-brand i {
            font-size: 1.8rem;
        }

        .table-info-banner {
            background: rgba(45, 45, 45, 0.9);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin: 0 15px;
            transition: all 0.3s ease;
        }

        .table-info-banner:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color);
            box-shadow: var(--box-shadow-hover);
        }

        .table-info-banner .table-number {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }

        .view-orders-btn {
            background: linear-gradient(145deg, var(--primary-color), var(--secondary-color));
            color: var(--background-color);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
        }

        .view-orders-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(200, 161, 101, 0.3);
            color: var(--background-color);
        }

        .confirmation-container {
            background: var(--card-background);
            border-radius: 20px;
            box-shadow: var(--box-shadow);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            margin: 2rem auto;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
        }

        .confirmation-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
            border-color: var(--primary-color);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(145deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: scaleIn 0.5s ease-out, pulse 2s infinite;
            box-shadow: 0 0 20px rgba(200, 161, 101, 0.3);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .success-icon i {
            color: var(--background-color);
            font-size: 2.5rem;
        }

        .confirmation-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .order-details {
            background: rgba(45, 45, 45, 0.5);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .order-details:hover {
            border-color: var(--primary-color);
            box-shadow: var(--box-shadow-hover);
        }

        .order-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-number::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--primary-color);
            border-radius: 2px;
            display: inline-block;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .detail-row:hover {
            background: rgba(200, 161, 101, 0.1);
            padding-left: 1rem;
            padding-right: 1rem;
            margin-left: -1rem;
            margin-right: -1rem;
            border-radius: 8px;
        }

        .order-items {
            margin-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: rgba(200, 161, 101, 0.1);
            transform: translateX(5px);
        }

        .order-total {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .back-to-menu {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-to-menu:hover {
            background: var(--primary-color);
            color: var(--background-color);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-hover);
        }

        @media (max-width: 768px) {
            .confirmation-container {
                padding: 2rem;
                margin: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .view-orders-btn,
            .back-to-menu {
                width: 100%;
                justify-content: center;
                padding: 1rem;
            }

            .order-details {
                padding: 1.5rem;
            }

            .table-info-banner {
                margin: 0 10px;
                padding: 6px 12px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 0.5rem 0;
            }

            .navbar-brand {
                font-size: 1.2rem;
            }

            .confirmation-container {
                padding: 1.5rem;
            }

            .success-icon {
                width: 60px;
                height: 60px;
            }

            .confirmation-title {
                font-size: 1.5rem;
            }

            .table-info-banner {
                margin: 0 8px;
                padding: 4px 10px;
            }

            .order-item {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }

        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php<?php echo $table_number && $token ? '?table=' . $table_number . '&token=' . $token : ''; ?>">
                <i class="fas fa-utensils"></i>
                Gourmet Delights
            </a>
            <div class="d-flex align-items-center gap-3">
                <?php if ($table_number): ?>
                <div class="table-info-banner">
                    <span class="table-number">Table <?php echo $table_number; ?></span>
                </div>
                <?php if ($token): ?>
                <a href="view_orders.php?table=<?php echo $table_number; ?>&token=<?php echo $token; ?>" class="view-orders-btn">
                    <i class="fas fa-list-ul"></i>
                    View Orders
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Confirmation Content -->
    <div class="container">
        <div class="confirmation-container">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="mb-4">Thank you for your order. Your food will be prepared shortly.</p>
            
            <?php if ($order): ?>
            <div class="order-details">
                <div class="order-number">
                    Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                </div>
                <div class="detail-row">
                    <span>Table Number:</span>
                    <span>Table <?php echo htmlspecialchars($order['table_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span>Order Time:</span>
                    <span><?php echo date('h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                
                <div class="order-items">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                        <span>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="order-total">
                        <div class="detail-row">
                            <span>Total:</span>
                            <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons mt-4">
                <a href="view_orders.php<?php echo ($table_number && $token) ? '?table=' . $table_number . '&token=' . $token : ''; ?>" class="view-orders-btn">
                    <i class="fas fa-list-ul"></i>
                    View All Orders
                </a>
                
                <a href="index.php<?php echo ($table_number && $token) ? '?table=' . $table_number . '&token=' . $token : ''; ?>" class="back-to-menu">
                    <i class="fas fa-arrow-left"></i>
                    Back to Menu
                </a>
            </div>
        </div>
    </div>

    <script>
        // Clear cart after successful order
        localStorage.removeItem('cart');
    </script>
</body>
</html> 