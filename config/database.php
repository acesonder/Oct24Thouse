<?php
/**
 * Database Configuration
 * Transition House - Database connection settings
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    public $conn;

    public function __construct() {
        // Load from environment variables for security
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'transition_house';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            error_log("Database: Attempting connection to " . $this->host . " as " . $this->username);
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            error_log("Database: Connection successful to database " . $this->db_name);
        } catch(PDOException $exception) {
            error_log("Database: Connection error - " . $exception->getMessage());
            error_log("Database: Host=" . $this->host . ", DB=" . $this->db_name . ", User=" . $this->username);
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
