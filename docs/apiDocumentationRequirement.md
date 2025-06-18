## API Documentation Requirements

### 1. Complete Endpoint List
- All routes with HTTP methods (GET, POST, PUT, DELETE)
- URL patterns and parameters
- Which endpoints are public vs authenticated
- Role-based access (Customer/Vendor/Admin)

### 2. Request/Response Formats
- All possible request body fields for each endpoint
- Required vs optional fields
- Data types and validation rules
- Expected response structures (success/error)
- HTTP status codes used

### 3. Authentication Details
- How JWT tokens are passed (header format)
- Token expiration rules
- Login/registration flows
- Role-based permissions per endpoint

### 4. Database Schema Information
- Table structures (especially array fields like food_types, images)
- Relationships between tables
- Any special PostgreSQL array formatting requirements

### 5. File Upload Specifications
- Which endpoints accept files
- File types accepted (images, etc.)
- Size limits
- Cloudinary integration details
- Multiple vs single file handling

### 6. Error Handling Patterns
- Standard error response format
- Common error codes and messages
- Validation error structures

### 7. Business Logic Rules
- Order workflow states
- Payment processing flow
- Delivery status transitions
- Any special business rules or constraints

### 8. External Service Integrations
- Stripe payment webhook details
- Cloudinary image upload specifications
- Any other third-party services

### Required Information
- Route definitions (from your Router setup)
- Controller method signatures (what each endpoint does)
- Model field definitions (database schema)
- Sample request/response examples (from your testing)
- Any special formatting rules (like PostgreSQL arrays)