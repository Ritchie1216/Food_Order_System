<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../classes/Category.php');
require_once(__DIR__ . '/../classes/MenuItem.php');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$categoryModel = new Category($db);
$menuItemModel = new MenuItem($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page_title = 'Category Management';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $data = [
                        'name' => $_POST['name'],
                        'description' => $_POST['description'],
                        'status' => 'active'
                    ];
                    
                    if ($categoryModel->create($data)) {
                        $message = "Category added successfully!";
                        $message_type = "success";
                    }
                    break;

                case 'update':
                    $data = [
                        'name' => $_POST['name'],
                        'description' => $_POST['description']
                    ];
                    
                    if ($categoryModel->update($_POST['category_id'], $data)) {
                        $message = "Category updated successfully!";
                        $message_type = "success";
                    }
                    break;

                case 'update_status':
                    if ($categoryModel->updateStatus($_POST['category_id'], $_POST['status'])) {
                        $message = "Status updated successfully!";
                        $message_type = "success";
                    }
                    break;

                case 'delete':
                    // Check if category has menu items
                    $category_items = $menuItemModel->getItemsByCategory($_POST['category_id']);
                    if (!empty($category_items)) {
                        $message = "Cannot delete category: It contains menu items!";
                        $message_type = "danger";
                    } else {
                        if ($categoryModel->delete($_POST['category_id'])) {
                            $message = "Category deleted successfully!";
                            $message_type = "success";
                        }
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

// Get all categories with item counts
$categories = $categoryModel->getCategoryWithItemCount();

// Custom CSS
$extra_css = <<<'CSS'
<style>
:root {
    --primary: #d4af37;
    --primary-dark: #b8860b;
    --primary-light: #e5c158;
    --success: #2d2d2d;
    --warning: #d4af37;
    --danger: #8b0000;
    --surface: #1a1a1a;
    --gray-50: #121212;
    --gray-100: #1a1a1a;
    --gray-200: #242424;
    --gray-300: #2d2d2d;
    --gray-400: #363636;
    --gray-500: #404040;
    --gray-600: #808080;
    --gray-700: #a6a6a6;
    --gray-800: #d4d4d4;
    --border-color: rgba(45, 45, 45, 0.8);
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.4);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.5);
    --gradient-gold: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    --gradient-gold-hover: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
}

.category-card {
    position: relative;
    background: var(--gray-200);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-color);
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: var(--gradient-gold);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.category-card:hover::before {
    opacity: 0.05;
}

.category-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
}

.category-header {
    position: relative;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--gray-300);
    border-bottom: 1px solid var(--border-color);
}

.category-title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.category-icon {
    width: 48px;
    height: 48px;
    background: var(--gradient-gold);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1) rotate(10deg);
    box-shadow: var(--shadow-md);
}

.category-icon i {
    font-size: 1.5rem;
    color: var(--gray-50);
    transition: all 0.3s ease;
}

.category-card:hover .category-icon i {
    transform: scale(1.1);
}

.category-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
    margin: 0;
}

.category-status {
    padding: 0.5rem 1rem;
    border-radius: 30px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.status-active {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 223, 0, 0.3) 100%);
    color: #FFD700;
    border: 2px solid #FFD700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.4),
                inset 0 0 10px rgba(255, 215, 0, 0.2);
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    font-weight: 600;
    letter-spacing: 0.5px;
    padding: 0.6rem 1.2rem;
    animation: glow 1.5s ease-in-out infinite alternate;
}

.status-active i {
    color: #FFD700;
    filter: drop-shadow(0 0 3px rgba(255, 215, 0, 0.7));
    margin-right: 6px;
    font-size: 1rem;
}

@keyframes glow {
    from {
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.4),
                    inset 0 0 10px rgba(255, 215, 0, 0.2);
    }
    to {
        box-shadow: 0 0 25px rgba(255, 215, 0, 0.6),
                    inset 0 0 15px rgba(255, 215, 0, 0.3);
    }
}

.status-inactive {
    background: rgba(139, 0, 0, 0.1);
    color: #ff4444;
    border: 1px solid #ff4444;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
}

.category-body {
    padding: 1.5rem;
    position: relative;
}

.category-description {
    color: var(--gray-600);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.category-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-box {
    background: var(--gray-300);
    border-radius: 12px;
    padding: 1rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.stat-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--gradient-gold);
    opacity: 0.5;
}

.stat-box:hover {
    transform: translateY(-2px);
    background: var(--gray-400);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-value i {
    font-size: 1rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 500;
}

.category-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    padding: 1rem;
    background: var(--gray-300);
    border-top: 1px solid var(--border-color);
}

.action-btn {
    position: relative;
    padding: 0.75rem;
    border-radius: 10px;
    font-weight: 500;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    cursor: pointer;
    overflow: hidden;
    background: var(--gray-400);
}

.action-btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 120%;
    height: 120%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
    transform: translate(-50%, -50%) scale(0);
    opacity: 0;
    transition: transform 0.5s ease, opacity 0.3s ease;
}

.action-btn:hover::after {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
}

.action-btn i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.action-btn:hover i {
    transform: scale(1.2);
}

.btn-edit {
    color: var(--primary);
}

.btn-edit:hover {
    background: var(--gray-500);
    border-color: var(--primary);
}

.btn-toggle {
    background: var(--gradient-gold);
    color: var(--gray-50);
}

.btn-toggle:hover {
    background: var(--gradient-gold-hover);
    transform: translateY(-2px);
}

.btn-delete {
    color: #ff4444;
}

.btn-delete:hover {
    background: var(--danger);
    color: var(--gray-50);
}

/* Search and Filter Card */
.card {
    background: var(--gray-200);
    border: 1px solid var(--border-color);
    border-radius: 16px;
}

.card-body {
    padding: 1.5rem;
}

/* Search Box */
.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-600);
    pointer-events: none;
}

.search-box input {
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: var(--gray-300);
    border: 1px solid var(--border-color);
    color: var(--gray-800);
    border-radius: 12px;
    width: 100%;
    transition: all 0.3s ease;
}

.search-box input:focus {
    background: var(--gray-400);
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
}

/* Filter Buttons */
.btn-group {
    background: var(--gray-300);
    padding: 0.25rem;
    border-radius: 12px;
}

.btn-outline-primary {
    color: var(--gray-600);
    border: none;
    border-radius: 8px !important;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover,
.btn-outline-primary.active {
    background: var(--gradient-gold);
    color: var(--gray-50);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--gray-200);
    border-radius: 16px;
    border: 1px solid var(--border-color);
}

.empty-state-icon {
    font-size: 3rem;
    color: var(--primary);
    margin-bottom: 1.5rem;
    opacity: 0.8;
}

.empty-state h4 {
    color: var(--gray-800);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.empty-state-text {
    color: var(--gray-600);
    margin-bottom: 2rem;
}

/* Modal Styles */
.modal-content {
    background: var(--gray-200);
    border: 1px solid var(--border-color);
    border-radius: 16px;
}

.modal-header {
    background: var(--gray-300);
    border-bottom: 1px solid var(--border-color);
    border-radius: 16px 16px 0 0;
    padding: 1.25rem 1.5rem;
}

.modal-title {
    color: var(--gray-800);
    font-weight: 600;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    background: var(--gray-300);
    border-top: 1px solid var(--border-color);
    border-radius: 0 0 16px 16px;
    padding: 1.25rem 1.5rem;
}

.form-label {
    color: var(--gray-700);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control {
    background: var(--gray-300);
    border: 1px solid var(--border-color);
    color: var(--gray-800);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: var(--gray-400);
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
}

.btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

@media (max-width: 768px) {
    .category-grid {
        grid-template-columns: 1fr;
        padding: 1rem;
        gap: 1rem;
    }

    .category-header {
        padding: 1rem;
    }

    .category-icon {
        width: 40px;
        height: 40px;
    }

    .category-icon i {
        font-size: 1.25rem;
    }

    .category-name {
        font-size: 1.1rem;
    }

    .category-body {
        padding: 1rem;
    }

    .stat-box {
        padding: 0.75rem;
    }

    .stat-value {
        font-size: 1.25rem;
    }
}
</style>
CSS;

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Category Management</h1>
            <p class="text-muted">Manage your menu categories</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-2"></i>Add New Category
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="position-relative search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="categorySearch" placeholder="Search categories...">
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-primary" data-filter="active">Active</button>
                        <button type="button" class="btn btn-outline-primary" data-filter="inactive">Inactive</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
        <div class="category-card category-item" data-status="<?php echo $category['status']; ?>">
            <div class="category-header">
                <h3 class="category-name">
                    <?php echo htmlspecialchars($category['name']); ?>
                </h3>
                <span class="category-status">
                    <i class="fas fa-<?php echo $category['status'] === 'active' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                    <?php echo ucfirst($category['status']); ?>
                </span>
            </div>
            
            <div class="category-body">
                <p class="category-description">
                    <?php echo htmlspecialchars($category['description'] ?: 'No description available'); ?>
                </p>
                
                <div class="category-stats">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $category['item_count']; ?></div>
                        <div class="stat-label">Menu Items</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">
                            <i class="fas fa-<?php echo $category['status'] === 'active' ? 'check' : 'times'; ?>"></i>
                        </div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>
            </div>
            
            <div class="category-actions">
                <button type="button" class="action-btn btn-edit" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editCategoryModal" 
                        data-category='<?php echo json_encode($category); ?>'>
                    <i class="fas fa-edit"></i>
                    Edit
                </button>
                
                <form method="POST" class="d-inline flex-grow-1">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                    <input type="hidden" name="status" 
                           value="<?php echo $category['status'] === 'active' ? 'inactive' : 'active'; ?>">
                    <button type="submit" class="action-btn btn-toggle w-100">
                        <i class="fas fa-<?php echo $category['status'] === 'active' ? 'times' : 'check'; ?>"></i>
                        <?php echo $category['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                    </button>
                </form>
                
                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                    <button type="submit" class="action-btn btn-delete">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($categories)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open empty-state-icon"></i>
            <h4>No Categories Found</h4>
            <p class="empty-state-text">Start by adding your first category</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i>Add Category
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for search, filter and edit modal
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("categorySearch");
    const categoryItems = document.querySelectorAll(".category-item");
    let searchTimeout;

    // Enhanced search function
    function searchCategories(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();
        
        categoryItems.forEach(item => {
            const categoryName = item.querySelector(".category-name").textContent.toLowerCase();
            const categoryDesc = item.querySelector(".category-description").textContent.toLowerCase();
            const itemCount = item.querySelector(".stat-value").textContent;
            const status = item.querySelector(".category-status").textContent.toLowerCase();
            
            // Search in multiple fields
            const matchName = categoryName.includes(searchTerm);
            const matchDesc = categoryDesc.includes(searchTerm);
            const matchCount = itemCount.includes(searchTerm);
            const matchStatus = status.includes(searchTerm);

            // Show/hide with animation
            if (matchName || matchDesc || matchCount || matchStatus) {
                item.style.display = "block";
                item.style.opacity = "1";
                item.style.transform = "translateY(0)";
            } else {
                item.style.opacity = "0";
                item.style.transform = "translateY(20px)";
                setTimeout(() => {
                    if (item.style.opacity === "0") {
                        item.style.display = "none";
                    }
                }, 300);
            }
        });

        // Show/hide empty state
        const visibleItems = document.querySelectorAll(".category-item[style*=\'display: block\']");
        const emptyState = document.querySelector(".empty-state") || createEmptyState();
        
        if (visibleItems.length === 0) {
            emptyState.style.display = "block";
            emptyState.style.opacity = "1";
        } else {
            emptyState.style.opacity = "0";
            setTimeout(() => {
                emptyState.style.display = "none";
            }, 300);
        }
    }

    // Create empty state for search results
    function createEmptyState() {
        const emptyState = document.createElement("div");
        emptyState.className = "empty-state";
        emptyState.innerHTML = `
            <i class="fas fa-search empty-state-icon"></i>
            <h4>No Categories Found</h4>
            <p class="empty-state-text">Try adjusting your search term</p>
            <button class="btn btn-outline-primary" onclick="clearSearch()">
                <i class="fas fa-times me-2"></i>Clear Search
            </button>
        `;
        document.querySelector(".category-grid").appendChild(emptyState);
        return emptyState;
    }

    // Clear search function
    window.clearSearch = function() {
        searchInput.value = "";
        searchCategories("");
        searchInput.focus();
    }

    // Add search input event listener with debounce
    searchInput.addEventListener("input", function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchCategories(this.value);
        }, 300);
    });

    // Add search box enhancements
    searchInput.addEventListener("focus", function() {
        this.parentElement.style.boxShadow = "0 0 0 3px rgba(79, 70, 229, 0.1)";
    });

    searchInput.addEventListener("blur", function() {
        this.parentElement.style.boxShadow = "none";
    });

    // Add clear button to search box
    const searchBox = searchInput.parentElement;
    const clearButton = document.createElement("button");
    clearButton.className = "search-clear-btn";
    clearButton.innerHTML = \'<i class="fas fa-times"></i>\';
    clearButton.style.display = "none";
    searchBox.appendChild(clearButton);

    // Update search box styles
    searchBox.style.position = "relative";
    
    const clearButtonStyles = `
        .search-clear-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .search-clear-btn:hover {
            background: var(--gray-100);
            color: var(--danger);
        }
    `;
    
    // Add styles to document
    const styleSheet = document.createElement("style");
    styleSheet.textContent = clearButtonStyles;
    document.head.appendChild(styleSheet);

    // Handle clear button visibility
    searchInput.addEventListener("input", function() {
        clearButton.style.display = this.value ? "flex" : "none";
    });

    clearButton.addEventListener("click", function() {
        clearSearch();
        this.style.display = "none";
    });

    // Add keyboard shortcuts
    document.addEventListener("keydown", function(e) {
        // Press "/" to focus search
        if (e.key === "/" && !searchInput.matches(":focus")) {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Press "Esc" to clear search
        if (e.key === "Escape" && searchInput.matches(":focus")) {
            clearSearch();
            searchInput.blur();
        }
    });

    // Filter functionality
    const filterButtons = document.querySelectorAll("[data-filter]");
    
    filterButtons.forEach(button => {
        button.addEventListener("click", function() {
            const filter = this.dataset.filter;
            
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");
            
            // Filter items
            categoryItems.forEach(item => {
                if (filter === "all" || item.dataset.status === filter) {
                    item.style.display = "block";
                    item.style.opacity = "1";
                    item.style.transform = "translateY(0)";
                } else {
                    item.style.opacity = "0";
                    item.style.transform = "translateY(20px)";
                    setTimeout(() => {
                        if (item.style.opacity === "0") {
                            item.style.display = "none";
                        }
                    }, 300);
                }
            });
        });
    });
});
</script>
';

// Include the layout template
include 'includes/layout.php';
?> 