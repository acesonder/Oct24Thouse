<?php
/**
 * Authentication Helper
 * JWT-based authentication with security features
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
    private $db;
    private $secret_key;
    private $issuer;
    private $audience;
    private $token_expiry = 3600; // 1 hour

    public function __construct($db) {
        $this->db = $db;
        $this->secret_key = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
        $this->issuer = getenv('JWT_ISSUER') ?: 'transition-house';
        $this->audience = getenv('JWT_AUDIENCE') ?: 'transition-house-users';
    }

    /**
     * Register a new user
     */
    public function register($data) {
        // Validate required fields
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['role_id'])) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['email' => $data['email']]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

        // Insert user
        $query = "INSERT INTO users (email, username, password_hash, role_id, first_name, last_name, phone, date_of_birth, requires_2fa) 
                  VALUES (:email, :username, :password_hash, :role_id, :first_name, :last_name, :phone, :date_of_birth, :requires_2fa)";
        
        $stmt = $this->db->prepare($query);
        
        try {
            $stmt->execute([
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'password_hash' => $password_hash,
                'role_id' => $data['role_id'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'requires_2fa' => $data['role_id'] == 2 ? 1 : 0 // Staff requires 2FA
            ]);
            
            $user_id = $this->db->lastInsertId();
            $this->logAudit($user_id, 'user_registered', 'users', $user_id, null, $data);
            
            return ['success' => true, 'user_id' => $user_id];
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    /**
     * Login user and generate JWT token
     */
    public function login($email, $password, $ip_address = null, $user_agent = null) {
        $query = "SELECT u.*, r.name as role_name 
                  FROM users u 
                  INNER JOIN roles r ON u.role_id = r.id 
                  WHERE u.email = :email AND u.is_active = 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        $user = $stmt->fetch();
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if 2FA is required
        if ($user['requires_2fa']) {
            // In a full implementation, this would trigger 2FA flow
            // For now, we'll just note it
            return [
                'success' => false,
                'requires_2fa' => true,
                'user_id' => $user['id'],
                'message' => '2FA required'
            ];
        }
        
        // Generate JWT token
        $token = $this->generateToken($user);
        
        // Store session
        $this->storeSession($user['id'], $token, $ip_address, $user_agent);
        
        // Update last login
        $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute(['id' => $user['id']]);
        
        $this->logAudit($user['id'], 'user_login', 'users', $user['id'], null, null);
        
        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role_name']
            ]
        ];
    }

    /**
     * Generate JWT token
     */
    private function generateToken($user) {
        $issued_at = time();
        $expiration = $issued_at + $this->token_expiry;
        
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issued_at,
            'exp' => $expiration,
            'data' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role_name']
            ]
        ];
        
        return JWT::encode($payload, $this->secret_key, 'HS256');
    }

    /**
     * Verify JWT token
     */
    public function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            
            // Check if session is still valid
            $token_hash = hash('sha256', $token);
            $query = "SELECT id FROM sessions WHERE token_hash = :token_hash AND is_revoked = 0 AND expires_at > NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['token_hash' => $token_hash]);
            
            if ($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Invalid or expired session'];
            }
            
            return ['success' => true, 'data' => $decoded->data];
        } catch (Exception $e) {
            error_log("Token verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invalid token'];
        }
    }

    /**
     * Store session in database
     */
    private function storeSession($user_id, $token, $ip_address, $user_agent) {
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + $this->token_expiry);
        
        $query = "INSERT INTO sessions (user_id, token_hash, expires_at, ip_address, user_agent) 
                  VALUES (:user_id, :token_hash, :expires_at, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'user_id' => $user_id,
            'token_hash' => $token_hash,
            'expires_at' => $expires_at,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ]);
    }

    /**
     * Logout user by revoking session
     */
    public function logout($token) {
        $token_hash = hash('sha256', $token);
        
        $query = "UPDATE sessions SET is_revoked = 1 WHERE token_hash = :token_hash";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['token_hash' => $token_hash]);
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Log audit trail
     */
    private function logAudit($user_id, $action, $entity_type, $entity_id, $old_values, $new_values) {
        $query = "INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
                  VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'old_values' => $old_values ? json_encode($old_values) : null,
            'new_values' => $new_values ? json_encode($new_values) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Check user permission
     */
    public function hasPermission($user_id, $required_role) {
        $query = "SELECT r.name FROM users u 
                  INNER JOIN roles r ON u.role_id = r.id 
                  WHERE u.id = :user_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        $user = $stmt->fetch();
        
        // Role hierarchy: admin > staff > peer > client
        $role_hierarchy = ['admin' => 4, 'staff' => 3, 'peer' => 2, 'client' => 1, 'partner' => 1];
        
        return $role_hierarchy[$user['name']] >= $role_hierarchy[$required_role];
    }
}
