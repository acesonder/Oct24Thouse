<?php
/**
 * Intake API
 * Handles intake form creation, updating, and retrieval
 */

// Get intake by ID or create new intake
if ($method === 'GET' && preg_match('/intake\/(\d+)/', $endpoint, $matches)) {
    $intake_id = $matches[1];
    
    $query = "SELECT i.*, u.first_name, u.last_name, u.email 
              FROM intakes i 
              LEFT JOIN users u ON i.client_id = u.id 
              WHERE i.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $intake_id]);
    
    if ($stmt->rowCount() == 0) {
        sendResponse(['success' => false, 'message' => 'Intake not found'], 404);
    }
    
    $intake = $stmt->fetch();
    
    // Decode JSON fields
    $intake['intake_data'] = json_decode($intake['intake_data'], true);
    $intake['housing_history'] = json_decode($intake['housing_history'], true);
    $intake['risk_assessment'] = json_decode($intake['risk_assessment'], true);
    $intake['supports'] = json_decode($intake['supports'], true);
    
    sendResponse(['success' => true, 'intake' => $intake]);
}

// Create new intake
if ($endpoint === 'intake/create' && $method === 'POST') {
    $client_id = $input['client_id'] ?? $user->id;
    $intake_data = $input['intake_data'] ?? [];
    $housing_history = $input['housing_history'] ?? [];
    $risk_assessment = $input['risk_assessment'] ?? [];
    $supports = $input['supports'] ?? [];
    $vulnerability_score = $input['vulnerability_score'] ?? null;
    
    $query = "INSERT INTO intakes (client_id, staff_id, intake_data, housing_history, risk_assessment, supports, vulnerability_score, status) 
              VALUES (:client_id, :staff_id, :intake_data, :housing_history, :risk_assessment, :supports, :vulnerability_score, 'draft')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'client_id' => $client_id,
        'staff_id' => $user->id,
        'intake_data' => json_encode($intake_data),
        'housing_history' => json_encode($housing_history),
        'risk_assessment' => json_encode($risk_assessment),
        'supports' => json_encode($supports),
        'vulnerability_score' => $vulnerability_score
    ]);
    
    $intake_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'intake_id' => $intake_id, 'message' => 'Intake created successfully'], 201);
}

// Update existing intake
if ($method === 'PUT' && preg_match('/intake\/(\d+)/', $endpoint, $matches)) {
    $intake_id = $matches[1];
    
    $fields = [];
    $params = ['id' => $intake_id];
    
    if (isset($input['intake_data'])) {
        $fields[] = "intake_data = :intake_data";
        $params['intake_data'] = json_encode($input['intake_data']);
    }
    
    if (isset($input['housing_history'])) {
        $fields[] = "housing_history = :housing_history";
        $params['housing_history'] = json_encode($input['housing_history']);
    }
    
    if (isset($input['risk_assessment'])) {
        $fields[] = "risk_assessment = :risk_assessment";
        $params['risk_assessment'] = json_encode($input['risk_assessment']);
    }
    
    if (isset($input['supports'])) {
        $fields[] = "supports = :supports";
        $params['supports'] = json_encode($input['supports']);
    }
    
    if (isset($input['vulnerability_score'])) {
        $fields[] = "vulnerability_score = :vulnerability_score";
        $params['vulnerability_score'] = $input['vulnerability_score'];
    }
    
    if (isset($input['status'])) {
        $fields[] = "status = :status";
        $params['status'] = $input['status'];
    }
    
    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }
    
    $query = "UPDATE intakes SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    sendResponse(['success' => true, 'message' => 'Intake updated successfully']);
}

// List all intakes (staff only)
if ($endpoint === 'intake/list' && $method === 'GET') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? null;
    
    $query = "SELECT i.*, u.first_name, u.last_name, u.email 
              FROM intakes i 
              LEFT JOIN users u ON i.client_id = u.id";
    
    if ($status) {
        $query .= " WHERE i.status = :status";
    }
    
    $query .= " ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    if ($status) {
        $stmt->bindValue(':status', $status);
    }
    
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $intakes = $stmt->fetchAll();
    
    // Decode JSON fields
    foreach ($intakes as &$intake) {
        $intake['intake_data'] = json_decode($intake['intake_data'], true);
    }
    
    sendResponse(['success' => true, 'intakes' => $intakes, 'page' => $page]);
}
