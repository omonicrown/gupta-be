<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Console\Command;

class CheckSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check';

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
                if ($user->sub_end == Carbon::now()->isoFormat('YYYY-MM-DD')) {
                    $updateUser = User::find($user->id);
                    $updateUser->sub_status = 'expired';
                    $updateUser->save();
                }
            }
        });





        // $this->comment($user);
        // info($user);

        // $Year = Carbon::now()->isoFormat('YYYY-MM-DD');
        // $now =Carbon::now()->isoFormat('YYYY-MM-DD');

        // $this->comment($Year == $now ? 'hello':'durrr');
    }
}
