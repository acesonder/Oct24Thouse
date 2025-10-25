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

// Search users by username
if ($endpoint === 'chat/search-users' && $method === 'GET') {
    try {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendResponse(['success' => false, 'message' => 'Query too short'], 400);
        }
        
        $searchQuery = "SELECT id, username, first_name, last_name, email 
                       FROM users 
                       WHERE (username LIKE :query OR first_name LIKE :query OR last_name LIKE :query OR email LIKE :query) 
                       AND id != :user_id AND is_active = 1 
                       LIMIT 10";
        
        $stmt = $db->prepare($searchQuery);
        $stmt->execute([
            'query' => '%' . $query . '%',
            'user_id' => $user->id
        ]);
        
        $users = $stmt->fetchAll();
        
        sendResponse(['success' => true, 'users' => $users]);
    } catch (PDOException $e) {
        error_log("User search error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Search failed'], 500);
    }
}

// Start new conversation
if ($endpoint === 'chat/start-conversation' && $method === 'POST') {
    try {
        $recipientUsername = $input['recipient_username'] ?? null;
        $message = $input['message'] ?? null;
        
        if (!$recipientUsername || !$message) {
            sendResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }
        
        // Find recipient
        $userQuery = "SELECT id FROM users WHERE username = :username AND is_active = 1";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute(['username' => $recipientUsername]);
        
        if ($userStmt->rowCount() == 0) {
            sendResponse(['success' => false, 'message' => 'User not found'], 404);
        }
        
        $recipient = $userStmt->fetch();
        $recipientId = $recipient['id'];
        
        // Check if conversation already exists
        $convQuery = "SELECT id FROM conversations 
                     WHERE (user1_id = :user1 AND user2_id = :user2) 
                        OR (user1_id = :user2 AND user2_id = :user1)";
        $convStmt = $db->prepare($convQuery);
        $convStmt->execute([
            'user1' => $user->id,
            'user2' => $recipientId
        ]);
        
        if ($convStmt->rowCount() > 0) {
            $conversation = $convStmt->fetch();
            $conversationId = $conversation['id'];
        } else {
            // Create new conversation
            $createQuery = "INSERT INTO conversations (user1_id, user2_id) VALUES (:user1, :user2)";
            $createStmt = $db->prepare($createQuery);
            $createStmt->execute([
                'user1' => $user->id,
                'user2' => $recipientId
            ]);
            $conversationId = $db->lastInsertId();
        }
        
        // Send first message
        $msgQuery = "INSERT INTO chat_messages (conversation_id, sender_id, content) 
                    VALUES (:conversation_id, :sender_id, :content)";
        $msgStmt = $db->prepare($msgQuery);
        $msgStmt->execute([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'content' => $message
        ]);
        
        sendResponse([
            'success' => true,
            'conversation_id' => $conversationId,
            'message' => 'Conversation started'
        ], 201);
    } catch (PDOException $e) {
        error_log("Start conversation error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to start conversation'], 500);
    }
}

// Get conversations list
if ($endpoint === 'chat/conversations' && $method === 'GET') {
    try {
        $query = "SELECT c.id, c.user1_id, c.user2_id, c.updated_at,
                  CASE 
                    WHEN c.user1_id = :user_id THEN CONCAT(u2.first_name, ' ', u2.last_name)
                    ELSE CONCAT(u1.first_name, ' ', u1.last_name)
                  END as other_user_name,
                  (SELECT content FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                  (SELECT created_at FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                  (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND sender_id != :user_id AND is_read = 0) as unread_count
                  FROM conversations c
                  LEFT JOIN users u1 ON c.user1_id = u1.id
                  LEFT JOIN users u2 ON c.user2_id = u2.id
                  WHERE c.user1_id = :user_id OR c.user2_id = :user_id
                  ORDER BY c.updated_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute(['user_id' => $user->id]);
        
        $conversations = $stmt->fetchAll();
        
        sendResponse(['success' => true, 'conversations' => $conversations]);
    } catch (PDOException $e) {
        error_log("Get conversations error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to load conversations'], 500);
    }
}

// Get messages for a conversation
if ($method === 'GET' && preg_match('/chat\/messages\/(\d+)/', $endpoint, $matches)) {
    try {
        $conversationId = $matches[1];
        
        // Verify user is part of conversation
        $convQuery = "SELECT user1_id, user2_id FROM conversations WHERE id = :id";
        $convStmt = $db->prepare($convQuery);
        $convStmt->execute(['id' => $conversationId]);
        
        if ($convStmt->rowCount() == 0) {
            sendResponse(['success' => false, 'message' => 'Conversation not found'], 404);
        }
        
        $conv = $convStmt->fetch();
        
        if ($conv['user1_id'] != $user->id && $conv['user2_id'] != $user->id) {
            sendResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        
        // Get other user info
        $otherUserId = ($conv['user1_id'] == $user->id) ? $conv['user2_id'] : $conv['user1_id'];
        $otherUserQuery = "SELECT first_name, last_name FROM users WHERE id = :id";
        $otherUserStmt = $db->prepare($otherUserQuery);
        $otherUserStmt->execute(['id' => $otherUserId]);
        $otherUser = $otherUserStmt->fetch();
        
        // Get messages
        $msgQuery = "SELECT m.*, u.first_name as sender_name 
                    FROM chat_messages m
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE m.conversation_id = :conversation_id 
                    ORDER BY m.created_at ASC";
        
        $msgStmt = $db->prepare($msgQuery);
        $msgStmt->execute(['conversation_id' => $conversationId]);
        
        $messages = $msgStmt->fetchAll();
        
        // Mark messages as read
        $markReadQuery = "UPDATE chat_messages 
                         SET is_read = 1 
                         WHERE conversation_id = :conversation_id 
                         AND sender_id != :user_id 
                         AND is_read = 0";
        $markReadStmt = $db->prepare($markReadQuery);
        $markReadStmt->execute([
            'conversation_id' => $conversationId,
            'user_id' => $user->id
        ]);
        
        sendResponse([
            'success' => true,
            'messages' => $messages,
            'conversation' => [
                'id' => $conversationId,
                'other_user_name' => $otherUser['first_name'] . ' ' . $otherUser['last_name']
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Get messages error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to load messages'], 500);
    }
}

// Send message (updated to support conversations)
if ($endpoint === 'chat/send' && $method === 'POST') {
    try {
        $conversationId = $input['conversation_id'] ?? null;
        $content = $input['content'] ?? null;
        
        if (!$conversationId || !$content) {
            sendResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }
        
        // Verify user is part of conversation
        $convQuery = "SELECT user1_id, user2_id FROM conversations WHERE id = :id";
        $convStmt = $db->prepare($convQuery);
        $convStmt->execute(['id' => $conversationId]);
        
        if ($convStmt->rowCount() == 0) {
            sendResponse(['success' => false, 'message' => 'Conversation not found'], 404);
        }
        
        $conv = $convStmt->fetch();
        
        if ($conv['user1_id'] != $user->id && $conv['user2_id'] != $user->id) {
            sendResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        
        // Insert message
        $msgQuery = "INSERT INTO chat_messages (conversation_id, sender_id, content) 
                    VALUES (:conversation_id, :sender_id, :content)";
        $msgStmt = $db->prepare($msgQuery);
        $msgStmt->execute([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'content' => $content
        ]);
        
        $messageId = $db->lastInsertId();
        
        // Update conversation timestamp
        $updateQuery = "UPDATE conversations SET updated_at = NOW() WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute(['id' => $conversationId]);
        
        sendResponse([
            'success' => true,
            'message_id' => $messageId,
            'message' => 'Message sent'
        ], 201);
    } catch (PDOException $e) {
        error_log("Send message error: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to send message'], 500);
    }
}

// If no endpoint matched, return error
error_log("Chat API: Unknown endpoint - $endpoint with method $method");
sendResponse(['success' => false, 'message' => 'Chat endpoint not found: ' . $endpoint], 404);
