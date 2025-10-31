<?php
/**
 * Database Configuration Handler
 * Manages database connections and configurations
 */

class Database {
    private static $instance = null;
    private $connection;
    private $config = [];
    
    private function __construct($profile = 'default') {
        $this->loadConfig($profile);
        $this->connect();
    }
    
    /**
     * Load database configuration from profile
     */
    private function loadConfig($profile) {
        $configFile = __DIR__ . "/profiles/{$profile}.json";
        
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            // Default configuration
            $this->config = [
                'host' => 'localhost',
                'database' => 'transition_house',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'port' => 3306
            ];
        }
    }
    
    /**
     * Save current configuration to a profile
     */
    public function saveProfile($profileName, $config) {
        $profileDir = __DIR__ . '/profiles';
        if (!is_dir($profileDir)) {
            mkdir($profileDir, 0755, true);
        }
        
        $profileFile = $profileDir . "/{$profileName}.json";
        return file_put_contents($profileFile, json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * List all available profiles
     */
    public static function listProfiles() {
        $profileDir = __DIR__ . '/profiles';
        if (!is_dir($profileDir)) {
            return [];
        }
        
        $files = glob($profileDir . '/*.json');
        return array_map(function($file) {
            return basename($file, '.json');
        }, $files);
    }
    
    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};charset={$this->config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Select or create database
            if (!empty($this->config['database'])) {
                $this->selectDatabase($this->config['database']);
            }
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Select or create database
     */
    public function selectDatabase($dbName, $overwrite = false) {
        try {
            if ($overwrite) {
                $this->connection->exec("DROP DATABASE IF EXISTS `{$dbName}`");
            }
            
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("USE `{$dbName}`");
            $this->config['database'] = $dbName;
            
            return true;
        } catch (PDOException $e) {
            error_log("Database selection error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database instance
     */
    public static function getInstance($profile = 'default') {
        if (self::$instance === null) {
            self::$instance = new self($profile);
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Get current configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Execute SQL file
     */
    public function executeSqlFile($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception("SQL file not found: {$filepath}");
        }
        
        $sql = file_get_contents($filepath);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $executed = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            
            try {
                $this->connection->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                $errors[] = "Error executing statement: " . $e->getMessage();
                error_log("SQL execution error: " . $e->getMessage());
            }
        }
        
        return [
            'executed' => $executed,
            'errors' => $errors
        ];
    }
    
    /**
     * Backup database
     */
    public function backup($backupDir = null) {
        if ($backupDir === null) {
            $backupDir = __DIR__ . '/../backups';
        }
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s > %s 2>&1',
            escapeshellarg($this->config['host']),
            escapeshellarg($this->config['port']),
            escapeshellarg($this->config['username']),
            !empty($this->config['password']) ? '-p' . escapeshellarg($this->config['password']) : '',
            escapeshellarg($this->config['database']),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return);
        
        if ($return === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Backup failed',
            'output' => implode("\n", $output)
        ];
    }
    
    /**
     * Restore database from backup
     */
    public function restore($backupFile) {
        if (!file_exists($backupFile)) {
            return [
                'success' => false,
                'message' => 'Backup file not found'
            ];
        }
        
        $command = sprintf(
            'mysql -h%s -P%s -u%s %s %s < %s 2>&1',
            escapeshellarg($this->config['host']),
            escapeshellarg($this->config['port']),
            escapeshellarg($this->config['username']),
            !empty($this->config['password']) ? '-p' . escapeshellarg($this->config['password']) : '',
            escapeshellarg($this->config['database']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $return);
        
        return [
            'success' => $return === 0,
            'message' => $return === 0 ? 'Database restored successfully' : 'Restore failed',
            'output' => implode("\n", $output)
        ];
    }
    
    /**
     * Get database statistics
     */
    public function getStats() {
        try {
            $tables = $this->connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            $stats = [
                'database' => $this->config['database'],
                'tables' => count($tables),
                'table_list' => []
            ];
            
            foreach ($tables as $table) {
                $count = $this->connection->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                $stats['table_list'][] = [
                    'name' => $table,
                    'rows' => $count
                ];
            }
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
