<?php

namespace App\Console\Commands;

use App\Mail\LinksEmail;
use App\Models\MarketPlaceLink;
use App\Models\User;
use Illuminate\Console\Command;
use Mail;

class SendLinksEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send links information to all users via email';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            // Load the necessary relations
            $sessionData = $user->load("link", 'link.linkInfo', 'link.shortUrl', 'link.shortUrl.visits');
            $redirectLinksData = $user->load("redirectLinks", 'redirectLinks.linkInfo', 'redirectLinks.shortUrl', 'redirectLinks.shortUrl.visits');
            $multiLinksData = $user->load("multiLink", 'multiLink.linkInfo', 'multiLink.shortUrl', 'multiLink.shortUrl.visits');
            $miniStore = MarketPlaceLink::where('user_id', $user->id)->get();
            // Consolidate the data
            $data = [
                'session_links' => $sessionData,
                'redirect_links' => $redirectLinksData,
                'multi_links' => $multiLinksData,
                'mini_store' => $miniStore
            ];

            // Send the email
            Mail::to($user->email)->send(mailable: new LinksEmail($data));

            // Optionally, you can log progress or output
            $this->info("Email sent to {$user->email}");
        }

        return 0;
    }
}
