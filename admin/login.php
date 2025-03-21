<?php
session_start();
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../classes/Auth.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Debug information
    try {
        // Check if user exists
        $query = "SELECT * FROM admins WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Username not found";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-background: #111111;
            --color-surface: #1C1C1C;
            --color-surface-hover: #252525;
            --color-primary: #C6A96C;
            --color-primary-dark: #9F8755;
            --color-accent: #E5C992;
            --color-text: #F5F5F5;
            --color-text-secondary: rgba(245, 245, 245, 0.7);
            --color-border: rgba(198, 169, 108, 0.15);
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.25);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.3);
            --gradient-gold: linear-gradient(135deg, #C6A96C 0%, #E5C992 100%);
            --gradient-dark-gold: linear-gradient(135deg, #9F8755 0%, #C6A96C 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            min-height: 100vh;
            min-height: -webkit-fill-available;
            background-color: var(--color-background);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--color-text);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 1rem;
        }

        html {
            height: -webkit-fill-available;
        }

        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .login-container {
            position: relative;
            width: 100%;
            max-width: 420px;
            margin: auto;
            z-index: 1;
        }

        .login-card {
            background: var(--color-surface);
            border-radius: 20px;
            padding: clamp(1.5rem, 5vw, 2.5rem);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, 
                                      rgba(198, 169, 108, 0.08), 
                                      transparent 70%);
            pointer-events: none;
        }

        .brand-logo {
            width: clamp(60px, 15vw, 80px);
            height: clamp(60px, 15vw, 80px);
            margin: 0 auto 2rem;
            background: var(--gradient-dark-gold);
            border-radius: clamp(15px, 4vw, 20px);
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-10deg);
            transition: transform 0.3s ease;
            box-shadow: 0 4px 15px rgba(198, 169, 108, 0.2);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }

        .brand-logo i {
            font-size: clamp(1.8rem, 5vw, 2.5rem);
            color: var(--color-background);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .login-header {
            text-align: center;
            margin-bottom: clamp(2rem, 5vw, 2.5rem);
        }

        .login-title {
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: 700;
            background: var(--gradient-gold);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--color-text-secondary);
            font-size: clamp(0.85rem, 2.5vw, 0.95rem);
        }

        .form-group {
            margin-bottom: clamp(1.25rem, 4vw, 1.5rem);
            position: relative;
        }

        .form-label {
            display: block;
            color: var(--color-text);
            margin-bottom: clamp(0.5rem, 2vw, 0.75rem);
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
            font-weight: 500;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .form-control {
            width: 100%;
            height: clamp(3rem, 8vw, 3.5rem);
            padding: 0 1rem 0 3rem;
            background: var(--color-surface-hover);
            border: 2px solid var(--color-border);
            border-radius: 12px;
            color: var(--color-text);
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            -webkit-appearance: none;
            appearance: none;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(198, 169, 108, 0.15);
            outline: none;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-primary);
            font-size: clamp(1rem, 3vw, 1.2rem);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .form-control:focus + .input-icon {
            color: var(--color-accent);
        }

        .login-button {
            width: 100%;
            height: clamp(3rem, 8vw, 3.5rem);
            border: none;
            border-radius: 12px;
            background: var(--gradient-dark-gold);
            color: var(--color-background);
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(198, 169, 108, 0.2);
            -webkit-tap-highlight-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-button:active {
            transform: translateY(1px);
        }

        .alert {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.2);
            color: #ff4444;
            padding: clamp(0.875rem, 3vw, 1rem);
            border-radius: 12px;
            margin-bottom: clamp(1.25rem, 4vw, 1.5rem);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
        }

        .alert i {
            font-size: clamp(1rem, 3vw, 1.1rem);
            flex-shrink: 0;
        }

        @media (hover: hover) {
            .brand-logo:hover {
                transform: rotate(0deg) scale(1.05);
            }

            .login-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(198, 169, 108, 0.3);
            }

            .login-button:hover::before {
                left: 100%;
            }
        }

        @media (max-width: 768px) {
            body {
                align-items: flex-start;
                padding: 1.5rem 1rem;
            }

            .login-container {
                margin-top: auto;
                margin-bottom: auto;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .form-control {
                font-size: 16px; /* 防止 iOS 缩放 */
            }
        }

        @media (max-height: 600px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                padding: 1rem;
            }

            .login-container {
                margin-top: 0;
            }

            .brand-logo {
                margin-bottom: 1rem;
            }

            .login-header {
                margin-bottom: 1.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }
        }

        @supports (-webkit-touch-callout: none) {
            body {
                min-height: -webkit-fill-available;
            }
        }
    </style>
</head>
<body>
    <div class="particles-container" id="particles-js"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <i class="fas fa-utensils"></i>
            </div>

            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your administrator account</p>
            </div>

            <?php if($error): ?>
            <div class="alert" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required autocomplete="username"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <i class="fas fa-user input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required autocomplete="current-password">
                        <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                    <span>Sign In</span>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // 优化移动端性能的粒子效果配置
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: isMobile ? 20 : 40,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#C6A96C'
                },
                shape: {
                    type: 'circle'
                },
                opacity: {
                    value: 0.2,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: isMobile ? 2 : 3,
                    random: true
                },
                line_linked: {
                    enable: true,
                    distance: isMobile ? 100 : 150,
                    color: '#C6A96C',
                    opacity: 0.15,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: isMobile ? 1 : 1.5,
                    direction: 'none',
                    random: true,
                    straight: false,
                    out_mode: 'out',
                    bounce: false
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: !isMobile,
                        mode: 'grab'
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 140,
                        line_linked: {
                            opacity: 0.3
                        }
                    },
                    push: {
                        particles_nb: isMobile ? 2 : 3
                    }
                }
            },
            retina_detect: true
        });

        // 处理 iOS 设备上的视口高度问题
        function updateHeight() {
            document.documentElement.style.setProperty(
                '--vh', 
                `${window.innerHeight * 0.01}px`
            );
        }

        window.addEventListener('resize', updateHeight);
        window.addEventListener('orientationchange', updateHeight);
        updateHeight();
    </script>
</body>
</html> 