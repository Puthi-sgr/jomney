# API Testing Guide - Customer Payment Integration

## Overview

This document provides testing instructions for all customer payment endpoints including Stripe integration.

## Base URLs

- Local: `http://localhost/api/v1`
- Development: `http://your-dev-server/api/v1`

## Authentication

All customer endpoints require authentication. Include the following header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Table of Contents

1. [Authentication](#authentication-endpoints)
2. [Payment Methods Management](#payment-methods-management)
3. [Stripe Integration](#stripe-integration)
4. [Order Payment Processing](#order-payment-processing)
5. [Payment History](#payment-history)

---

## Authentication Endpoints

### 1. Customer Registration

**Controller:** `CustomerAuthController::register`

```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "phone": "+1234567890"
}
```

### 2. Customer Login

**Controller:** `CustomerAuthController::login`

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

---

## Payment Methods Management

### 1. Get Payment Methods (Stripe Only)

**Controller:** `CustomerPaymentController::getPaymentMethods`

```http
GET /api/v1/payment-methods
Authorization: Bearer YOUR_JWT_TOKEN
```

### ~~2. Add Payment Method (Mock) - DISABLED~~

~~**Controller:** `CustomerPaymentController::addPaymentMethod`~~

```http
# COMMENTED OUT - USE STRIPE INTEGRATION INSTEAD
# POST /api/v1/payment-methods
```

### ~~3. Remove Payment Method (Mock) - DISABLED~~

~~**Controller:** `CustomerPaymentController::removePaymentMethod`~~

```http
# COMMENTED OUT - USE STRIPE INTEGRATION INSTEAD
# DELETE /api/v1/payment-methods/{id}
```

---

## Stripe Integration

### 1. Create Setup Intent

**Controller:** `CustomerPaymentController::createSetupIntent`

```http
POST /api/v1/payment-methods/stripe/setup-intent
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Response:**

```json
{
  "success": true,
  "message": "Setup intent created",
  "data": {
    "client_secret": "seti_xxx_secret_xxx",
    "setup_intent_id": "seti_xxx"
  }
}
```

### 2. Save Payment Method (After Stripe Confirmation)

**Controller:** `CustomerPaymentController::savePaymentMethod`

```http
POST /api/v1/payment-methods/stripe/save
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "payment_method_id": "pm_1234567890"
}
```

### 3. Remove Stripe Payment Method

**Controller:** `CustomerPaymentController::removeStripePaymentMethod`

```http
DELETE /api/v1/payment-methods/stripe/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## Order Payment Processing

### 1. Process Stripe Payment (ACTIVE)

**Controller:** `CustomerPaymentController::processStripePayment`

```http
POST /api/v1/orders/{orderId}/stripe-payment
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "payment_method_id": 1
}
```

**Response Scenarios:**

**Success:**

```json
{
  "success": true,
  "message": "Payment completed successfully",
  "data": {
    "payment_id": 123,
    "order_id": 456,
    "amount": 25.99,
    "status": "succeeded"
  }
}
```

**Requires 3D Secure:**

```json
{
  "success": true,
  "message": "Payment requires additional authentication",
  "data": {
    "payment_id": 123,
    "order_id": 456,
    "requires_action": true,
    "payment_intent": {
      "id": "pi_xxx",
      "client_secret": "pi_xxx_secret_xxx"
    },
    "next_action": {
      "type": "use_stripe_sdk"
    }
  }
}
```

### ~~2. Process Payment (Mock) - DISABLED~~

~~**Controller:** `CustomerPaymentController::processPayment`~~

```http
# COMMENTED OUT - USE STRIPE PAYMENT INSTEAD
# POST /api/v1/orders/{orderId}/payment
```

### ~~3. Legacy Checkout - DISABLED~~

~~**Controller:** `CustomerPaymentController::checkout`~~

```http
# COMMENTED OUT - USE STRIPE PAYMENT INSTEAD
# POST /api/customer/payments/checkout
```

---

## Payment History

### 1. Get Payment History

**Controller:** `CustomerPaymentController::getPaymentHistory`

```http
GET /api/v1/payments
Authorization: Bearer YOUR_JWT_TOKEN
```

### 2. Get Specific Payment

**Controller:** `CustomerPaymentController::getPayment`

```http
GET /api/v1/payments/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## Testing Stripe Integration Flow

### Complete Stripe Payment Flow:

1.  **Login Customer**

    ```http
    POST /api/v1/auth/login
    ```

2.  **Create Setup Intent**

    ```http
    POST /api/v1/payment-methods/stripe/setup-intent
    ```

3.  **Frontend: Use Stripe.js to confirm setup intent with card details**

4.  **Save Payment Method**

    ```http
    POST /api/v1/payment-methods/stripe/save
    {
      "payment_method_id": "pm_from_stripe"
    }
    ```

    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://js.stripe.com/v3/"></script>
    </head>
    <body>
        <button onclick="createPaymentMethod()">Create Payment Method</button>
        <div id="result"></div>

        <script>
            const stripe = Stripe('pk_test_YOUR_PUBLISHABLE_KEY'); // Replace with your key

            async function createPaymentMethod() {
                const {paymentMethod, error} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: {
                        number: '4242424242424242',
                        exp_month: 12,
                        exp_year: 2025,
                        cvc: '123',
                    },
                });

                if (error) {
                    document.getElementById('result').innerHTML = 'Error: ' + error.message;
                } else {
                    document.getElementById('result').innerHTML =
                        '<strong>Payment Method ID:</strong> ' + paymentMethod.id;
                    console.log('Full Payment Method:', paymentMethod);
                }
            }
        </script>

    </body>
    </html>

5.  **Create Order (separate endpoint)**

    ```http
    POST /api/v1/orders
    ```

6.  **Process Payment**

    ```http
    POST /api/v1/orders/{orderId}/stripe-payment
    {
      "payment_method_id": 1
    }
    ```

7.  **Check Payment Status**
    ```http
    GET /api/v1/payments/{paymentId}
    ```

---

## Error Responses

### Common Error Status Codes:

- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

### Example Error Response:

```json
{
  "success": false,
  "message": "Payment method not found",
  "data": [],
  "status_code": 404
}
```

---

## Environment Variables Required

Ensure the following environment variables are set:

```env
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxx
```

---

## Testing Tools

### Recommended Tools:

- **Postman** - For API testing
- **curl** - Command line testing
- **Insomnia** - API client

### Stripe Test Cards:

- **Success:** `4242424242424242`
- **Decline:** `4000000000000002`
- **3D Secure:** `4000002500003155`

---

## Notes

1. All payment methods are stored in the `payment_methods` table
2. Payments are recorded in the `payments` table
3. Orders status is updated when payment succeeds
4. Stripe webhook handling is implemented in the main `PaymentController`
5. Customer Stripe customer IDs are stored in the `customers` table

---

## Controller Mapping

| Endpoint                                           | Controller                  | Method                      |
| -------------------------------------------------- | --------------------------- | --------------------------- |
| `POST /api/v1/payment-methods/stripe/setup-intent` | `CustomerPaymentController` | `createSetupIntent`         |
| `POST /api/v1/payment-methods/stripe/save`         | `CustomerPaymentController` | `savePaymentMethod`         |
| `DELETE /api/v1/payment-methods/stripe/{id}`       | `CustomerPaymentController` | `removeStripePaymentMethod` |
| `POST /api/v1/orders/{orderId}/stripe-payment`     | `CustomerPaymentController` | `processStripePayment`      |
| `GET /api/v1/payment-methods`                      | `CustomerPaymentController` | `getPaymentMethods`         |
| `POST /api/v1/payment-methods`                     | `CustomerPaymentController` | `addPaymentMethod`          |
| `DELETE /api/v1/payment-methods/{id}`              | `CustomerPaymentController` | `removePaymentMethod`       |
| `POST /api/v1/orders/{orderId}/payment`            | `CustomerPaymentController` | `processPayment`            |
| `GET /api/v1/payments`                             | `CustomerPaymentController` | `getPaymentHistory`         |
| `GET /api/v1/payments/{id}`                        | `CustomerPaymentController` | `getPayment`                |
| `POST /api/customer/payments/checkout`             | `CustomerPaymentController` | `checkout`                  |
