<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\EmailVerify;
use App\Mail\NewUserMail;
use App\Mail\ResetPassword;
use App\Mail\WelcomeUserMail;
use App\Models\passwordReset;
use App\Models\VendorWallet;
use App\Services\WalletService;
use Exception;
use App\Models\User;
use Http;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\LinkInfo;
use App\Models\Short;
use Illuminate\Support\Facades\DB;
use AshAllenDesign\ShortURL\Models\ShortURL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{

    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Create User
     * @param Request $request
     * @return User
     */
    public function createUser(Request $request)
    {
        try {

            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'phone_number' => 'string',
                    'password' => 'required',
                    'service_type' => 'required|in:whatsapp,sms,all' // NEW VALIDATION
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $current = Carbon::now();

            // Set service-specific limits based on selected service
            $serviceLimits = $this->getServiceLimits($request->service_type);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_ip' => $request->getClientIp(),
                'phone_number' => $request->phone_number,
                'service_type' => $request->service_type, // NEW FIELD
                'sub_status' => 'trial',
                'sub_type' => 'free',
                'sub_start' => Carbon::today()->toDateString(),
                'sub_end' => $current->addDays(14)->toDateString(),
                'no_of_wlink' => $serviceLimits['wlink'],
                'status' => 'active',
                'no_of_rlink' => $serviceLimits['rlink'],
                'no_of_mlink' => $serviceLimits['mlink'],
                'no_of_mstore' => $serviceLimits['mstore'],
                'no_of_malink' => $serviceLimits['malink'],
                'password' => Hash::make($request->password)
            ]);

            // Create wallets based on service type
            if (in_array($request->service_type, ['whatsapp', 'all'])) {
                VendorWallet::create([
                    'user_id' => $user->id,
                    'total_amount' => '0',
                    'previous_amount' => '0',
                    'user_email' => $user->email,
                    'user_phone_number' => $user->phone_number,
                    'last_tnx_ref' => '0'
                ]);

                // Create marketplace wallet for user
                $this->walletService->createWallet($user);
            }

            // Create SMS wallet if SMS service is selected
            if (in_array($request->service_type, ['sms', 'all'])) {
                // Create SMS wallet
                \App\Models\SmsWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'total_spent' => 0.00,
                    'total_recharged' => 0.00,
                    'currency' => 'NGN',
                    'status' => 'active'
                ]);
            }

            $reveiverEmailAddress = $request->email;
            $details = [
                'custname' => $request->name,
                'email' => Crypt::encrypt($request->email),
            ];

            Mail::to($reveiverEmailAddress)->send(new EmailVerify($details));

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'name' => $user->name,
                'service_type' => $user->service_type,
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service-specific limits based on selected service type
     * @param string $serviceType
     * @return array
     */
    private function getServiceLimits($serviceType)
    {
        switch ($serviceType) {
            case 'whatsapp':
                return [
                    'wlink' => '5',
                    'rlink' => '5',
                    'mlink' => '3',
                    'mstore' => '10',
                    'malink' => '1'
                ];

            case 'sms':
                return [
                    'wlink' => '0',  // No WhatsApp features
                    'rlink' => '0',  // No redirect links
                    'mlink' => '0',  // No multi links
                    'mstore' => '0', // No mini store
                    'malink' => '0'  // No marketplace links
                ];

            case 'all':
                return [
                    'wlink' => '5',
                    'rlink' => '5',
                    'mlink' => '3',
                    'mstore' => '10',
                    'malink' => '1'
                ];

            default:
                return [
                    'wlink' => '0',
                    'rlink' => '0',
                    'mlink' => '0',
                    'mstore' => '0',
                    'malink' => '0'
                ];
        }
    }

    /**
     * verify email
     * @param Request $request
     * @return User
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Some Field required',
                'data' => $validator->errors()
            ], 500);
        }

        try {
            DB::beginTransaction();
            $email = Crypt::decrypt($request->email);

            $user = User::where('email', $email)->first();
            $user->isVerified = 'true';
            $user->save();

            $reveiverEmailAddress = $user->email;
            $details = [
                'custname' => $user->name,
            ];

            Mail::to($reveiverEmailAddress)->send(new WelcomeUserMail($details));

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Verified',
                'data' => $user,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Login The User - Updated to include service_type
     * @param Request $request
     * @return User
     */
    public function loginUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 200);
            }

            $user = User::where('email', $request->email)->first();
            PersonalAccessToken::where('tokenable_id', $user->id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'name' => $user->name,
                'service_type' => $user->service_type, // Include service type in response
                'data' => $user,
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function forgot(ForgotPasswordRequest $request)
    {
        try {
            $user = ($query = User::query());
            $user = $user->where($query->qualifyColumn('email'), $request->input('email'))->first();

            if (!$user || !$user->email) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Record',
                    'data' => "Email does not match our records"
                ], 200);
            }

            $resetPasswordToken = str_pad(random_int(1, 999), 4, '0', STR_PAD_LEFT);

            if (!$userPassReset = passwordReset::where('email', $user->email)->first()) {
                passwordReset::create([
                    'email' => $user->email,
                    'token' => $resetPasswordToken
                ]);
            } else {
                $userPassReset->update([
                    'email' => $user->email,
                    'token' => $resetPasswordToken
                ]);
            }

            $reveiverEmailAddress = $user->email;
            $details = [
                'custname' => $user->name,
                'otp' => $resetPasswordToken,
            ];

            Mail::to($reveiverEmailAddress)->send(new ResetPassword($details));

            return response()->json([
                'status' => true,
                'message' => 'Password Reset Link Sent Successfully.',
                'data' => 'We sent an OTP to ' . $user->email
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function reset(ResetPasswordRequest $request)
    {
        try {
            $attribute = $request->validated();
            $user = User::where('email', $attribute['email'])->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Record',
                    'data' => "Incorrect email address provided"
                ], 200);
            }

            $resetRequest = passwordReset::where('email', $user->email)->first();

            if (!$resetRequest || $request->token != $resetRequest->token) {
                return response()->json([
                    'status' => false,
                    'message' => 'An Error Occured',
                    'data' => "Incorrect 4 digit code,try again"
                ], 200);
            }

            $user->fill([
                'password' => Hash::make($attribute['password']),
            ]);

            $user->save();

            $user->tokens()->delete();
            $resetRequest->delete();

            $success['name'] = $user->name;
            $success['account_id'] = $user->id;

            return response()->json([
                'status' => true,
                'message' => 'Password Reset Successfully!',
                'data' => $success
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse  
     */
    public function session(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load("link", 'link.linkInfo', 'link.shortUrl', 'link.shortUrl.visits')
        );
    }

    public function redirectLinks(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load("redirectLinks", 'redirectLinks.linkInfo', 'redirectLinks.shortUrl', 'redirectLinks.shortUrl.visits')
        );
    }

    public function getMultiLinks(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load("multiLink", 'multiLink.linkInfo', 'multiLink.shortUrl', 'multiLink.shortUrl.visits')
        );
    }

    public function getLinks(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load("link")
        );
    }

    public function getLinksAll(Request $request): JsonResponse
    {
        return response()->json(
            [
                'status' => true,
                'data' => LinkInfo::get()
            ]
        );
    }

    public function getLinksShort(Request $request): JsonResponse
    {
        return response()->json(
            [
                'status' => true,
                'data' => ShortURL::get()
            ]
        );
    }

    public function getlinksByName(Request $request, $name): JsonResponse
    {
        $Link = Link::where('type', 'tiered')->where('name', $name)->first();
        $Links = Link::where('type', 'message')->where('user_id', $Link->user_id)->get(['name']);
        return response()->json(
            [
                'status' => true,
                'data' => $Links
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()->currentAccessToken()->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'logout successful'
            ]);
        }
    }
}
