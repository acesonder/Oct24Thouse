<?php
/**
 * Training API
 * Handles training modules and certifications
 */

// List training modules
if ($endpoint === 'training/modules' && $method === 'GET') {
    $category = $_GET['category'] ?? null;
    
    $query = "SELECT * FROM training_modules WHERE 1=1";
    $params = [];
    
    if ($category) {
        $query .= " AND category = :category";
        $params['category'] = $category;
    }
    
    $query .= " ORDER BY title";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $modules = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'modules' => $modules]);
}

// Complete training module
if ($endpoint === 'training/complete' && $method === 'POST') {
    $module_id = $input['module_id'] ?? null;
    $score = $input['score'] ?? null;
    
    if (!$module_id) {
        sendResponse(['success' => false, 'message' => 'Module ID required'], 400);
    }
    
    // Get module details to calculate expiry
    $moduleQuery = "SELECT certification_valid_months FROM training_modules WHERE id = :module_id";
    $moduleStmt = $db->prepare($moduleQuery);
    $moduleStmt->execute(['module_id' => $module_id]);
    
    if ($moduleStmt->rowCount() == 0) {
        sendResponse(['success' => false, 'message' => 'Module not found'], 404);
    }
    
    $module = $moduleStmt->fetch();
    $expires_at = null;
    
    if ($module['certification_valid_months']) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$module['certification_valid_months']} months"));
    }
    
    $query = "INSERT INTO training_completions (user_id, module_id, score, expires_at) 
              VALUES (:user_id, :module_id, :score, :expires_at)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'user_id' => $user->id,
        'module_id' => $module_id,
        'score' => $score,
        'expires_at' => $expires_at
    ]);
    
    $completion_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'completion_id' => $completion_id, 'message' => 'Training completed'], 201);
}

// Get user's training completions
if ($endpoint === 'training/my-completions' && $method === 'GET') {
    $query = "SELECT tc.*, tm.title, tm.category, tm.certification_valid_months 
              FROM training_completions tc 
              LEFT JOIN training_modules tm ON tc.module_id = tm.id 
              WHERE tc.user_id = :user_id 
              ORDER BY tc.completed_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => $user->id]);
    
    $completions = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'completions' => $completions]);
}

// If no endpoint matched, return error
error_log("Training API: Unknown endpoint - $endpoint with method $method");
sendResponse(['success' => false, 'message' => 'Training endpoint not found: ' . $endpoint], 404);
