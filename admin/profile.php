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

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate and update password
    if (!empty($current_password) && !empty($new_password)) {
        if ($new_password === $confirm_password) {
            if ($auth->updatePassword($_SESSION['admin_id'], $current_password, $new_password)) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Current password is incorrect!";
            }
        } else {
            $error_message = "New passwords do not match!";
        }
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

.profile-header {
    background: var(--surface-dark);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.profile-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.profile-subtitle {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-top: 0.5rem;
}

.profile-section {
    background: var(--surface-dark);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.profile-info-card {
    background: var(--surface-dark);
    border-radius: 16px;
    padding: 1.5rem;
    height: 100%;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
}

.profile-info-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary);
    box-shadow: var(--card-shadow);
}

.profile-avatar {
    width: 120px;
    height: 120px;
    background: linear-gradient(145deg, var(--primary), var(--primary-light));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(200, 161, 101, 0.3);
}

.profile-avatar i {
    font-size: 3rem;
    color: var(--bg-dark);
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.info-list li:hover {
    background: rgba(200, 161, 101, 0.05);
    padding: 1rem 1rem;
    margin: 0 -1rem;
    border-radius: 8px;
}

.info-list li:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-secondary);
    font-weight: 500;
    width: 120px;
}

.info-value {
    color: var(--text-primary);
    font-weight: 600;
}

.password-form {
    background: var(--surface-dark);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.form-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
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

.password-toggle {
    cursor: pointer;
    position: absolute;
    right: 1rem;
    top: 65%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: var(--primary);
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

.btn-primary {
    background: linear-gradient(145deg, var(--primary), var(--primary-light));
    color: var(--bg-dark);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(200, 161, 101, 0.3);
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
}

.badge.bg-success {
    background: rgba(76, 175, 80, 0.2) !important;
    color: var(--success);
}

@media (max-width: 768px) {
    .profile-info-card {
        margin-bottom: 1.5rem;
    }
    
    .info-list li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .info-label {
        width: 100%;
    }
}
</style>';

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <!-- Profile Header -->
    <div class="profile-header">
        <h1 class="profile-title">My Profile</h1>
        <p class="profile-subtitle">Manage your account settings and security preferences</p>
    </div>

    <?php if ($success_message || $error_message): ?>
    <div class="alert alert-<?php echo $success_message ? 'success' : 'danger'; ?>" role="alert">
        <i class="fas fa-<?php echo $success_message ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
        <?php echo $success_message ?: $error_message; ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Info Card -->
        <div class="col-12 col-lg-4">
            <div class="profile-info-card">
                <div class="text-center">
                    <div class="profile-avatar mx-auto">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></h4>
                    <p class="text-muted">Administrator</p>
                </div>
                <ul class="info-list mt-4">
                    <li>
                        <span class="info-label">Email</span>
                        <span class="info-value">admin@example.com</span>
                    </li>
                    <li>
                        <span class="info-label">Role</span>
                        <span class="info-value">Administrator</span>
                    </li>
                    <li>
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?php echo date('F Y'); ?></span>
                    </li>
                    <li>
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge bg-success">Active</span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Password Change Form -->
        <div class="col-12 col-lg-8">
            <div class="password-form">
                <h3 class="form-title">Change Password</h3>
                <form method="POST" action="">
                    <div class="mb-4 position-relative">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                    </div>

                    <div class="mb-4 position-relative">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                    </div>

                    <div class="mb-4 position-relative">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for animations and interactions
$extra_js = '
<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

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

    // Animate cards on load with stagger effect
    const cards = document.querySelectorAll(".profile-info-card, .password-form");
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
        const submitBtn = this.querySelector(".btn-primary");
        submitBtn.style.width = submitBtn.offsetWidth + "px";
        submitBtn.innerHTML = \'<i class="fas fa-spinner fa-spin"></i>\';
        submitBtn.style.opacity = "0.8";
        submitBtn.disabled = true;
    });
});
</script>';

include 'includes/layout.php';
?> 