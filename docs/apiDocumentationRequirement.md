|

## API Documentation Requirements

#### Admin Authentication Endpoints

- `POST /api/admin/login` (Public) - Admin login

  - Request (JSON): `{ "email": "string", "password": "string" }`
  - Response (JSON): `{ "token": "string", "user": { "id": "string", "name": "string", "email": "string" } }`

- `POST /api/admin/logout` (Public) - Admin logout

  - Request (JSON): None
  - Response (JSON): `{ "message": "Logged out successfully" }`

- `GET /api/admin/user` (Admin) - Get admin user info
  - Request (JSON): None
  - Response (JSON): `{ "id": "string", "name": "string", "email": "string", "role": "string" }`

#### Admin Dashboard Endpoints

- `GET /api/admin/stats` (Admin) - Get dashboard statistics
  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Stats overview", "data": { "total_customers": "number", "total_vendors": "number", "total_orders": "number", "total_revenue": "number", "orders_by_status": { "pending": "number", "confirmed": "number", "delivered": "number" } } }`

#### Vendor Management Endpoints

- `GET /api/admin/vendors` (Admin) - List all vendors

  - Request: None
  - Response Example:
    ```json
    {
      "success": true,
      "message": "Vendors list",
      "data": {
        "vendors": [
          {
            "id": 1,
            "name": "Vendor Name",
            "email": "vendor@email.com",
            "phone": "1234567890",
            "address": "123 Main St",
            "food_types": ["thai", "burgers"],
            "rating": 4.5,
            "image": "https://...",
            "revenue": 1234.56,
            "totalOrders": 10
          }
        ]
      }
    }
    ```
  - **Key changes:**
    - Response now includes `revenue` and `totalOrders` for each vendor.

- `POST /api/admin/vendors` (Admin) - Create vendor

  - Request (JSON or Form-data): `{ "email": "string", "password": "string", "name": "string", "phone": "string", "address": "string", "food_types": ["string"], "rating": "number" }` + optional image file (form-data only)
  - Response (JSON): `{ "success": true, "message": "Vendor created" }`

- `GET /api/admin/vendors/{id}` (Admin) - Get vendor details

  - Request: None
  - Response Example:
    ```json
    {
      "success": true,
      "message": "Vendor details",
      "data": {
        "vendor": {
          "id": 1,
          "name": "Vendor Name",
          "email": "vendor@email.com",
          "phone": "1234567890",
          "address": "123 Main St",
          "food_types": ["thai", "burgers"],
          "rating": 4.5,
          "image": "https://..."
        },
        "foods": [
          // ... food items
        ],
        "revenue": 1234.56,
        "totalOrders": 10,
        "foodOrders": [
          // ... food orders
        ]
      }
    }
    ```
  - **Key changes:**
    - Response now includes `foods`, `revenue`, `totalOrders`, and `foodOrders` in addition to vendor details.

- `POST /api/admin/vendors/{id}` (Admin) - Update vendor image

  - Request (Form-data only): image file
  - Response (JSON): `{ "success": true, "message": "Image upload success" }`

- `GET /api/admin/vendors/{id}/earnings` (Admin) - Get vendor earnings summary

  - Request: None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Vendor earnings",
      "data": {
        "vendor": {
          "id": "number",
          "name": "string",
          "email": "string",
          "phone": "string",
          "address": "string",
          "food_types": ["string"],
          "rating": "number",
          "image": "string"
        },
        "totalAmount": "number",
        "orders": [
          {
            "order_id": "number",
            "placed_at": "string",
            "foodId": "number",
            "food_name": "string",
            "food_description": "string",
            "food_price": "number",
            "amount": "number",
            "gross": "number"
          }
          // ... more food items per order
        ]
      }
    }
    ```

- `GET /api/admin/vendors/{id}/orders` (Admin) - Get all food orders for a vendor

  - Request: None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Orders by vendor",
      "data": {
        "vendor": {
          "id": "number",
          "name": "string",
          "email": "string",
          "phone": "string",
          "address": "string",
          "food_types": ["string"],
          "rating": "number",
          "image": "string"
        },
        "totalOrders": "number",
        "foodOrders": [
          {
            "order_id": "number",
            "food_id": "number",
            "name": "string",
            "description": "string",
            "price": "number",
            "quantity": "number",
            "vendor_id": "number",
            "created_at": "string",
            "status_key": "string"
          }
          // ... more food orders
        ]
      }
    }
    ```

- `PUT /api/admin/vendors/{id}` (Admin) - Update vendor details

  - Request (Form-data): `{ "name": "string", "address": "string", "phone": "string", "food_types": ["string"], "rating": "number", "image": "string", "password": "string" }` (all fields optional)
  - Response (JSON): `{ "success": true, "message": "Vendor updated" }`

- `DELETE /api/admin/vendors/delete/{id}` (Admin) - Delete vendor
  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Vendor deleted" }`

#### Food Management Endpoints

- `GET /api/admin/foods` (Admin) - List all foods

  - Request (JSON): None (optional query parameters: ?vendor_id=number&category=string)
  - Response (JSON): `{ "success": true, "message": "Foods list", "data": [{ "id": "number", "vendor_id": "number", "name": "string", "description": "string", "category": "string", "price": "number", "ready_time": "number", "rating": "number", "image": "string" }] }`

- `POST /api/admin/foods` (Admin) - Create food item

  - Request (JSON or Form-data): `{ "vendor_id": "number", "name": "string", "description": "string", "category": "string", "price": "number", "ready_time": "number", "rating": "number" }` + optional image file (form-data only)
  - Response (JSON): `{ "success": true, "message": "Food created successfully", "data": { "food_id": "number", "image_uploaded": "boolean", "image_url": "string" } }`

- `POST /api/admin/foods/image/{id}` (Admin) - Update food image

  - Request (Form-data only): image file
  - Response (JSON): `{ "success": true, "message": "Image updated successfully", "data": { "food_id": "number", "image_url": "string" } }`

- `GET /api/admin/foods/{id}` (Admin) - Get food details

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Food details", "data": [{ "id": "number", "vendor_id": "number", "name": "string", "description": "string", "category": "string", "price": "number", "ready_time": "number", "rating": "number", "image": "string" }] }`

- `PUT /api/admin/foods/{id}` (Admin) - Update food details

  - Request (JSON only): `{ "vendor_id": "number", "name": "string", "description": "string", "category": "string", "price": "number", "ready_time": "number", "rating": "number", "images": ["string"] }` (all fields optional but vendor_id key must be present)
  - Response (JSON): `{ "success": true, "message": "Food item updated" }`

- `DELETE /api/admin/foods/{id}` (Admin) - Delete food item
  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Food item deleted" }`

#### Order Management Endpoints

- `GET /api/admin/orders` (Admin) - List all orders

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "All orders", "data": { "orders": [{ "id": "number", "customer_id": "number", "status_id": "number", "total_amount": "number", "created_at": "string" }] } }`

- `GET /api/admin/orders/{id}` (Admin) - Get order details

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Order", "data": { "foodItems": [{ "order_id": "number", "food_id": "number", "food_name": "string", "quantity": "number", "price": "number", "total": "number" }] } }`

- `PATCH /api/admin/orders/{id}/status` (Admin) - Update order status
  - Request (JSON only): `{ "status_key": "string" }`
  - Response (JSON): `{ "success": true, "message": "Order status updated" }`

#### Customer Management Endpoints

- `GET /api/admin/customers` (Admin) - List all customers

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Customers list", "data": [{ "id": "number", "email": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string", "image": "string" }] }`

- `POST /api/admin/customers` (Admin) - Create customer

  - Request (JSON only): `{ "email": "string", "password": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string" }`
  - Response (JSON): `{ "success": true, "message": "Customer created successfully" }`

- `GET /api/admin/customers/{id}` (Admin) - Get customer details

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Customer details", "data": { "id": "number", "email": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string", "image": "string" } }`

- `POST /api/admin/customers/image/{id}` (Admin) - Update customer image

  - Request (Form-data only): image file
  - Response (JSON): `{ "success": true, "message": "Image upload success" }`

- `PUT /api/admin/customers/{id}` (Admin) - Update customer details

  - Request (JSON only): `{ "email": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string" }` (all fields optional)
  - Response (JSON): `{ "success": true, "message": "Customer updated successfully" }`

- `DELETE /api/admin/customers/{id}` (Admin) - Delete customer
  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Customer deleted" }`

#### Payment Management Endpoints

- `GET /api/admin/payments` (Admin) - List all payments

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "All payments", "data": [{ "id": "number", "order_id": "number", "amount": "number", "status": "string", "payment_method": "string", "created_at": "string" }] }`

- `GET /api/admin/payments/{id}` (Admin) - Get payment details
  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment details", "data": { "id": "number", "order_id": "number", "amount": "number", "status": "string", "payment_method": "string", "created_at": "string" } }`

-----------------Public-----------------------------

### Public Endpoints

- `GET /api/public/vendors` (Public) - List all vendors

  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "All vendors retrieved",
      "data": {
        "vendors": [
          {
            "id": "number",
            "name": "string",
            "phone": "string",
            "address": "string",
            "food_types": ["string"],
            "rating": "number",
            "image": "string",
            "foods": [
              {
                "id": "number",
                "name": "string",
                "description": "string",
                "category": "string",
                "price": "number",
                "ready_time": "number",
                "rating": "number",
                "image": "string",
                "qty_available": "number"
              }
            ]
          }
        ]
      }
    }
    ```

- `GET /api/public/foods` (Public) - List all foods

  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "All foods retrieved",
      "data": {
        "foods": [
          {
            "id": "number",
            "name": "string",
            "description": "string",
            "category": "string",
            "price": "number",
            "ready_time": "number",
            "rating": "number",
            "image": "string",
            "qty_available": "number",
            "vendor": {
              "id": "number",
              "name": "string",
              "phone": "string",
              "address": "string",
              "food_types": ["string"],
              "rating": "number",
              "image": "string"
            }
          }
        ]
      }
    }
    ```

- `GET /api/public/vendors/{id}` (Public) - Get vendor details with their food list

  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Vendor details with foods",
      "data": {
        "vendor": {
          "id": "number",
          "name": "string",
          "phone": "string",
          "address": "string",
          "food_types": ["string"],
          "rating": "number",
          "image": "string"
        },
        "foods": [
          {
            "id": "number",
            "name": "string",
            "description": "string",
            "category": "string",
            "price": "number",
            "ready_time": "number",
            "rating": "number",
            "image": "string",
            "qty_available": "number"
          }
        ]
      }
    }
    ```

- `GET /api/public/foods/{id}` (Public) - Get food details
  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Food details",
      "data": {
        "id": "number",
        "name": "string",
        "description": "string",
        "category": "string",
        "price": "number",
        "ready_time": "number",
        "rating": "number",
        "image": "string",
        "vendor": {
          "id": "number",
          "name": "string",
          "phone": "string",
          "address": "string",
          "food_types": ["string"],
          "rating": "number",
          "image": "string"
        }
      }
    }
    ```

### Customer Auth

- `POST /api/v1/auth/register` (Public) - Register new customer

  - Request (JSON): `{ "email": "string", "password": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string" }`
  - Response (JSON): `{ "success": true, "message": "Registration successful", "data": { "token": "string", "user_id": "number" } }`

- `POST /api/v1/auth/login` (Public) - Customer login

  - Request (JSON): `{ "email": "string", "password": "string" }`
  - Response (JSON): `{ "success": true, "message": "Login successful", "data": { "token": "string", "user_id": "number" } }`

- `POST /api/v1/auth/logout` (Customer) - Customer logout

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Logged out successfully" }`

- `GET /api/v1/auth/profile` (Customer) - Get customer profile

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Customer profile", "data": { "id": "number", "email": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string", "image": "string" } }`

- `PUT /api/v1/auth/profile` (Customer) - Update customer profile

  - Request (JSON): `{ "name": "string", "phone": "string", "address": "string" }` (all fields optional)
  - Response (JSON): `{ "success": true, "message": "Profile updated", "data": { "Customer": { "id": "number", "name": "string", "phone": "string", "address": "string" } } }`

- `POST /api/v1/auth/profile/image` (Customer) - Update customer profile picture
  - Request (Form-data): image file
  - Response (JSON): `{ "success": true, "message": "Image upload success" }`### Customer Profile

### Customer Order

- `POST /api/v1/orders` (Customer) - Create new order

  - Request (JSON): `{ "items": [{ "food_id": "number", "quantity": "number" }], "remarks": "string" }`
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Order created",
      "data": {
        "orders": {
          "id": "number",
          "customer_id": "number",
          "status": {
            "id": "number",
            "label": "string"
          },
          "total": "number",
          "remarks": "string",
          "created_at": "string",
          "food_detail": [
            {
              "food_id": "number",
              "name": "string",
              "description": "string",
              "category": "string",
              "price": "number",
              "quantity": "number",
              "vendor": {
                "id": "number",
                "name": "string",
                "phone": "string",
                "address": "string",
                "food_types": ["string"],
                "rating": "number",
                "image": "string"
              }
            }
          ]
        }
      }
    }
    ```

- `GET /api/v1/orders` (Customer) - Get order history

  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Orders retrieved",
      "data": {
        "customer": {
          "id": "number",
          "email": "string",
          "name": "string",
          "address": "string",
          "phone": "string",
          "location": "string",
          "lat_lng": "string",
          "image": "string"
        },
        "orders": [
          {
            "id": "number",
            "status": {
              "id": "number",
              "label": "string"
            },
            "total": "number",
            "remarks": "string",
            "created_at": "string",
            "food_detail": [
              {
                "food_id": "number",
                "name": "string",
                "description": "string",
                "category": "string",
                "price": "number",
                "quantity": "number",
                "vendor": {
                  "id": "number",
                  "name": "string",
                  "phone": "string",
                  "address": "string",
                  "food_types": ["string"],
                  "rating": "number",
                  "image": "string"
                }
              }
            ]
          }
        ]
      }
    }
    ```

- `GET /api/v1/orders/{id}` (Customer) - Get order details

  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Order retrieved",
      "data": {
        "order": {
          "id": "number",
          "customer_id": "number",
          "status": {
            "id": "number",
            "label": "string"
          },
          "total": "number",
          "remarks": "string",
          "created_at": "string",
          "food_detail": [
            {
              "food_id": "number",
              "name": "string",
              "description": "string",
              "category": "string",
              "price": "number",
              "quantity": "number",
              "vendor": {
                "id": "number",
                "name": "string",
                "phone": "string",
                "address": "string",
                "food_types": ["string"],
                "rating": "number",
                "image": "string"
              }
            }
          ]
        }
      }
    }
    ```

- `DELETE /api/v1/orders/{id}` (Customer) - Cancel order
  - Request (JSON): None
  - Response (JSON):
    ```json
    {
      "success": true,
      "message": "Order cancelled",
      "data": {
        "id": "number",
        "status": {
          "id": "number",
          "label": "string"
        }
      }
    }
    ```

### Customer Payment

- `GET /api/v1/payment-methods` (Customer) - Get all payment methods

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment methods retrieved", "data": [{ "id": "number", "type": "string", "card_brand": "string", "card_last4": "string", "exp_month": "number", "exp_year": "number" }] }`

- `POST /api/v1/payment-methods` (Customer) - Add new payment method

  - Request (JSON): `{ "type": "string", "card_brand": "string", "card_last4": "string", "exp_month": "number", "exp_year": "number" }`
  - Response (JSON): `{ "success": true, "message": "Payment method added successfully", "data": { "payment_method_id": "number" } }`

- `DELETE /api/v1/payment-methods/{id}` (Customer) - Remove payment method

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment method removed successfully" }`

- `GET /api/v1/payments` (Customer) - Get payment history

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment history retrieved", "data": [{ "id": "number", "order_id": "number", "amount": "number", "status": "string", "created_at": "string" }] }`

- `GET /api/v1/payments/{id}` (Customer) - Get payment details

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment details", "data": { "id": "number", "order_id": "number", "payment_method_id": "number", "amount": "number", "currency": "string", "status": "string" } }`

- `POST /api/v1/orders/{orderId}/payment` (Customer) - Process payment for order

  - Request (JSON): `{ "payment_method_id": "number" }`
  - Response (JSON): `{ "success": true, "message": "Payment initiated successfully", "data": { "payment_id": "number", "order_id": "number", "amount": "number", "status": "string" } }`

  - Response (JSON): `{ "success": true }` or `{ "error": "string" }`

### Stripe Payment

- `POST /api/v1/payment-methods/stripe/setup-intent` (Customer) - Create SetupIntent for adding payment methods

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Setup intent created", "data": { "client_secret": "string", "setup_intent_id": "string" } }`

- `POST /api/v1/payment-methods/stripe/save` (Customer) - Save payment method after SetupIntent succeeds

  - Request (JSON): `{ "payment_method_id": "string" }`
  - Response (JSON): `{ "success": true, "message": "Payment method saved successfully", "data": { "payment_method_id": "number", "card_brand": "string", "card_last4": "string" } }`

- `GET /api/v1/payment-methods` (Customer) - Get all payment methods

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment methods retrieved", "data": [{ "id": "number", "customer_id": "number", "stripe_pm_id": "string", "type": "string", "card_brand": "string", "card_last4": "string", "exp_month": "number", "exp_year": "number", "created_at": "string", "updated_at": "string" }] }`

- `DELETE /api/v1/payment-methods/stripe/{id}` (Customer) - Remove Stripe payment method

  - Request (JSON): None
  - Response (JSON): `{ "success": true, "message": "Payment method removed successfully", "data": null }`

- `POST /api/v1/orders/{orderid}/stripe-payment` (Customer) - Process payment with saved Stripe payment method
  - Request (JSON): `{ "payment_method_id": "number" }`
  - Response (JSON):
    - Success: `{ "success": true, "message": "Payment completed successfully", "data": { "payment_id": "number", "order_id": "number", "amount": "number", "status": "succeeded" } }`
    - Requires Action: `{ "success": true, "message": "Payment requires additional authentication", "data": { "payment_id": "number", "order_id": "number", "requires_action": true, "payment_intent": { "id": "string", "client_secret": "string" }, "next_action": "object" } }`
    - Processing: `{ "success": true, "message": "Payment is being processed", "data": { "payment_id": "number", "order_id": "number", "status": "processing" } }`

<!-- #### Order Status Settings Endpoints

- `GET /api/admin/order-statuses` (Admin) - List all order statuses
- `POST /api/admin/order-statuses` (Admin) - Create order status
- `GET /api/admin/order-statuses/{key}` (Admin) - Get status details
- `PUT /api/admin/order-statuses/{key}` (Admin) - Update order status
- `DELETE /api/admin/order-statuses/{key}` (Admin) - Delete order status -->

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
