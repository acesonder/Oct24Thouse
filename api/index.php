<?php
/**
 * API Router
 * Main entry point for all API requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';

// Remove base path and query string
$endpoint = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
$endpoint = trim($endpoint, '/');

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
    
    if (strpos($endpoint, 'setup') === 0) {
        require_once __DIR__ . '/setup.php';
        exit();
    }
    
    // Endpoint not found
    sendResponse(['success' => false, 'message' => 'Endpoint not found'], 404);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error'], 500);
}
