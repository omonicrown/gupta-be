<?php

namespace App\Services;

use App\Mail\CustomerReciept;
use App\Mail\InitiatedTransaction;
use App\Mail\Program;
use App\Mail\VendorReciept;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\userAccountDetail;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\userCoursePayment;
use App\Models\VendorWallet;
use App\Models\Witdrawal;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mail;
use Carbon\Carbon;

class PaymentService extends BaseController
{
    public function makePaymentForSubscription($data)
    {

        try {
            $request = [
                'tx_ref' => time().'',
                'amount' => $data['amount'],
                'currency' => 'NGN',
                'payment_options' => 'card',
                'redirect_url' => 'https://www.mygupta.co/subscription', //replace with yours  http://localhost:3000/   https://www.mygupta.co
                'customer' => [
                    'email' => Auth()->user()->email,
                    'name' => Auth()->user()->name,
                    'phone_number' => Auth()->user()->phone_number
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
            curl_setopt_array(
                $curl,
                array(
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
                        'Authorization: Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
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



    public function makeOutsideProductPaymentWithFlutterwave($data)
    {
        try {

            DB::beginTransaction();
            $userData = User::where('id', $data['user_id'])->first();

            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'amount_paid' => '0',
                'user_email' => $userData->email,
                'user_phone_number' => $userData->phone_number,
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_full_name'],
                'customer_phone_number' => $data['customer_phone_number'],
                'paying_for' => $data['pay_for'],
                'product_qty' => $data['product_qty'],
                'transaction_status' => 'pending',
                'tnx_ref' => CarbonImmutable::now(),
                'currency' => 'ngn',
            ]);



            $request = [
                'tx_ref' => time().'',
                'amount' => $data['amount'],
                'currency' => 'NGN',
                'payment_options' => 'card',
                'redirect_url' => 'https://www.mygupta.co/store/' . $data['store_id'], //replace with yours
                'customer' => [
                    'email' => $data['customer_email'],
                    'name' => $data['customer_full_name'],
                    'phone_number' => $transaction->id
                ],
                'meta' => [
                    'price' => $data['amount']
                ],
                'customizations' => [
                    'title' => $data['pay_for'], //Set your title
                    'description' => 'Level'
                ]
            ];


            // dd( $nurr);

            //* Call fluterwave endpoint
            $curl = curl_init();
            curl_setopt_array(
                $curl,
                array(
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
                        'Authorization:Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
                        'Content-Type: application/json'
                    ),
                )
            );

            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response);
            DB::commit();

            // return  $res;

            return $this->success(
                ('Data Fetched Successfully'),
                $res
            );

        } catch (Exception $th) {
            DB::rollback();
            // dd($th->getMessage());
            return $this->exception($th);

        }



    }




    public function requestWitdrawal($data)
    {
        try {

            DB::beginTransaction();
            $userData = User::where('id', Auth::user()->id)->first();
            $walletDatails = VendorWallet::where('user_id', Auth::user()->id)->first();

            if ($walletDatails->total_amount >= $data['amount']) {

                VendorWallet::updateOrCreate(
                    ['user_id' => Auth::user()->id],
                    [
                        'previous_amount' => ($walletDatails->total_amount),
                        'total_amount' => ($walletDatails->total_amount - $data['amount'])

                    ]
                );



                // $walletDatails->total_amount = ($walletDatails->total_amount - $data['amount']);
                // $walletDatails->save();


                Witdrawal::create([
                    'account_bank' => $data['account_bank'],
                    'account_number' => $data['account_number'],
                    'amount' => $data['amount'],
                    'narration' => 'vendor witdrawal',
                    'reference' => time(),
                    'first_name' => $userData->name,
                    'last_name' => $userData->name,
                    'email' => $userData->email,
                    'beneficiary_country' => "NG",
                    'status' => 'PENDING',
                    'mobile_number' => $userData->phone_number,
                    'merchant_name' => "PAY WITH GUPTA"
                ]);


                $reveiverEmailAddress = $userData->email;
                $details = [
                    'custname' => $userData->name,
                    'amount' => $data['amount']
                ];

                Mail::to($reveiverEmailAddress)->send(new InitiatedTransaction($details));

                DB::commit();
                return $this->success(('Witdrawal Initated Successfully'), []);



            } else {
                DB::commit();
                return $this->sendError(('Insufficient Funds'), []);
            }



            // return  $res;



        } catch (Exception $th) {
            DB::rollback();
            // dd($th->getMessage());
            return $this->exception($th);

        }



    }



    public function payOutCustomers($data)
    {
        try {

            DB::beginTransaction();
            $request = [
                // 'tx_ref' => time(),
                'account_bank' => $data['account_bank'],
                'account_number' => $data['account_number'],
                'amount' => $data['amount'],
                'narration' => $data['narration'],
                'reference' => $data['reference'],
                'currency' => 'NGN',
                'meta' => [
                    'sender' => 'GUPTA LINKS',
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'beneficiary_country' => "NG",
                    'mobile_number' => $data['mobile_number'],
                    'merchant_name' => "PAY WITH GUPTA"
                ]
            ];


            // dd( $nurr);

            //* Call fluterwave endpoint
            $curl = curl_init();
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_URL => 'https://api.flutterwave.com/v3/transfers', //don't change this
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($request),
                    CURLOPT_HTTPHEADER => array(
                        'Authorization:Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
                        'Content-Type: application/json'
                    ),
                )
            );

            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response);
            DB::commit();

            // return  $res;

            return $this->success(('Transaction Initiated'), $res);

        } catch (Exception $th) {
            DB::rollback();
            // dd($th->getMessage());
            return $this->exception($th);

        }



    }


    public function verify_flutterwave_payment_for_subscription($data)
    {
        $query = array(
            "SECKEY" => env('FLUTTERWAVE_SECRET_KEY'),
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
        $tnx_ref = $resp['data']['txref'];
        $chargeAmount = $resp['data']['amount'];
        $chargeCurrency = $resp['data']['currency'];

        if (($chargeResponsecode == "00" || $chargeResponsecode == "0")) {
            $current = CarbonImmutable::now();

            //Basic Month sub
            if ($chargeAmount == '1500' || $chargeAmount == '4275' || $chargeAmount == '8550' || $chargeAmount == '17100') {
                try {
                    $userAccount = User::where('id', Auth::user()->id)->first();
                    $userAccount->no_of_wlink = '25';
                    $userAccount->no_of_rlink = '25';
                    $userAccount->no_of_mlink = '25';
                    $userAccount->no_of_mstore = '250';
                    $userAccount->no_of_malink = '5';
                    $userAccount->sub_type = 'basic';
                    $userAccount->sub_start = Carbon::today()->toDateString();
                    $userAccount->sub_end = ($chargeAmount == '1500') ? $current->addDays(30)->toDateString() : ($chargeAmount == '4275' ? $current->addMonths(2)->toDateString() : ($chargeAmount == '8550' ? $current->addMonths(5)->toDateString() : ($chargeAmount == '17100' ? $current->addMonths(11)->toDateString() : '')));
                    $userAccount->sub_status = 'active';
                    $userAccount->save();

                    Subscription::updateOrCreate(
                        ['tnx_ref' => $tnx_ref],
                        [
                            'user_id' => Auth::user()->id,
                            'sub_type' => 'basic',
                            'tnx_ref' => $tnx_ref,
                            'amount_paid' => $chargeAmount,
                            'user_email' => Auth::user()->email,
                            'subscription_status' => 'paid',
                            'currency' => 'ngn',
                        ]
                    );
                } catch (\Throwable $th) {
                    return $th->getMessage();
                }


            }

            //popular month sub
            if ($chargeAmount == '2500' || $chargeAmount == '7125' || $chargeAmount == '14250' || $chargeAmount == '28500') {

                try {
                    $userAccount = User::where('id', Auth::user()->id)->first();
                    $userAccount->no_of_wlink = '100';
                    $userAccount->no_of_rlink = '100';
                    $userAccount->no_of_mlink = '100';
                    $userAccount->no_of_malink = '100';
                    $userAccount->no_of_mstore = '500';
                    $userAccount->sub_type = 'popular';
                    $userAccount->sub_start = Carbon::today()->toDateString();
                    $userAccount->sub_end = ($chargeAmount == '2500') ? $current->addDays(30)->toDateString() : ($chargeAmount == '7125' ? $current->addMonths(2)->toDateString() : ($chargeAmount == '14250' ? $current->addMonths(5)->toDateString() : ($chargeAmount == '28500' ? $current->addMonths(11)->toDateString() : '')));
                    $userAccount->sub_status = 'active';
                    $userAccount->save();
                    Subscription::updateOrCreate(
                        ['tnx_ref' => $tnx_ref],
                        [
                            'user_id' => Auth::user()->id,
                            'sub_type' => 'popular',
                            'tnx_ref' => $tnx_ref,
                            'amount_paid' => $chargeAmount,
                            'user_email' => Auth::user()->email,
                            'subscription_status' => 'paid',
                            'currency' => 'ngn',
                        ]
                    );

                } catch (\Throwable $th) {
                    return $th->getMessage();
                }

            }

            //premium Month sub
            if ($chargeAmount == '7000' || $chargeAmount == '19950' || $chargeAmount == '39900' || $chargeAmount == '79800') {
                $userAccount = User::where('id', Auth::user()->id)->first();
                $userAccount->no_of_wlink = '2000';
                $userAccount->no_of_rlink = '2000';
                $userAccount->no_of_mlink = '2000';
                $userAccount->no_of_malink = '200';
                $userAccount->no_of_mstore = '5000';
                $userAccount->sub_type = 'premium';
                $userAccount->sub_start = Carbon::today()->toDateString();
                $userAccount->sub_end = ($chargeAmount == '7000') ? $current->addDays(30)->toDateString() : ($chargeAmount == '19950' ? $current->addMonths(3)->toDateString() : ($chargeAmount == '39900' ? $current->addMonths(5)->toDateString() : ($chargeAmount == '79800' ? $current->addMonths(11)->toDateString() : '')));
                $userAccount->sub_status = 'active';
                $userAccount->save();

                Subscription::updateOrCreate(
                    ['tnx_ref' => $tnx_ref],
                    [
                        'user_id' => Auth::user()->id,
                        'sub_type' => 'premium',
                        'tnx_ref' => $tnx_ref,
                        'amount_paid' => $chargeAmount,
                        'user_email' => Auth::user()->email,
                        'subscription_status' => 'paid',
                        'currency' => 'ngn',
                    ]
                );
            }

            //Popular year sub
            // if ($chargeAmount == '20') {
            //     $userAccount = User::where('id', Auth::user()->id)->first();
            //     $userAccount->no_of_wlink = '20';
            //     $userAccount->no_of_rlink = '20';
            //     $userAccount->no_of_mlink = '5';
            //     $userAccount->no_of_mstore = '2';
            //     $userAccount->sub_type = '';
            //     $userAccount->sub_start = Carbon::today()->toDateString();
            //     $userAccount->sub_end = $current->addMonths(11)->toDateString();
            //     $userAccount->sub_status = 'active';
            //     $userAccount->save();

            //     Subscription::updateOrCreate(
            //         ['tnx_ref' => $tnx_ref],
            //         [
            //             'user_id' => Auth::user()->id,
            //             'sub_type' => '',
            //             'tnx_ref' => $tnx_ref,
            //             'amount_paid' => $chargeAmount,
            //             'user_email' => Auth::user()->email,
            //             'subscription_status' => 'paid',
            //             'currency' => 'ngn',
            //         ]
            //     );
            // }

            $data = $resp['data'];
            return $data;
        } else {
            return "Failed";
        }
    }

    public function verify_flutterwave_payment_for_product($data)
    {
        $query = array(
            "SECKEY" => env('FLUTTERWAVE_SECRET_KEY'),
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
        $tnx_ref = $resp['data']['txref'];
        $tnx_id = $resp['data']['custphone'];
        $chargeAmount = $resp['data']['amount'];
        $chargeCurrency = $resp['data']['currency'];
        $transact = [];

        if (($chargeResponsecode == "00" || $chargeResponsecode == "0")) {
            $current = CarbonImmutable::now();
            try {
                DB::beginTransaction();
                $transact = Transaction::updateOrCreate(
                    ['id' => $tnx_id],
                    [
                        'amount_paid' => $chargeAmount,
                        'transaction_status' => $paymentStatus,
                        'tnx_ref' => $tnx_ref
                    ]
                );

                $wallet = VendorWallet::where('user_id', $transact->user_id)->first();
                if ($wallet->last_tnx_ref !== $tnx_ref) {
                    $wallet->previous_amount = $wallet->total_amount;
                    $wallet->total_amount = ($wallet->total_amount + $chargeAmount);
                    $wallet->last_tnx_ref = $tnx_ref;
                    $wallet->save();

                    //Customer Email
                    $reveiverEmailAddress = $transact->customer_email;
                    $details = [
                        'custname' => $transact->customer_name,
                        'vendor_contact' => $transact->user_phone_number,
                        'pay_for' => $transact->paying_for,
                        'quantity' => $transact->product_qty,
                        'amount' => $chargeAmount
                    ];

                    Mail::to($reveiverEmailAddress)->send(new CustomerReciept($details));


                    //vendor Email
                    $reveiverEmailAddress2 = $transact->user_email;
                    $details2 = [
                        'custname' => $transact->customer_name,
                        'customer_contact' => $transact->customer_phone_number,
                        'pay_for' => $transact->paying_for,
                        'quantity' => $transact->product_qty,
                        'amount' => $chargeAmount
                    ];

                    Mail::to($reveiverEmailAddress2)->send(new VendorReciept($details2));

                }


                DB::commit();
            } catch (\Throwable $th) {
                DB::rollback();
                return $th->getMessage();
            }

            $data = $resp['data'];


            return $this->success(
                ('Data Fetched Successfully'),
                $data
            );

        } else {
            // return $this->error('failed');
            return $this->success(
                ('Data Fetched Successfully'),
                'failed'
            );
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
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
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
        return $this->success('Fetched successful', VendorWallet::where('user_id', Auth::user()->id)->first());


        // return userAccountDetail::where('user_id', Auth::user()->user_id)->first();
    }

    public function transactionDetails()
    {

        $walletDatails = VendorWallet::where('user_id', Auth::user()->id)->first();
        $witdrawal = Witdrawal::where('email', Auth::user()->email)->where('status', 'SUCCESSFUL')->latest()->first();
        $deposit = Transaction::where('user_id', Auth::user()->id)->where('transaction_status', 'successful')->latest()->first();
        $transactions = Transaction::where('user_id', Auth::user()->id)->where('transaction_status', 'successful')->paginate(10);
        return $this->success('Fetched successful', [
            'walletDetails' => $walletDatails,
            'transactions' => $transactions,
            'witdrawal' => $witdrawal,
            'deposit' => $deposit,
            'sub_start' => Auth::user()->sub_start,
            'sub_end' => Auth::user()->sub_end,
            'sub_type' => Auth::user()->sub_type,
        ]);


        // return userAccountDetail::where('user_id', Auth::user()->user_id)->first();
    }



    public function getAllWitdrawals()
    {
        return $this->success('Fetched successful', Witdrawal::paginate(10));


        // return userAccountDetail::where('user_id', Auth::user()->user_id)->first();
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
        curl_setopt_array(
            $curl,
            array(
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
