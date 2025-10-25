<?php
/**
 * Setup API
 * Handles database management, demo data, and system configuration
 * Protected by setup password
 */

define('SETUP_PASSWORD', '079777');

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Verify setup password
$setup_password = $_SERVER['HTTP_X_SETUP_PASSWORD'] ?? null;
if ($setup_password !== SETUP_PASSWORD) {
    error_log("Setup access denied: Invalid setup password");
    sendResponse(['success' => false, 'message' => 'Unauthorized access to setup'], 403);
}

// Check database status
if ($endpoint === 'setup/check-database' && $method === 'GET') {
    try {
        error_log("Setup: Checking database status");
        
        if (!$db) {
            throw new Exception("Database connection is null");
        }
        
        $query = "SHOW TABLES";
        $stmt = $db->query($query);
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Setup: Database check successful - " . count($tables) . " tables found");
        
        sendResponse([
            'success' => true,
            'tables' => count($tables),
            'table_list' => $tables
        ]);
    } catch (PDOException $e) {
        error_log("Setup: Database connection failed - " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ], 500);
    } catch (Exception $e) {
        error_log("Setup: Database check error - " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Error checking database: ' . $e->getMessage()
        ], 500);
    }
}

// Reset tables
if ($endpoint === 'setup/reset-tables' && $method === 'POST') {
    try {
        error_log("Setup: Starting table reset");
        
        // Drop existing tables (in reverse order of dependencies)
        $dropTables = [
            'audit_log',
            'sessions',
            'password_resets',
            'chat_messages',
            'chat_groups',
            'chat_group_members',
            'peer_engagements',
            'peer_mentorships',
            'training_completions',
            'referrals',
            'case_tasks',
            'case_goals',
            'case_plans',
            'intake_assessments',
            'intakes',
            'bed_assignments',
            'beds',
            'users'
        ];
        
        foreach ($dropTables as $table) {
            try {
                $db->exec("DROP TABLE IF EXISTS $table");
                error_log("Setup: Dropped table $table");
            } catch (PDOException $e) {
                error_log("Setup: Could not drop table $table - " . $e->getMessage());
                // Table might not exist, continue
            }
        }
        
        // Read and execute schema
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found at: $schemaFile");
        }
        
        error_log("Setup: Reading schema from $schemaFile");
        $schema = file_get_contents($schemaFile);
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $db->exec($statement);
                } catch (PDOException $e) {
                    error_log("Setup: Schema execution error: " . $e->getMessage() . " - Statement: " . substr($statement, 0, 100));
                }
            }
        }
        
        error_log("Setup: Tables reset successfully");
        
        sendResponse([
            'success' => true,
            'message' => 'Tables reset successfully'
        ]);
    } catch (Exception $e) {
        error_log("Setup: Reset tables error: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Failed to reset tables: ' . $e->getMessage()
        ], 500);
    }
}

// Import demo data
if ($endpoint === 'setup/import-demo' && $method === 'POST') {
    try {
        error_log("Setup: Starting demo data import");
        
        // Create demo users
        $demoUsers = [
            [
                'email' => 'client@demo.com',
                'username' => 'demo_client',
                'password' => 'demo1234',
                'role_id' => 1,
                'first_name' => 'Demo',
                'last_name' => 'Client'
            ],
            [
                'email' => 'staff@demo.com',
                'username' => 'demo_staff',
                'password' => 'demo1234',
                'role_id' => 2,
                'first_name' => 'Demo',
                'last_name' => 'Staff'
            ],
            [
                'email' => 'peer@demo.com',
                'username' => 'demo_peer',
                'password' => 'demo1234',
                'role_id' => 3,
                'first_name' => 'Demo',
                'last_name' => 'Peer'
            ],
            [
                'email' => 'partner@demo.com',
                'username' => 'demo_partner',
                'password' => 'demo1234',
                'role_id' => 4,
                'first_name' => 'Demo',
                'last_name' => 'Partner'
            ],
            [
                'email' => 'admin@demo.com',
                'username' => 'demo_admin',
                'password' => 'demo1234',
                'role_id' => 5,
                'first_name' => 'Demo',
                'last_name' => 'Admin'
            ]
        ];
        
        $usersCreated = 0;
        foreach ($demoUsers as $user) {
            // Check if user exists
            $checkQuery = "SELECT id FROM users WHERE email = :email";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute(['email' => $user['email']]);
            
            if ($checkStmt->rowCount() == 0) {
                $password_hash = password_hash($user['password'], PASSWORD_BCRYPT);
                
                $insertQuery = "INSERT INTO users (email, username, password_hash, role_id, first_name, last_name, is_active) 
                               VALUES (:email, :username, :password_hash, :role_id, :first_name, :last_name, 1)";
                
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'password_hash' => $password_hash,
                    'role_id' => $user['role_id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ]);
                $usersCreated++;
                error_log("Setup: Created demo user - " . $user['email']);
            } else {
                error_log("Setup: Demo user already exists - " . $user['email']);
            }
        }
        
        // Create demo beds
        $bedsCreated = 0;
        for ($i = 1; $i <= 20; $i++) {
            $checkQuery = "SELECT id FROM beds WHERE bed_number = :bed_number";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute(['bed_number' => "BED-{$i}"]);
            
            if ($checkStmt->rowCount() == 0) {
                $insertQuery = "INSERT INTO beds (bed_number, room_number, floor, status) 
                               VALUES (:bed_number, :room_number, :floor, 'available')";
                
                $floor = ceil($i / 10);
                $room = (($i - 1) % 5) + 1;
                
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    'bed_number' => "BED-{$i}",
                    'room_number' => "R{$floor}{$room}",
                    'floor' => $floor
                ]);
                $bedsCreated++;
            }
        }
        
        error_log("Setup: Demo data import completed - Users: $usersCreated, Beds: $bedsCreated");
        
        sendResponse([
            'success' => true,
            'message' => "Demo data imported successfully (Users: $usersCreated, Beds: $bedsCreated)"
        ]);
    } catch (Exception $e) {
        error_log("Setup: Import demo data error: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Failed to import demo data: ' . $e->getMessage()
        ], 500);
    }
}

// Backup database
if ($endpoint === 'setup/backup' && $method === 'POST') {
    try {
        error_log("Setup: Starting database backup");
        
        $backupDir = __DIR__ . '/../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
            error_log("Setup: Created backup directory: $backupDir");
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        
        // Get database credentials from config
        $dbName = getenv('DB_NAME') ?: 'transition_house';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASSWORD') ?: '';
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        
        // Create backup using mysqldump
        $command = sprintf(
            'mysqldump -h%s -u%s %s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return);
        
        if ($return === 0 && file_exists($filepath)) {
            error_log("Setup: Backup created successfully - $filename");
            sendResponse([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename,
                'size' => filesize($filepath)
            ]);
        } else {
            error_log("Setup: Backup failed - mysqldump return code: $return");
            sendResponse([
                'success' => false,
                'message' => 'Backup failed - mysqldump not available or error occurred'
            ], 500);
        }
    } catch (Exception $e) {
        error_log("Setup: Backup error: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Backup failed: ' . $e->getMessage()
        ], 500);
    }
}

// Test database connection
if ($endpoint === 'setup/test-db' && $method === 'GET') {
    try {
        error_log("Setup: Testing database connection");
        
        if (!$db) {
            throw new Exception("Database connection is null");
        }
        
        $query = "SELECT VERSION() as version";
        $stmt = $db->query($query);
        $result = $stmt->fetch();
        
        error_log("Setup: Database test successful - MySQL version: " . $result['version']);
        
        sendResponse([
            'success' => true,
            'message' => 'Database connection successful',
            'version' => $result['version']
        ]);
    } catch (Exception $e) {
        error_log("Setup: Database test failed - " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ], 500);
    }
}

// If no endpoint matched, return error
error_log("Setup: Unknown endpoint requested - $endpoint with method $method");
sendResponse([
    'success' => false,
    'message' => 'Setup endpoint not found: ' . $endpoint
], 404);
