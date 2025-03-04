<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Services\FlutterwaveService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $walletService;
    protected $flutterwaveService;
    
    public function __construct(WalletService $walletService, FlutterwaveService $flutterwaveService)
    {
        $this->walletService = $walletService;
        $this->flutterwaveService = $flutterwaveService;
    }
    
    /**
     * Get wallet details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $wallet = SmsWallet::where('user_id', $user->id)->first();
            
            if (!$wallet) {
                // Create wallet for user if it doesn't exist
                $wallet = $this->walletService->createWallet($user);
            }
            
            return response()->json([
                'wallet' => $wallet,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch wallet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get transaction history
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $type = $request->get('type');
            $status = $request->get('status');
            
            $query = SmsTransaction::where('user_id', $user->id);
            
            // Filter by transaction type
            if ($type) {
                $query->where('type', $type);
            }
            
            // Filter by transaction status
            if ($status) {
                $query->where('status', $status);
            }
            
            $transactions = $query->latest()->paginate($perPage);
            
            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Initiate payment to fund wallet
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'currency' => 'sometimes|required|string|size:3',
            'description' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            
            // Set default currency to NGN if not provided
            $currency = $request->get('currency', 'NGN');
            
            // Initialize payment
            $result = $this->flutterwaveService->initializePayment(
                $user,
                $request->amount,
                $currency,
                $request->redirect_url,
                $request->description ?? 'Wallet Funding'
            );
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Payment initialized successfully',
                'transaction_id' => $result['transaction_id'],
                'payment_link' => $result['payment_link'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initialize payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Verify payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_reference' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            // Verify payment
            $result = $this->flutterwaveService->verifyPayment($request->transaction_reference);
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Payment verification successful',
                'verified' => $result['verified'],
                'transaction_id' => $result['transaction_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Flutterwave webhook handler
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        try {
            // Process webhook
            $result = $this->flutterwaveService->processWebhook($request->all());
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get transaction details
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTransaction(Request $request, $id)
    {
        try {
            $user = $request->user();
            $transaction = SmsTransaction::where('user_id', $user->id)
                ->findOrFail($id);
            
            return response()->json($transaction);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate invoice PDF for a transaction
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function generateInvoice(Request $request, $id)
    {
        try {
            $user = $request->user();
            $transaction = SmsTransaction::where('user_id', $user->id)
                ->where('status', 'completed')
                ->findOrFail($id);
            
            // Generate invoice using a PDF library
            // This is just a placeholder - you'd need to implement actual PDF generation
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('invoices.transaction', [
                'transaction' => $transaction,
                'user' => $user,
            ]);
            
            return $pdf->download('invoice-' . $transaction->reference . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}