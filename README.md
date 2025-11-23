# Admin & Customer API — README

Generated from `Admin API document.xlsx`.

## Overview

- This API powers an **Admin back‑office** and a **Customer app** for a food ordering marketplace.
- Endpoints are grouped by responsibility (auth, vendors, foods, orders, payments).
- Authentication uses **JWT** for protected routes. Public browsing endpoints do not require auth.

## Base URL & Versioning

- **Base path:** `/api`
- **Customer versioned paths:** `/api/v1/...`
- **Admin paths:** `/api/admin/...`

## Authentication

**Admin**

1) `POST /api/admin/login` with JSON `{ "email": "string", "password": "string" }` → returns a token.
2) Use `Authorization: Bearer <token>` on subsequent admin requests.
3) `GET /api/admin/user` to fetch the logged‑in admin profile.
4) `POST /api/admin/logout` to revoke the current token.

**Customer**

1) `POST /api/v1/auth/register` to create an account.
2) `POST /api/v1/auth/login` → receive token, then call `GET /api/v1/auth/profile`.
3) Update profile: `PUT /api/v1/auth/profile` (JSON). Upload avatar: `POST /api/v1/auth/profile/image` (Form‑data file upload).

## How the API Works — Application Flow

### Public browse (no auth)
1) **List vendors & foods:** `GET /api/public/vendors`, `GET /api/public/foods`.
2) **View vendor details:** `GET /api/public/vendors/{id}` → shows vendor info and foods offered.
3) **View food details:** `GET /api/public/foods/{id}`.

### Ordering (customer JWT)
1) **Create order:** `POST /api/v1/orders` → returns new order.
2) **Read order:** `GET /api/v1/orders/{id}`.
3) **Cancel order (if allowed):** `DELETE /api/v1/orders/{id}`.

## Payments — Two Options

### A) Mock payments (simple demo)
- Manage stored payment methods under `/api/v1/payment-methods` (GET/POST/DELETE).
- Pay an order: `POST /api/v1/orders/{orderid}/payment`.

### B) Stripe integration (recommended)
1) **Create SetupIntent** to add a card: `POST /api/v1/payment-methods/stripe/setup-intent` → returns `client_secret`.
2) **Complete Setup on the client** using Stripe Elements with the `client_secret`.
3) **Save payment method**: `POST /api/v1/payment-methods/stripe/save`.
4) **List methods**: `GET /api/v1/payment-methods`.
5) **Charge an order with a saved method**: `POST /api/v1/orders/{orderid}/stripe-payment`.
6) **Remove a saved method**: `DELETE /api/v1/payment-methods/stripe/{id}`.

## Common Response Shape

- Most endpoints respond with:
  ```json
  { "success": true, "message": "<info>", "data": { ... } }
  ```
- Errors typically mirror the same envelope with `success: false` and an explanatory `message`.

## Endpoint Reference

### Admin Authentication Endpoints

**POST /api/admin/login**
- **Purpose:** Admin signs in
- **Auth:** None
- **Body Type:** JSON
- **Request Body / Params:**
```json
{ "email": "string", "password": "string" }
```
- **Response 200 (example):**
```json
{ "token": "string", "user": { "id": "string", "name": "string", "email": "string" } }
```
- **cURL:**
```bash
curl -X POST -H 'Content-Type: application/json' --data '{ "email": "string", "password": "string" }' https://api.example.com/api/admin/login
```

**POST /api/admin/logout**
- **Purpose:** Invalidate session/JWT
- **Auth:** None
- **Response 200 (example):**
```json
{ "message": "Logged out successfully" }
```
- **cURL:**
```bash
curl -X POST https://api.example.com/api/admin/logout
```

**GET /api/admin/user**
- **Purpose:** Get current admin info
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "id": "string", "name": "string", "email": "string", "role": "string" }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/user
```

### Admin Dashoard

**GET /api/admin/stats**
- **Purpose:** Dashboard statistics snapshot
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Stats overview", "data": { "total_customers": number, "total_vendors": number, "total_orders": number, "total_revenue": number, "orders_by_status": { "pending": number, "confirmed": number, "delivered": number } } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/stats
```

### Vendor management

**GET /api/admin/vendors**
- **Purpose:** List all vendors
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": true, "message": string, "data": { "vendors": [ { "id": integer, "name": string, "email": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string, "revenue": number, "totalOrders": integer } ] } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/vendors
```

**POST /api/admin/vendors**
- **Purpose:** Create vendor
- **Auth:** JWT
- **Body Type:** JSON / Form-data
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/vendors
```

**GET /api/admin/vendors/{id}**
- **Purpose:** Get vendor details
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": true, "message": string, "data": { "vendor": { "id": integer, "name": string, "email": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string }, "foods": [ … ], "revenue": number, "totalOrders": integer, "foodOrders": [ … ] } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/vendors/{id}
```

**POST /api/admin/vendors/{id}**
- **Purpose:** Update vendor image
- **Auth:** JWT
- **Body Type:** Form-data
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -F 'key=value' https://api.example.com/api/admin/vendors/{id}
```

**PUT /api/admin/vendors/{id}**
- **Purpose:** Update vendor details
- **Auth:** JWT
- **Body Type:** Form-data
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X PUT -H 'Authorization: Bearer <JWT>' -F 'key=value' https://api.example.com/api/admin/vendors/{id}
```

**DELETE /api/admin/vendors/delete/{id}**
- **Purpose:** Remove vendor
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X DELETE -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/vendors/delete/{id}
```

**GET /api/admin/vendors/{id}/earnings**
- **Purpose:** Vendor earnings summary
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "vendor": { "id": integer, "name": string, "email": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string }, "totalAmount": number, "orders": [ { "order_id": integer, "placed_at": string, "foodId": integer, "food_name": string, "food_description": string, "food_price": number, "amount": number, "gross": number } ] } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/vendors/{id}/earnings
```

**GET /api/admin/vendors/{id}/orders**
- **Purpose:** Vendor's food orders
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "vendor": { "id": integer, "name": string, "email": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string }, "totalOrders": integer, "foodOrders": [ { "order_id": integer, "food_id": integer, "name": string, "description": string, "price": number, "quantity": integer, "vendor_id": integer, "created_at": string, "status_key": string } ] } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/vendors/{id}/orders
```

### Food management

**GET /api/admin/foods**
- **Purpose:** List foods (filterable)
- **Auth:** JWT
- **Response 200 (example):**
```json
{
    "success": boolean,
    "message": string,
    "data": {
        "foods": [
            {
                "id": integer,
                "name": string,
                "description": string,
                "category": string,
                "price": string,
                "ready_time": integer,
                "rating": string,
                "image": string,
                "qty_available": integer,
                "vendor": {
                    "id": integer,
                    "email": string,
                    "name": string,
                    "phone": string,
                    "address": string,
                    "food_types": array<string>,
                    "rating": string,
                    "image": string
                }
            }
        ]
    }
}
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/foods
```

**POST /api/admin/foods**
- **Purpose:** Create food item
- **Auth:** JWT
- **Body Type:** JSON / Form-data
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "food_id": integer, "image_uploaded": boolean, "image_url": string } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/foods
```

**POST /api/admin/foods/image/{id}**
- **Purpose:** Update food image
- **Auth:** JWT
- **Body Type:** Form-data
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "food_id": integer, "image_url": string } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -F 'key=value' https://api.example.com/api/admin/foods/image/{id}
```

**GET /api/admin/foods/{id}**
- **Purpose:** Get food details
- **Auth:** JWT
- **Response 200 (example):**
```json
{
    "success": boolean,
    "message": string,
    "data": {
        "food": {
            "id": integer,
            "name": string,
            "description": string,
            "category": string,
            "price": string,
            "ready_time": integer,
            "rating": string,
            "image": string,
            "vendor": {
                "id": integer,
                "email": string,
                "name": string,
                "phone": string,
                "address": string,
                "food_types": array<string>,
                "rating": string,
                "image": string
            }
        }
    }
}
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/foods/{id}
```

**PUT /api/admin/foods/{id}**
- **Purpose:** Update food details
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X PUT -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/foods/{id}
```

**DELETE /api/admin/foods/{id}**
- **Purpose:** Delete food item
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X DELETE -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/foods/{id}
```

### Order management

**GET /api/admin/orders**
- **Purpose:** List all orders
- **Auth:** JWT
- **Response 200 (example):**
```json
{
    "success": boolean,
    "message": string,
    "data": {
        "orders": [
            {
                "id": integer,
                "status_id": integer,
                "total_amount": string,
                "remarks": string,
                "created_at": string,
                "updated_at": string,
                "customername": string,
                "statuslabel": string,
                "customer": {
                    "id": integer,
                    "email": string,
                    "name": string,
                    "address": string,
                    "phone": string,
                    "location": string,
                    "lat_lng": string|null,
                    "image": string,
                    "stripe_customer_id": string|null
                }
            }
        ]
    }
}
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/orders
```

**GET /api/admin/orders/{id}**
- **Purpose:** Get order details
- **Auth:** JWT
- **Response 200 (example):**
```json
{
    "success": boolean,
    "message": string,
    "data": {
        "order": {
            "id": integer,
            "total_amount": string,
            "remarks": string,
            "created_at": string,
            "updated_at": string,
            "status": {
                "id": integer,
                "key": string,
                "label": string,
                "created_at": string,
                "updated_at": string
            },
            "customer": {
                "id": integer,
                "email": string,
                "password": string,
                "name": string,
                "address": string,
                "phone": string,
                "location": string,
                "lat_lng": string,
                "created_at": string,
                "updated_at": string,
                "image": string,
                "stripe_customer_id": string|null
            },
            "food_detail": [
                {
                    "id": integer,
                    "food_id": integer,
                    "order_id": integer,
                    "price": string,
                    "quantity": string,
                    "created_at": string,
                    "updated_at": string,
                    "name": string,
                    "image": string
                }
            ]
        }
    }
}
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/orders/{id}
```

**PATCH /api/admin/orders/{id}/status**
- **Purpose:** Update order status
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X PATCH -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/orders/{id}/status
```

**GET /api/admin/foods/{id}/inventory**
- **Auth:** JWT
- **Response 200 (example):**
```json
{
    success: boolean,
    message: string,
    data: {
        food_id: number,
        food_name: string,
        current_stock: number
    }
}
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/foods/{id}/inventory
```

**PATCH /api/admin/foods/{id}/inventory/adjust'**
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
{
    success: boolean,
    message: string,
    data: {
        food_id: number,
        food_name: string,
        previous_stock: number,
        adjustment: number,
        new_stock: number
    }
}
```
- **cURL:**
```bash
curl -X PATCH -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/foods/{id}/inventory/adjust'
```

### Customer management

**GET /api/admin/customers**
- **Purpose:** List all customers
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": [ { "id": integer, "email": string, "name": string, "address": string, "phone": string, "location": string, "lat_lng": string, "image": string } ] }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/customers
```

**POST /api/admin/customers**
- **Purpose:** Create customer
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/customers
```

**GET /api/admin/customers/{id}**
- **Purpose:** Get customer details
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "id": integer, "email": string, "name": string, "address": string, "phone": string, "location": string, "lat_lng": string, "image": string } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/customers/{id}
```

**POST /api/admin/customers/image/{id}**
- **Purpose:** Update customer image
- **Auth:** JWT
- **Body Type:** Form-data
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -F 'key=value' https://api.example.com/api/admin/customers/image/{id}
```

**PUT /api/admin/customers/{id}**
- **Purpose:** Update customer details
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X PUT -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/admin/customers/{id}
```

**DELETE /api/admin/customers/{id}**
- **Purpose:** Delete customer
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string }
```
- **cURL:**
```bash
curl -X DELETE -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/customers/{id}
```

### Payment management

**GET /api/admin/payments**
- **Purpose:** List all payments
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": [ { "id": integer, "order_id": integer, "amount": number, "status": string, "payment_method": string, "created_at": string } ] }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/payments
```

**GET /api/admin/payments/{id}**
- **Purpose:** Get payment details
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "id": integer, "order_id": integer, "amount": number, "status": string, "payment_method": string, "created_at": string } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/admin/payments/{id}
```

### Public Endpoint

**GET /api/public/vendors**
- **Purpose:** List all vendors (with foods)
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "vendors": [ { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string, "foods": [ { "id": integer, "name": string, "description": string, "category": string, "price": number, "ready_time": integer, "rating": number, "image": string, "qty_available": integer } ] } ] } }
```
- **cURL:**
```bash
curl -X GET https://api.example.com/api/public/vendors
```

**GET /api/public/foods**
- **Purpose:** List all foods (with vendor)
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "foods": [ { "id": integer, "name": string, "description": string, "category": string, "price": number, "ready_time": integer, "rating": number, "image": string, "qty_available": integer, "vendor": { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string } } ] } }
```
- **cURL:**
```bash
curl -X GET https://api.example.com/api/public/foods
```

**GET /api/public/vendors/{id}**
- **Purpose:** Vendor details + food list
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "vendor": { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string }, "foods": [ { "id": integer, "name": string, "description": string, "category": string, "price": number, "ready_time": integer, "rating": number, "image": string, "qty_available": integer } ] } }
```
- **cURL:**
```bash
curl -X GET https://api.example.com/api/public/vendors/{id}
```

**GET /api/public/foods/{id}**
- **Purpose:** Food details (with vendor)
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "id": integer, "name": string, "description": string, "category": string, "price": number, "ready_time": integer, "rating": number, "image": string, "vendor": { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string } } }
```
- **cURL:**
```bash
curl -X GET https://api.example.com/api/public/foods/{id}
```

### Customer Authorization

**POST /api/v1/auth/register**
- **Purpose:** Register new customer
- **Body Type:** JSON
- **Response 200 (example):**
```json
{ "success": true, "message": "Registration successful", "data": { "token": "string", "user_id": "number" } }
```
- **cURL:**
```bash
curl -X POST -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/auth/register
```

**POST /api/v1/auth/login**
- **Purpose:** Customer login
- **Body Type:** JSON
- **Response 200 (example):**
```json
{ "success": true, "message": "Login successful", "data": { "token": "string", "user_id": "number" } }
```
- **cURL:**
```bash
curl -X POST -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/auth/login
```

**POST /api/v1/auth/logout**
- **Purpose:** Customer logout
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Logged out successfully" }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/auth/logout
```

**GET /api/v1/auth/profile**
- **Purpose:** Get customer profile
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Customer profile", "data": { "id": "number", "email": "string", "name": "string", "address": "string", "phone": "string", "location": "string", "lat_lng": "string", "image": "string" } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/auth/profile
```

**PUT /api/v1/auth/profile**
- **Purpose:** Update customer profile
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
{ "success": true, "message": "Profile updated", "data": { "Customer": { "id": "number", "name": "string", "phone": "string", "address": "string" } } }
```
- **cURL:**
```bash
curl -X PUT -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/auth/profile
```

**POST /api/v1/auth/profile/image**
- **Purpose:** Update customer profile picture
- **Auth:** JWT
- **Body Type:** Form-data
- **Response 200 (example):**
```json
{ "success": true, "message": "Image upload success" }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -F 'key=value' https://api.example.com/api/v1/auth/profile/image
```

### Customer Order

**POST /api/v1/orders**
- **Purpose:** Create new order
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "orders": { "id": integer, "customer_id": integer, "status": { "id": integer, "label": string }, "total": number, "remarks": string, "created_at": string, "food_detail": [ { "food_id": integer, "name": string, "description": string, "category": string, "price": number, "quantity": integer, "vendor": { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string } } ] } } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/orders
```

**GET /api/v1/orders**
- **Purpose:** Order history
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "customer": { "id": integer, "email": string, "name": string, "address": string, "phone": string, "location": string, "lat_lng": string, "image": string }, "orders": [ { "id": integer, "status": { "id": integer, "label": string }, "total": number, "remarks": string, "created_at": string, "food_detail": [ { "food_id": integer, "name": string, "description": string, "category": string, "price": number, "quantity": integer, "vendor": { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string } } ] } ] } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/orders
```

**GET /api/v1/orders/{id}**
- **Purpose:** Order details
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "order": { "id": integer, "customer_id": integer, "status": { "id": integer, "label": string }, "total": number, "remarks": string, "created_at": string, "food_detail": [ { "food_id": integer, "name": string, "description": string, "category": string, "price": number, "quantity": integer, "vendor": { "id": integer, "name": string, "phone": string, "address": string, "food_types": string[], "rating": number, "image": string } } ] } } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/orders/{id}
```

**DELETE /api/v1/orders/{id}**
- **Purpose:** Cancel order
- **Auth:** JWT
- **Response 200 (example):**
```json
json { "success": boolean, "message": string, "data": { "id": integer, "status": { "id": integer, "label": string } } }
```
- **cURL:**
```bash
curl -X DELETE -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/orders/{id}
```

### Customer Payment (Mock)

**GET /api/v1/payment-methods**
- **Purpose:** Get all payment methods for customer
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment methods retrieved", "data": [{ "id": "number", "customer_id": "number", "stripe_pm_id": "string", "type": "string", "card_brand": "string", "card_last4": "string", "exp_month": "number", "exp_year": "number", "created_at": "string" }] }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payment-methods
```

**POST /api/v1/payment-methods**
- **Purpose:** Add new payment method (mock)
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment method added successfully", "data": { "payment_method_id": "number" } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/payment-methods
```

**DELETE /api/v1/payment-methods/{id}**
- **Purpose:** Remove payment method
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment method removed successfully" }
```
- **cURL:**
```bash
curl -X DELETE -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payment-methods/{id}
```

**POST /api/v1/orders/{orderid}/payment**
- **Purpose:** Process payment for an order
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment initiated successfully", "data": { "payment_id": "number", "order_id": "number", "amount": "number", "status": "string" } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/orders/{orderid}/payment
```

**GET /api/v1/payments**
- **Purpose:** Get payment history for customer
- **Auth:** JWT
- **Body Type:** || "Failed to delete vendor",
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment history retrieved", "data": [{ "id": "number", "order_id": "number", "payment_method_id": "number", "stripe_payment_id": "string", "amount": "number", "currency": "string", "status": "string", "payment_type": "string", "card_last4": "string", "order_total": "number", "created_at": "string", "updated_at": "string" }] }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payments
```

**GET /api/v1/payments/{id}**
- **Purpose:** Get specific payment details
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment details", "data": { "id": "number", "order_id": "number", "payment_method_id": "number", "stripe_payment_id": "string", "amount": "number", "currency": "string", "status": "string", "payment_type": "string", "card_last4": "string", "order_total": "number", "created_at": "string", "updated_at": "string" } }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payments/{id}
```

### Customer Stripe Payment Integration

**POST /api/v1/payment-methods/stripe/setup-intent**
- **Purpose:** Create SetupIntent for adding payment methods
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Setup intent created", "data": { "client_secret": "string", "setup_intent_id": "string" } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payment-methods/stripe/setup-intent
```

**POST /api/v1/payment-methods/stripe/save**
- **Purpose:** Save payment method after SetupIntent succeeds
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment method saved successfully", "data": { "payment_method_id": "number", "card_brand": "string", "card_last4": "string" } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/payment-methods/stripe/save
```

**GET /api/v1/payment-methods**
- **Purpose:** Get all payment methods for customer
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment methods retrieved", "data": [{ "id": "number", "customer_id": "number", "stripe_pm_id": "string", "type": "string", "card_brand": "string", "card_last4": "string", "exp_month": "number", "exp_year": "number", "created_at": "string", "updated_at": "string" }] }
```
- **cURL:**
```bash
curl -X GET -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payment-methods
```

**DELETE /api/v1/payment-methods/stripe/{id}**
- **Purpose:** Remove Stripe payment method
- **Auth:** JWT
- **Response 200 (example):**
```json
{ "success": true, "message": "Payment method removed successfully", "data": null }
```
- **cURL:**
```bash
curl -X DELETE -H 'Authorization: Bearer <JWT>' https://api.example.com/api/v1/payment-methods/stripe/{id}
```

**POST /api/v1/orders/{orderid}/stripe-payment**
- **Purpose:** Process payment with saved Stripe payment method
- **Auth:** JWT
- **Body Type:** JSON
- **Response 200 (example):**
```json
Success: { "success": true, "message": "Payment completed successfully", "data": { "payment_id": "number", "order_id": "number", "amount": "number", "status": "succeeded" } } Requires Action: { "success": true, "message": "Payment requires additional authentication", "data": { "payment_id": "number", "order_id": "number", "requires_action": true, "payment_intent": { "id": "string", "client_secret": "string" }, "next_action": "object" } } Processing: { "success": true, "message": "Payment is being processed", "data": { "payment_id": "number", "order_id": "number", "status": "processing" } }
```
- **cURL:**
```bash
curl -X POST -H 'Authorization: Bearer <JWT>' -H 'Content-Type: application/json' --data '{ }' https://api.example.com/api/v1/orders/{orderid}/stripe-payment
```
