<?php
/**
 * Peer Engagement API
 * Handles peer profiles, mentorship, and engagement tracking
 */

// Get peer profile
if ($method === 'GET' && preg_match('/peer\/profile\/(\d+)/', $endpoint, $matches)) {
    $peer_id = $matches[1];
    
    $query = "SELECT p.*, u.first_name, u.last_name, u.email 
              FROM peer_profiles p 
              LEFT JOIN users u ON p.user_id = u.id 
              WHERE p.user_id = :peer_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['peer_id' => $peer_id]);
    
    if ($stmt->rowCount() == 0) {
        sendResponse(['success' => false, 'message' => 'Peer profile not found'], 404);
    }
    
    $profile = $stmt->fetch();
    
    // Decode JSON fields
    $profile['specializations'] = json_decode($profile['specializations'], true);
    $profile['certifications'] = json_decode($profile['certifications'], true);
    
    sendResponse(['success' => true, 'profile' => $profile]);
}

// Log peer engagement
if ($endpoint === 'peer/engagement' && $method === 'POST') {
    // Check if user is peer, staff, or admin
    if ($user->role !== 'peer' && $user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $client_id = $input['client_id'] ?? null;
    $engagement_type = $input['engagement_type'] ?? null;
    $duration = $input['duration'] ?? null;
    $topic = $input['topic'] ?? null;
    $outcome = $input['outcome'] ?? null;
    $notes = $input['notes'] ?? null;
    
    if (!$client_id || !$engagement_type) {
        sendResponse(['success' => false, 'message' => 'Client ID and engagement type required'], 400);
    }
    
    $query = "INSERT INTO peer_engagements (peer_id, client_id, engagement_type, duration, topic, outcome, notes) 
              VALUES (:peer_id, :client_id, :engagement_type, :duration, :topic, :outcome, :notes)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'peer_id' => $user->id,
        'client_id' => $client_id,
        'engagement_type' => $engagement_type,
        'duration' => $duration,
        'topic' => $topic,
        'outcome' => $outcome,
        'notes' => $notes
    ]);
    
    $engagement_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'engagement_id' => $engagement_id, 'message' => 'Engagement logged successfully'], 201);
}

// Request mentorship
if ($endpoint === 'peer/mentorship/request' && $method === 'POST') {
    $mentor_id = $input['mentor_id'] ?? null;
    
    if (!$mentor_id) {
        sendResponse(['success' => false, 'message' => 'Mentor ID required'], 400);
    }
    
    // Check if mentor is approved
    $checkQuery = "SELECT is_mentor, is_approved FROM peer_profiles WHERE user_id = :mentor_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute(['mentor_id' => $mentor_id]);
    
    if ($checkStmt->rowCount() == 0) {
        sendResponse(['success' => false, 'message' => 'Mentor not found'], 404);
    }
    
    $mentor = $checkStmt->fetch();
    
    if (!$mentor['is_mentor'] || !$mentor['is_approved']) {
        sendResponse(['success' => false, 'message' => 'Mentor not available'], 400);
    }
    
    $query = "INSERT INTO mentorships (mentor_id, mentee_id, status) 
              VALUES (:mentor_id, :mentee_id, 'requested')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'mentor_id' => $mentor_id,
        'mentee_id' => $user->id
    ]);
    
    $mentorship_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'mentorship_id' => $mentorship_id, 'message' => 'Mentorship requested successfully'], 201);
}

// List available mentors
if ($endpoint === 'peer/mentors' && $method === 'GET') {
    $query = "SELECT p.*, u.first_name, u.last_name 
              FROM peer_profiles p 
              LEFT JOIN users u ON p.user_id = u.id 
              WHERE p.is_mentor = 1 AND p.is_approved = 1 
              ORDER BY p.reputation_points DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $mentors = $stmt->fetchAll();
    
    // Decode JSON fields
    foreach ($mentors as &$mentor) {
        $mentor['specializations'] = json_decode($mentor['specializations'], true);
        $mentor['certifications'] = json_decode($mentor['certifications'], true);
    }
    
    sendResponse(['success' => true, 'mentors' => $mentors]);
}

// If no endpoint matched, return error
error_log("Peer API: Unknown endpoint - $endpoint with method $method");
sendResponse(['success' => false, 'message' => 'Peer endpoint not found: ' . $endpoint], 404);
