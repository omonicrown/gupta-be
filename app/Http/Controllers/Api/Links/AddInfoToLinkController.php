<?php

namespace App\Http\Controllers\Api\Links;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use Exception;
use App\Models\Link;
use App\Models\LinkInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class AddInfoToLinkController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     * @return Link
     */
    public function __invoke(Request $request)
    {
        try {

            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'phone_number' => 'required',
                    'message' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            // DB::beginTransaction();
            // if (Link::where('user_id', auth()->user()->id)->count() <= 5) {
                $link = Link::create([
                    'name' => str_replace(' ', '', $request->name),
                    'type' => 'message',
                    'user_id' => auth()->user()->id
                ]);

                $shortURL = ShortURL::destinationUrl(
                    'https://api.whatsapp.com/send?phone=' . substr($request->phone_number, 1) . '&text=' . $request->message
                )->urlKey(
                    $link->name
                )->trackVisits()->make();

                $info = LinkInfo::updateOrCreate(
                    [
                        'link_id' => $link->id
                    ],
                    [
                        'phone_number' => $request->phone_number,
                        'message' => $request->message,
                    ]
                );

                $link->update([
                    'short_url_id' => $shortURL->id
                ]);

                // DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Link Created Successfully',
                    'info' => $info,
                    'link' => $link,
                    'url'  => $shortURL->default_short_url,
                    'created' => Link::where('user_id', auth()->user()->id)->count()
                ], 200);
            // }else{
            //     return response()->json([
            //         'status' => true,
            //         'message' => 'You have used up your Limit.Upgrade to Pro',
            //         'error' => 1,
            //         'created' => Link::where('user_id', auth()->user()->id)->count()
            //     ], 200);
            // }
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
