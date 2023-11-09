<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\LinkInfo;
use App\Models\Short;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

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

    public function allLinks(Request $request): JsonResponse
    { 
        return response()->json(
            $request->user()->load("allLink", 'allLink.linkInfo', 'allLink.shortUrl', 'allLink.shortUrl.visits')
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
        return response()->json([
            'status'=>true,
            'data' => Link::where('type','message')->where('user_id',Auth::user()->id)->get(['name'])
        ]
        );
    }

    public function getLinksAll(Request $request): JsonResponse
    {
        return response()->json([
            'status'=>true,
            'data' => LinkInfo::get()
        ]
        );
    }

    public function getlinksByName(Request $request,$name): JsonResponse
    {
        $Link = Link::where('type','tiered')->where('name',$name)->first();
        $Links = Link::where('type','message')->where('user_id',$Link->user_id)->get(['name']);
        return response()->json([
            'status'=>true,
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
                'status'  => true,
                'message' => 'logout successful'
            ]);
        }
    }
}
