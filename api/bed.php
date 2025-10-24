<?php
/**
 * Bed Management API
 * Handles bed availability, assignments, and reservations
 */

// Get all beds
if ($endpoint === 'bed/list' && $method === 'GET') {
    $status = $_GET['status'] ?? null;
    
    $query = "SELECT b.*, s.client_id, u.first_name, u.last_name 
              FROM beds b 
              LEFT JOIN stays s ON b.id = s.bed_id AND s.status = 'active'
              LEFT JOIN users u ON s.client_id = u.id";
    
    if ($status) {
        $query .= " WHERE b.status = :status";
    }
    
    $query .= " ORDER BY b.bed_number";
    
    $stmt = $db->prepare($query);
    
    if ($status) {
        $stmt->execute(['status' => $status]);
    } else {
        $stmt->execute();
    }
    
    $beds = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'beds' => $beds]);
}

// Assign bed to client
if ($endpoint === 'bed/assign' && $method === 'POST') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $bed_id = $input['bed_id'] ?? null;
    $client_id = $input['client_id'] ?? null;
    $planned_exit_date = $input['planned_exit_date'] ?? null;
    
    if (!$bed_id || !$client_id) {
        sendResponse(['success' => false, 'message' => 'Bed ID and Client ID required'], 400);
    }
    
    // Check if bed is available
    $checkQuery = "SELECT status FROM beds WHERE id = :bed_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute(['bed_id' => $bed_id]);
    
    if ($checkStmt->rowCount() == 0) {
        sendResponse(['success' => false, 'message' => 'Bed not found'], 404);
    }
    
    $bed = $checkStmt->fetch();
    
    if ($bed['status'] !== 'available') {
        sendResponse(['success' => false, 'message' => 'Bed not available'], 400);
    }
    
    // Create stay record
    $stayQuery = "INSERT INTO stays (client_id, bed_id, planned_exit_date, status) 
                  VALUES (:client_id, :bed_id, :planned_exit_date, 'active')";
    
    $stayStmt = $db->prepare($stayQuery);
    $stayStmt->execute([
        'client_id' => $client_id,
        'bed_id' => $bed_id,
        'planned_exit_date' => $planned_exit_date
    ]);
    
    // Update bed status
    $updateQuery = "UPDATE beds SET status = 'occupied' WHERE id = :bed_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute(['bed_id' => $bed_id]);
    
    sendResponse(['success' => true, 'message' => 'Bed assigned successfully']);
}

// Release bed
if ($endpoint === 'bed/release' && $method === 'POST') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $bed_id = $input['bed_id'] ?? null;
    $exit_reason = $input['exit_reason'] ?? null;
    $exit_destination = $input['exit_destination'] ?? null;
    
    if (!$bed_id) {
        sendResponse(['success' => false, 'message' => 'Bed ID required'], 400);
    }
    
    // Update stay record
    $stayQuery = "UPDATE stays 
                  SET status = 'completed', 
                      actual_exit_date = NOW(), 
                      exit_reason = :exit_reason,
                      exit_destination = :exit_destination
                  WHERE bed_id = :bed_id AND status = 'active'";
    
    $stayStmt = $db->prepare($stayQuery);
    $stayStmt->execute([
        'bed_id' => $bed_id,
        'exit_reason' => $exit_reason,
        'exit_destination' => $exit_destination
    ]);
    
    // Update bed status
    $updateQuery = "UPDATE beds SET status = 'available' WHERE id = :bed_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute(['bed_id' => $bed_id]);
    
    sendResponse(['success' => true, 'message' => 'Bed released successfully']);
}

// Get bed statistics
if ($endpoint === 'bed/stats' && $method === 'GET') {
    $query = "SELECT 
                COUNT(*) as total_beds,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_beds,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_beds,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved_beds,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_beds
              FROM beds";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $stats = $stmt->fetch();
    
    sendResponse(['success' => true, 'stats' => $stats]);
}
