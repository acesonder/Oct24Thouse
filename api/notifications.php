<?php
/**
 * Notifications API
 * Handles in-app notifications
 */

// List notifications for current user
if ($endpoint === 'notifications/list' && $method === 'GET') {
    try {
        $query = "SELECT * FROM notifications 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT 50";
        
        $stmt = $db->prepare($query);
        $stmt->execute(['user_id' => $user->id]);
        
        $notifications = $stmt->fetchAll();
        
        // Count unread
        $unreadQuery = "SELECT COUNT(*) as count FROM notifications 
                       WHERE user_id = :user_id AND is_read = 0";
        $unreadStmt = $db->prepare($unreadQuery);
        $unreadStmt->execute(['user_id' => $user->id]);
        $unreadCount = $unreadStmt->fetch()['count'];
        
        sendResponse([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    } catch (PDOException $e) {
        error_log("Notifications list error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to load notifications'], 500);
    }
}

// Mark notification as read
if ($method === 'PUT' && preg_match('/notifications\/(\d+)\/read/', $endpoint, $matches)) {
    try {
        $notification_id = $matches[1];
        
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = NOW() 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'id' => $notification_id,
            'user_id' => $user->id
        ]);
        
        sendResponse(['success' => true, 'message' => 'Notification marked as read']);
    } catch (PDOException $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to update notification'], 500);
    }
}

// Mark all notifications as read
if ($endpoint === 'notifications/mark-all-read' && $method === 'PUT') {
    try {
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = NOW() 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $db->prepare($query);
        $stmt->execute(['user_id' => $user->id]);
        
        sendResponse(['success' => true, 'message' => 'All notifications marked as read']);
    } catch (PDOException $e) {
        error_log("Mark all read error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to update notifications'], 500);
    }
}

// Create notification (for system/admin use)
if ($endpoint === 'notifications/create' && $method === 'POST') {
    try {
        // Check if user has permission to create notifications (admin/staff only)
        if ($user->role !== 'admin' && $user->role !== 'staff') {
            sendResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        $userId = $input['user_id'] ?? null;
        $title = $input['title'] ?? null;
        $message = $input['message'] ?? null;
        $type = $input['type'] ?? 'info';
        $actionUrl = $input['action_url'] ?? null;
        
        if (!$userId || !$title || !$message) {
            sendResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }
        
        $query = "INSERT INTO notifications (user_id, title, message, type, action_url) 
                  VALUES (:user_id, :title, :message, :type, :action_url)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl
        ]);
        
        $notificationId = $db->lastInsertId();
        
        sendResponse([
            'success' => true,
            'notification_id' => $notificationId,
            'message' => 'Notification created successfully'
        ], 201);
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to create notification'], 500);
    }
}

// Helper function to create notification
function createNotification($db, $userId, $title, $message, $type = 'info', $actionUrl = null) {
    try {
        $query = "INSERT INTO notifications (user_id, title, message, type, action_url) 
                  VALUES (:user_id, :title, :message, :type, :action_url)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl
        ]);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create notification helper error: " . $e->getMessage());
        return false;
    }
}

// If no endpoint matched, return error
error_log("Notifications API: Unknown endpoint - $endpoint with method $method");
sendResponse(['success' => false, 'message' => 'Notifications endpoint not found: ' . $endpoint], 404);
