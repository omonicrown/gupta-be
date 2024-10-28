<?php

namespace App\Console\Commands;

use App\Mail\newCustomerFollowup;
use App\Mail\WeekendFollowup;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class Notification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now()->isoFormat('YYYY-MM-DD');
        $user = User::orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $reveiverEmailAddress = $user->email;
                $details = [
                    'custname' => $user->name,

                ];

                Mail::to($reveiverEmailAddress)->send(new WeekendFollowup($details));
                Http::post('https://api.ng.termii.com/api/sms/send', [
                    'api_key' => 'TLSrs8NBktDuABDpxfNYURRiBK7R15XnsHHDVwnp914eKSIJqLSYCDlIE4x1EU',
                    'type' => 'plain',
                    'to' => $user->phone_number,
                    'from' => 'Gupta',
                    'channel' => 'generic',
                    'sms' => "Hello ".$user->name."!\n\n Kickstart your week with new sales opportunities!\n Explore Gupta’s subscription plans to simplify selling and check your email for updates.\n\n We’re here to support you, let’s make this a winning week together!",

                ]);

                // if ($user->sub_end == Carbon::now()->isoFormat('YYYY-MM-DD')) {
                //     $updateUser = User::find($user->id);
                //     $updateUser->sub_status = 'expired';
                //     $updateUser->save();
                // }
            }
        });





        // $this->comment($user);
        // info($user);

        // $Year = Carbon::now()->isoFormat('YYYY-MM-DD');
        // $now =Carbon::now()->isoFormat('YYYY-MM-DD');

        // $this->comment($Year == $now ? 'hello':'durrr');
    }
}
