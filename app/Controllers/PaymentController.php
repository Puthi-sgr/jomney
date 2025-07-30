<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\JWTService;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Core\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Exception;


class PaymentController{

    private Payment $paymentModel;
    private PaymentMethod $paymentMethodModel;
    private Order $orderModel;
    private Request $request;

    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $this->request = new Request();
        $this->paymentModel = new Payment();
        $this->paymentMethodModel = new PaymentMethod();
        $this->orderModel = new Order();
    }

     /**
     * Create a PaymentIntent for an order
     * {
            "order_id": 123,
            "payment_method_id": 456
        }
     */
    public function createPaymentIntent(): void
    {
        $data = $this->request->all();
        
        $orderId = (int)($data['order_id'] ?? 0);
        $paymentMethodId = (int)($data['payment_method_id'] ?? 0);

        if ($orderId <= 0) {
            Response::error("Invalid order ID", [], 400);
            return;
        }

        try {
            // Get order details
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                Response::error("Order not found", [], 404);
                return;
            }

            $amount = (float)$order['total_amount'];
            if ($amount <= 0) {
                Response::error("Invalid order amount", [], 400);
                return;
            }

            // Get payment method if provided
            $stripePaymentMethodId = null;
            //if more than 0 it means method id is provided
            if ($paymentMethodId > 0) {
                //Check the method in the database
                $paymentMethod = $this->paymentMethodModel->find($paymentMethodId);

                if ($paymentMethod) {
                    $stripePaymentMethodId = $paymentMethod['stripe_pm_id'];
                    //the stripe payment method id will be the same the method id we stored in the database
                }
            }

            // Create PaymentIntent, u intent to pay it**
            $paymentIntentData = [
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $orderId,
                    'payment_method_id' => $paymentMethodId
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            // If we have a payment method, attach it
            if ($stripePaymentMethodId) {
                $paymentIntentData['payment_method'] = $stripePaymentMethodId;
                $paymentIntentData['confirmation_method'] = 'manual';
                $paymentIntentData['confirm'] = true;
            }

            $paymentIntent = PaymentIntent::create($paymentIntentData);

            // Create payment record in database
            $paymentData = [
                'order_id' => $orderId,
                'payment_method_id' => $paymentMethodId ?: null,
                'stripe_payment_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => 'usd',
                'status' => $this->mapStripeStatus($paymentIntent->status)
            ];

            $paymentId = $this->paymentModel->create($paymentData);

            if (!$paymentId) {
                Response::error("Failed to create payment record", [], 500);
                return;
            }

            Response::success("Payment intent created", [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
                'payment_id' => $paymentId
            ]);

        } catch (Exception $e) {
            error_log("Payment creation failed: " . $e->getMessage());
            Response::error("Payment creation failed: " . $e->getMessage(), [], 500);
        }
    }

    /**
     * Map Stripe status to our internal status
     */
    public function mapStripeStatus(string $stripeStatus): string
    {
        return match($stripeStatus) {
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            'processing' => 'processing',
            'succeeded' => 'succeeded',
            'canceled' => 'canceled',
            'payment_failed' => 'failed',
            default => 'pending'
        };
    }

    public function webhook():void {
        //1. Extract the payload
        //2. Get the http header from stripe
        //3. Get secret stripe payload
        $payload = file_get_contents("php://input");
        $sigHeader = $_SERVER["HTTP_STRIPE_SIGNATURE"] ?? '';
        $endPointSecret = $_ENV['STRIPE_SECRET_KEY'];

        try{
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endPointSecret
            );

            $type = $event->type;

            if($type === "payment_intent.succeeded"){
                $pi = $event->data->object;
                //update payment status
                $this->paymentModel->updateStatus($pi->id, 'succeeded');
            }
        }catch(\UnexpectedValueException $e){
            Response::error(
                'Webhook error: ', [$e->getMessage()] , 400
            );
        }
    }
}