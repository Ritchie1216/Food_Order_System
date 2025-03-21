<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../classes/MenuItem.php');
require_once(__DIR__ . '/../classes/Category.php');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$menuItemModel = new MenuItem($db);
$categoryModel = new Category($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page_title = 'Menu Management';

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $data = [
                        'name' => $_POST['name'],
                        'description' => $_POST['description'],
                        'price' => $_POST['price'],
                        'category_id' => $_POST['category_id'],
                        'status' => 'available'
                    ];

                    // Handle image upload if present
                    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                        $image_path = $menuItemModel->uploadImage($_FILES['image']);
                        if ($image_path) {
                            $data['image_path'] = $image_path;
                        }
                    }
                    
                    if ($menuItemModel->create($data)) {
                        $message = "Menu item added successfully!";
                        $message_type = "success";
                    }
                    break;

                case 'update':
                    $data = [
                        'name' => $_POST['name'],
                        'description' => $_POST['description'],
                        'price' => $_POST['price'],
                        'category_id' => $_POST['category_id']
                    ];

                    // Handle image upload if present
                    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                        $image_path = $menuItemModel->uploadImage($_FILES['image']);
                        if ($image_path) {
                            // Delete old image
                            $old_item = $menuItemModel->getById($_POST['item_id']);
                            if ($old_item && $old_item['image_path']) {
                                $menuItemModel->deleteImage($old_item['image_path']);
                            }
                            $data['image_path'] = $image_path;
                        }
                    }
                    
                    if ($menuItemModel->update($_POST['item_id'], $data)) {
                        $message = "Menu item updated successfully!";
                        $message_type = "success";
                    }
                    break;

                case 'update_status':
                    if ($menuItemModel->updateStatus($_POST['item_id'], $_POST['status'])) {
                        $message = "Status updated successfully!";
                        $message_type = "success";
                    }
                    break;

                case 'delete':
                    // Get item details to delete image if exists
                    $item = $menuItemModel->getById($_POST['item_id']);
                    if ($item && $menuItemModel->delete($_POST['item_id'])) {
                        if ($item['image_path']) {
                            $menuItemModel->deleteImage($item['image_path']);
                        }
                        $message = "Item deleted successfully!";
                        $message_type = "success";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "danger";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get all active categories
$categories = $categoryModel->getActiveCategories();

// Get all menu items with category names
$menu_items = $menuItemModel->getAllWithCategories();

// Custom CSS
$extra_css = '
<style>
:root {
    --primary: #1a1a1a;
    --primary-light: #2d2d2d;
    --secondary: #4a4a4a;
    --accent: #d4af37;
    --accent-light: #e5c158;
    --success: #2d2d2d;
    --warning: #d4af37;
    --danger: #8b0000;
    --gray-50: #121212;
    --gray-100: #1a1a1a;
    --gray-200: #242424;
    --gray-300: #2d2d2d;
    --gray-400: #363636;
    --gray-500: #404040;
    --gray-600: #808080;
    --gray-700: #a6a6a6;
    --gray-800: #d4d4d4;
    --surface: #1a1a1a;
    --surface-hover: #242424;
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.4);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.5);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --gradient-primary: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    --gradient-hover: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
    --gradient-gold: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
    --gradient-gold-hover: linear-gradient(135deg, #e5c158 0%, #d4af37 100%);
}

body {
    background: var(--gray-50);
    color: var(--gray-800);
    min-height: 100vh;
}

.container-fluid {
    background: var(--gray-50);
    min-height: 100vh;
    padding: 2rem;
}

/* Header Styles */
.page-header {
    margin-bottom: 2rem;
    background: var(--gray-100);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
}

.page-title {
    color: var(--gray-800);
}

.page-subtitle {
    color: var(--gray-600);
}

/* Category Filters */
.category-filters-container {
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
}

.category-filter {
    background: var(--gray-200);
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
}

.category-filter:hover {
    background: var(--gray-300);
    border-color: var(--accent);
    color: var(--accent);
}

.category-filter.active {
    background: var(--accent);
    color: var(--gray-100);
    border-color: var(--accent);
}

.filter-count {
    background: var(--gray-300);
    color: var(--gray-700);
}

.category-filter:hover .filter-count {
    background: var(--gray-200);
    color: var(--accent);
}

.category-filter.active .filter-count {
    background: rgba(0, 0, 0, 0.2);
    color: var(--gray-100);
}

/* Menu Grid */
.menu-grid {
    background: var(--gray-50);
}

.menu-card {
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
}

.menu-card:hover {
    border-color: var(--accent);
    background: var(--gray-200);
}

.menu-content {
    background: var(--gray-100);
}

.menu-title {
    color: var(--gray-800);
}

.menu-description {
    color: var(--gray-600);
}

.menu-price {
    color: var(--accent);
}

.price-currency {
    color: var(--gray-600);
}

/* Status Badges */
.menu-status-badge {
    background: var(--gray-200);
    border: 1px solid var(--gray-300);
}

.status-available {
    background: rgba(26, 26, 26, 0.9);
    color: var(--accent);
    border-color: var(--accent);
}

.status-unavailable {
    background: rgba(139, 0, 0, 0.9);
    color: var(--gray-800);
    border-color: var(--danger);
}

/* Action Buttons */
.action-btn {
    background: var(--gray-200);
    color: var(--gray-800);
    border: 1px solid var(--gray-300);
}

.btn-edit:hover {
    background: var(--gray-300);
    border-color: var(--accent);
    color: var(--accent);
}

.btn-toggle {
    background: var(--accent);
    color: var(--gray-100);
    border-color: var(--accent);
}

.btn-delete:hover {
    background: var(--danger);
    border-color: var(--danger);
    color: var(--gray-100);
}

/* Empty State */
.empty-state {
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
}

.empty-icon {
    color: var(--accent);
}

.empty-state h4 {
    color: var(--gray-800);
}

.empty-text {
    color: var(--gray-600);
}

/* Alerts */
.alert {
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    color: var(--gray-800);
}

.alert-success {
    border-color: var(--accent);
    background: rgba(212, 175, 55, 0.1);
}

.alert-danger {
    border-color: var(--danger);
    background: rgba(139, 0, 0, 0.1);
}

/* Scrollbar */
.category-filters::-webkit-scrollbar-track {
    background: var(--gray-200);
}

.category-filters::-webkit-scrollbar-thumb {
    background: var(--accent);
}

/* Add New Item Button */
.header-btn {
    background: var(--gray-200);
    color: var(--accent);
    border: 1px solid var(--accent);
}

.header-btn:hover {
    background: var(--gray-300);
    color: var(--accent-light);
    border-color: var(--accent-light);
}

/* No Image Placeholder */
.no-image-placeholder {
    background: var(--gray-200);
    color: var(--gray-600);
}

/* Menu Actions Gradient */
.menu-actions {
    background: linear-gradient(
        180deg,
        rgba(26, 26, 26, 0) 0%,
        var(--gray-100) 25%
    );
}

/* Add new header styles */
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
}

.page-subtitle {
    color: var(--gray-500);
    margin-top: 0.25rem;
}

.header-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--gradient-primary);
    color: var(--accent);
    border: 1px solid var(--accent);
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 0.875rem;
    transition: var(--transition);
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.header-btn::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent, rgba(212, 175, 55, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.header-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    color: var(--accent-light);
    border-color: var(--accent-light);
}

.header-btn:hover::before {
    transform: translateX(100%);
}

.header-btn i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.header-btn:hover i {
    transform: scale(1.1);
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--gray-50);
}

.menu-card {
    position: relative;
    background: var(--surface);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: var(--transition);
    height: 400px;
    opacity: 1;
    transform: translateY(0);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.menu-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-lg);
    border-color: var(--accent);
}

.menu-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.menu-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-card:hover .menu-image {
    transform: scale(1.1);
}

.menu-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 200px;
    background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0) 0%,
        rgba(0, 0, 0, 0.5) 100%
    );
    pointer-events: none;
}

.menu-category-badge {
    position: absolute;
    left: 1rem;
    top: 160px;
    background: var(--gradient-gold);
    color: var(--gray-800);
    padding: 0.6rem 1.2rem;
    border-radius: 30px;
    font-size: 0.875rem;
    font-weight: 600;
    z-index: 2;
    box-shadow: var(--shadow-md);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: var(--transition);
}

.menu-category-badge:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    background: var(--gradient-gold-hover);
}

.menu-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: var(--surface);
}

.menu-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
    line-height: 1.3;
}

.menu-description {
    color: var(--gray-600);
    font-size: 0.875rem;
    line-height: 1.5;
    margin: 0;
}

.menu-price-section {
    margin-top: auto;
    padding: 0.75rem 0;
}

.menu-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent);
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.menu-actions {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 1rem 1.5rem;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 1) 25%
    );
    transform: translateY(100%);
    transition: var(--transition);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.menu-card:hover .menu-actions {
    transform: translateY(0);
}

.action-btn {
    border: none;
    padding: 0.75rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition);
    cursor: pointer;
    color: white;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.action-btn:hover::before {
    transform: translateX(100%);
}

.action-btn i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.action-btn:hover i {
    transform: scale(1.1);
}

.btn-edit {
    background: var(--gradient-primary);
}

.btn-toggle {
    background: var(--gradient-gold);
    color: var(--gray-800);
}

.btn-toggle:hover {
    background: var(--gradient-gold-hover);
    transform: translateY(-2px);
}

.btn-delete {
    background: var(--gradient-primary);
}

.btn-delete:hover {
    background: var(--gradient-hover);
    transform: translateY(-2px);
}

.category-filters-container {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    margin: 0 1.5rem 1.5rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.category-filters {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    scrollbar-width: thin;
    scrollbar-color: var(--accent) var(--gray-50);
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

.category-filters::-webkit-scrollbar {
    height: 6px;
}

.category-filters::-webkit-scrollbar-track {
    background: var(--gray-50);
    border-radius: 10px;
}

.category-filters::-webkit-scrollbar-thumb {
    background: var(--accent);
    border-radius: 10px;
}

.category-filter {
    padding: 0.625rem 1.25rem;
    background: var(--gray-50);
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-md);
    color: var(--gray-600);
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.category-filter::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--gradient-gold);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 0;
}

.category-filter:hover {
    border-color: var(--accent);
    color: var(--accent);
    transform: translateY(-2px);
}

.category-filter.active {
    background: var(--gradient-gold);
    border-color: transparent;
    color: var(--gray-800);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.category-filter i {
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease;
}

.category-filter:hover i {
    transform: scale(1.1);
}

.filter-count {
    background: var(--gray-100);
    color: var(--gray-600);
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    transition: var(--transition);
    position: relative;
    z-index: 1;
}

.category-filter:hover .filter-count {
    background: rgba(212, 175, 55, 0.1);
    color: var(--accent);
}

.category-filter.active .filter-count {
    background: rgba(26, 26, 26, 0.1);
    color: var(--gray-800);
}

/* Enhanced Animations */
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

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
    }

    .menu-card {
    animation: fadeIn 0.5s ease forwards;
}

.menu-card:nth-child(2) { animation-delay: 0.1s; }
.menu-card:nth-child(3) { animation-delay: 0.2s; }
.menu-card:nth-child(4) { animation-delay: 0.3s; }
.menu-card:nth-child(5) { animation-delay: 0.4s; }
.menu-card:nth-child(6) { animation-delay: 0.5s; }
.menu-card:nth-child(7) { animation-delay: 0.6s; }
.menu-card:nth-child(8) { animation-delay: 0.7s; }
.menu-card:nth-child(9) { animation-delay: 0.8s; }
.menu-card:nth-child(10) { animation-delay: 0.9s; }

.menu-card:hover {
    animation: pulse 2s infinite;
}

/* Loading State */
.menu-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.empty-icon {
    font-size: 3rem;
    color: var(--accent);
    margin-bottom: 1rem;
    animation: pulse 2s infinite;
}

.empty-state h4 {
    font-size: 1.5rem;
    color: var(--gray-800);
    margin-bottom: 0.5rem;
}

.empty-text {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

.add-item-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--gradient-gold);
    color: var(--gray-800);
    border-radius: var(--radius-md);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.add-item-btn::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.add-item-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    background: var(--gradient-gold-hover);
}

.add-item-btn:hover::before {
    transform: translateX(100%);
}

.add-item-btn i {
    transition: transform 0.3s ease;
}

.add-item-btn:hover i {
    transform: scale(1.1);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .menu-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
        padding: 1rem;
    }

    .menu-card {
        height: 380px;
    }

    .menu-image-container {
        height: 180px;
    }

    .menu-content {
        padding: 1.25rem;
    }

    .menu-title {
        font-size: 1.1rem;
    }

    .menu-description {
        font-size: 0.8rem;
    }

    .menu-price {
        font-size: 1.25rem;
    }

    .category-filters-container {
        margin: 0 1rem 1rem;
        padding: 1rem;
    }

    .category-filter {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .menu-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 0.75rem;
    }

    .menu-card {
        height: 360px;
    }

    .menu-image-container {
        height: 160px;
    }

    .menu-actions {
        padding: 0.75rem;
    }

    .action-btn {
        padding: 0.5rem;
        font-size: 0.8rem;
    }

    .category-filters {
        gap: 0.5rem;
    }

    .category-filter {
        padding: 0.4rem 0.8rem;
    }
}
</style>
';

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Menu Management</h1>
            <p class="page-subtitle">Manage your restaurant\'s menu items</p>
        </div>
        <a href="add_menu_item.php" class="header-btn">
            <i class="fas fa-plus"></i>
            Add New Item
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Category Filters -->
    <div class="category-filters-container">
        <div class="category-filters">
            <div class="category-filter active" data-category="all">
                <i class="fas fa-th-large"></i>
                All Items
                <span class="filter-count"><?php echo count($menu_items); ?></span>
            </div>
            <?php
            $category_counts = array();
            $category_icons = [
                'Main Course' => 'utensils',
                'Appetizer' => 'concierge-bell',
                'Dessert' => 'ice-cream',
                'Beverage' => 'cocktail',
                'Soup' => 'hotdog',
                'Salad' => 'seedling',
                'Pizza' => 'pizza-slice',
                'Pasta' => 'bread-slice',
                'Seafood' => 'fish',
                'Rice' => 'bowl-rice',
                'Noodles' => 'ramen',
                'Sandwich' => 'burger',
                'Grill' => 'fire',
                'Snacks' => 'cookie',
                'Coffee' => 'mug-hot',
                'Tea' => 'mug-saucer',
                'Juice' => 'glass-water',
                'Smoothie' => 'blender',
                'Breakfast' => 'egg',
                'Lunch' => 'plate-wheat',
                'Dinner' => 'utensils',
                'Special' => 'star',
                'Combo' => 'layer-group',
                'Sides' => 'french-fries',
                'Chicken' => 'drumstick-bite',
                'Meat' => 'meat',
                'Vegetarian' => 'leaf',
                'Spicy' => 'pepper-hot',
                'Healthy' => 'carrot',
                'Kids Menu' => 'ice-cream',
                'default' => 'utensils-alt'
            ];

            foreach ($menu_items as $item) {
                if (!isset($category_counts[$item['category_name']])) {
                    $category_counts[$item['category_name']] = 0;
                }
                $category_counts[$item['category_name']]++;
            }
            
            foreach ($category_counts as $category => $count):
                $icon = $category_icons[htmlspecialchars($category)] ?? $category_icons['default'];
            ?>
            <div class="category-filter" data-category="<?php echo htmlspecialchars($category); ?>">
                <i class="fas fa-<?php echo $icon; ?>"></i>
                <?php echo htmlspecialchars($category); ?>
                <span class="filter-count"><?php echo $count; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Menu Items Grid -->
    <div class="menu-grid">
        <?php if (!empty($menu_items)): ?>
            <?php foreach ($menu_items as $item): ?>
            <div class="menu-card" 
                 data-category="<?php echo htmlspecialchars($item['category_name']); ?>"
                 data-id="<?php echo $item['id']; ?>">
                <div class="menu-image-container">
                    <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                        <img src="<?php echo '../' . htmlspecialchars($item['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="menu-image">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-utensils"></i>
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>
                    <div class="menu-overlay"></div>
                    <div class="menu-status-badge status-<?php echo $item['status']; ?>">
                        <i class="fas fa-<?php echo $item['status'] === 'available' ? 'check-circle' : 'times-circle'; ?>"></i>
                        <?php echo ucfirst($item['status']); ?>
                    </div>
                    <div class="menu-category-badge">
                        <?php echo htmlspecialchars($item['category_name']); ?>
                    </div>
                </div>

                <div class="menu-content">
                    <h3 class="menu-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="menu-description"><?php echo htmlspecialchars($item['description']); ?></p>
                    
                    <div class="menu-price-section">
                        <div class="menu-price">
                            <span class="price-currency">RM</span>
                            <?php echo number_format($item['price'], 2); ?>
                        </div>
                    </div>
                </div>

                <div class="menu-actions">
                    <button type="button" class="action-btn btn-edit" 
                            onclick="window.location.href='edit_menu_item.php?id=<?php echo $item['id']; ?>'"
                            title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" 
                            class="action-btn btn-toggle"
                            onclick="toggleStatus(<?php echo $item['id']; ?>)"
                            title="<?php echo $item['status'] === 'available' ? 'Mark as Unavailable' : 'Mark as Available'; ?>">
                        <i class="fas fa-<?php echo $item['status'] === 'available' ? 'times' : 'check'; ?>"></i>
                    </button>
                    <button type="button" class="action-btn btn-delete"
                            onclick="deleteItem(<?php echo $item['id']; ?>)"
                            title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-utensils empty-icon"></i>
                <h4>No Menu Items Found</h4>
                <p class="empty-text">Start by adding your first menu item</p>
                <a href="add_menu_item.php" class="add-item-btn">
                    <i class="fas fa-plus"></i>
                    Add Menu Item
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for category filtering
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const categoryFilters = document.querySelectorAll(".category-filter");
    const menuCards = document.querySelectorAll(".menu-card");
    
    // Function to handle smooth scrolling
    function smoothScroll(element, target) {
        const start = element.scrollLeft;
        const distance = target - start;
        const duration = 500;
        let startTime = null;

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const progress = Math.min(timeElapsed / duration, 1);
            
            const easing = t => t < .5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
            element.scrollLeft = start + distance * easing(progress);

            if (timeElapsed < duration) {
                requestAnimationFrame(animation);
            }
        }

        requestAnimationFrame(animation);
    }

    // Category filtering with smooth transitions
    categoryFilters.forEach(filter => {
        filter.addEventListener("click", function() {
            const category = this.dataset.category;
            
            // Update active state
            categoryFilters.forEach(f => f.classList.remove("active"));
            this.classList.add("active");

            // Smooth scroll to center the active filter
            const container = document.querySelector(".category-filters");
            const scrollTarget = this.offsetLeft - (container.offsetWidth / 2) + (this.offsetWidth / 2);
            smoothScroll(container, scrollTarget);
            
            // Filter items with animation
            menuCards.forEach(card => {
                if (category === "all" || card.dataset.category === category) {
                    card.classList.remove("hiding");
                    card.style.display = "";
                    setTimeout(() => {
                        card.classList.add("showing");
                    }, 50);
                } else {
                    card.classList.add("hiding");
                    card.classList.remove("showing");
                    setTimeout(() => {
                        card.style.display = "none";
                    }, 300);
                }
            });
        });
    });

    // Horizontal scroll handling
    const categoryFiltersContainer = document.querySelector(".category-filters");
    let isScrolling = false;
    let startX;
    let scrollLeft;

    categoryFiltersContainer.addEventListener("mousedown", (e) => {
        isScrolling = true;
        startX = e.pageX - categoryFiltersContainer.offsetLeft;
        scrollLeft = categoryFiltersContainer.scrollLeft;
        categoryFiltersContainer.style.cursor = "grabbing";
    });

    categoryFiltersContainer.addEventListener("mousemove", (e) => {
        if (!isScrolling) return;
        e.preventDefault();
        const x = e.pageX - categoryFiltersContainer.offsetLeft;
        const walk = (x - startX) * 2;
        categoryFiltersContainer.scrollLeft = scrollLeft - walk;
    });

    categoryFiltersContainer.addEventListener("mouseup", () => {
        isScrolling = false;
        categoryFiltersContainer.style.cursor = "grab";
    });

    categoryFiltersContainer.addEventListener("mouseleave", () => {
        isScrolling = false;
        categoryFiltersContainer.style.cursor = "grab";
    });

    // Smooth wheel scrolling
    categoryFiltersContainer.addEventListener("wheel", (e) => {
        e.preventDefault();
        const delta = Math.max(-1, Math.min(1, e.deltaY || -e.detail));
        smoothScroll(categoryFiltersContainer, categoryFiltersContainer.scrollLeft + (delta * 100));
    }, { passive: false });
});
</script>
';

// Add JavaScript for price validation
$extra_js .= '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const priceInputs = document.querySelectorAll("input[type=number][name=price]");
    
    priceInputs.forEach(input => {
        input.addEventListener("input", function() {
            // Format to 2 decimal places
            if (this.value !== "") {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
        
        input.addEventListener("blur", function() {
            // Ensure minimum value
            if (this.value < 0) {
                this.value = "0.00";
            }
        });
    });
});
</script>
';

// Add this to your existing $extra_js
$extra_js .= '
<script>
// Function to toggle item status
function toggleStatus(itemId) {
    if (!confirm("Are you sure you want to change this item\'s availability?")) {
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.style.display = "none";

    const actionInput = document.createElement("input");
    actionInput.type = "hidden";
    actionInput.name = "action";
    actionInput.value = "update_status";

    const itemIdInput = document.createElement("input");
    itemIdInput.type = "hidden";
    itemIdInput.name = "item_id";
    itemIdInput.value = itemId;

    // Find current status from the menu card
    const menuCard = document.querySelector(`.menu-card[data-id="${itemId}"]`);
    const currentStatus = menuCard.querySelector(".menu-status-badge").classList.contains("status-available");
    
    const statusInput = document.createElement("input");
    statusInput.type = "hidden";
    statusInput.name = "status";
    statusInput.value = currentStatus ? "unavailable" : "available";

    form.appendChild(actionInput);
    form.appendChild(itemIdInput);
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}

// Function to delete item
function deleteItem(itemId) {
    if (!confirm("Are you sure you want to delete this item? This action cannot be undone.")) {
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.style.display = "none";

    const actionInput = document.createElement("input");
    actionInput.type = "hidden";
    actionInput.name = "action";
    actionInput.value = "delete";

    const itemIdInput = document.createElement("input");
    itemIdInput.type = "hidden";
    itemIdInput.name = "item_id";
    itemIdInput.value = itemId;

    form.appendChild(actionInput);
    form.appendChild(itemIdInput);
    document.body.appendChild(form);
    form.submit();
}
</script>
';

// Include the layout template
include 'includes/layout.php';
?> 
