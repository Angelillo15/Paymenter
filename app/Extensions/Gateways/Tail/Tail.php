<?php

namespace App\Extensions\Gateways\Tail;

use App\Classes\Extensions\Gateway;
use App\Helpers\ExtensionHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class Tail extends Gateway
{
    /**
    * Get the extension metadata
    * 
    * @return array
    */
    public function getMetadata()
    {
        return [
            'display_name' => 'Tebex (Paypal, Credit Card, etc)',
            'version' => '1.0.0',
            'author' => 'Angelillo15',
            'website' => 'https://angelillo15.es',
        ];
    }

    /**
     * Get all the configuration for the extension
     * 
     * @return array
     */
    public function getConfig()
    {
        return [
            [
                'name' => 'api_key',
                'type' => 'text',
                'friendly_name' => 'API Key',
                'description' => 'The API key for your Tail Server',
                'required' => true,
            ],
            [
                'name' => 'jwt_secret',
                'type' => 'text',
                'friendly_name' => 'JWT Secret',
                'description' => 'The JWT secret token for your Tail Server webhooks',
                'required' => true,
            ],
            [
                'name' => 'host',
                'type' => 'text',
                'friendly_name' => 'Host',
                'description' => 'The host of your Tail Server',
                'required' => true,
            ]
        ];
    }
    
    /**
     * Get the URL to redirect to
     * 
     * @param int $total
     * @param array $products
     * @param int $invoiceId
     * @return string
     */
    public function pay($total, $products, $invoiceId): string
    {
        $url = ExtensionHelper::getConfig('Tail', 'host');
        $api_key = ExtensionHelper::getConfig('Tail', 'api_key');
        $productsArray = array();

        foreach ($products as $product) {
            $productsArray[] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $product->quantity,
            ];
        }

        $response = Http::withHeaders([
            'api-key' => $api_key,
        ])->post($url . '/tebex/checkout', [
            'order_id' => $invoiceId,
            'price' => $total,
            'return_url' => route('clients.invoice.show', $invoiceId),
            'items' => $productsArray,
        ]);

        return $response->json()['links']['checkout'];
    }

    /**
     * Handle the webhook
     */
    public function webhook(Request $request)
    {
        $jwt_secret = ExtensionHelper::getConfig('Tail', 'jwt_secret');
        $jwt_signed = $request->jwt;
        
        $decoded = JWT::decode($jwt_signed, new Key($jwt_secret, 'HS256'));

        ExtensionHelper::paymentDone($decoded->id);
        response()->json(['message' => 'Webhook received'], 200);
    }
}
