<!DOCTYPE html>
<html>
<head>
    <title>Stripe Payment Method Test</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        button { padding: 10px 20px; background: #635bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #5a52e8; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        #result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .payment-method { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .card-container { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin: 20px 0; }
        .StripeElement { padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .StripeElement--focus { border-color: #635bff; }
        .StripeElement--invalid { border-color: #e25950; }
        .test-cards { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .test-card { background: white; padding: 10px; margin: 5px 0; border-radius: 4px; cursor: pointer; border: 1px solid #ddd; }
        .test-card:hover { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>🧪 Stripe Payment Method Test</h1>
    <p>This tool helps you create test payment methods for API testing using Stripe Elements.</p>
    
    <div class="test-cards">
        <h3>📋 Test Card Numbers (Click to Auto-Fill):</h3>
        <div class="test-card" onclick="fillCard('4242424242424242', 'Success Card')">
            <strong>✅ Success Card:</strong> 4242 4242 4242 4242
        </div>
        <div class="test-card" onclick="fillCard('4000000000000002', 'Declined Card')">
            <strong>❌ Declined Card:</strong> 4000 0000 0000 0002
        </div>
        <div class="test-card" onclick="fillCard('4000002500003155', '3D Secure Card')">
            <strong>🔐 3D Secure Card:</strong> 4000 0025 0000 3155
        </div>
    </div>
    
    <div class="card-container">
        <h3>💳 Enter Card Details:</h3>
        <div id="card-element">
            <!-- Stripe Elements will create form elements here -->
        </div>
        <div id="card-errors" role="alert"></div>
        <br>
        <button id="create-payment-method" disabled>Create Payment Method</button>
        <span id="card-type"></span>
    </div>
    
    <div id="result"></div>
    <div id="payment-methods"></div>

    <script>
        const stripe = Stripe('<?php echo $stripePublishableKey; ?>');
        const elements = stripe.elements();
        let paymentMethods = [];
        let currentCardType = '';

        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#9e2146',
                },
            },
        });

        cardElement.mount('#card-element');

        // Handle real-time validation errors from the card Element
        cardElement.on('change', ({error, complete, brand}) => {
            const displayError = document.getElementById('card-errors');
            const createButton = document.getElementById('create-payment-method');
            const cardTypeSpan = document.getElementById('card-type');
            
            if (error) {
                displayError.textContent = error.message;
                createButton.disabled = true;
            } else {
                displayError.textContent = '';
                createButton.disabled = !complete;
            }
            
            // Show card brand
            if (brand && brand !== 'unknown') {
                cardTypeSpan.textContent = `(${brand.toUpperCase()})`;
            } else {
                cardTypeSpan.textContent = '';
            }
        });

        // Handle form submission
        document.getElementById('create-payment-method').addEventListener('click', async (event) => {
            event.preventDefault();
            
            const resultDiv = document.getElementById('result');
            const createButton = document.getElementById('create-payment-method');
            
            createButton.disabled = true;
            createButton.textContent = 'Creating...';
            resultDiv.innerHTML = '<p>Creating payment method...</p>';

            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            createButton.disabled = false;
            createButton.textContent = 'Create Payment Method';

            if (error) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ Error:</strong> ${error.message}
                </div>`;
            } else {
                const cardType = currentCardType || `${paymentMethod.card.brand} Card`;
                paymentMethods.push({...paymentMethod, cardType});
                
                resultDiv.innerHTML = `<div class="success">
                    <strong>✅ ${cardType} Created Successfully!</strong><br>
                    <strong>Payment Method ID:</strong> <code>${paymentMethod.id}</code><br>
                    <strong>Last 4:</strong> ${paymentMethod.card.last4}<br>
                    <strong>Brand:</strong> ${paymentMethod.card.brand}<br>
                    <strong>Exp:</strong> ${paymentMethod.card.exp_month}/${paymentMethod.card.exp_year}<br>
                    <button onclick="copyToClipboard('${paymentMethod.id}')">📋 Copy ID</button>
                </div>`;
                
                updatePaymentMethodsList();
                console.log('Full Payment Method:', paymentMethod);
                
                // Clear the form
                cardElement.clear();
                currentCardType = '';
                document.getElementById('card-type').textContent = '';
            }
        });

        function fillCard(cardNumber, cardType) {
            // Note: Stripe Elements doesn't allow programmatic filling for security
            // This function just sets the card type for display purposes
            currentCardType = cardType;
            document.getElementById('card-type').textContent = `(${cardType})`;
            
            // Show instruction
            document.getElementById('result').innerHTML = `<div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px;">
                <strong>💡 Manual Entry Required:</strong><br>
                Please manually enter: <strong>${cardNumber}</strong><br>
                Expiry: <strong>12/25</strong> | CVC: <strong>123</strong><br>
                <small>Stripe Elements requires manual entry for security.</small>
            </div>`;
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
        <li>Enter card details in the form above (or use test card numbers)</li>
        <li>Click "Create Payment Method"</li>
        <li>Copy the Payment Method ID (starts with <code>pm_</code>)</li>
        <li>Use it in your Postman API calls</li>
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