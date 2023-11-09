<?php

use App\Mail\Reciept;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('nurse-program', function () {

    $reveiverEmailAddress = "samuelfemi85@gmail.com";
        $details = [
            'name' => 'Bendolf Malcom',
            'email' => 'samuelfemi85@gmail.com',
            'code' => '#67679732'
        ];

       
        Mail::to($reveiverEmailAddress)->send(new Reciept($details));
        dd('success');

       
        if (Mail::failures() != 0) {
            return "Email has been sent successfully.";
        }
        return "Oops! There was some error sending the email.";

    // $this->comment(Inspiring::quote());


})->purpose('Display an inspiring quote');