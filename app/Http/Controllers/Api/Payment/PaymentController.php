<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\MakeOutsideProductPaymentRequest;
use App\Http\Requests\Payment\MakePayment;
use App\Http\Requests\Payment\WitdrawFundRequest;
use App\Mail\CompleteTransaction;
use App\Models\VendorWallet;
use App\Models\Witdrawal;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

class PaymentController extends Controller
{
    public function makePayment(MakePayment $request, PaymentService $userService)
    {
        // return ($request);
        try {
            return $this->success(
                ('Data Fetched Successfully'),
                $userService->makePaymentForSubscription($request->all())

            );
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function makeOutsideProductPaymentWithFlutterwave(MakeOutsideProductPaymentRequest $request, PaymentService $userService)
    {
        // return ($request);
        try {
            return $userService->makeOutsideProductPaymentWithFlutterwave($request->all());

        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }


    public function requestWitdrawal(WitdrawFundRequest $request, PaymentService $userService)
    {
        // return ($request);
        try {
            return $userService->requestWitdrawal($request->all());
            //    return $request;

        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function payOutCustomers(Request $request, PaymentService $userService)
    {
        // return ($request);
        try {
            return $userService->payOutCustomers($request->all());
            //    return $request;

        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }



    public function paymentCallback(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return $this->success(
                ('Data Fetched Successfully'),
                $userService->verify_flutterwave_payment_for_subscription($request->all())

            );
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function paymentCallbackForProduct(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return
                $userService->verify_flutterwave_payment_for_product($request->all());

        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function walletDetails(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return $userService->walletDetails();
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }


    public function transactionDetails(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return $userService->transactionDetails();
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function getAllWitdrawals(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return $userService->getAllWitdrawals();
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }


    public function paymentCallbackForOutsidePayment(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return $this->success(
                ('Data Fetched Successfully'),
                $userService->verify_flutterwave_payment_for_subscription($request->all())

            );
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }




    public function webhook(Request $request, PaymentService $userService)
    {
        try {
            //This verifies the webhook is sent from Flutterwave
            // $verified = Flutterwave::verifyWebhook();
            // if it is a charge event, verify and confirm it is a successful transaction
            // if ($request->data->status == 'successful') {
            //     $verificationData = Flutterwave::verifyPayment($request->data['id']);
            //     if ($verificationData['status'] === 'success') {
            //         // process for successful charge
            //     }
            // }


            $userData = Witdrawal::where('reference', $request->data->reference)->first();

            // if it is a transfer event, verify and confirm it is a successful transfer
            if ($request->event == 'transfer.completed') {
                // $transfer = Flutterwave::transfers()->fetch($request->data['id']);
                if ($request->data->status === 'SUCCESSFUL') {
                    Witdrawal::updateOrCreate(
                        ['reference' =>$request->data->reference],
                        [
                            'status' => $request->data->status
                        ]
                    );

                    $reveiverEmailAddress = $userData->email;
                    $details = [
                        'custname' => $userData->first_name,
                        'amount' => $request->data->amount
                    ];

                    Mail::to($reveiverEmailAddress)->send(new CompleteTransaction($details));

                    // update transfer status to successful in your db
                } else if ($request->data->status === 'FAILED') {
                    Witdrawal::updateOrCreate(
                        ['reference' =>$request->data->reference],
                        [
                            'status' => $request->data->status
                        ]
                    );

                    $reveiverEmailAddress = $userData->email;
                    $details = [
                        'custname' => 'failed',
                        'amount' => '2000'
                    ];
                    Mail::to($reveiverEmailAddress)->send(new CompleteTransaction($details));

                    // VendorWallet::updateOrCreate(
                    //     ['email' => $transfer['data']['meta']['email']],
                    //     [
                    //         'total_amount' => ($walletDatails->total_amount - $data['amount'])
                    //     ]
                    // );


                } else if ($request->data->status=== 'PENDING') {
                    // update transfer status to pending in your db

                    $reveiverEmailAddress = 'samuelfemi85@gmail.com';
                    $details = [
                        'custname' => 'pending',
                        'amount' => '2000'
                    ];
                    Mail::to($reveiverEmailAddress)->send(new CompleteTransaction($details));
                }

            }



            return $this->success(
                ('Data Fetched Successfully'),
                []
            );

            
            //    return $request;

        } catch (Exception $exception) {
            $reveiverEmailAddress = 'samuelfemi85@gmail.com';
            $details = [
                'custname' => 'failed 232',
                'amount' => '2000'
            ];
            Mail::to($reveiverEmailAddress)->send(new CompleteTransaction($details));
            return $this->exception($exception);
        }
    }



}
