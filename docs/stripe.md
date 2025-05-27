***To test stripe api***
stripe listen --forward-to localhost:8080/payments/webhook

***Create payment test***

curl -X POST http://localhost:8080/payments/create \
  -H "Authorization: Bearer <YOUR_JWT>" \
  -H "Content-Type: application/json" \
  -d '{
        "order_id": 1,
        "amount": 19.98,
        "currency": "usd"
      }'