<?php

namespace App\Http\Controllers\Api\Auth;

use App\Mail\EmailVerify;
use App\Mail\NewUserMail;
use App\Mail\WelcomeUserMail;
use Exception;
use App\Models\User;
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

class AuthController extends Controller
{
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
                'phone_number' => $request->phone_number,
                'sub_status' => 'trial',
                'sub_start' => Carbon::today()->toDateString(),
                'sub_end' => $current->addDays(14)->toDateString(),
                'no_of_wlink' => '3',
                'no_of_rlink' => '3',
                'no_of_mlink' => '2',
                'no_of_mstore' => '1',
                'password' => Hash::make($request->password)
            ]);

            $reveiverEmailAddress = $request->email;
            $details = [
                'custname' => $request->name . ' ' . $request->last_name,
                'email' => $request->email,
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
            'email' => 'required|email',
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

            $user = User::where('email', $request->email)->first();
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
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'name' => $user->name,
                'data'=> $user,
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
        return response()->json([
            'status' => true,
            'data' => LinkInfo::get()
        ]
        );
    }

    public function getLinksShort(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => ShortURL::get()
        ]
        );
    }

    public function getlinksByName(Request $request, $name): JsonResponse
    {
        $Link = Link::where('type', 'tiered')->where('name', $name)->first();
        $Links = Link::where('type', 'message')->where('user_id', $Link->user_id)->get(['name']);
        return response()->json([
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
