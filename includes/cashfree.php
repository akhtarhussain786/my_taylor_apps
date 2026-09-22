<?php
/**
 * MY TAYLOR - Cashfree Payment Gateway Integration Engine
 * Handles Payment Session Creation, Webhook/Verification & Sandbox Fallbacks
 */

require_once __DIR__ . '/../config/settings.php';

class CashfreeGateway {
    private $appId;
    private $secretKey;
    private $mode;
    private $apiVersion = '2023-08-01';

    public function __construct() {
        $this->appId = getSetting('cashfree_app_id', '');
        $this->secretKey = getSetting('cashfree_secret_key', '');
        $this->mode = getSetting('cashfree_mode', 'TEST');
    }

    public function getBaseUrl() {
        return ($this->mode === 'PROD') 
            ? 'https://api.cashfree.com/pg' 
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Create Cashfree Order
     */
    public function createOrder($orderId, $amount, $customerName, $customerPhone, $customerEmail, $returnUrl) {
        $url = $this->getBaseUrl() . '/orders';

        $payload = [
            'order_id'       => $orderId,
            'order_amount'   => (float)$amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => 'CUST_' . preg_replace('/\D/', '', $customerPhone),
                'customer_name'  => $customerName,
                'customer_email' => $customerEmail ?: 'customer@mytaylor.local',
                'customer_phone' => $customerPhone
            ],
            'order_meta' => [
                'return_url' => $returnUrl . '?order_id={order_id}'
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'x-api-version: ' . $this->apiVersion,
            'x-client-id: ' . $this->appId,
            'x-client-secret: ' . $this->secretKey
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || ($httpCode !== 200 && $httpCode !== 201)) {
            $respData = json_decode($response, true);
            return [
                'success' => false,
                'mode'    => $this->mode,
                'message' => $respData['message'] ?? ($err ?: 'Cashfree gateway response code: ' . $httpCode),
                'raw'     => $respData
            ];
        }

        $res = json_decode($response, true);
        return [
            'success'          => true,
            'payment_session_id' => $res['payment_session_id'] ?? null,
            'order_id'         => $res['order_id'] ?? $orderId,
            'mode'             => $this->mode
        ];
    }

    /**
     * Get Cashfree Order Status / Verification
     */
    public function getOrderStatus($orderId) {
        $url = $this->getBaseUrl() . '/orders/' . urlencode($orderId);

        $headers = [
            'Content-Type: application/json',
            'x-api-version: ' . $this->apiVersion,
            'x-client-id: ' . $this->appId,
            'x-client-secret: ' . $this->secretKey
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || ($httpCode !== 200)) {
            $respData = json_decode($response, true);
            return [
                'success' => false,
                'message' => $respData['message'] ?? ($err ?: 'Status check failed: HTTP ' . $httpCode),
                'order_status' => 'PENDING'
            ];
        }

        $res = json_decode($response, true);
        return [
            'success'      => true,
            'order_status' => $res['order_status'] ?? 'UNKNOWN',
            'order_amount' => $res['order_amount'] ?? 0,
            'data'         => $res
        ];
    }
}
