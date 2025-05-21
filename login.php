<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/POS_SYS/error.log');

session_start();

error_log("Login page loaded at " . date('Y-m-d H:i:s'));

session_unset();
session_destroy();
session_start();

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

// Check if database needs initialization
function initializeDatabase($database) {
    try {
        // Check if categories table exists
        $result = $database->getConnection()->query("SHOW TABLES LIKE 'categories'");
        if ($result->num_rows == 0) {
            // Database needs initialization
            error_log("Database initialization required. Running install script...");
            
            // Read and execute the install.sql file
            $sql = file_get_contents('database/install.sql');
            
            // Split into individual queries
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($queries as $query) {
                if (!empty($query)) {
                    try {
                        // Skip ALTER TABLE statements for adding columns that might already exist
                        if (stripos($query, 'ALTER TABLE users ADD COLUMN table_start') !== false ||
                            stripos($query, 'ALTER TABLE users ADD COLUMN table_end') !== false) {
                            continue;
                        }
                        $database->getConnection()->query($query);
                    } catch (Exception $queryError) {
                        // Log the error but continue with other queries
                        error_log("Query failed: " . $queryError->getMessage());
                        error_log("Failed query: " . $query);
                    }
                }
            }
            
            error_log("Database initialization completed successfully");
            return true;
        }
        return true;
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Initialize Database and Auth
try {
    $database = new Database();
    $auth = new Auth($database);

    // Check and initialize database if needed
    if (!initializeDatabase($database)) {
        $error = "System initialization failed. Please contact administrator.";
    }

    // Debug: Print connection status
    if ($database->isConnected()) {
        error_log("Database connection successful");
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $error = "System is temporarily unavailable. Please try again later.";
    // You might want to redirect to an error page or show a user-friendly message
}

$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $sql = "SELECT id, username, password, role, full_name, table_start, table_end 
                FROM users 
                WHERE username = ? AND status = 'active'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['table_start'] = $user['table_start'];
            $_SESSION['table_end'] = $user['table_end'];

            // Create new session record
            $database->createUserSession($user['id']);
            
            // Clean up old sessions
            $database->cleanupOldSessions();

            // Redirect based on role
            switch ($_SESSION['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'manager':
                    header('Location: manager/dashboard.php');
                    break;
                case 'cashier':
                    header('Location: cashier/index.php');
                    break;
                case 'waiter':
                    header('Location: waiter/index.php');
                    break;
                default:
                    header('Location: login.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cubana POS</title>
    <?php
    // Add absolute path check for CSS file
    $cssFile = 'assets/css/login.css';
    $absolutePath = __DIR__ . '/' . $cssFile;
    if (!file_exists($absolutePath)) {
        error_log("CSS file not found at: " . $absolutePath);
    } else {
        error_log("CSS file exists at: " . $absolutePath);
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
   

    <div class="login-container">
        <div class="logo-container">
            <img src="images/logo_cubana.png" alt="Cubana Ma Pub Logo" class="logo">
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="copyright">
            © <?php echo date('Y'); ?> Cubana Ma Pub<br>
            <span>Powered by OSI Solutions Ltd</span>
        </div>
    </div>

    <div class="bg-icons">
        <!-- Column 1 -->
        <img src="images/logo_cubana.png" class="floating-icon size-xs" style="left: 5%; bottom: -100px; animation-delay: 0s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-sm" style="left: 5%; bottom: -100px; animation-delay: 4s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-md" style="left: 5%; bottom: -100px; animation-delay: 8s;" alt="Cubana Logo">
        <!-- Column 2 -->
        <img src="images/logo_cubana.png" class="floating-icon size-md" style="left: 25%; bottom: -100px; animation-delay: 1s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-lg" style="left: 25%; bottom: -100px; animation-delay: 5s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-sm" style="left: 25%; bottom: -100px; animation-delay: 9s;" alt="Cubana Logo">
        <!-- Column 3 -->
        <img src="images/logo_cubana.png" class="floating-icon size-lg" style="left: 45%; bottom: -100px; animation-delay: 2s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-xs" style="left: 45%; bottom: -100px; animation-delay: 6s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-md" style="left: 45%; bottom: -100px; animation-delay: 10s;" alt="Cubana Logo">
        <!-- Column 4 -->
        <img src="images/logo_cubana.png" class="floating-icon size-sm" style="left: 65%; bottom: -100px; animation-delay: 3s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-md" style="left: 65%; bottom: -100px; animation-delay: 7s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-lg" style="left: 65%; bottom: -100px; animation-delay: 11s;" alt="Cubana Logo">
        <!-- Column 5 -->
        <img src="images/logo_cubana.png" class="floating-icon size-xs" style="left: 85%; bottom: -100px; animation-delay: 2.5s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-lg" style="left: 85%; bottom: -100px; animation-delay: 6.5s;" alt="Cubana Logo">
        <img src="images/logo_cubana.png" class="floating-icon size-sm" style="left: 85%; bottom: -100px; animation-delay: 10.5s;" alt="Cubana Logo">
    </div>
</body>
</html> 