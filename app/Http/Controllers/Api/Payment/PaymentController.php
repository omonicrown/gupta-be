<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\MakePayment;
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
            return $this->success(('Data Fetched Successfully'),
                $userService->makePaymentForSubscription($request->all())
                
            );
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function paymentCallback(Request $request, PaymentService $userService): JsonResponse
    {
        try {
            return $this->success(('Data Fetched Successfully'),
                $userService->verify_flutterwave_payment($request->all())
                
            );
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }


  
}
