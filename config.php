<?php
// config.php
// Database configuration and helper functions for the Legal Management System
// Safe session start if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'adivogados');

try {
    // Connect to MySQL server (initially without database to support auto-creation)
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select database
    $pdo->exec("USE `" . DB_NAME . "`");

    // Check if tables need to be created (e.g. check if 'users' table exists)
    $checkTable = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $checkTable->rowCount() > 0;

    if (!$tableExists) {
        $sqlPath = __DIR__ . '/database.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Execute the SQL creation script
            $pdo->exec($sql);
        }
    }

    // Verify session user status if logged in
    if (isset($_SESSION['user_id'])) {
        $stmtUserCheck = $pdo->prepare("SELECT status, role, name, email FROM `users` WHERE id = ?");
        $stmtUserCheck->execute([$_SESSION['user_id']]);
        $currentUserData = $stmtUserCheck->fetch();

        if (!$currentUserData || in_array($currentUserData['status'], ['banned', 'suspended'])) {
            $isBanned = ($currentUserData && $currentUserData['status'] === 'banned');
            $blockMessage = $isBanned 
                ? 'Sua conta foi banida permanentemente pelo ADM. Acesso negado.' 
                : 'Sua conta foi suspensa/punida temporariamente. Entre em contato com a administração.';

            session_unset();
            session_destroy();
            
            $isApiRequest = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
            if ($isApiRequest) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'account_banned' => true,
                    'status_type' => $currentUserData ? $currentUserData['status'] : 'banned',
                    'message' => $blockMessage
                ]);
                exit;
            } else {
                header('Location: index.php?error=' . ($isBanned ? 'banned' : 'suspended'));
                exit;
            }
        } else {
            $_SESSION['user_role'] = $currentUserData['role'];
            $_SESSION['user_name'] = $currentUserData['name'];
            $_SESSION['user_email'] = $currentUserData['email'];
        }
    }
} catch (PDOException $e) {
    // Format response based on request type
    $isApiRequest = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
    if ($isApiRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()
        ]);
        exit;
    } else {
        // Human-friendly error page with modern style
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro de Banco de Dados - Advocacia</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #0f172a;
                    color: #f1f5f9;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .container {
                    background: rgba(30, 41, 59, 0.7);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    padding: 2.5rem;
                    border-radius: 12px;
                    max-width: 500px;
                    width: 100%;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
                    text-align: center;
                }
                h1 { color: #f43f5e; margin-top: 0; font-size: 1.8rem; }
                p { color: #94a3b8; line-height: 1.6; }
                .code-box {
                    background-color: #020617;
                    padding: 1rem;
                    border-radius: 6px;
                    font-family: monospace;
                    font-size: 0.9rem;
                    color: #fda4af;
                    text-align: left;
                    overflow-x: auto;
                    margin: 1.5rem 0;
                    border-left: 4px solid #f43f5e;
                }
                .btn {
                    display: inline-block;
                    background-color: #2563eb;
                    color: white;
                    padding: 0.75rem 1.5rem;
                    border-radius: 6px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: background-color 0.2s;
                }
                .btn:hover { background-color: #1d4ed8; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Conexão com Banco de Dados Falhou</h1>
                <p>Não foi possível estabelecer uma conexão com o MySQL. Certifique-se de que o servidor de banco de dados está ativo e que as credenciais no arquivo <code>config.php</code> estão corretas.</p>
                <div class="code-box"><?php echo htmlspecialchars($e->getMessage()); ?></div>
                <p>Se você estiver usando o XAMPP localmente, verifique se o painel do MySQL está iniciado.</p>
                <a href="index.php" class="btn">Tentar Novamente</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Logs a system activity.
 *
 * @param int|null $userId
 * @param string $userName
 * @param string $action
 * @param string|null $details
 */
function logActivity($userId, $userName, $action, $details = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO `logs` (user_id, user_name, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $userName, $action, $details]);
    } catch (Exception $e) {
        // Gracefully ignore log recording failures to prevent app crashes
    }
}

/**
 * Get configuration value by key.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function getSystemSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM `settings` WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Role labels mapping for Portuguese output.
 */
function getRoleLabel($role) {
    $roles = [
        'admin' => 'ADM',
        'moderator' => 'Moderador',
        'member' => 'Membro',
        'lawyer' => 'Membro',
        'receptionist' => 'Moderador',
        'viewer' => 'Membro'
    ];
    return isset($roles[$role]) ? $roles[$role] : 'Membro';
}

/**
 * Role permissions definition matrix.
 */
function getRolePermissions($role = null) {
    if ($role === null && isset($_SESSION['user_role'])) {
        $role = $_SESSION['user_role'];
    }

    $permissions = [
        'admin' => [
            'manage_users' => true,
            'ban_users' => true,
            'suspend_users' => true,
            'manage_roles' => true,
            'manage_requests' => true,
            'view_requests' => true,
            'manage_settings' => true,
            'view_logs' => true,
            'delete_requests' => true
        ],
        'moderator' => [
            'manage_users' => true,
            'ban_users' => false,
            'suspend_users' => true,
            'manage_roles' => false,
            'manage_requests' => true,
            'view_requests' => true,
            'manage_settings' => false,
            'view_logs' => (getSystemSetting('enable_logs_for_lawyers', '1') === '1'),
            'delete_requests' => false
        ],
        'member' => [
            'manage_users' => false,
            'ban_users' => false,
            'suspend_users' => false,
            'manage_roles' => false,
            'manage_requests' => true,
            'view_requests' => true,
            'manage_settings' => false,
            'view_logs' => false,
            'delete_requests' => false
        ]
    ];

    // Map legacy roles
    if ($role === 'lawyer' || $role === 'viewer') {
        $role = 'member';
    } else if ($role === 'receptionist') {
        $role = 'moderator';
    }

    return isset($permissions[$role]) ? $permissions[$role] : $permissions['member'];
}

/**
 * Check if the current user (or specified role) has a given permission capability.
 */
function hasPermission($permissionName, $role = null) {
    $rolePermissions = getRolePermissions($role);
    return !empty($rolePermissions[$permissionName]);
}
