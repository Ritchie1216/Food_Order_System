<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../classes/Table.php');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$tableModel = new Table($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page_title = "Table Management";
$message = '';
$message_type = '';

// Handle Add Table
if (isset($_POST['add_table'])) {
    $table_number = trim($_POST['table_number']);
    
    try {
        if (empty($table_number)) {
            throw new Exception("Table number is required");
        }
        
        // Check if table number already exists
        $check_query = "SELECT id FROM tables WHERE table_number = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$table_number]);
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception("Table number already exists");
        }
        
        // Add new table
        $insert_query = "INSERT INTO tables (table_number, status) VALUES (?, 'active')";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute([$table_number])) {
            $message = "Table added successfully";
            $message_type = "success";
        } else {
            throw new Exception("Failed to add table");
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Handle Edit Table
if (isset($_POST['edit_table'])) {
    $table_id = $_POST['table_id'];
    $new_table_number = trim($_POST['new_table_number']);
    $new_status = $_POST['new_status'];
    
    try {
        if (empty($new_table_number)) {
            throw new Exception("Table number is required");
        }
        
        // Check if new table number already exists
        $check_query = "SELECT id FROM tables WHERE table_number = ? AND id != ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$new_table_number, $table_id]);
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception("Table number already exists");
        }
        
        // Update table
        $update_query = "UPDATE tables SET table_number = ?, status = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$new_table_number, $new_status, $table_id])) {
            $message = "Table updated successfully";
            $message_type = "success";
        } else {
            throw new Exception("Failed to update table");
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Handle Delete Table
if (isset($_POST['delete_table'])) {
    $table_id = $_POST['table_id'];
    
    try {
        // Check if table has any orders
        $check_orders_query = "SELECT COUNT(*) as order_count FROM orders WHERE table_id = ?";
        $check_stmt = $db->prepare($check_orders_query);
        $check_stmt->execute([$table_id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['order_count'] > 0) {
            // Table has orders - perform soft delete
            $update_query = "UPDATE tables SET status = 'inactive' WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$table_id]);
            
            $message = "Table has order history. The table has been deactivated.";
            $message_type = "info";
        } else {
            // No orders - safe to delete
            $delete_query = "DELETE FROM tables WHERE id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$table_id]);
            
            $message = "Table deleted successfully";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Get all tables with order counts
try {
    $query = "SELECT t.*, 
              COUNT(DISTINCT o.id) as total_orders,
              COUNT(DISTINCT CASE WHEN o.status IN ('pending', 'processing') THEN o.id END) as pending_orders,
              COUNT(DISTINCT CASE WHEN qc.is_active = 1 AND (qc.expires_at IS NULL OR qc.expires_at > NOW()) THEN qc.id END) as active_qr_codes
              FROM tables t
              LEFT JOIN orders o ON t.id = o.table_id
              LEFT JOIN qr_codes qc ON t.id = qc.table_id
              GROUP BY t.id
              ORDER BY t.table_number";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error retrieving tables: " . $e->getMessage();
    $message_type = "danger";
    $tables = [];
}

// Custom CSS
$extra_css = '
<style>
:root {
    --gradient-primary: linear-gradient(135deg, #d4af37, #b8860b);
    --gradient-success: linear-gradient(135deg, #2d2d2d, #363636);
    --gradient-danger: linear-gradient(135deg, #8b0000, #a83232);
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.4);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.5);
}

.table-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 20px;
    background: #1a1a1a;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.table-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(212, 175, 55, 0.2);
}

.table-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: var(--gradient-primary);
    opacity: 0.1;
    border-radius: 20px 20px 0 0;
}

.table-header {
    position: relative;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #242424;
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
}

.table-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.table-icon i {
    font-size: 1.75rem;
    color: #121212;
}

.table-info {
    flex: 1;
}

.table-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: #d4d4d4;
    margin: 0;
    line-height: 1.2;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
    gap: 0.5rem;
}

.status-active {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(184, 134, 11, 0.15));
    color: #d4af37;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.status-inactive {
    background: rgba(139, 0, 0, 0.1);
    color: #ff4444;
    border: 1px solid rgba(255, 68, 68, 0.3);
}

.order-count {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #2d2d2d;
    color: #d4af37;
    padding: 0.4rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(212, 175, 55, 0.2);
}

.order-count i {
    font-size: 0.875rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding: 1.5rem;
    background: #242424;
    border-radius: 16px;
    margin: 0 1.5rem 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: #2d2d2d;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: rgba(212, 175, 55, 0.2);
}

.stat-label {
    font-size: 0.875rem;
    color: #808080;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #d4af37;
    line-height: 1;
}

.table-actions {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(212, 175, 55, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #242424;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: none;
    background: #2d2d2d;
    color: #808080;
    transition: all 0.3s ease;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.btn-action:hover {
    transform: translateY(-2px);
    border-color: rgba(212, 175, 55, 0.3);
}

.btn-edit:hover {
    background: var(--gradient-primary);
    color: #121212;
}

.btn-qr:hover {
    background: linear-gradient(135deg, #d4af37, #b8860b);
    color: #121212;
}

.btn-delete:hover {
    background: var(--gradient-danger);
    color: #ffffff;
}

.search-box {
    max-width: 300px;
}

.search-box input {
    border-radius: 50px;
    padding: 0.75rem 1.25rem 0.75rem 3rem;
    border: 1px solid rgba(212, 175, 55, 0.1);
    background: #2d2d2d;
    color: #d4d4d4;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.search-box input:focus {
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.search-box i {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #808080;
    font-size: 1rem;
}

.filter-buttons .btn {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: 50px;
    transition: all 0.3s ease;
    background: #2d2d2d;
    color: #808080;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.filter-buttons .btn.active {
    background: var(--gradient-primary);
    border-color: transparent;
    color: #121212;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.card {
    background: #1a1a1a;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.btn-primary {
    background: var(--gradient-primary);
    border: none;
    color: #121212;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #e5c158, #d4af37);
    transform: translateY(-2px);
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.modal-header {
    background: #242424;
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
}

.modal-title {
    color: #d4d4d4;
}

.modal-footer {
    background: #242424;
    border-top: 1px solid rgba(212, 175, 55, 0.1);
}

.form-control {
    background: #2d2d2d;
    border: 1px solid rgba(212, 175, 55, 0.1);
    color: #d4d4d4;
}

.form-control:focus {
    background: #363636;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.form-label {
    color: #a6a6a6;
}

.btn-light {
    background: #2d2d2d;
    border: 1px solid rgba(212, 175, 55, 0.1);
    color: #d4d4d4;
}

.btn-light:hover {
    background: #363636;
    border-color: rgba(212, 175, 55, 0.2);
    color: #d4af37;
}

.alert {
    background: #242424;
    border: 1px solid rgba(212, 175, 55, 0.1);
    color: #d4d4d4;
}

.alert-success {
    background: rgba(45, 45, 45, 0.9);
    border-color: rgba(212, 175, 55, 0.3);
    color: #d4af37;
}

.alert-danger {
    background: rgba(139, 0, 0, 0.1);
    border-color: rgba(255, 68, 68, 0.3);
    color: #ff4444;
}

.text-muted {
    color: #808080 !important;
}

.h3 {
    color: #d4d4d4;
}

.table-wrapper {
    background: #1a1a1a;
    border-radius: 8px;
    border: 1px solid rgba(212, 175, 55, 0.1);
    overflow: hidden;
}

.custom-table {
    width: 100%;
    margin-bottom: 0;
}

.custom-table th {
    background: #242424;
    color: #d4d4d4;
    font-weight: 600;
    padding: 1rem;
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
    white-space: nowrap;
}

.custom-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
    background: #1a1a1a;
    color: #d4d4d4;
}

.custom-table tr:hover td {
    background: #242424;
}

.table-icon-cell {
    width: 48px;
}

.table-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-sm);
}

.table-icon i {
    font-size: 1rem;
    color: #121212;
}

.table-number {
    font-weight: 600;
    color: #d4d4d4;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.813rem;
    font-weight: 500;
    gap: 0.35rem;
}

.status-active {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(184, 134, 11, 0.15));
    color: #d4af37;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.status-inactive {
    background: rgba(139, 0, 0, 0.1);
    color: #ff4444;
    border: 1px solid rgba(255, 68, 68, 0.3);
}

.stats-cell {
    display: flex;
    gap: 1.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #2d2d2d;
    border-radius: 6px;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.stat-item:hover {
    border-color: rgba(212, 175, 55, 0.2);
    background: #363636;
}

.stat-label {
    font-size: 0.813rem;
    color: #808080;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.stat-value {
    font-weight: 600;
    color: #d4af37;
}

.actions-cell {
    width: 120px;
    text-align: right;
}

.btn-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: none;
    background: #2d2d2d;
    color: #808080;
    transition: all 0.3s ease;
    border: 1px solid rgba(212, 175, 55, 0.1);
    margin-left: 0.25rem;
}

.btn-action:hover {
    transform: translateY(-2px);
    border-color: rgba(212, 175, 55, 0.3);
}

.search-filter-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #242424;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.search-box {
    flex: 1;
    max-width: 300px;
    position: relative;
}

.search-box input {
    width: 100%;
    border-radius: 6px;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid rgba(212, 175, 55, 0.1);
    background: #2d2d2d;
    color: #d4d4d4;
    font-size: 0.875rem;
}

.filter-buttons .btn {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-radius: 6px;
    font-size: 0.875rem;
}
</style>
';

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Table Management</h1>
            <p class="text-muted">Manage your restaurant tables</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
            <i class="fas fa-plus me-2"></i>Add New Table
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
                        <input type="text" class="form-control" id="tableSearch" placeholder="Search tables...">
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

    <!-- Tables Grid -->
    <div class="table-wrapper">
        <table class="custom-table">
            <thead>
                <tr>
                    <th class="table-icon-cell"></th>
                    <th>Table Number</th>
                    <th>Status</th>
                    <th>Statistics</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $table): ?>
                <tr>
                    <td class="table-icon-cell">
                        <div class="table-icon">
                            <i class="fas fa-chair"></i>
                        </div>
                    </td>
                    <td>
                        <div class="table-number">
                            Table <?php echo htmlspecialchars($table['table_number']); ?>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $table['status']; ?>">
                            <i class="fas fa-<?php echo $table['status'] === 'active' ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo ucfirst($table['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="stats-cell">
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-clipboard-list"></i>
                                    Total Orders
                                </div>
                                <div class="stat-value"><?php echo $table['total_orders']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-clock"></i>
                                    Pending
                                </div>
                                <div class="stat-value"><?php echo $table['pending_orders']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-qrcode"></i>
                                    QR Codes
                                </div>
                                <div class="stat-value"><?php echo $table['active_qr_codes']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="actions-cell">
                        <button type="button" class="btn-action btn-edit" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editTableModal<?php echo $table['id']; ?>"
                                title="Edit Table">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="qr_codes.php?table=<?php echo $table['id']; ?>" 
                           class="btn-action btn-qr"
                           title="Manage QR Codes">
                            <i class="fas fa-qrcode"></i>
                        </a>
                        <button type="button" class="btn-action btn-delete" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteTableModal<?php echo $table['id']; ?>"
                                title="Delete Table">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Table Number</label>
                        <input type="text" class="form-control" name="table_number" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_table" class="btn btn-primary">Add Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Table Modals -->
<?php foreach ($tables as $table): ?>
<div class="modal fade" id="editTableModal<?php echo $table['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Table Number</label>
                        <input type="text" class="form-control" name="new_table_number" 
                               value="<?php echo htmlspecialchars($table['table_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="new_status">
                            <option value="active" <?php echo $table['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $table['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_table" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Table Modal -->
<div class="modal fade" id="deleteTableModal<?php echo $table['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete Table <?php echo htmlspecialchars($table['table_number']); ?>?</p>
                <?php if ($table['total_orders'] > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This table has order history. It will be deactivated instead of deleted.
                </div>
                <?php endif; ?>
            </div>
            <form method="POST">
                <div class="modal-footer">
                    <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_table" class="btn btn-danger">
                        <?php echo $table['total_orders'] > 0 ? 'Deactivate Table' : 'Delete Table'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();

// Add JavaScript for search and filter functionality
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Search functionality
    const searchInput = document.getElementById("tableSearch");
    const tableItems = document.querySelectorAll(".table-item");
    
    searchInput.addEventListener("input", function() {
        const searchTerm = this.value.toLowerCase();
        
        tableItems.forEach(item => {
            const tableNumber = item.querySelector(".card-title").textContent.toLowerCase();
            
            if (tableNumber.includes(searchTerm)) {
                item.style.display = "";
            } else {
                item.style.display = "none";
            }
        });
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
            tableItems.forEach(item => {
                if (filter === "all" || item.dataset.status === filter) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
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