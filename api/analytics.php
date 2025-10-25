<?php
/**
 * Analytics API
 * Provides data analytics and reporting
 */

// Dashboard analytics
if ($endpoint === 'analytics/dashboard' && $method === 'GET') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $analytics = [];
    
    // Total clients
    $clientQuery = "SELECT COUNT(*) as total FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'client')";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $analytics['total_clients'] = $clientStmt->fetch()['total'];
    
    // Active stays
    $stayQuery = "SELECT COUNT(*) as total FROM stays WHERE status = 'active'";
    $stayStmt = $db->prepare($stayQuery);
    $stayStmt->execute();
    $analytics['active_stays'] = $stayStmt->fetch()['total'];
    
    // Average length of stay
    $losQuery = "SELECT AVG(DATEDIFF(IFNULL(actual_exit_date, NOW()), admission_date)) as avg_los 
                 FROM stays WHERE status IN ('active', 'completed')";
    $losStmt = $db->prepare($losQuery);
    $losStmt->execute();
    $analytics['avg_length_of_stay'] = round($losStmt->fetch()['avg_los'], 1);
    
    // Bed occupancy rate
    $occupancyQuery = "SELECT 
                       (SELECT COUNT(*) FROM beds WHERE status = 'occupied') /
                       (SELECT COUNT(*) FROM beds WHERE status != 'maintenance') * 100 as rate";
    $occupancyStmt = $db->prepare($occupancyQuery);
    $occupancyStmt->execute();
    $analytics['occupancy_rate'] = round($occupancyStmt->fetch()['rate'], 1);
    
    // Recent intakes (last 30 days)
    $intakeQuery = "SELECT COUNT(*) as total FROM intakes 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $intakeStmt = $db->prepare($intakeQuery);
    $intakeStmt->execute();
    $analytics['recent_intakes'] = $intakeStmt->fetch()['total'];
    
    // Exit outcomes (last 90 days)
    $outcomeQuery = "SELECT exit_destination, COUNT(*) as count 
                     FROM stays 
                     WHERE actual_exit_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                     AND exit_destination IS NOT NULL
                     GROUP BY exit_destination";
    $outcomeStmt = $db->prepare($outcomeQuery);
    $outcomeStmt->execute();
    $analytics['exit_outcomes'] = $outcomeStmt->fetchAll();
    
    sendResponse(['success' => true, 'analytics' => $analytics]);
}

// Occupancy trends
if ($endpoint === 'analytics/occupancy' && $method === 'GET') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $days = $_GET['days'] ?? 30;
    
    // This would require a more complex query with daily snapshots
    // For now, return a simple response
    sendResponse(['success' => true, 'message' => 'Occupancy trends coming soon']);
}

// Export data (HIFIS-compatible format)
if ($endpoint === 'analytics/export' && $method === 'GET') {
    // Check if user is admin
    if ($user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $format = $_GET['format'] ?? 'csv';
    $type = $_GET['type'] ?? 'clients';
    
    // This would generate export files
    sendResponse(['success' => true, 'message' => 'Export functionality coming soon']);
}

// If no endpoint matched, return error
error_log("Analytics API: Unknown endpoint - $endpoint with method $method");
sendResponse(['success' => false, 'message' => 'Analytics endpoint not found: ' . $endpoint], 404);
