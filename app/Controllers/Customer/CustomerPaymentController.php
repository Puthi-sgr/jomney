<?php
namespace App\Controllers\Customer;

use App\Core\Response;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\Customer;
use Stripe\Stripe;
use Stripe\Customer as StripeCustomer;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\SetupIntent;
use Exception;

class CustomerPaymentController
{
    private Payment $paymentModel;
    private PaymentMethod $paymentMethodModel;
    private Order $orderModel;
    private Customer $customerModel;

    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $this->paymentModel = new Payment();
        $this->paymentMethodModel = new PaymentMethod();
        $this->orderModel = new Order();
        $this->customerModel = new Customer();
    }


        /**
     * Create a SetupIntent for adding payment methods
     */
    public function createSetupIntent(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        try {
            // Get or create Stripe customer
            $stripeCustomerId = $this->getOrCreateStripeCustomer($customerId);
            
            $setupIntent = SetupIntent::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'usage' => 'off_session'
            ]);

            Response::success('Setup intent created', [
                'client_secret' => $setupIntent->client_secret,
                'setup_intent_id' => $setupIntent->id
            ]);

        } catch (Exception $e) {
            error_log("Setup intent creation failed: " . $e->getMessage());
            Response::error("Failed to create setup intent", [], 500);
        }
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
     * Save payment method after SetupIntent succeeds
     */
    public function savePaymentMethod(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $paymentMethodId = $body['payment_method_id'] ?? '';
        
        if (empty($paymentMethodId)) {
            Response::error('Payment method ID is required', [], 422);
            return;
        }

        try {
            // Retrieve the payment method from Stripe
            $stripePaymentMethod = StripePaymentMethod::retrieve($paymentMethodId);
            
            // Get or create Stripe customer
            $stripeCustomerId = $this->getOrCreateStripeCustomer($customerId);
            
            // Attach payment method to customer if not already attached
            if (!$stripePaymentMethod->customer) {
                $stripePaymentMethod->attach(['customer' => $stripeCustomerId]);
            }

            // Save to database
            $data = [
                'customer_id' => $customerId,
                'stripe_pm_id' => $stripePaymentMethod->id,
                'type' => $stripePaymentMethod->type,
                'card_brand' => $stripePaymentMethod->card->brand ?? null,
                'card_last4' => $stripePaymentMethod->card->last4 ?? null,
                'exp_month' => $stripePaymentMethod->card->exp_month ?? null,
                'exp_year' => $stripePaymentMethod->card->exp_year ?? null,
            ];

            $paymentMethodDbId = $this->paymentMethodModel->create($data);
            
            if (!$paymentMethodDbId) {
                Response::error('Failed to save payment method', [], 500);
                return;
            }

            Response::success('Payment method saved successfully', [
                'payment_method_id' => $paymentMethodDbId,
                'card_brand' => $data['card_brand'],
                'card_last4' => $data['card_last4']
            ], 201);

        } catch (Exception $e) {
            error_log("Save payment method failed: " . $e->getMessage());
            Response::error('Failed to save payment method: ' . $e->getMessage(), [], 500);
        }
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
     */
    public function removeStripePaymentMethod(int $id): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $paymentMethod = $this->paymentMethodModel->findByCustomerAndId($customerId, $id);
        if (!$paymentMethod) {
            Response::error('Payment method not found', [], 404);
            return;
        }

        try {
            // Detach from Stripe
            $stripePaymentMethod = StripePaymentMethod::retrieve($paymentMethod['stripe_pm_id']);
            $stripePaymentMethod->detach();

            // Remove from database
            $result = $this->paymentMethodModel->delete($id);
            if ($result) {
                Response::success('Payment method removed successfully');
            } else {
                Response::error('Failed to remove payment method', [], 500);
            }

        } catch (Exception $e) {
            error_log("Remove payment method failed: " . $e->getMessage());
            Response::error('Failed to remove payment method', [], 500);
        }
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
