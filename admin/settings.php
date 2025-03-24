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

// Handle settings update
$success_message = '';
$error_message = '';

// Check if settings table exists and create if not
try {
    $check_table = "SHOW TABLES LIKE 'settings'";
    $table_exists = $db->query($check_table)->rowCount() > 0;
    
    if (!$table_exists) {
        // Create settings table
        $create_table = "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            restaurant_name VARCHAR(255) NOT NULL DEFAULT 'My Restaurant',
            contact_email VARCHAR(255) NOT NULL DEFAULT 'contact@restaurant.com',
            opening_time TIME NOT NULL DEFAULT '09:00:00',
            closing_time TIME NOT NULL DEFAULT '22:00:00',
            last_order_time TIME NOT NULL DEFAULT '21:30:00',
            online_ordering TINYINT(1) DEFAULT 1,
            reservations TINYINT(1) DEFAULT 1,
            order_notifications TINYINT(1) DEFAULT 1,
            cash_payments TINYINT(1) DEFAULT 1,
            card_payments TINYINT(1) DEFAULT 1,
            digital_payments TINYINT(1) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 6.00,
            currency_symbol VARCHAR(10) DEFAULT 'RM',
            currency_code VARCHAR(10) DEFAULT 'MYR',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($create_table);
        error_log("Created settings table");
    }
} catch (Exception $e) {
    error_log("Error checking/creating settings table: " . $e->getMessage());
}

// Get current settings
try {
    $settings_query = "SELECT * FROM settings LIMIT 1";
    $settings_stmt = $db->prepare($settings_query);
    $settings_stmt->execute();
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Initialize default settings if none exist
        $default_settings = [
            'restaurant_name' => 'My Restaurant',
            'contact_email' => 'contact@restaurant.com',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
            'last_order_time' => '21:30',
            'online_ordering' => 1,
            'reservations' => 1,
            'order_notifications' => 1,
            'cash_payments' => 1,
            'card_payments' => 1,
            'digital_payments' => 0,
            'tax_rate' => 6,
            'currency_symbol' => 'RM',
            'currency_code' => 'MYR'
        ];
        
        $fields = implode(', ', array_keys($default_settings));
        $values = implode(', ', array_fill(0, count($default_settings), '?'));
        $insert_query = "INSERT INTO settings ($fields) VALUES ($values)";
        
        try {
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute(array_values($default_settings));
            error_log("Inserted default settings");
            
            // Fetch the newly inserted settings
            $settings_stmt = $db->prepare($settings_query);
            $settings_stmt->execute();
            $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error inserting default settings: " . $e->getMessage());
            throw $e;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $error_message = "Error loading settings: " . $e->getMessage();
    $settings = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Prepare update query
        $update_fields = [
            'restaurant_name' => $_POST['restaurant_name'],
            'contact_email' => $_POST['contact_email'],
            'opening_time' => $_POST['opening_time'],
            'closing_time' => $_POST['closing_time'],
            'last_order_time' => $_POST['last_order_time'],
            'online_ordering' => isset($_POST['online_ordering']) ? 1 : 0,
            'reservations' => isset($_POST['reservations']) ? 1 : 0,
            'order_notifications' => isset($_POST['order_notifications']) ? 1 : 0,
            'cash_payments' => isset($_POST['cash_payments']) ? 1 : 0,
            'card_payments' => isset($_POST['card_payments']) ? 1 : 0,
            'digital_payments' => isset($_POST['digital_payments']) ? 1 : 0,
            'tax_rate' => $_POST['tax_rate'],
            'currency_symbol' => $_POST['currency_symbol'],
            'currency_code' => $_POST['currency_code']
        ];
        
        // If this is the first record, do an insert instead of update
        if (empty($settings['id'])) {
            $fields = implode(', ', array_keys($update_fields));
            $values = implode(', ', array_fill(0, count($update_fields), '?'));
            $query = "INSERT INTO settings ($fields) VALUES ($values)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute(array_values($update_fields));
        } else {
            // Build the SET part of the query
            $set_clauses = [];
            foreach ($update_fields as $field => $value) {
                $set_clauses[] = "$field = :$field";
            }
            
            // Create the complete update query
            $update_query = "UPDATE settings SET " . implode(', ', $set_clauses);
            $update_stmt = $db->prepare($update_query);
            
            // Bind all parameters
            foreach ($update_fields as $field => $value) {
                $update_stmt->bindValue(":$field", $value);
            }
            
            $result = $update_stmt->execute();
        }
        
        if ($result) {
            $db->commit();
            $success_message = "Settings updated successfully!";
            
            // Refresh settings
            $settings_query = "SELECT * FROM settings LIMIT 1";
            $settings_stmt = $db->prepare($settings_query);
            $settings_stmt->execute();
            $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Failed to update settings");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating settings: " . $e->getMessage());
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Custom CSS with modern design
$extra_css = '
<style>
:root {
    --primary: #c8a165;
    --primary-light: #d4af37;
    --success: #4caf50;
    --warning: #ff9800;
    --danger: #f44336;
    --info: #3b82f6;
    --bg-dark: #1a1a1a;
    --surface-dark: #2d2d2d;
    --text-primary: #ffffff;
    --text-secondary: #cccccc;
    --border-color: rgba(200, 161, 101, 0.2);
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

body {
    background: var(--bg-dark);
    color: var(--text-primary);
}

.settings-header {
    background: var(--surface-dark);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.settings-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.settings-subtitle {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-top: 0.5rem;
}

.settings-card {
    background: var(--surface-dark);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.settings-card:hover {
    transform: translateY(-2px);
    border-color: var(--primary);
    box-shadow: var(--card-shadow);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-title i {
    color: var(--primary);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-text {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-control {
    background: var(--bg-dark);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(200, 161, 101, 0.2);
    background: var(--bg-dark);
    color: var(--text-primary);
}

.custom-switch {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.custom-switch:hover {
    background: rgba(200, 161, 101, 0.05);
    padding: 0.75rem 1rem;
    margin: 0 -1rem;
    border-radius: 8px;
}

.custom-switch:last-child {
    border-bottom: none;
}

.switch-label {
    font-weight: 500;
    color: var(--text-primary);
    margin: 0;
}

.switch-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin: 0;
}

.form-switch {
    padding-left: 3em;
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
    background-color: var(--surface-dark);
    border: 2px solid var(--border-color);
}

.form-switch .form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.form-switch .form-check-input:focus {
    box-shadow: 0 0 0 2px rgba(200, 161, 101, 0.2);
}

.btn-save {
    background: linear-gradient(145deg, var(--primary), var(--primary-light));
    color: var(--bg-dark);
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(200, 161, 101, 0.3);
}

.alert {
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
    background: var(--surface-dark);
}

.alert-success {
    border-color: var(--success);
    color: var(--success);
    background: rgba(76, 175, 80, 0.1);
}

.alert-danger {
    border-color: var(--danger);
    color: var(--danger);
    background: rgba(244, 67, 54, 0.1);
}

.time-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

input[type="time"] {
    background: var(--bg-dark);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    width: 100%;
}

input[type="time"]:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(200, 161, 101, 0.2);
}

@media (max-width: 768px) {
    .settings-card {
        padding: 1.25rem;
    }
    
    .custom-switch {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .form-switch {
        padding-left: 0;
        margin-top: 0.5rem;
    }
}
</style>';

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <!-- Settings Header -->
    <div class="settings-header">
        <h1 class="settings-title">System Settings</h1>
        <p class="settings-subtitle">Configure your restaurant management system preferences</p>
    </div>

    <?php if ($success_message || $error_message): ?>
    <div class="alert alert-<?php echo $success_message ? 'success' : 'danger'; ?>" role="alert">
        <i class="fas fa-<?php echo $success_message ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
        <?php echo $success_message ?: $error_message; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- General Settings -->
        <div class="settings-card">
            <h3 class="card-title">
                <i class="fas fa-cog"></i>
                General Settings
            </h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Restaurant Name</label>
                        <input type="text" class="form-control" name="restaurant_name" value="<?php echo htmlspecialchars($settings['restaurant_name'] ?? 'My Restaurant'); ?>">
                        <div class="form-text">This name will appear on receipts and the customer interface</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'contact@restaurant.com'); ?>">
                        <div class="form-text">Primary email for customer support and notifications</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Hours -->
        <div class="settings-card">
            <h3 class="card-title">
                <i class="fas fa-clock"></i>
                Business Hours
            </h3>
            <div class="time-grid">
                <div class="form-group">
                    <label class="form-label">Opening Time</label>
                    <input type="time" class="form-control" name="opening_time" value="<?php echo htmlspecialchars($settings['opening_time'] ?? '09:00'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Closing Time</label>
                    <input type="time" class="form-control" name="closing_time" value="<?php echo htmlspecialchars($settings['closing_time'] ?? '22:00'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Order Time</label>
                    <input type="time" class="form-control" name="last_order_time" value="<?php echo htmlspecialchars($settings['last_order_time'] ?? '21:30'); ?>">
                </div>
            </div>
        </div>

        <!-- Order Settings -->
        <div class="settings-card">
            <h3 class="card-title">
                <i class="fas fa-shopping-cart"></i>
                Order Settings
            </h3>
            <div class="custom-switch">
                <div>
                    <h6 class="switch-label">Enable Online Ordering</h6>
                    <p class="switch-description">Allow customers to place orders through the website</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="online_ordering" <?php echo ($settings['online_ordering'] ?? 1) ? 'checked' : ''; ?>>
                </div>
            </div>
            <div class="custom-switch">
                <div>
                    <h6 class="switch-label">Table Reservations</h6>
                    <p class="switch-description">Enable table booking system</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="reservations" <?php echo ($settings['reservations'] ?? 1) ? 'checked' : ''; ?>>
                </div>
            </div>
            <div class="custom-switch">
                <div>
                    <h6 class="switch-label">Order Notifications</h6>
                    <p class="switch-description">Send email notifications for new orders</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="order_notifications" <?php echo ($settings['order_notifications'] ?? 1) ? 'checked' : ''; ?>>
                </div>
            </div>
        </div>

        <!-- Payment Settings -->
        <div class="settings-card">
            <h3 class="card-title">
                <i class="fas fa-credit-card"></i>
                Payment Settings
            </h3>
            <div class="custom-switch">
                <div>
                    <h6 class="switch-label">Cash Payments</h6>
                    <p class="switch-description">Accept cash payments on delivery</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="cash_payments" <?php echo ($settings['cash_payments'] ?? 1) ? 'checked' : ''; ?>>
                </div>
            </div>
            <div class="custom-switch">
                <div>
                    <h6 class="switch-label">Card Payments</h6>
                    <p class="switch-description">Accept credit/debit card payments</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="card_payments" <?php echo ($settings['card_payments'] ?? 1) ? 'checked' : ''; ?>>
                </div>
            </div>
            <div class="custom-switch">
                <div>
                    <h6 class="switch-label">Digital Wallets</h6>
                    <p class="switch-description">Accept payments through digital wallets</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="digital_payments" <?php echo ($settings['digital_payments'] ?? 0) ? 'checked' : ''; ?>>
                </div>
            </div>
        </div>

        <!-- Tax & Currency -->
        <div class="settings-card">
            <h3 class="card-title">
                <i class="fas fa-dollar-sign"></i>
                Tax & Currency
            </h3>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" class="form-control" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? 6); ?>" min="0" max="100" step="0.1">
                        <div class="form-text">Applied to all orders</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'RM'); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Currency Code</label>
                        <input type="text" class="form-control" name="currency_code" value="<?php echo htmlspecialchars($settings['currency_code'] ?? 'MYR'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="text-end mb-4">
            <button type="submit" class="btn btn-save">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for animations and interactions
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Animate alerts with fade out
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease, transform 0.5s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-10px)";
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Animate settings cards on load with stagger effect
    const cards = document.querySelectorAll(".settings-card");
    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(20px)";
        setTimeout(() => {
            card.style.transition = "all 0.5s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, index * 100);
    });

    // Form submission animation
    const form = document.querySelector("form");
    form.addEventListener("submit", function(e) {
        const saveBtn = this.querySelector(".btn-save");
        saveBtn.style.width = saveBtn.offsetWidth + "px";
        saveBtn.innerHTML = \'<i class="fas fa-spinner fa-spin"></i>\';
        saveBtn.style.opacity = "0.8";
        saveBtn.disabled = true;
    });

    // Switch toggle animation
    const switches = document.querySelectorAll(".form-check-input");
    switches.forEach(switchEl => {
        switchEl.addEventListener("change", function() {
            this.style.transition = "all 0.3s ease";
            if (this.checked) {
                this.style.transform = "scale(1.1)";
                setTimeout(() => this.style.transform = "scale(1)", 150);
            }
        });
    });
});
</script>';

include 'includes/layout.php';
?> 