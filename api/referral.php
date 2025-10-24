<?php
/**
 * Referral API
 * Handles referrals to partner agencies
 */

// Create referral
if ($endpoint === 'referral/send' && $method === 'POST') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $client_id = $input['client_id'] ?? null;
    $partner_id = $input['partner_id'] ?? null;
    $service_type = $input['service_type'] ?? null;
    $referral_data = $input['referral_data'] ?? [];
    $notes = $input['notes'] ?? null;
    
    if (!$client_id || !$service_type) {
        sendResponse(['success' => false, 'message' => 'Client ID and service type required'], 400);
    }
    
    $query = "INSERT INTO referrals (client_id, staff_id, partner_id, service_type, referral_data, notes, status) 
              VALUES (:client_id, :staff_id, :partner_id, :service_type, :referral_data, :notes, 'pending')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'client_id' => $client_id,
        'staff_id' => $user->id,
        'partner_id' => $partner_id,
        'service_type' => $service_type,
        'referral_data' => json_encode($referral_data),
        'notes' => $notes
    ]);
    
    $referral_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'referral_id' => $referral_id, 'message' => 'Referral sent successfully'], 201);
}

// Update referral status
if ($method === 'PUT' && preg_match('/referral\/(\d+)/', $endpoint, $matches)) {
    $referral_id = $matches[1];
    $status = $input['status'] ?? null;
    $notes = $input['notes'] ?? null;
    
    if (!$status) {
        sendResponse(['success' => false, 'message' => 'Status required'], 400);
    }
    
    $query = "UPDATE referrals SET status = :status";
    $params = ['id' => $referral_id, 'status' => $status];
    
    if ($notes) {
        $query .= ", notes = :notes";
        $params['notes'] = $notes;
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    sendResponse(['success' => true, 'message' => 'Referral updated successfully']);
}

// List referrals
if ($endpoint === 'referral/list' && $method === 'GET') {
    $client_id = $_GET['client_id'] ?? null;
    $partner_id = $_GET['partner_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $query = "SELECT r.*, 
              c.first_name as client_first_name, c.last_name as client_last_name,
              s.first_name as staff_first_name, s.last_name as staff_last_name
              FROM referrals r 
              LEFT JOIN users c ON r.client_id = c.id
              LEFT JOIN users s ON r.staff_id = s.id
              WHERE 1=1";
    
    $params = [];
    
    if ($client_id) {
        $query .= " AND r.client_id = :client_id";
        $params['client_id'] = $client_id;
    }
    
    if ($partner_id) {
        $query .= " AND r.partner_id = :partner_id";
        $params['partner_id'] = $partner_id;
    }
    
    if ($status) {
        $query .= " AND r.status = :status";
        $params['status'] = $status;
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $referrals = $stmt->fetchAll();
    
    // Decode JSON fields
    foreach ($referrals as &$referral) {
        $referral['referral_data'] = json_decode($referral['referral_data'], true);
    }
    
    sendResponse(['success' => true, 'referrals' => $referrals]);
}
