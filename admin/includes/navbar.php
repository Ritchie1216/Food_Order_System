<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Custom CSS for Navbar -->
<style>
:root {
    --navbar-bg: #1a1a1a;
    --card-bg: #1a1a1a;
    --primary-color: #d4af37;
    --primary-dark: #b8860b;
    --text-primary: #d4d4d4;
    --text-secondary: #808080;
    --text-muted: #4a4a4a;
    --border-color: rgba(45, 45, 45, 0.8);
    --danger-color: #8b0000;
}

.top-navbar {
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    height: var(--navbar-height);
    background: var(--navbar-bg) !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    z-index: 1040;
    padding: 0 1.5rem;
    backdrop-filter: blur(8px);
    border-bottom: 1px solid var(--border-color);
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--primary-color) !important;
    padding: 0;
}

.brand-wrapper {
    width: var(--sidebar-width);
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    border-right: 1px solid var(--border-color);
    margin-right: 1rem;
}

.brand-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
    transition: all 0.3s ease;
}

.brand-icon:hover {
    transform: rotate(10deg) scale(1.1);
}

.brand-icon i {
    color: #1a1a1a;
    font-size: 1.4rem;
}

.navbar .nav-link {
    color: var(--text-secondary) !important;
    padding: 0.5rem 1rem !important;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.navbar .nav-link:hover {
    color: var(--primary-color) !important;
    background: rgba(212, 175, 55, 0.1);
    transform: translateY(-2px);
}

.navbar .nav-link.active {
    color: var(--primary-color) !important;
    background: rgba(212, 175, 55, 0.15);
}

.notification-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(212, 175, 55, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
}

.notification-btn:hover {
    background: rgba(212, 175, 55, 0.15);
    transform: translateY(-2px);
}

.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: var(--danger-color);
    color: var(--text-primary);
    border-radius: 30px;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    border: 2px solid var(--navbar-bg);
    padding: 0 6px;
}

.user-profile {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 16px;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
}

.user-profile:hover {
    background: rgba(212, 175, 55, 0.1);
    transform: translateY(-2px);
    border-color: var(--primary-color);
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}

.user-avatar i {
    color: #1a1a1a;
    font-size: 1.2rem;
}

.user-info {
    margin-right: 12px;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    font-size: 0.95rem;
}

.user-role {
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin: 0;
}

.dropdown-menu {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    border-radius: 16px;
    padding: 0.5rem;
    min-width: 240px;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    color: var(--text-secondary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: rgba(212, 175, 55, 0.1);
    color: var(--primary-color);
    transform: translateX(5px);
}

.dropdown-item i {
    font-size: 1.1rem;
    color: var(--text-muted);
    transition: all 0.3s ease;
}

.dropdown-item:hover i {
    color: var(--primary-color);
}

.dropdown-divider {
    border-color: var(--border-color);
    margin: 0.5rem 0;
}

.notification-list {
    max-height: 360px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.3s ease;
    background: var(--card-bg);
}

.notification-item:hover {
    background: rgba(212, 175, 55, 0.1);
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(212, 175, 55, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.notification-text {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.notification-time {
    color: var(--text-muted);
    font-size: 0.75rem;
}

/* Toast Styles */
.toast {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.toast-header {
    background: var(--navbar-bg);
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
}

.toast-header .btn-close {
    filter: invert(1);
}

@media (max-width: 991.98px) {
    .brand-wrapper {
        width: auto;
        border: none;
        padding: 0 1rem;
    }
    
    .user-info {
        display: none;
    }

    .navbar-brand span {
        display: none;
    }
}

@media (max-width: 576px) {
    .top-navbar {
        padding: 0 1rem;
    }

    .notification-btn {
        width: 38px;
        height: 38px;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
    }
}
</style>

<nav class="navbar top-navbar">
    <div class="d-flex align-items-center w-100">
        <!-- Brand -->
        <div class="brand-wrapper">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <div class="brand-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <span>FoodAdmin</span>
            </a>
        </div>

        <!-- Toggle Button -->
        <button class="btn notification-btn d-lg-none me-2" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Right Menu Items -->
        <div class="d-flex align-items-center ms-auto gap-3">
            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn notification-btn" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0">
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Notifications</h6>
                            <a href="#" class="text-muted text-decoration-none" style="font-size: 0.8rem;">Mark all as read</a>
                        </div>
                    </div>
                    <div class="notification-list">
                        <div class="notification-item">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon me-3">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="notification-content">
                                    <h6 class="notification-title">New order received</h6>
                                    <p class="notification-text">Table 5 - 3 items</p>
                                    <span class="notification-time">2 minutes ago</span>
                                </div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <h6 class="notification-title">Order completed</h6>
                                    <p class="notification-text">Order #1234 has been completed</p>
                                    <span class="notification-time">5 minutes ago</span>
                                </div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon me-3">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <h6 class="notification-title">Low stock alert</h6>
                                    <p class="notification-text">Item "Chicken Wings" is running low</p>
                                    <span class="notification-time">10 minutes ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 text-center border-top">
                        <a href="#" class="btn btn-link text-primary text-decoration-none">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="dropdown">
                <a href="#" class="user-profile text-decoration-none" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <p class="user-name"><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></p>
                        <p class="user-role">Administrator</p>
                    </div>
                    <i class="fas fa-chevron-down ms-2" style="color: var(--text-muted); font-size: 0.8rem;"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <div class="px-3 py-2">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <p class="user-name mb-0"><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></p>
                                <p class="user-role mb-0">Administrator</p>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                    <a class="dropdown-item" href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Toast Notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div class="toast" role="alert" id="notificationToast">
        <div class="toast-header">
            <i class="fas fa-bell me-2 text-primary"></i>
            <strong class="me-auto">Notification</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            <!-- Notification content will be inserted here -->
        </div>
    </div>
</div> 