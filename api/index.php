<?php
/**
 * API Router
 * Main entry point for all API requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Setup-Password');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
} catch (Exception $e) {
    error_log("API: Failed to initialize database - " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization failed. Please check configuration.',
        'error' => $e->getMessage()
    ]);
    exit();
}

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';

// Remove base path and query string
$endpoint = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
$endpoint = trim($endpoint, '/');

error_log("API: Request - Method: $method, Endpoint: $endpoint");

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Get authorization header
$headers = getallheaders();
$token = null;
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

/**
 * Helper function to send JSON response
 */
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

/**
 * Helper function to verify authentication
 */
function requireAuth($auth, $token) {
    if (!$token) {
        sendResponse(['success' => false, 'message' => 'No token provided'], 401);
    }
    
    $result = $auth->verifyToken($token);
    if (!$result['success']) {
        sendResponse(['success' => false, 'message' => 'Invalid token'], 401);
    }
    
    return $result['data'];
}

// Route the request
try {
    // Public endpoints (no authentication required)
    if ($endpoint === 'auth/register' && $method === 'POST') {
        $result = $auth->register($input);
        sendResponse($result, $result['success'] ? 201 : 400);
    }
    
    if ($endpoint === 'auth/login' && $method === 'POST') {
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        
        if (!$email || !$password) {
            sendResponse(['success' => false, 'message' => 'Email and password required'], 400);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $result = $auth->login($email, $password, $ip, $user_agent);
        sendResponse($result, $result['success'] ? 200 : 401);
    }
    
    if ($endpoint === 'auth/forgot-password' && $method === 'POST') {
        $email = $input['email'] ?? null;
        
        if (!$email) {
            sendResponse(['success' => false, 'message' => 'Email required'], 400);
        }
        
        $result = $auth->forgotPassword($email);
        sendResponse($result);
    }
    
    // Setup endpoints (protected by setup password, not user authentication)
    if (strpos($endpoint, 'setup') === 0) {
        require_once __DIR__ . '/setup.php';
        exit();
    }
    
    // Protected endpoints (authentication required)
    $user = requireAuth($auth, $token);
    
    if ($endpoint === 'auth/logout' && $method === 'POST') {
        $result = $auth->logout($token);
        sendResponse($result);
    }
    
    if ($endpoint === 'auth/me' && $method === 'GET') {
        sendResponse(['success' => true, 'user' => $user]);
    }
    
    // Include specific API modules
    if (strpos($endpoint, 'intake') === 0) {
        require_once __DIR__ . '/intake.php';
        exit();
    }
    
    if (strpos($endpoint, 'case') === 0) {
        require_once __DIR__ . '/case.php';
        exit();
    }
    
    if (strpos($endpoint, 'bed') === 0) {
        require_once __DIR__ . '/bed.php';
        exit();
    }
    
    if (strpos($endpoint, 'peer') === 0) {
        require_once __DIR__ . '/peer.php';
        exit();
    }
    
    if (strpos($endpoint, 'chat') === 0) {
        require_once __DIR__ . '/chat.php';
        exit();
    }
    
    if (strpos($endpoint, 'referral') === 0) {
        require_once __DIR__ . '/referral.php';
        exit();
    }
    
    if (strpos($endpoint, 'training') === 0) {
        require_once __DIR__ . '/training.php';
        exit();
    }
    
    if (strpos($endpoint, 'analytics') === 0) {
        require_once __DIR__ . '/analytics.php';
        exit();
    }
    
    if (strpos($endpoint, 'notifications') === 0) {
        require_once __DIR__ . '/notifications.php';
        exit();
    }
    
    // Endpoint not found
    sendResponse(['success' => false, 'message' => 'Endpoint not found'], 404);
    
} catch (Exception $e) {
    error_log("API Error: Endpoint=$endpoint, Method=$method, Error=" . $e->getMessage());
    error_log("API Error Stack Trace: " . $e->getTraceAsString());
    sendResponse(['success' => false, 'message' => 'Internal server error', 'error' => $e->getMessage()], 500);
}
