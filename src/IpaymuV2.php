<?php

namespace IpaymuV2\Laravel;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use CURLFile; // Tambahkan ini untuk CURLFile

class IpaymuV2
{
    public $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('IPAYMU_HOST'); // Menggunakan IPAYMU_HOST sesuai permintaan sebelumnya
    }

    public function header($body, $method = 'POST')
    {
        $va             = env('IPAYMU_VA'); // Menggunakan IPAYMU_VA sesuai permintaan sebelumnya
        $secret         = env('IPAYMU_SECRET'); // Menggunakan IPAYMU_SECRET sesuai permintaan sebelumnya

        // *Don't change this
        if ($body) {
            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        } else {
            $jsonBody = "{}";
        }
        $requestBody  = strtolower(hash('sha256', $jsonBody));
        $stringToSign = strtoupper($method) . ':' . $va . ':' . $requestBody . ':' . $secret;
        $signature    = hash_hmac('sha256', $stringToSign, $secret);
        $timestamp    = Date('YmdHis');
        //End Generate Signature

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'va' => $va,
            'body' => $body
        ];
    }


    public function send($endPoint, $body, $contentType = 'application/json', $logName = null, $method = 'POST') // Default content type ke application/json
    {
        $header = $this->header($body, $method);

        $curl = curl_init();

        $httpHeader = [
            'Content-Type: ' . $contentType,
            'signature: ' . $header['signature'],
            'va: ' . env('IPAYMU_VA'), // Menggunakan IPAYMU_VA
            'timestamp: ' . $header['timestamp']
        ];

        // Jika content type adalah multipart/form-data, hapus Content-Type dari header
        if ($contentType == 'multipart/form-data') {
            $httpHeader = array_filter($httpHeader, function($h) {
                return strpos($h, 'Content-Type') === false;
            });
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl . $endPoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $contentType == 'application/json' ? json_encode($header['body']) : $header['body'], // Sesuaikan pengiriman body
            CURLOPT_HTTPHEADER => $httpHeader,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response, true);

        // Menghapus bagian logging kustom yang bergantung pada App\Jobs\PushLog
        // Pengguna dapat mengimplementasikan logging kustom di aplikasi mereka sendiri
        if ($logName != null) {
            Log::info("iPaymuV2 API Call - {$logName}: " . json_encode(['request' => $body, 'response' => $res]));
        }

        return $res;
    }

    public function getBalance($va = null)
    {
        $body['account'] = $va != null ? $va : env('IPAYMU_VA'); // Menggunakan IPAYMU_VA

        return $this->send('api/v2/balance', $body, 'application/json', null, 'POST');
    }

    public function createPaymentPage(array $product, array $qty, array $price, $name, $email, $phone, $callback, $account = null)
    {
        //Request Body//
        $body['product']    = $product;
        $body['qty']        = $qty;
        $body['price']      = $price;

        $body['buyerName']  = $name;
        $body['buyerEmail']  = $email;
        $body['buyerPhone']  = $phone;
        $body['notifyUrl']  = $callback;

        if ($account) {
            $body['account'] = $account;
        }

        $body['feeDirection']  = 'BUYER';

        $res = $this->send('api/v2/payment', $body, 'application/json', 'ipaymu-payment-link', 'POST');

        return $res;
    }

    public function createDirectPayment(array $product, array $qty, array $price, $name, $email, $phone, $callback, $method, $channel, $account = null)
    {
        $body['product']    = $product;
        $body['qty']        = $qty;
        $body['price']      = $price;

        $body['name']  = $name;
        $body['email']  = $email;
        $body['phone']  = $phone;

        $body['expired'] = 24;
        $body['expiredType'] = 'hours';
        $body['referenceId'] = 1;

        // Perlu diperhatikan: route('close') dan route('client.edc.notify') tidak akan berfungsi di dalam paket
        // Ini harus disediakan oleh aplikasi yang menggunakan paket ini
        // Untuk saat ini, saya akan menggantinya dengan placeholder atau meminta pengguna untuk menentukannya
        // Saya akan menggantinya dengan string kosong dan menambahkan catatan di README.md
        $body['returnUrl']  = ''; // Placeholder
        $body['notifyUrl']  = $callback;

        $body['amount'] = $price[0] * $qty[0];
        $body['paymentMethod']  = $method;
        $body['paymentChannel']  = $channel;

        if ($account) {
            $body['account'] = $account;
        }

        $body['feeDirection']  = 'BUYER';

        return $this->send('api/v2/payment/direct', $body, 'application/json', 'ipaymu-payment-direct', 'POST');
    }
}
