# API Documentation - Transition House Platform

## Base URL
```
Production: https://transitionhouse.org/api
Development: http://localhost/api
```

## Authentication

All protected endpoints require a JWT token in the Authorization header:

```
Authorization: Bearer {token}
```

### POST /auth/register
Register a new user.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "role_id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "phone": "555-1234",
  "date_of_birth": "1990-01-01"
}
```

**Response:**
```json
{
  "success": true,
  "user_id": 123
}
```

### POST /auth/login
Authenticate and receive JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 123,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": "client"
  }
}
```

### POST /auth/logout
Revoke current session.

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### GET /auth/me
Get current user information.

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 123,
    "email": "user@example.com",
    "role": "client"
  }
}
```

## Intake Management

### POST /intake/create
Create a new intake form.

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "client_id": 123,
  "intake_data": {
    "reason": "Seeking emergency shelter",
    "arrival_date": "2024-10-24"
  },
  "housing_history": {
    "previous_housing": "Couch surfing",
    "length": "3 months"
  },
  "risk_assessment": {
    "medical": "None",
    "mental_health": "Anxiety"
  },
  "supports": {
    "family": "Limited",
    "employment": "Unemployed"
  },
  "vulnerability_score": 7
}
```

**Response:**
```json
{
  "success": true,
  "intake_id": 456,
  "message": "Intake created successfully"
}
```

### GET /intake/{id}
Get intake details.

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "intake": {
    "id": 456,
    "client_id": 123,
    "status": "submitted",
    "intake_data": {...},
    "created_at": "2024-10-24T10:00:00Z"
  }
}
```

### GET /intake/list
List all intakes (staff only).

**Headers:** Requires Authorization

**Query Parameters:**
- `page`: Page number (default: 1)
- `limit`: Results per page (default: 20)
- `status`: Filter by status

**Response:**
```json
{
  "success": true,
  "intakes": [...],
  "page": 1
}
```

## Bed Management

### GET /bed/list
Get list of all beds.

**Headers:** Requires Authorization

**Query Parameters:**
- `status`: Filter by status (available, occupied, reserved, maintenance)

**Response:**
```json
{
  "success": true,
  "beds": [
    {
      "id": 1,
      "bed_number": "A-101",
      "status": "available",
      "room": "A-1",
      "bed_type": "single"
    }
  ]
}
```

### GET /bed/stats
Get bed statistics.

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_beds": 50,
    "available_beds": 15,
    "occupied_beds": 30,
    "reserved_beds": 3,
    "maintenance_beds": 2
  }
}
```

### POST /bed/assign
Assign a bed to a client (staff only).

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "bed_id": 1,
  "client_id": 123,
  "planned_exit_date": "2024-11-24"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bed assigned successfully"
}
```

### POST /bed/release
Release a bed (staff only).

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "bed_id": 1,
  "exit_reason": "Secured housing",
  "exit_destination": "Permanent housing"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bed released successfully"
}
```

## Case Management

### POST /case/create
Create a case plan (staff only).

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "client_id": 123,
  "plan_name": "Housing Stability Plan",
  "description": "Support client in securing permanent housing",
  "start_date": "2024-10-24",
  "target_end_date": "2025-01-24"
}
```

**Response:**
```json
{
  "success": true,
  "case_plan_id": 789,
  "message": "Case plan created"
}
```

### GET /case/{id}
Get case plan with goals and tasks.

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "case_plan": {
    "id": 789,
    "plan_name": "Housing Stability Plan",
    "goals": [
      {
        "id": 1,
        "title": "Secure Employment",
        "progress_status": "in_progress",
        "tasks": [...]
      }
    ]
  }
}
```

## Peer Engagement

### GET /peer/mentors
List available mentors.

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "mentors": [
    {
      "user_id": 45,
      "first_name": "Jane",
      "last_name": "Smith",
      "bio": "10 years experience in harm reduction",
      "specializations": ["harm reduction", "mental health"],
      "reputation_points": 150
    }
  ]
}
```

### POST /peer/mentorship/request
Request mentorship.

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "mentor_id": 45
}
```

**Response:**
```json
{
  "success": true,
  "mentorship_id": 234,
  "message": "Mentorship requested successfully"
}
```

### POST /peer/engagement
Log peer engagement (peer/staff only).

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "client_id": 123,
  "engagement_type": "crisis_support",
  "duration": 30,
  "topic": "Mental health check-in",
  "outcome": "Positive, client feels supported",
  "notes": "Follow up in 2 days"
}
```

**Response:**
```json
{
  "success": true,
  "engagement_id": 567,
  "message": "Engagement logged successfully"
}
```

## Messaging

### POST /chat/send
Send a message.

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "recipient_id": 123,
  "content": "Hello, how are you?",
  "message_type": "text"
}
```

**Response:**
```json
{
  "success": true,
  "message_id": 890,
  "message": "Message sent"
}
```

### GET /chat/messages
Get messages.

**Headers:** Requires Authorization

**Query Parameters:**
- `recipient_id`: For direct messages
- `group_id`: For group messages
- `limit`: Number of messages (default: 50)
- `offset`: Pagination offset

**Response:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 890,
      "sender_id": 45,
      "content": "Hello, how are you?",
      "created_at": "2024-10-24T10:30:00Z"
    }
  ]
}
```

### POST /chat/group/create
Create a chat group (peer/staff only).

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "name": "Housing Support Group",
  "description": "Support for housing navigation",
  "group_type": "support"
}
```

**Response:**
```json
{
  "success": true,
  "group_id": 12,
  "message": "Group created"
}
```

## Referrals

### POST /referral/send
Send a referral (staff only).

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "client_id": 123,
  "partner_id": 78,
  "service_type": "housing",
  "referral_data": {
    "urgency": "high",
    "requirements": "Accessible unit"
  },
  "notes": "Client needs immediate placement"
}
```

**Response:**
```json
{
  "success": true,
  "referral_id": 345,
  "message": "Referral sent successfully"
}
```

## Training

### GET /training/modules
List training modules.

**Headers:** Requires Authorization

**Query Parameters:**
- `category`: Filter by category

**Response:**
```json
{
  "success": true,
  "modules": [
    {
      "id": 1,
      "title": "Trauma-Informed Care",
      "category": "trauma_informed",
      "duration": 60
    }
  ]
}
```

### POST /training/complete
Mark training as completed.

**Headers:** Requires Authorization

**Request Body:**
```json
{
  "module_id": 1,
  "score": 85
}
```

**Response:**
```json
{
  "success": true,
  "completion_id": 456,
  "message": "Training completed"
}
```

## Analytics

### GET /analytics/dashboard
Get dashboard analytics (staff/admin only).

**Headers:** Requires Authorization

**Response:**
```json
{
  "success": true,
  "analytics": {
    "total_clients": 150,
    "active_stays": 35,
    "avg_length_of_stay": 45.5,
    "occupancy_rate": 70.0,
    "recent_intakes": 12
  }
}
```

## Error Responses

All endpoints may return error responses:

```json
{
  "success": false,
  "message": "Error description"
}
```

Common HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

## Rate Limiting

API requests are limited to 100 requests per minute per user.

Exceeding the limit returns:
```json
{
  "success": false,
  "message": "Rate limit exceeded"
}
```
