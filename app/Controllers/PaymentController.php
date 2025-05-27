<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\JWTService;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Exception;


class PaymentController{
    private Payment $paymentModel;

    public function __construct(){
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $this->paymentModel = new Payment();
    }

    public function create(): void{
        $data = json_decode(file_get_contents('php://input'), true);
        //grabs the data
        $orderId = (int)($data['order_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0.0);
        $currency = $data['currency'] ?? 'usd';

        if($orderId <= 0 || $amount <= 0){
            Response::error("Invalid order or amount", [], 400);
            return;
        }
        
        //Create strip payment part
        try{
            $pi = PaymentIntent::create([
                'amount' => (int)($amount * 100),
                'currency' => $currency,
                'metadata' => ['order_id' => $orderId]
            ]);
            

            if($pi->status === 'succeeded' || $pi->status === 'requires_confirmation' || $pi->status === 'requires_action' ||  $pi->status === 'requires_payment_method'){
                //payment success
                $this->paymentModel->create($orderId, $pi->id, $amount, $currency, 'pending');
                Response::success(
                    "Payment success", 
                    ['payment_intent' => $pi, '
                    client_secret' => $pi->client_secret]);
                    
            }else if($pi->status === 'payment_failed' || $pi->status === 'canceled'){
                //payment failed
                $this->paymentModel->create($orderId, $pi->id, $amount, $currency, 'failed');
                Response::error("Payment failed", [], 400);
            }else{
                error_log("Unexpected status received: " . $pi->status); 
                Response::error("Unexpected payment status", [], 400);
            }
        }catch(Exception $e){
            Response::error("Payment creation failed", [], 500);
        }

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