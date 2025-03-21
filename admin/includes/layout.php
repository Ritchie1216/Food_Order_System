<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Restaurant Admin' : 'Restaurant Admin'; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
    :root {
        --primary-color: #7C3AED;
        --primary-dark: #6D28D9;
        --primary-light: #8B5CF6;
        --success-color: #10B981;
        --warning-color: #F59E0B;
        --danger-color: #EF4444;
        --info-color: #3B82F6;
        --navbar-height: 70px;
        --sidebar-width: 280px;
        --body-bg: #F3F4F6;
        --card-bg: #FFFFFF;
        --text-primary: #1F2937;
        --text-secondary: #4B5563;
        --text-muted: #9CA3AF;
        --border-color: rgba(124, 58, 237, 0.1);
    }

    body {
        min-height: 100vh;
        background: var(--body-bg);
        padding-top: var(--navbar-height);
        font-family: 'Inter', sans-serif;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 1.5rem;
        min-height: calc(100vh - var(--navbar-height));
    }

    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
        }
    }

    .loading-spinner {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-spinner::after {
        content: "";
        width: 40px;
        height: 40px;
        border: 3px solid var(--primary-light);
        border-top-color: var(--primary-color);
        border-radius: 50%;
        animation: spinner 0.6s linear infinite;
    }

    @keyframes spinner {
        to {
            transform: rotate(360deg);
        }
    }

    /* Smooth Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: var(--body-bg);
        border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-light);
        border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-color);
    }

    /* Card Styles */
    .card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: var(--card-bg);
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem;
        border-radius: 16px 16px 0 0 !important;
    }

    /* Button Styles */
    .btn {
        border-radius: 12px;
        padding: 0.625rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: var(--primary-color);
        border-color: var(--primary-color);
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.3);
    }

    /* Form Controls */
    .form-control {
        border-radius: 12px;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
    }

    /* Table Styles */
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .table th {
        background: var(--body-bg);
        border-bottom: 2px solid var(--border-color);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .table td {
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background: rgba(124, 58, 237, 0.05);
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 12px;
        padding: 1rem 1.25rem;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-color);
    }

    .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-color);
    }

    .alert-info {
        background: rgba(59, 130, 246, 0.1);
        color: var(--info-color);
    }
    </style>

    <!-- Page Specific CSS -->
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner"></div>

    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php if (isset($content)) echo $content; ?>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Loading Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide loading spinner when page is loaded
        const spinner = document.getElementById('loadingSpinner');
        spinner.style.opacity = '0';
        setTimeout(() => {
            spinner.style.display = 'none';
        }, 300);

        // Handle sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                if (window.innerWidth > 991.98) {
                    mainContent.style.marginLeft = sidebar.classList.contains('show') ? '0' : 'var(--sidebar-width)';
                }
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('show');
                    mainContent.style.marginLeft = 'var(--sidebar-width)';
                } else {
                    mainContent.style.marginLeft = '0';
                }
            }, 250);
        });
    });

    // Show loading spinner when navigating
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' && !e.target.hasAttribute('data-bs-toggle')) {
            const spinner = document.getElementById('loadingSpinner');
            spinner.style.display = 'flex';
            spinner.style.opacity = '1';
        }
    });
    </script>

    <!-- Page Specific Scripts -->
    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html> 