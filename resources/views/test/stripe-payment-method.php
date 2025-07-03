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
        .jwt-slot { background: #fffbe6; border: 1px solid #ffe58f; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .jwt-slot label { font-weight: bold; }
        .jwt-slot input { width: 60%; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; margin-right: 8px; }
        .jwt-slot button { padding: 6px 14px; font-size: 14px; }
    </style>
</head>
<body>
    <h1>🧪 Stripe Payment Method Test</h1>
    <p>This tool helps you create and save test payment methods for API testing using Stripe Elements.</p>
    
    <!-- JWT Token Slot -->
    <div class="jwt-slot">
        <label for="jwt-token-input">🔑 JWT Token:</label>
        <input type="text" id="jwt-token-input" placeholder="Paste your JWT token here" autocomplete="off">
        <button onclick="saveJwtToken()">Save</button>
        <span id="jwt-status" style="margin-left:10px; color: #389e0d;"></span>
        <br>
        <small>
            This token will be used for API requests. It is stored in <code>localStorage</code> for this page.<br>
            You can update it anytime.
        </small>
    </div>

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
        <button id="create-payment-method" disabled>Create & Save Payment Method</button>
        <span id="card-type"></span>
    </div>
    
    <div id="result"></div>
    <div id="payment-methods"></div>

    <script>
        // You must set this variable in your PHP controller/view
        const stripe = Stripe('<?php echo $stripePublishableKey; ?>');
        const elements = stripe.elements();
        let paymentMethods = [];
        let currentCardType = '';

        // --- JWT Token Slot Logic ---
        // On page load, fill the input if token exists
        document.addEventListener('DOMContentLoaded', function() {
            const jwtInput = document.getElementById('jwt-token-input');
            const token = localStorage.getItem('jwt_token');
            if (token) {
                jwtInput.value = token;
                document.getElementById('jwt-status').textContent = 'Token loaded';
            }
        });

        // Save JWT token from input to localStorage
        function saveJwtToken() {
            const jwtInput = document.getElementById('jwt-token-input');
            const status = document.getElementById('jwt-status');
            const token = jwtInput.value.trim();
            if (token) {
                localStorage.setItem('jwt_token', token);
                status.textContent = 'Token saved!';
                status.style.color = '#389e0d';
            } else {
                localStorage.removeItem('jwt_token');
                status.textContent = 'Token cleared';
                status.style.color = '#faad14';
            }
            setTimeout(() => { status.textContent = ''; }, 2000);
        }

        // Helper: get JWT token from localStorage or prompt
        function getJwtToken() {
            let token = localStorage.getItem('jwt_token');
            if (!token) {
                // Instead of prompt, focus the input for user
                document.getElementById('jwt-token-input').focus();
                document.getElementById('jwt-status').textContent = 'Please enter your JWT token above.';
            }
            return token;
        }

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

        // --- NEW: Helper to create SetupIntent on backend and get client_secret ---
        async function createSetupIntent(jwtToken) {
            // You should have a backend endpoint that creates a SetupIntent and returns its client_secret
            // For demo, let's assume it's /api/v1/payment-methods/stripe/setup-intent
            const response = await fetch('/api/v1/payment-methods/stripe/setup-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + jwtToken
                }
            });
            if (!response.ok) {
                throw new Error('Failed to create SetupIntent');
            }

            const data = await response.json();
            if (!data.data || !data.data.client_secret) {
                throw new Error('No client_secret returned from backend');
            }
            return data.data.client_secret;
        }

        // Handle form submission: create and save payment method, and invoke SetupIntent
        document.getElementById('create-payment-method').addEventListener('click', async (event) => {
            event.preventDefault();
            
            const resultDiv = document.getElementById('result');
            const createButton = document.getElementById('create-payment-method');
            
            createButton.disabled = true;
            createButton.textContent = 'Creating...';
            resultDiv.innerHTML = '<p>Creating payment method...</p>';

            // 1. Create payment method with Stripe
            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (error) {
                createButton.disabled = false;
                createButton.textContent = 'Create & Save Payment Method';
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ Error:</strong> ${error.message}
                </div>`;
                return;
            }

            // 2. Get JWT token for Authorization header
            const jwtToken = getJwtToken();
            if (!jwtToken) {
                resultDiv.innerHTML = `<div class="error">❌ No JWT token provided. Please enter it in the slot above.</div>`;
                createButton.disabled = false;
                createButton.textContent = 'Create & Save Payment Method';
                return;
            }

            // 3. Create SetupIntent on backend and confirm it with Stripe.js
            resultDiv.innerHTML = '<p>Creating SetupIntent...</p>';
            createButton.textContent = 'Creating SetupIntent...';

            let setupIntentClientSecret = null;
            try {
                setupIntentClientSecret = await createSetupIntent(jwtToken);
            } catch (err) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ SetupIntent Error:</strong> ${err.message}
                </div>`;
                createButton.disabled = false;
                createButton.textContent = 'Create & Save Payment Method';
                return;
            }

            // 4. Confirm the card setup with the SetupIntent
            resultDiv.innerHTML = '<p>Confirming card setup with Stripe...</p>';
            createButton.textContent = 'Confirming...';

            let setupResult;

            try {
                setupResult = await stripe.confirmCardSetup(setupIntentClientSecret, {
                    payment_method: paymentMethod.id
                });
            } catch (err) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ Stripe confirmCardSetup Error:</strong> ${err.message}
                </div>`;
                createButton.disabled = false;
                createButton.textContent = 'Create & Save Payment Method';
                return;
            }

            if (setupResult.error) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ SetupIntent Confirmation Error:</strong> ${setupResult.error.message}
                </div>`;
                createButton.disabled = false;
                createButton.textContent = 'Create & Save Payment Method';
                return;
            }

            // 5. Save payment method to backend via API
            resultDiv.innerHTML = '<p>Saving payment method to server...</p>';
            createButton.textContent = 'Saving...';

            try {
                // Call your backend API to save the payment method
                const response = await fetch('/api/v1/payment-methods/stripe/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + jwtToken
                    },
                    body: JSON.stringify({
                        payment_method_id: paymentMethod.id,
                        setup_intent_id: setupResult.setupIntent.id // Pass this for reference/debug
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Success: show info from backend
                    const cardType = currentCardType || `${paymentMethod.card.brand} Card`;
                    paymentMethods.push({...paymentMethod, cardType});
                    
                    resultDiv.innerHTML = `<div class="success">
                        <strong>✅ ${cardType} Created, SetupIntent Confirmed & Saved Successfully!</strong><br>
                        <strong>Payment Method ID:</strong> <code>${paymentMethod.id}</code><br>
                        <strong>Last 4:</strong> ${paymentMethod.card.last4}<br>
                        <strong>Brand:</strong> ${paymentMethod.card.brand}<br>
                        <strong>Exp:</strong> ${paymentMethod.card.exp_month}/${paymentMethod.card.exp_year}<br>
                        <strong>SetupIntent ID:</strong> <code>${setupResult.setupIntent.id}</code><br>
                        <strong>Saved DB ID:</strong> <code>${data.data?.payment_method_id ?? 'N/A'}</code><br>
                        <button onclick="copyToClipboard('${paymentMethod.id}')">📋 Copy ID</button>
                    </div>`;
                    
                    updatePaymentMethodsList();
                    console.log('Full Payment Method:', paymentMethod);
                    
                    // Clear the form
                    cardElement.clear();
                    currentCardType = '';
                    document.getElementById('card-type').textContent = '';
                } else {
                    // Backend error
                    resultDiv.innerHTML = `<div class="error">
                        <strong>❌ Server Error:</strong> ${data.message || 'Unknown error'}
                    </div>`;
                }
            } catch (err) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>❌ Network/Server Error:</strong> ${err.message}
                </div>`;
            }

            createButton.disabled = false;
            createButton.textContent = 'Create & Save Payment Method';
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
    <h3>📝 What happens now?</h3>
    <ol>
        <li>Enter your JWT token in the slot above.</li>
        <li>Enter card details in the form above (or use test card numbers)</li>
        <li>Click "Create & Save Payment Method"</li>
        <li>The payment method will be created with Stripe, a SetupIntent will be created and confirmed, and then the payment method will be saved to your backend using the <code>savePaymentMethod</code> controller</li>
        <li>You'll see the result below, including the database ID and SetupIntent ID if successful</li>
    </ol>
    
    <h4>🔗 API Usage (for reference):</h4>
    <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px;">
POST /api/v1/payment-methods/stripe/save
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "payment_method_id": "pm_COPY_FROM_ABOVE",
  "setup_intent_id": "seti_COPY_FROM_ABOVE"
}</pre>
</body>
</html>