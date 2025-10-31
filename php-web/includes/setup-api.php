<?php
/**
 * Setup API Handler
 * Handles all setup operations including database configuration,
 * user management, backups, diagnostics, and system configuration
 */

// Security check - setup password
define('SETUP_PASSWORD', '079777');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/setup_errors.log');

// CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Setup-Password');
header('Content-Type: application/json');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verify setup password
$setupPassword = $_SERVER['HTTP_X_SETUP_PASSWORD'] ?? '';
if ($setupPassword !== SETUP_PASSWORD) {
    sendResponse([
        'success' => false,
        'message' => 'Unauthorized: Invalid setup password'
    ], 403);
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$action = end($pathParts);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Load database class
require_once __DIR__ . '/../config/database.php';

/**
 * Send JSON response
 */
function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/**
 * Log to setup log file
 */
function logSetup($message) {
    $logFile = __DIR__ . '/../logs/setup.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
    error_log("Setup: {$message}");
}

// Route handlers
try {
    switch ($action) {
        
        // Test database connection
        case 'test-connection':
            logSetup("Testing database connection");
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                $version = $conn->query("SELECT VERSION()")->fetchColumn();
                
                sendResponse([
                    'success' => true,
                    'message' => 'Database connection successful',
                    'mysql_version' => $version
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Connection failed: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Configure database
        case 'configure-database':
            logSetup("Configuring database");
            
            $dbName = $input['database_name'] ?? '';
            $overwrite = $input['overwrite'] ?? false;
            $profile = $input['profile_name'] ?? 'default';
            
            if (empty($dbName)) {
                sendResponse([
                    'success' => false,
                    'message' => 'Database name is required'
                ], 400);
            }
            
            try {
                $db = Database::getInstance();
                
                // Create/select database
                $result = $db->selectDatabase($dbName, $overwrite);
                
                if ($result) {
                    // Save configuration to profile
                    $config = array_merge($db->getConfig(), $input);
                    $db->saveProfile($profile, $config);
                    
                    // Execute schema if requested
                    if ($input['create_tables'] ?? false) {
                        $schemaFile = __DIR__ . '/../../database/schema.sql';
                        if (file_exists($schemaFile)) {
                            $execResult = $db->executeSqlFile($schemaFile);
                            logSetup("Schema executed: {$execResult['executed']} statements");
                        }
                    }
                    
                    logSetup("Database configured: {$dbName}, profile: {$profile}");
                    
                    sendResponse([
                        'success' => true,
                        'message' => 'Database configured successfully',
                        'database' => $dbName,
                        'profile' => $profile
                    ]);
                } else {
                    sendResponse([
                        'success' => false,
                        'message' => 'Failed to configure database'
                    ], 500);
                }
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Configuration error: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // List profiles
        case 'list-profiles':
            $profiles = Database::listProfiles();
            sendResponse([
                'success' => true,
                'profiles' => $profiles
            ]);
            break;
            
        // Get database stats
        case 'database-stats':
            try {
                $db = Database::getInstance();
                $stats = $db->getStats();
                
                sendResponse([
                    'success' => true,
                    'stats' => $stats
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Failed to get stats: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Create account
        case 'create-account':
            logSetup("Creating user account");
            
            $required = ['email', 'username', 'password', 'role_id', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse([
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ], 400);
                }
            }
            
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                // Check if user exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
                $stmt->execute([
                    'email' => $input['email'],
                    'username' => $input['username']
                ]);
                
                if ($stmt->rowCount() > 0) {
                    sendResponse([
                        'success' => false,
                        'message' => 'User already exists with that email or username'
                    ], 400);
                }
                
                // Create user
                $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT);
                
                $stmt = $conn->prepare("
                    INSERT INTO users (email, username, password_hash, role_id, first_name, last_name, is_active)
                    VALUES (:email, :username, :password_hash, :role_id, :first_name, :last_name, 1)
                ");
                
                $stmt->execute([
                    'email' => $input['email'],
                    'username' => $input['username'],
                    'password_hash' => $passwordHash,
                    'role_id' => $input['role_id'],
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name']
                ]);
                
                logSetup("User created: {$input['email']}");
                
                sendResponse([
                    'success' => true,
                    'message' => 'Account created successfully',
                    'user_id' => $conn->lastInsertId()
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Failed to create account: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // List accounts
        case 'list-accounts':
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                $stmt = $conn->query("
                    SELECT u.id, u.email, u.username, u.first_name, u.last_name, 
                           u.role_id, r.name as role_name, u.is_active, u.created_at
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    ORDER BY u.created_at DESC
                ");
                
                $users = $stmt->fetchAll();
                
                sendResponse([
                    'success' => true,
                    'accounts' => $users
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Failed to list accounts: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Modify account
        case 'modify-account':
            logSetup("Modifying user account");
            
            $userId = $input['user_id'] ?? null;
            if (!$userId) {
                sendResponse([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                $updates = [];
                $params = ['user_id' => $userId];
                
                if (isset($input['email'])) {
                    $updates[] = "email = :email";
                    $params['email'] = $input['email'];
                }
                if (isset($input['username'])) {
                    $updates[] = "username = :username";
                    $params['username'] = $input['username'];
                }
                if (isset($input['first_name'])) {
                    $updates[] = "first_name = :first_name";
                    $params['first_name'] = $input['first_name'];
                }
                if (isset($input['last_name'])) {
                    $updates[] = "last_name = :last_name";
                    $params['last_name'] = $input['last_name'];
                }
                if (isset($input['role_id'])) {
                    $updates[] = "role_id = :role_id";
                    $params['role_id'] = $input['role_id'];
                }
                if (isset($input['is_active'])) {
                    $updates[] = "is_active = :is_active";
                    $params['is_active'] = $input['is_active'];
                }
                if (!empty($input['password'])) {
                    $updates[] = "password_hash = :password_hash";
                    $params['password_hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
                }
                
                if (empty($updates)) {
                    sendResponse([
                        'success' => false,
                        'message' => 'No fields to update'
                    ], 400);
                }
                
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :user_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                logSetup("User modified: ID {$userId}");
                
                sendResponse([
                    'success' => true,
                    'message' => 'Account modified successfully'
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Failed to modify account: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Delete account
        case 'delete-account':
            $userId = $input['user_id'] ?? null;
            if (!$userId) {
                sendResponse([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
                $stmt->execute(['user_id' => $userId]);
                
                logSetup("User deleted: ID {$userId}");
                
                sendResponse([
                    'success' => true,
                    'message' => 'Account deleted successfully'
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Failed to delete account: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Backup database
        case 'backup':
            logSetup("Creating database backup");
            
            try {
                $db = Database::getInstance();
                $result = $db->backup();
                
                if ($result['success']) {
                    logSetup("Backup created: {$result['filename']}");
                    sendResponse($result);
                } else {
                    sendResponse($result, 500);
                }
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Backup failed: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // List backups
        case 'list-backups':
            $backupDir = __DIR__ . '/../backups';
            $backups = [];
            
            if (is_dir($backupDir)) {
                $files = glob($backupDir . '/*.sql');
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => filesize($file),
                        'created' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
            
            sendResponse([
                'success' => true,
                'backups' => $backups
            ]);
            break;
            
        // Restore database
        case 'restore':
            $filename = $input['filename'] ?? '';
            if (empty($filename)) {
                sendResponse([
                    'success' => false,
                    'message' => 'Backup filename is required'
                ], 400);
            }
            
            logSetup("Restoring database from: {$filename}");
            
            try {
                $db = Database::getInstance();
                $backupFile = __DIR__ . '/../backups/' . $filename;
                $result = $db->restore($backupFile);
                
                if ($result['success']) {
                    logSetup("Database restored from: {$filename}");
                }
                
                sendResponse($result);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Restore failed: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Import demo data
        case 'import-demo':
            logSetup("Importing demo data");
            
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                // Demo users
                $demoUsers = [
                    ['client@demo.com', 'demo_client', 'Demo Client', 1],
                    ['staff@demo.com', 'demo_staff', 'Demo Staff', 2],
                    ['peer@demo.com', 'demo_peer', 'Demo Peer', 3],
                    ['partner@demo.com', 'demo_partner', 'Demo Partner', 4],
                    ['admin@demo.com', 'demo_admin', 'Demo Admin', 5]
                ];
                
                $password = password_hash('demo1234', PASSWORD_BCRYPT);
                $created = 0;
                
                foreach ($demoUsers as $user) {
                    try {
                        $stmt = $conn->prepare("
                            INSERT INTO users (email, username, password_hash, role_id, first_name, last_name, is_active)
                            VALUES (:email, :username, :password, :role, :first, 'Demo', 1)
                            ON DUPLICATE KEY UPDATE password_hash = :password
                        ");
                        
                        $stmt->execute([
                            'email' => $user[0],
                            'username' => $user[1],
                            'password' => $password,
                            'role' => $user[3],
                            'first' => $user[2]
                        ]);
                        $created++;
                    } catch (PDOException $e) {
                        // User might already exist
                    }
                }
                
                logSetup("Demo data imported: {$created} users");
                
                sendResponse([
                    'success' => true,
                    'message' => "Demo data imported: {$created} accounts",
                    'accounts_created' => $created
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Failed to import demo data: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Get error logs
        case 'error-logs':
            $logFile = __DIR__ . '/../logs/setup_errors.log';
            $lines = $input['lines'] ?? 100;
            
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $logs = array_slice($logs, -$lines);
                
                sendResponse([
                    'success' => true,
                    'logs' => implode('', $logs)
                ]);
            } else {
                sendResponse([
                    'success' => true,
                    'logs' => 'No error logs found'
                ]);
            }
            break;
            
        // Run diagnostics
        case 'diagnostics':
            logSetup("Running system diagnostics");
            
            $results = [];
            
            // Check PHP version
            $results['php_version'] = [
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'WARNING'
            ];
            
            // Check database connection
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                $results['database'] = [
                    'status' => 'OK',
                    'message' => 'Connected'
                ];
            } catch (Exception $e) {
                $results['database'] = [
                    'status' => 'ERROR',
                    'message' => $e->getMessage()
                ];
            }
            
            // Check directories
            $directories = ['backups', 'logs', 'uploads'];
            foreach ($directories as $dir) {
                $path = __DIR__ . "/../{$dir}";
                $results["dir_{$dir}"] = [
                    'path' => $path,
                    'exists' => is_dir($path),
                    'writable' => is_writable($path),
                    'status' => (is_dir($path) && is_writable($path)) ? 'OK' : 'ERROR'
                ];
            }
            
            // Check required PHP extensions
            $extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
            foreach ($extensions as $ext) {
                $results["ext_{$ext}"] = [
                    'loaded' => extension_loaded($ext),
                    'status' => extension_loaded($ext) ? 'OK' : 'ERROR'
                ];
            }
            
            sendResponse([
                'success' => true,
                'diagnostics' => $results
            ]);
            break;
            
        // Reset system
        case 'reset-system':
            if (!($input['confirm'] ?? false)) {
                sendResponse([
                    'success' => false,
                    'message' => 'Reset must be confirmed'
                ], 400);
            }
            
            logSetup("Resetting system");
            
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                // Get all tables
                $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                // Disable foreign key checks
                $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Drop all tables
                foreach ($tables as $table) {
                    $conn->exec("DROP TABLE IF EXISTS `{$table}`");
                }
                
                // Re-enable foreign key checks
                $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                logSetup("System reset completed");
                
                sendResponse([
                    'success' => true,
                    'message' => 'System reset successfully',
                    'tables_dropped' => count($tables)
                ]);
            } catch (Exception $e) {
                sendResponse([
                    'success' => false,
                    'message' => 'Reset failed: ' . $e->getMessage()
                ], 500);
            }
            break;
            
        // Update branding
        case 'update-branding':
            $brandingFile = __DIR__ . '/../config/branding.json';
            
            $branding = [
                'site_name' => $input['site_name'] ?? 'Transition House',
                'site_logo' => $input['site_logo'] ?? '',
                'primary_color' => $input['primary_color'] ?? '#2c3e50',
                'secondary_color' => $input['secondary_color'] ?? '#3498db',
                'theme' => $input['theme'] ?? 'default'
            ];
            
            file_put_contents($brandingFile, json_encode($branding, JSON_PRETTY_PRINT));
            
            logSetup("Branding updated");
            
            sendResponse([
                'success' => true,
                'message' => 'Branding updated successfully',
                'branding' => $branding
            ]);
            break;
            
        // Get branding
        case 'get-branding':
            $brandingFile = __DIR__ . '/../config/branding.json';
            
            if (file_exists($brandingFile)) {
                $branding = json_decode(file_get_contents($brandingFile), true);
            } else {
                $branding = [
                    'site_name' => 'Transition House',
                    'site_logo' => '',
                    'primary_color' => '#2c3e50',
                    'secondary_color' => '#3498db',
                    'theme' => 'default'
                ];
            }
            
            sendResponse([
                'success' => true,
                'branding' => $branding
            ]);
            break;
            
        default:
            sendResponse([
                'success' => false,
                'message' => 'Unknown action: ' . $action
            ], 404);
    }
    
} catch (Exception $e) {
    logSetup("Error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}
