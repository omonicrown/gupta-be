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

            $current = Carbon::now();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_ip' => $request->getClientIp(),
                'phone_number' => $request->phone_number,
                'sub_status' => 'trial',
                'sub_type' => 'free',
                'sub_start' => Carbon::today()->toDateString(),
                'sub_end' => $current->addDays(14)->toDateString(),
                'no_of_wlink' => '5',
                'status' => 'active',
                'no_of_rlink' => '5',
                'no_of_mlink' => '3',
                'no_of_mstore' => '10',
                'no_of_malink' => '1',
                'password' => Hash::make($request->password)
            ]);


            VendorWallet::create([
                'user_id' => $user->id,
                'total_amount' => '0',
                'previous_amount' => '0',
                'user_email' => $user->email,
                'user_phone_number' => $user->phone_number,
                'last_tnx_ref' => '0'
            ]);

            // Create wallet for user
            $this->walletService->createWallet($user);

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
            // return ($user->name);
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
     * Login The User
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
            PersonalAccessToken::where('tokenable_id',$user->id)->delete();

          
            
                return response()->json([
                    'status' => true,
                    'message' => 'User Logged In Successfully',
                    'name' => $user->name,
                    'data' => $user,
                    'token' => $user->createToken("API TOKEN")->plainTextToken
                ], 200);
           

            // 'user_ip' => $request->getClientIp(),



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
                return
                response()->json([
                    'status' => false,
                    'message' => 'No Record',
                    'data' => "Email does not match our records"
                ], 200);
                // $this->sendError('No Record', 'No Record Found');
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

            // $response = Http::post('https://api.ng.termii.com/api/sms/send', [
            //     'api_key' => 'TLSrs8NBktDuABDpxfNYURRiBK7R15XnsHHDVwnp914eKSIJqLSYCDlIE4x1EU',
            //     'type' => 'plain',
            //     'to' => $user->phone_number,
            //     'from' => 'Afriproedu',
            //     'channel' => 'generic',
            //     'sms' => "Hello,your OTP is " . $resetPasswordToken . ". This will expire in the next 30 minutes.",

            // ]);

            $reveiverEmailAddress = $user->email;
            $details = [
                'custname' => $user->name,
                'otp' => $resetPasswordToken,
            ];


            Mail::to($reveiverEmailAddress)->send(new ResetPassword($details));


            // dd($response);

            // echo $response;

            return $this->success('We sent an OTP to ' . $user->email . '', 'Password Reset Link Sent Successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }


    }

    public function reset(ResetPasswordRequest $request)
    {

        try {
            $attribute = $request->validated();
            $user = User::where('email', $attribute['email'])->first();
            // $user = ($query = User::query());
            // $user = $user->where($query->qualifyColumn('email'),$request->input('email'))->first();

            if (!$user) {
                return

                response()->json([
                    'status' => false,
                    'message' => 'No Record',
                    'data' => "Incorrect email address provided"
                ], 200);
            }

            $resetRequest = passwordReset::where('email', $user->email)->first();
            // dd($resetRequest->token);

            if (!$resetRequest || $request->token != $resetRequest->token) {
                return

                response()->json([
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
            // $success['token'] = $user->createToken('MyAuth')->plainTextToken;
            $success['name'] = $user->name;
            // $success['role'] =  $user->role;
            $success['account_id'] = $user->id;

            return $this->success('Pasword Reset Successfully!', $success);

        } catch (\Throwable $th) {
            dd($th->getMessage());
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
