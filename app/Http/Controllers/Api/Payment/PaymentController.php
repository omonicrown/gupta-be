<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\MakeOutsideProductPaymentRequest;
use App\Http\Requests\Payment\MakePayment;
use App\Http\Requests\Payment\WitdrawFundRequest;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
    
    public function payOutCustomers(WitdrawFundRequest $request, PaymentService $userService)
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



}
