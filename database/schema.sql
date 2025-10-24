-- Transition House Database Schema
-- PHIPA/PIPEDA Compliant Design

CREATE DATABASE IF NOT EXISTS transition_house CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE transition_house;

-- User Roles and Permissions
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (name, description) VALUES
('client', 'Residents and shelter seekers'),
('staff', 'Case managers, housing workers, outreach staff'),
('peer', 'Trained individuals with lived/living experience'),
('admin', 'System administrators with full permissions'),
('partner', 'Verified agencies receiving referrals');

-- Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    is_active BOOLEAN DEFAULT TRUE,
    requires_2fa BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_role (role_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Two-Factor Authentication
CREATE TABLE user_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    backup_codes TEXT,
    enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Peer Profiles
CREATE TABLE peer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    specializations JSON,
    certifications JSON,
    is_mentor BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    reputation_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentor (is_mentor),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- Beds and Occupancy
CREATE TABLE beds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bed_number VARCHAR(20) NOT NULL UNIQUE,
    room VARCHAR(50),
    bed_type VARCHAR(50),
    status ENUM('available', 'occupied', 'reserved', 'maintenance') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Stays and Admissions
CREATE TABLE stays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    bed_id INT,
    admission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    planned_exit_date DATE,
    actual_exit_date TIMESTAMP NULL,
    exit_reason VARCHAR(255),
    exit_destination VARCHAR(255),
    status ENUM('active', 'completed', 'transferred') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (bed_id) REFERENCES beds(id),
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_dates (admission_date, actual_exit_date)
) ENGINE=InnoDB;

-- Intake Forms
CREATE TABLE intakes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    staff_id INT,
    intake_data JSON NOT NULL,
    housing_history JSON,
    risk_assessment JSON,
    supports JSON,
    vulnerability_score INT,
    status ENUM('draft', 'submitted', 'reviewed', 'completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    INDEX idx_client (client_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Case Plans
CREATE TABLE case_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    staff_id INT NOT NULL,
    plan_name VARCHAR(255),
    description TEXT,
    start_date DATE,
    target_end_date DATE,
    status ENUM('active', 'completed', 'on_hold', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    INDEX idx_client (client_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Goals (SMART goals)
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_plan_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    target_date DATE,
    progress_status ENUM('not_started', 'in_progress', 'at_risk', 'completed') DEFAULT 'not_started',
    progress_percentage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_plan_id) REFERENCES case_plans(id) ON DELETE CASCADE,
    INDEX idx_case_plan (case_plan_id),
    INDEX idx_status (progress_status)
) ENGINE=InnoDB;

-- Tasks
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT,
    client_id INT,
    assigned_to INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    INDEX idx_goal (goal_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Assessments
CREATE TABLE assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    staff_id INT,
    assessment_type VARCHAR(100),
    assessment_data JSON NOT NULL,
    score INT,
    notes TEXT,
    assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    INDEX idx_client (client_id),
    INDEX idx_type (assessment_type)
) ENGINE=InnoDB;

-- Referrals
CREATE TABLE referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    staff_id INT NOT NULL,
    partner_id INT,
    service_type VARCHAR(100),
    referral_data JSON,
    status ENUM('pending', 'accepted', 'declined', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    FOREIGN KEY (partner_id) REFERENCES users(id),
    INDEX idx_client (client_id),
    INDEX idx_partner (partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Consent Management
CREATE TABLE consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    consent_type VARCHAR(100),
    granted_to_role VARCHAR(50),
    granted_to_user INT,
    is_granted BOOLEAN DEFAULT FALSE,
    scope JSON,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_to_user) REFERENCES users(id),
    INDEX idx_client (client_id),
    INDEX idx_type (consent_type)
) ENGINE=InnoDB;

-- Appointments
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    staff_id INT,
    partner_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    appointment_date TIMESTAMP NOT NULL,
    duration INT DEFAULT 30,
    location VARCHAR(255),
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    FOREIGN KEY (partner_id) REFERENCES users(id),
    INDEX idx_client (client_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Documents (encrypted storage)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    document_type VARCHAR(100),
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    is_encrypted BOOLEAN DEFAULT TRUE,
    encryption_key_id VARCHAR(255),
    qr_code VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_type (document_type)
) ENGINE=InnoDB;

-- Chat Groups
CREATE TABLE chat_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    group_type ENUM('program', 'support', 'crisis', 'community', 'private') DEFAULT 'community',
    created_by INT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    is_moderated BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_type (group_type)
) ENGINE=InnoDB;

-- Chat Group Members
CREATE TABLE chat_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'moderator', 'admin') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (group_id, user_id),
    INDEX idx_group (group_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Messages
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT,
    group_id INT,
    message_type ENUM('text', 'audio', 'image', 'voice', 'system') DEFAULT 'text',
    content TEXT NOT NULL,
    media_url VARCHAR(500),
    is_flagged BOOLEAN DEFAULT FALSE,
    flag_reason VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_group (group_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('general', 'meal', 'closure', 'meeting', 'crisis') DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    target_roles JSON,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_active (is_active),
    INDEX idx_type (announcement_type)
) ENGINE=InnoDB;

-- Peer Engagements
CREATE TABLE peer_engagements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peer_id INT NOT NULL,
    client_id INT NOT NULL,
    engagement_type VARCHAR(100),
    duration INT,
    topic VARCHAR(255),
    outcome TEXT,
    notes TEXT,
    engagement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peer_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES users(id),
    INDEX idx_peer (peer_id),
    INDEX idx_client (client_id),
    INDEX idx_date (engagement_date)
) ENGINE=InnoDB;

-- Peer Mentorship
CREATE TABLE mentorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    status ENUM('requested', 'active', 'completed', 'declined') DEFAULT 'requested',
    start_date DATE,
    end_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id),
    FOREIGN KEY (mentee_id) REFERENCES users(id),
    INDEX idx_mentor (mentor_id),
    INDEX idx_mentee (mentee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Training Modules
CREATE TABLE training_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT,
    category VARCHAR(100),
    duration INT,
    certification_valid_months INT,
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- Training Completions
CREATE TABLE training_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    score INT,
    certificate_url VARCHAR(500),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completion (user_id, module_id, completed_at),
    INDEX idx_user (user_id),
    INDEX idx_expiry (expires_at)
) ENGINE=InnoDB;

-- Incidents
CREATE TABLE incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reported_by INT NOT NULL,
    incident_type VARCHAR(100),
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NOT NULL,
    location VARCHAR(255),
    involved_users JSON,
    photos JSON,
    follow_up_actions TEXT,
    status ENUM('reported', 'in_progress', 'resolved', 'escalated') DEFAULT 'reported',
    incident_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    INDEX idx_type (incident_type),
    INDEX idx_status (status),
    INDEX idx_date (incident_date)
) ENGINE=InnoDB;

-- Inventory
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    quantity INT DEFAULT 0,
    unit VARCHAR(50),
    minimum_threshold INT DEFAULT 0,
    location VARCHAR(255),
    last_restocked TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_quantity (quantity)
) ENGINE=InnoDB;

-- Inventory Transactions
CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_type ENUM('restock', 'dispense', 'adjustment', 'waste') DEFAULT 'dispense',
    quantity INT NOT NULL,
    notes TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_item (item_id),
    INDEX idx_date (transaction_date)
) ENGINE=InnoDB;

-- Community Feed Posts
CREATE TABLE community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('discussion', 'question', 'announcement', 'event') DEFAULT 'discussion',
    is_moderated BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_type (post_type),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- Post Comments
CREATE TABLE post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_flagged BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_post (post_id)
) ENGINE=InnoDB;

-- Resource Directory
CREATE TABLE resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    address VARCHAR(500),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(500),
    hours_of_operation JSON,
    is_walk_in BOOLEAN DEFAULT FALSE,
    tags JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB;

-- Audit Log
CREATE TABLE audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Session Tokens (JWT tracking)
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    is_revoked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_token (token_hash),
    INDEX idx_expiry (expires_at)
) ENGINE=InnoDB;

-- Polls and Surveys
CREATE TABLE polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    poll_type ENUM('single_choice', 'multiple_choice', 'rating', 'text') DEFAULT 'single_choice',
    options JSON,
    target_roles JSON,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Poll Responses
CREATE TABLE poll_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    user_id INT NOT NULL,
    response JSON NOT NULL,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_response (poll_id, user_id),
    INDEX idx_poll (poll_id)
) ENGINE=InnoDB;

-- Governance Proposals
CREATE TABLE proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    proposal_type VARCHAR(100),
    status ENUM('draft', 'voting', 'approved', 'rejected', 'implemented') DEFAULT 'draft',
    voting_ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Proposal Votes
CREATE TABLE proposal_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT NOT NULL,
    user_id INT NOT NULL,
    vote ENUM('upvote', 'downvote', 'abstain') NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (proposal_id, user_id),
    INDEX idx_proposal (proposal_id)
) ENGINE=InnoDB;

-- Badges and Reputation
CREATE TABLE badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User Badges
CREATE TABLE user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_badge (user_id, badge_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Shift Scheduling
CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peer_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'scheduled',
    check_in_time TIMESTAMP NULL,
    check_out_time TIMESTAMP NULL,
    hours_worked DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peer_id) REFERENCES users(id),
    INDEX idx_peer (peer_id),
    INDEX idx_date (shift_date)
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(100),
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- System Configuration
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default system configuration
INSERT INTO system_config (config_key, config_value, description) VALUES
('site_name', 'Transition House Cobourg', 'Name of the organization'),
('max_bed_capacity', '50', 'Maximum bed capacity'),
('consent_expiry_days', '365', 'Days before consent needs reauthorization'),
('data_retention_days', '2555', 'Days to retain data (7 years for compliance)'),
('enable_peer_messaging', 'true', 'Enable peer-to-peer messaging'),
('enable_anonymous_chat', 'true', 'Enable anonymous chat rooms'),
('require_staff_2fa', 'true', 'Require 2FA for staff accounts');
