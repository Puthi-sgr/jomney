<?php

namespace App\Controllers;

class TestController
{
    public function stripePaymentMethodTest(): void
    {
        $stripePublishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? 'pk_test_your_key_here';
        
        // Include the HTML template
        include __DIR__ . '/../../resources/views/test/stripe-payment-method.php';
    }
}