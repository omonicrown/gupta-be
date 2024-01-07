<?php

namespace App\Http\Controllers\API\Payment;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Rave;

class FlutterwaveController extends BaseController
{
    /**
     * Initialize Rave payment process
     * Return void
     */
    public function initialize()
    {
        $amount = 11300;
        $first_name = "Lawson";
        $last_name = "Luke";

        $request = [
            'tx_ref' => time(),
            'amount' => $amount,
            'currency' => 'NGN',
            'payment_options' => 'card',
            'redirect_url' => 'your_success.php', //replace with yours
            'customer' => [
                'email' => '',
                'name' => $first_name . ' ' . $last_name
            ],
            'meta' => [
                'price' => $amount
            ],
            'customizations' => [
                'title' => 'Paying for a service', //Set your title
                'description' => 'Level'
            ]
        ];

        //* Call fluterwave endpoint
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.flutterwave.com/v3/payments', //don't change this
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer YOUR_SECRET KEY',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $res = json_decode($response);
        if ($res->status == 'success') {
            $link = $res->data->link;
            header('Location: ' . $link);
        } else {
            // echo 'We can not process your payment';
            echo $res->status;
        }
    }
}
