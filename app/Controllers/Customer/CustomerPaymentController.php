<?php
namespace App\Controllers\Customer;

use App\Core\Response;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Order;

class CustomerPaymentController
{
    private Payment $paymentModel;
    private PaymentMethod $paymentMethodModel;
    private Order $orderModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->paymentMethodModel = new PaymentMethod();
        $this->orderModel = new Order();
    }


    /**
     * GET /api/v1/payment-methods
     * Get all payment methods for the authenticated customer
     */
    public function getPaymentMethods(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $paymentMethods = $this->paymentMethodModel->allByCustomer($customerId);
        Response::success('Payment methods retrieved', $paymentMethods);
    }

     /**
     * POST /api/v1/payment-methods
     * Add a new payment method for the customer (mock for demo)
     */
    public function addPaymentMethod(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate required fields
        if (empty($body['type'])) {
            Response::error('Payment method type is required', [], 422);
            return;
        }

        // For demo purposes, we'll create mock payment methods
        $data = [
            'customer_id' => $customerId,
            'stripe_pm_id' => 'pm_mock_' . uniqid(), // Mock Stripe PM ID
            'type' => $body['type'],
            'card_brand' => $body['card_brand'] ?? null,
            'card_last4' => $body['card_last4'] ?? null,
            'exp_month' => $body['exp_month'] ?? null,
            'exp_year' => $body['exp_year'] ?? null,
        ];

        $paymentMethodId = $this->paymentMethodModel->create($data);
        
        if (!$paymentMethodId) {
            Response::error('Failed to add payment method', [], 500);
            return;
        }

        Response::success('Payment method added successfully', [
            'payment_method_id' => $paymentMethodId
        ], 201);
    }

     /**
     * DELETE /api/v1/payment-methods/{id}
     * Remove a payment method
     */
    public function removePaymentMethod(int $id): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        // Verify the payment method belongs to the customer
        $paymentMethod = $this->paymentMethodModel->findByCustomerAndId($customerId, $id);
        if (!$paymentMethod) {
            Response::error('Payment method not found', [], 404);
            return;
        }

        $result = $this->paymentMethodModel->delete($id);
        if ($result) {
            Response::success('Payment method removed successfully');
        } else {
            Response::error('Failed to remove payment method', [], 500);
        }
    }

    /**
     * POST /api/customer/payments/checkout
     */
    public function checkout(): void
    {
        $data = [
            'order_id' => $_POST['order_id'] ?? null,
            'payment_method_id' => $_POST['payment_method_id'] ?? null,
            'stripe_payment_id' => $_POST['stripe_payment_id'] ?? null,
            'amount' => $_POST['amount'] ?? null,
            'currency' => $_POST['currency'] ?? null,
            'status' => $_POST['status'] ?? 'pending'
        ];

        $result = $this->paymentModel->create($data);
        
        if (!$result) {
            Response::error('Failed to create payment', [], 400);
            return;
        }
        
        Response::success('Payment created successfully', $data);
    }

      /**
     * GET /api/v1/payments
     * Get payment history for the customer
     */
    public function getPaymentHistory(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $payments = $this->paymentModel->findByCustomer($customerId);
        Response::success('Payment history retrieved', $payments);
    }
    /**
     * POST /api/v1/orders/{orderId}/payment
     * Process payment for an order
     */
    public function processPayment(int $orderId): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // 1. Verify the order belongs to the customer and is pending
        $order = $this->orderModel->findOrderForCustomer($orderId, $customerId);
        if (!$order) {
            Response::error('Order not found', [ "data" => [$orderId, $customerId]], 404);
            return;
        }

        if ($order['status_key'] !== 'pending') {
            Response::error('Order is not in pending status', [], 422);
            return;
        }

        // 2. Check if payment already exists for this order
        $existingPayment = $this->paymentModel->findByOrderId($orderId);
        if ($existingPayment) {
            Response::error('Payment already exists for this order', [], 422);
            return;
        }

        // 3. Validate payment method
        $paymentMethodId = (int) ($body['payment_method_id'] ?? 0);
        $paymentMethod = $this->paymentMethodModel->findByCustomerAndId($customerId, $paymentMethodId);
        if (!$paymentMethod) {
            Response::error('Invalid payment method', [], 422);
            return;
        }

        // 4. Create payment record (mock processing for demo)
        $paymentData = [
            'order_id' => $orderId,
            'payment_method_id' => $paymentMethodId,
            'stripe_payment_id' => 'pi_mock_' . uniqid(), // Mock Stripe Payment Intent ID
            'amount' => $order['total_amount'],
            'currency' => 'usd',
            'status' => 'processing' // Start as processing
        ];

        $paymentId = $this->paymentModel->create($paymentData);
        if (!$paymentId) {
            Response::error('Failed to create payment', [], 500);
            return;
        }

        // 5. Simulate payment processing (for demo)
        // In real implementation, this would be handled by Stripe webhooks
        $this->simulatePaymentProcessing($paymentId, $orderId);

        Response::success('Payment initiated successfully', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'amount' => $order['total_amount'],
            'status' => 'processing'
        ], 201);
    }

    
    /**
     * GET /api/v1/payments/{id}
     * Get specific payment details
     */
    public function getPayment(int $paymentId): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $payment = $this->paymentModel->find($paymentId);
        if (!$payment) {
            Response::error('Payment not found', [], 404);
            return;
        }

        // Verify the payment belongs to the customer (through payment method)
        $paymentMethod = $this->paymentMethodModel->find($payment['payment_method_id']);
        if (!$paymentMethod || $paymentMethod['customer_id'] !== $customerId) {
            Response::error('Payment not found', [], 404);
            return;
        }

        Response::success('Payment details', $payment);
    }

    /**
     * Simulate payment processing for demo purposes
     */
    private function simulatePaymentProcessing(int $paymentId, int $orderId): void
    {
        // Simulate successful payment after a brief delay
        // In real implementation, this would be handled by Stripe webhooks
        
        // Update payment status to succeeded
        $this->paymentModel->updateStatus($paymentId, 'succeeded');
        
        // Update order status to confirmed (assuming status_id 2 is confirmed)
        $this->orderModel->updateStatus($orderId, 2);
    }
}
