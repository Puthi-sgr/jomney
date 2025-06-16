# HTTP Status Code Cheat Sheet for Food Delivery API

## 2xx Success
- **200 OK** - Request successful (GET, PUT updates)
- **201 Created** - Resource created successfully (POST)
- **204 No Content** - Successful request with no response body (DELETE)

## 4xx Client Errors
- **400 Bad Request** - Invalid request format/data
- **401 Unauthorized** - Missing or invalid authentication
- **403 Forbidden** - Valid auth but insufficient permissions
- **404 Not Found** - Resource doesn't exist
- **409 Conflict** - Resource already exists (duplicate email)
- **422 Unprocessable Entity** - Validation errors
- **429 Too Many Requests** - Rate limiting

## 5xx Server Errors
- **500 Internal Server Error** - Unexpected server error
- **502 Bad Gateway** - Upstream service error
- **503 Service Unavailable** - Server temporarily down

## Common API Scenarios

### Authentication
```php
// Missing token
Response::error('Authentication required', [], 401);

// Invalid token
Response::error('Invalid token', [], 401);

// Valid token but wrong role
Response::error('Admin access required', [], 403);
