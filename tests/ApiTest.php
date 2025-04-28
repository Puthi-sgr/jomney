<?php
// tests/ApiTest.php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private string $baseUrl;
    private string $token;

    protected function setUp(): void
    {
        // Load base URL from environment (set in phpunit.xml)
        $this->baseUrl = getenv('APP_URL');
    }

    private function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;

        // Configure method & payload
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            // Send JSON body
            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $headers[] = 'Content-Type: application/json';
        } else {
            // GET: append query string if data provided
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        // Common cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Attach any headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $code,
            'body' => json_decode($response, true),
        ];
    }

     /** 
     * Full test: login → unauthorized access → create order → list orders
     */
    public function testFullJwtProtectedWorkflow(): void
    {
        // 1) LOGIN to get a JWT
        $login = $this->request('POST', '/loginJWT', [
            'email'    => 'user@example.com',
            'password' => 'secret'
        ]);
        // Expect HTTP 200 and a token in response body
        $this->assertEquals(200, $login['code'], 'Login should return 200');
        $this->assertArrayHasKey('data', $login['body'], 'Response must have data field');
        $this->assertArrayHasKey('token', $login['body']['data'], 'Data must include token');

        // Save the token for subsequent calls
        $this->token = $login['body']['data']['token'];

        // 2) ACCESS /orders WITHOUT token → should be 401
        $unauth = $this->request('GET', '/orders');
        $this->assertEquals(401, $unauth['code'], 'Unauthenticated should get 401');

        // 3) ACCESS /orders WITH token → should be 200
        $auth    = $this->request('GET', '/orders', [], [
            'Authorization: Bearer ' . $this->token
        ]);
        $this->assertEquals(200, $auth['code'], 'Authenticated GET /orders returns 200');
        $this->assertIsArray($auth['body']['data'], 'Data field should be an array');

        // 4) CREATE a new order → POST /orders
        $orderData = [
            'items' => [['id' => 1, 'qty' => 2]],
            'total' => 19.98
        ];
        $create = $this->request('POST', '/orders', $orderData, [
            'Authorization: Bearer ' . $this->token
        ]);
        $this->assertEquals(200, $create['code'], 'Order creation returns 200');
        $this->assertTrue($create['body']['success'], 'Response success must be true');

        // 5) VERIFY one or more orders exist now
        $after = $this->request('GET', '/orders', [], [
            'Authorization: Bearer ' . $this->token
        ]);
        $this->assertGreaterThanOrEqual(
            1,
            count($after['body']['data']),
            'After creation, at least 1 order should exist'
        );
    }
}