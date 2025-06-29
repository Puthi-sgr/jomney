<!DOCTYPE html>
<html>
<head>
    <title>Stripe Payment Method Test</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        button { padding: 10px 20px; background: #635bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #5a52e8; }
        #result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .payment-method { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🧪 Stripe Payment Method Test</h1>
    <p>This tool helps you create test payment methods for API testing.</p>
    
    <div>
        <h3>Test Cards:</h3>
        <button onclick="createPaymentMethod('4242424242424242', 'Success Card')">Create Success Card (4242)</button>
        <button onclick="createPaymentMethod('4000000000000002', 'Declined Card')">Create Declined Card (0002)</button>
        <button onclick="createPaymentMethod('4000002500003155', '3D Secure Card')">Create 3D Secure Card (3155)</button>
    </div>
    
    <div id="result"></div>
    <div id="payment-methods"></div>

    <script>
        const stripe = Stripe('<?php echo $stripePublishableKey; ?>');
        let paymentMethods = [];
        
        async function createPaymentMethod(cardNumber, cardType) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Creating payment method...</p>';
            
            try {
                const {paymentMethod, error} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: {
                        number: cardNumber,
                        exp_month: 12,
                        exp_year: 2025,
                        cvc: '123',
                    },
                });

                if (error) {
                    resultDiv.innerHTML = `<div class="error">
                        <strong>❌ Error:</strong> ${error.message}
                    </div>`;
                } else {
                    paymentMethods.push({...paymentMethod, cardType});
                    resultDiv.innerHTML = `<div class="success">
                        <strong>✅ ${cardType} Created Successfully!</strong><br>
                        <strong>Payment Method ID:</strong> <code>${paymentMethod.id}</code><br>
                        <strong>Last 4:</strong> ${paymentMethod.card.last4}<br>
                        <strong>Brand:</strong> ${paymentMethod.card.brand}<br>
                        <button onclick="copyToClipboard('${paymentMethod.id}')">📋 Copy ID</button>
                    </div>`;
                    
                    updatePaymentMethodsList();
                    console.log('Full Payment Method:', paymentMethod);
                }
            } catch (err) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ Error:</strong> ${err.message}
                </div>`;
            }
        }
        
        function updatePaymentMethodsList() {
            const listDiv = document.getElementById('payment-methods');
            if (paymentMethods.length > 0) {
                let html = '<h3>📋 Created Payment Methods:</h3>';
                paymentMethods.forEach((pm, index) => {
                    html += `<div class="payment-method">
                        <strong>${pm.cardType}</strong> - <code>${pm.id}</code> 
                        (${pm.card.brand} ****${pm.card.last4})
                        <button onclick="copyToClipboard('${pm.id}')">📋 Copy</button>
                    </div>`;
                });
                listDiv.innerHTML = html;
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Payment Method ID copied to clipboard!');
            });
        }
    </script>
    
    <hr>
    <h3>📝 Next Steps:</h3>
    <ol>
        <li>Click a button above to create a test payment method</li>
        <li>Copy the Payment Method ID (starts with <code>pm_</code>)</li>
        <li>Use it in your Postman API calls:</li>
    </ol>
    
    <h4>🔗 API Usage:</h4>
    <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px;">
POST /api/v1/payment-methods/stripe/save
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "payment_method_id": "pm_COPY_FROM_ABOVE"
}</pre>
</body>
</html>