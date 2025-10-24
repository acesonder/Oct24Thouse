<?php
/**
 * Chat and Messaging API
 * Handles direct messages, group chats, and announcements
 */

// Send message
if ($endpoint === 'chat/send' && $method === 'POST') {
    $recipient_id = $input['recipient_id'] ?? null;
    $group_id = $input['group_id'] ?? null;
    $content = $input['content'] ?? null;
    $message_type = $input['message_type'] ?? 'text';
    $media_url = $input['media_url'] ?? null;
    
    if (!$content) {
        sendResponse(['success' => false, 'message' => 'Message content required'], 400);
    }
    
    if (!$recipient_id && !$group_id) {
        sendResponse(['success' => false, 'message' => 'Recipient or group required'], 400);
    }
    
    // If group message, check membership
    if ($group_id) {
        $memberQuery = "SELECT id FROM chat_group_members WHERE group_id = :group_id AND user_id = :user_id";
        $memberStmt = $db->prepare($memberQuery);
        $memberStmt->execute(['group_id' => $group_id, 'user_id' => $user->id]);
        
        if ($memberStmt->rowCount() == 0) {
            sendResponse(['success' => false, 'message' => 'Not a member of this group'], 403);
        }
    }
    
    $query = "INSERT INTO messages (sender_id, recipient_id, group_id, message_type, content, media_url) 
              VALUES (:sender_id, :recipient_id, :group_id, :message_type, :content, :media_url)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'sender_id' => $user->id,
        'recipient_id' => $recipient_id,
        'group_id' => $group_id,
        'message_type' => $message_type,
        'content' => $content,
        'media_url' => $media_url
    ]);
    
    $message_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'message_id' => $message_id, 'message' => 'Message sent'], 201);
}

// Get messages
if ($endpoint === 'chat/messages' && $method === 'GET') {
    $recipient_id = $_GET['recipient_id'] ?? null;
    $group_id = $_GET['group_id'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    if ($group_id) {
        // Get group messages
        $query = "SELECT m.*, u.first_name, u.last_name 
                  FROM messages m 
                  LEFT JOIN users u ON m.sender_id = u.id 
                  WHERE m.group_id = :group_id AND m.is_deleted = 0 
                  ORDER BY m.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
    } else if ($recipient_id) {
        // Get direct messages
        $query = "SELECT m.*, u.first_name, u.last_name 
                  FROM messages m 
                  LEFT JOIN users u ON m.sender_id = u.id 
                  WHERE ((m.sender_id = :user_id AND m.recipient_id = :recipient_id) 
                     OR (m.sender_id = :recipient_id AND m.recipient_id = :user_id))
                     AND m.is_deleted = 0 
                  ORDER BY m.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user->id, PDO::PARAM_INT);
        $stmt->bindValue(':recipient_id', $recipient_id, PDO::PARAM_INT);
    } else {
        sendResponse(['success' => false, 'message' => 'Recipient or group required'], 400);
    }
    
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'messages' => array_reverse($messages)]);
}

// Create chat group
if ($endpoint === 'chat/group/create' && $method === 'POST') {
    $name = $input['name'] ?? null;
    $description = $input['description'] ?? null;
    $group_type = $input['group_type'] ?? 'community';
    $is_anonymous = $input['is_anonymous'] ?? false;
    
    if (!$name) {
        sendResponse(['success' => false, 'message' => 'Group name required'], 400);
    }
    
    // Check if user can create groups (peer mentors and staff)
    if ($user->role !== 'staff' && $user->role !== 'admin' && $user->role !== 'peer') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $query = "INSERT INTO chat_groups (name, description, group_type, created_by, is_anonymous) 
              VALUES (:name, :description, :group_type, :created_by, :is_anonymous)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'name' => $name,
        'description' => $description,
        'group_type' => $group_type,
        'created_by' => $user->id,
        'is_anonymous' => $is_anonymous
    ]);
    
    $group_id = $db->lastInsertId();
    
    // Add creator as admin
    $memberQuery = "INSERT INTO chat_group_members (group_id, user_id, role) 
                    VALUES (:group_id, :user_id, 'admin')";
    
    $memberStmt = $db->prepare($memberQuery);
    $memberStmt->execute([
        'group_id' => $group_id,
        'user_id' => $user->id
    ]);
    
    sendResponse(['success' => true, 'group_id' => $group_id, 'message' => 'Group created'], 201);
}

// List chat groups
if ($endpoint === 'chat/groups' && $method === 'GET') {
    $query = "SELECT g.*, 
              (SELECT COUNT(*) FROM chat_group_members WHERE group_id = g.id) as member_count,
              (SELECT COUNT(*) FROM chat_group_members WHERE group_id = g.id AND user_id = :user_id) as is_member
              FROM chat_groups g 
              ORDER BY g.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => $user->id]);
    
    $groups = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'groups' => $groups]);
}

// Create announcement
if ($endpoint === 'chat/announcement' && $method === 'POST') {
    // Check if user is staff or admin
    if ($user->role !== 'staff' && $user->role !== 'admin') {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    $title = $input['title'] ?? null;
    $content = $input['content'] ?? null;
    $announcement_type = $input['announcement_type'] ?? 'general';
    $priority = $input['priority'] ?? 'medium';
    $target_roles = $input['target_roles'] ?? ['client', 'peer', 'staff'];
    
    if (!$title || !$content) {
        sendResponse(['success' => false, 'message' => 'Title and content required'], 400);
    }
    
    $query = "INSERT INTO announcements (created_by, title, content, announcement_type, priority, target_roles) 
              VALUES (:created_by, :title, :content, :announcement_type, :priority, :target_roles)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'created_by' => $user->id,
        'title' => $title,
        'content' => $content,
        'announcement_type' => $announcement_type,
        'priority' => $priority,
        'target_roles' => json_encode($target_roles)
    ]);
    
    $announcement_id = $db->lastInsertId();
    
    sendResponse(['success' => true, 'announcement_id' => $announcement_id, 'message' => 'Announcement created'], 201);
}
