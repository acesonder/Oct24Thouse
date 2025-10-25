<?php
/**
 * Case Management API
 * Handles case plans, goals, and tasks
 */

// Create case plan
if ($endpoint === 'case/create' && $method === 'POST') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $client_id = $input['client_id'] ?? null;
    $plan_name = $input['plan_name'] ?? null;
    $description = $input['description'] ?? null;
    $start_date = $input['start_date'] ?? date('Y-m-d');
    $target_end_date = $input['target_end_date'] ?? null;
    
    if (!$client_id || !$plan_name) {
        sendResponse(['success' => false, 'message' => 'Client ID and plan name required'], 400);
    }
    
    $query = "INSERT INTO case_plans (client_id, staff_id, plan_name, description, start_date, target_end_date) 
              VALUES (:client_id, :staff_id, :plan_name, :description, :start_date, :target_end_date)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'client_id' => $client_id,
        'staff_id' => $user->id,
        'plan_name' => $plan_name,
        'description' => $description,
        'start_date' => $start_date,
        'target_end_date' => $target_end_date
    ]);
    
    $case_plan_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'case_plan_id' => $case_plan_id, 'message' => 'Case plan created'], 201);
}

// Update case plan
if ($method === 'PUT' && preg_match('/case\/(\d+)/', $endpoint, $matches)) {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $case_plan_id = $matches[1];
    
    $fields = [];
    $params = ['id' => $case_plan_id];
    
    if (isset($input['plan_name'])) {
        $fields[] = "plan_name = :plan_name";
        $params['plan_name'] = $input['plan_name'];
    }
    
    if (isset($input['description'])) {
        $fields[] = "description = :description";
        $params['description'] = $input['description'];
    }
    
    if (isset($input['target_end_date'])) {
        $fields[] = "target_end_date = :target_end_date";
        $params['target_end_date'] = $input['target_end_date'];
    }
    
    if (isset($input['status'])) {
        $fields[] = "status = :status";
        $params['status'] = $input['status'];
    }
    
    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }
    
    $query = "UPDATE case_plans SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    sendResponse(['success' => true, 'message' => 'Case plan updated']);
}

// Get case plan with goals
if ($method === 'GET' && preg_match('/case\/(\d+)/', $endpoint, $matches)) {
    $case_plan_id = $matches[1];
    
    // Get case plan
    $planQuery = "SELECT cp.*, u.first_name, u.last_name, u.email 
                  FROM case_plans cp 
                  LEFT JOIN users u ON cp.client_id = u.id 
                  WHERE cp.id = :id";
    
    $planStmt = $db->prepare($planQuery);
    $planStmt->execute(['id' => $case_plan_id]);
    
    if ($planStmt->rowCount() == 0) {
        sendResponse(['success' => false, 'message' => 'Case plan not found'], 404);
    }
    
    $case_plan = $planStmt->fetch();
    
    // Get goals for this case plan
    $goalsQuery = "SELECT * FROM goals WHERE case_plan_id = :case_plan_id ORDER BY created_at";
    $goalsStmt = $db->prepare($goalsQuery);
    $goalsStmt->execute(['case_plan_id' => $case_plan_id]);
    $case_plan['goals'] = $goalsStmt->fetchAll();
    
    // Get tasks for each goal
    foreach ($case_plan['goals'] as &$goal) {
        $tasksQuery = "SELECT * FROM tasks WHERE goal_id = :goal_id ORDER BY due_date";
        $tasksStmt = $db->prepare($tasksQuery);
        $tasksStmt->execute(['goal_id' => $goal['id']]);
        $goal['tasks'] = $tasksStmt->fetchAll();
    }
    
    sendResponse(['success' => true, 'case_plan' => $case_plan]);
}

// List case plans
if ($endpoint === 'case/list' && $method === 'GET') {
    $client_id = $_GET['client_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $query = "SELECT cp.*, u.first_name, u.last_name 
              FROM case_plans cp 
              LEFT JOIN users u ON cp.client_id = u.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($client_id) {
        $query .= " AND cp.client_id = :client_id";
        $params['client_id'] = $client_id;
    }
    
    if ($status) {
        $query .= " AND cp.status = :status";
        $params['status'] = $status;
    }
    
    $query .= " ORDER BY cp.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $case_plans = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'case_plans' => $case_plans]);
}

// If no endpoint matched, return error
error_log("Case API: Unknown endpoint - $endpoint with method $method");
sendResponse(['success' => false, 'message' => 'Case endpoint not found: ' . $endpoint], 404);
