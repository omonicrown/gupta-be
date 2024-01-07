<?php

namespace App\Services;

use App\Mail\Program;
use App\Models\User;
use App\Models\userAccountDetail;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\NursingPayments;
use App\Models\NursingProgram;
use App\Models\userCoursePayment;
use App\Models\Waiter;
use Carbon\CarbonImmutable;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mail;
use PhpParser\Node\Stmt\TryCatch;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Message;

class PaymentService extends BaseController
{
    public function makePaymentWithFlutterwave($data)
    {

        // return $data;
        $request = [
            'tx_ref' => time(),
            'amount' => $data['amount'],
            'currency' => 'USD',
            'payment_options' => 'card',
            'redirect_url' => 'https://afriproedu.com/wallet', //replace with yours
            'customer' => [
                'email' => Auth()->user()->email,
                'name' => Auth()->user()->name . ' ' . Auth()->user()->last_name
            ],
            'meta' => [
                'price' => $data['amount']
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
                'Authorization: Bearer FLWPUBK_TEST-2841b8bd89c458c57f1cf773ef6eda0b-X',
                'Content-Type: application/json'
            ),
        )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response);
        if ($res->status == 'success') {
            return ($res);
        } else {
            // FLWPUBK_TEST-a3f1debc86ff81e490884965b8985bf9-X
            // FLWSECK_TEST-73b190630a6477e0f7440874acdc862f-X
            // FLWSECK_TEST9d6319106094
            // echo 'We can not process your payment';
            return ($res);
        }
    }


    public function makePaymentForSubscription($data)
    {

        try {
            $request = [
                'tx_ref' => time(),
                'amount' => $data['amount'],
                'currency' => 'USD',
                'payment_options' => 'card',
                'redirect_url' => 'https://www.mygupta.co/subscription', //replace with yours
                'customer' => [
                    'email' => Auth()->user()->email,
                    'name' => Auth()->user()->name,
                ],
                'meta' => [
                    'price' => $data['amount']
                ],
                'customizations' => [
                    'title' => 'Paying for subscription', //Set your title
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
                    'Authorization: Bearer FLWSECK_TEST-275ada82d4edb5f892f52e41dbc78a40-X',
                    'Content-Type: application/json'
                ),
            )
            );

            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response);
            if ($res->status == 'success') {
                return ($res);
            } else {
                // FLWPUBK_TEST-a3f1debc86ff81e490884965b8985bf9-X
                // FLWSECK_TEST-73b190630a6477e0f7440874acdc862f-X
                // FLWSECK_TEST9d6319106094
                // echo 'We can not process your payment';
                return ($res);
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }



    public function makeOutsidePaymentWithFlutterwave($data)
    {
        $request = [
            'tx_ref' => time(),
            'amount' => $data['amount'],
            'currency' => 'USD',
            'payment_options' => 'card',
            'redirect_url' => 'https://afriproedu.com/practical-nursing-application-form', //replace with yours
            'customer' => [
                'email' => $data['email'],
                'name' => $data['first_name'] . ' ' . $data['last_name']
            ],
            'meta' => [
                'price' => $data['amount']
            ],
            'customizations' => [
                'title' => 'Paying for a service', //Set your title
                'description' => 'Level'
            ]
        ];


        // dd( $nurr);

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
                'Authorization: Bearer FLWSECK-e493f89a819aae8e3d16266e170948cd-18a93169e5fvt-X',
                'Content-Type: application/json'
            ),
        )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response);
        if ($res->status == 'success') {
            try {
                NursingPayments::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'email' => $data['email'],
                        'amount' => $data['amount'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'status' => $data['status'],
                        'age' => $data['age'],
                        'nursing_program' => $data['nursing_program'],
                        'passport' => $data['passport'],
                        'location' => $data['location'],
                        'comment' => $data['comment'],
                        'tution_fee' => $data['tution_fee'],
                        'academic_background' => $data['academic_background'],
                        'phone_number' => $data['phone_number'],
                        'unique_id' => mt_rand(1000000000, 7000000000),
                    ]
                );
            } catch (\Throwable $th) {
                return ($th->getMessage());
            }

            return ($res);
        } else {
            // FLWPUBK_TEST-a3f1debc86ff81e490884965b8985bf9-X
            // FLWSECK_TEST-73b190630a6477e0f7440874acdc862f-X
            // FLWSECK_TEST9d6319106094
            // echo 'We can not process your payment';
            return ($res);
        }
    }

    public function makeOutsideWaiterPayment($data)
    {
        $request = [
            'tx_ref' => time(),
            'amount' => $data['amount'],
            'currency' => 'USD',
            'payment_options' => 'card',
            'redirect_url' => 'https://afriproedu.com' . $data['url'], //replace with yours
            'customer' => [
                'email' => $data['email'],
                'name' => $data['full_name']
            ],
            'meta' => [
                'price' => $data['amount']
            ],
            'customizations' => [
                'title' => 'Paying for a service', //Set your title
                'description' => 'Level'
            ]
        ];


        // dd( $nurr);

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
                'Authorization: Bearer FLWSECK-e493f89a819aae8e3d16266e170948cd-18a93169e5fvt-X',
                'Content-Type: application/json'
            ),
        )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response);
        if ($res->status == 'success') {
            try {
                Waiter::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'email' => $data['email'],
                        'full_name' => $data['full_name'],
                        'program' => $data['program'],
                        'country' => $data['country'],
                        'academic_background' => $data['academic_background'],
                        'profession' => $data['profession'],
                        'pay_tuition_fee' => $data['pay_tution_fee'],
                        'phone_number' => $data['phone_number'],
                        'amount' => $data['amount'],
                        'where_do_you_hear_about_us' => $data['where_do_you_hear_about_us'],
                        'who_will_pay_for_tuition' => $data['who_will_pay_for_tuition']
                    ]
                );

                $reveiverEmailAddress = 'samuelfemi85@gmail.com';
                $details = [
                    'custname' => $data['full_name'],
                    'email' => $data['email'],
                    'program' => $data['program'],
                    'phone_number' => $data['phone_number'],
                ];

                Mail::to($reveiverEmailAddress)->send(new Program($details));

                return ($res);
            } catch (\Throwable $th) {
                return ($th->getMessage());
            }


        } else {
            // FLWPUBK_TEST-a3f1debc86ff81e490884965b8985bf9-X
            // FLWSECK_TEST-73b190630a6477e0f7440874acdc862f-X
            // FLWSECK_TEST9d6319106094
            // echo 'We can not process your payment';
            return ($res);
        }
    }

    public function outsidePaymentCallback($data)
    {
        try {

            $query = array(
                "SECKEY" => "FLWSECK-e493f89a819aae8e3d16266e170948cd-18a93169e5fvt-X",
                "txref" => $data['reference']
            );

            $data_string = json_encode($query);

            $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $response = curl_exec($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            curl_close($ch);
            $resp = json_decode($response, true);
            $paymentStatus = $resp['data']['status'];
            $chargeResponsecode = $resp['data']['chargecode'];
            $chargeAmount = $resp['data']['amount'];
            $chargeCurrency = $resp['data']['currency'];

            if (($chargeResponsecode == "00" || $chargeResponsecode == "0")) {
                // $userAccount = userAccountDetail::where('user_id', Auth::user()->user_id)->first();
                // if ($userAccount->payment_reference !== $data['reference']) {
                //     $userAccount->account_balance = (($userAccount->account_balance) + (($resp['data']['amount'])));
                //     $userAccount->payment_reference = $data['reference'];
                //     $userAccount->save();
                // }





                $data = $resp['data'];
                return $response;
            } else {
                return $response;
            }
        } catch (\Throwable $th) {
            return ($th->getMessage());
        }


    }



    public function verify_flutterwave_payment($data)
    {
        $query = array(
            "SECKEY" => "FLWSECK_TEST-275ada82d4edb5f892f52e41dbc78a40-X",
            "txref" => $data['reference']
        );

        $data_string = json_encode($query);

        $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        $resp = json_decode($response, true);
        $paymentStatus = $resp['data']['status'];
        $chargeResponsecode = $resp['data']['chargecode'];
        $chargeAmount = $resp['data']['amount'];
        $chargeCurrency = $resp['data']['currency'];

        if (($chargeResponsecode == "00" || $chargeResponsecode == "0")) {
            $current = CarbonImmutable::now();

            //Basic Month sub
            if ($chargeAmount == '2') {
                $userAccount = User::where('id', Auth::user()->id)->first();
                $userAccount->no_of_wlink = '20';
                $userAccount->no_of_rlink = '20';
                $userAccount->no_of_mlink = '5';
                $userAccount->no_of_mstore = '2';
                $userAccount->sub_start = Carbon::today()->toDateString();
                $userAccount->sub_end = $current->addMonth()->toDateString();
                $userAccount->sub_status = 'active';
                $userAccount->save();
            }

            //basic year sub
            if ($chargeAmount == '20') {
                $userAccount = User::where('id', Auth::user()->id)->first();
                $userAccount->sub_start = Carbon::today()->toDateString();
                $userAccount->sub_end = $current->addMonths(11)->toDateString();
                $userAccount->sub_status = 'active';
                $userAccount->save();
            }

             //Basic Month sub
             if ($chargeAmount == '2') {
                $userAccount = User::where('id', Auth::user()->id)->first();
                $userAccount->sub_start = Carbon::today()->toDateString();
                $userAccount->sub_end = $current->addDays(30)->toDateString();
                $userAccount->sub_status = 'active';
                $userAccount->save();
            }

            //Popular year sub
            if ($chargeAmount == '20') {
                $userAccount = User::where('id', Auth::user()->id)->first();
                $userAccount->sub_start = Carbon::today()->toDateString();
                $userAccount->sub_end = $current->addMonths(11)->toDateString();
                $userAccount->sub_status = 'active';
                $userAccount->save();
            }

            $data = $resp['data'];
            return $data;
        } else {
            return "Failed";
        }
    }





    public function makePaymentWithPaystack($data)
    {
        $formData = [
            'email' => Auth()->user()->email,
            'amount' => $data['amount'] * 100,
            'callback_url' => 'https://afriproedu.com/wallet'
            // 'callback_url' => 'http://localhost:3000/wallet'
        ];
        $pay = json_decode($this->initiate_payment($formData));
        if ($pay) {
            if ($pay->status) {
                return ($pay->data->authorization_url);
            } else {
                return ($pay);
            }
        } else {
            return back()->withError("Something went wrong");
        }
    }


    //initiate paystack payment
    public function initiate_payment($formData)
    {
        $url = "https://api.paystack.co/transaction/initialize";

        $fields_string = http_build_query($formData);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . env('PAYSTACK_SECRET_KEY'),
            "Cache-Control: no-cache",
        )
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function walletDetails()
    {
        return userAccountDetail::where('user_id', Auth::user()->user_id)->first();
    }





    public function payment_callback($data)
    {
        $response = json_decode($this->verify_payment($data['reference']));
        if ($response) {
            if ($response->status) {
                $userAccount = userAccountDetail::where('user_id', Auth::user()->user_id)->first();
                if ($userAccount->payment_reference !== $response->data->reference) {
                    $userAccount->account_balance = (($userAccount->account_balance) + (($response->data->amount) / 100));
                    $userAccount->payment_reference = $response->data->reference;
                    $userAccount->save();
                }

                $data = $response->data;
                return $response;
            } else {
                return back()->withError($response->message);
            }
        } else {
            return back()->withError("Something went wrong");
        }
    }


    public function pay_for_course($data)
    {

        $userAccount = userAccountDetail::where('user_id', Auth::user()->user_id)->first();
        if ($userAccount->account_balance >= $data['course_price']) {
            DB::beginTransaction();
            try {
                $userAccount->account_balance = ($userAccount->account_balance - $data['course_price']);
                $userAccount->amount_spent = ($userAccount->amount_spent + $data['course_price']);
                $userAccount->due_date = ($data['course_price'] <= '1200' ? Carbon::today()->addMonth() : Carbon::today());
                $userAccount->status = ($userAccount->due_date < Carbon::today() ? "Inactive" : 'Active');
                $userAccount->next_paymemt_amount = ($data['course_price'] <= '1200' ? "1200" : '0.00');
                $userAccount->start_date = $userAccount->start_date == '' ? Carbon::today() : $userAccount->start_date;
                $userAccount->save();

                $paid = userCoursePayment::updateOrCreate(
                    ['user_id' => Auth::user()->user_id],
                    [
                        'user_id' => Auth::user()->user_id,
                        'paid_amount' => $data['course_price'],
                        'user_name' => Auth::user()->user_name . ' ' . Auth::user()->last_name,
                        'due_date' => ($data['course_price'] <= '1200' ? Carbon::today()->addMonth() : '0000-00-00 00:00:00'),
                        'language_type' => $data['language_type']
                    ]
                );
                DB::commit();
                return $this->sendResponse($paid, 'Payment successful.');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->sendError($e->getMessage());
            }
        } else {
            return $this->sendError('Insufficient Balanance');
        }

        $response = json_decode($this->verify_payment($data['reference']));
        if ($response) {
            if ($response->status) {
                $userAccount = userAccountDetail::where('user_id', Auth::user()->user_id)->first();
                if ($userAccount->payment_reference !== $response->data->reference) {
                    $userAccount->account_balance = (($userAccount->account_balance) + (($response->data->amount) / 100));
                    $userAccount->payment_reference = $response->data->reference;
                    $userAccount->save();
                }


                $data = $response->data;
                return $response;
            } else {
                return back()->withError($response->message);
            }
        } else {
            return back()->withError("Something went wrong");
        }
    }

    public function verify_payment_with_paystack($reference)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . env('PAYSTACK_SECRET_KEY'),
                "Cache-Control: no-cache",
            ),
        )
        );

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
