<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiKey extends Command
{
    protected $signature = 'api:key-generate {user : User ID or email} {--name= : Name for the API key} {--expires= : Expiration date (YYYY-MM-DD)}';
    protected $description = 'Generate a new API key for a user';

    public function handle()
    {
        $userIdentifier = $this->argument('user');
        $keyName = $this->option('name') ?? 'CLI Generated Key';
        $expires = $this->option('expires') ? date('Y-m-d', strtotime($this->option('expires'))) : null;
        
        // Find user by ID or email
        $user = is_numeric($userIdentifier) 
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();
            
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }
        
        $apiKey = ApiKey::generateFor(
            $user->id,
            $keyName,
            $expires
        );
        
        $this->info("API key generated successfully!");
        $this->line("Key: {$apiKey->key}");
        $this->line("Name: {$apiKey->name}");
        $this->line("Expires: " . ($apiKey->expires_at ? $apiKey->expires_at->format('Y-m-d') : 'Never'));
        
        return 0;
    }
}