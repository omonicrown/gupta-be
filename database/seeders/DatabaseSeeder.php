<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\SenderId;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '+1234567890',
            // 'name' => 'Test Company',
            'role' => 'admin',
            'status' => 'active',
        ]);
        
        // Create regular user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '+9876543210',
            // 'name' => 'User Company',
            'role' => 'user',
            'status' => 'active',
        ]);
        
        // Create wallets
        SmsWallet::create([
            'user_id' => $admin->id,
            'balance' => 10000,
            'currency' => 'NGN',
            'status' => 'active',
        ]);
        
        SmsWallet::create([
            'user_id' => $user->id,
            'balance' => 5000,
            'currency' => 'NGN',
            'status' => 'active',
        ]);
        
        // Create some transactions for the user
        for ($i = 1; $i <= 5; $i++) {
            SmsTransaction::create([
                'user_id' => $user->id,
                'sms_wallet_id' => $user->sms_wallet->id,
                'amount' => rand(500, 2000),
                'type' => 'deposit',
                'status' => 'completed',
                'reference' => 'TRX-' . uniqid(),
                'payment_method' => 'flutterwave',
                'description' => 'Wallet funding',
            ]);
        }
        
        SmsTransaction::create([
            'user_id' => $user->id,
            'sms_wallet_id' => $user->sms_wallet->id,
            'amount' => 1500,
            'type' => 'message_payment',
            'status' => 'completed',
            'reference' => 'MSG-' . uniqid(),
            'payment_method' => 'wallet',
            'description' => 'Message sending payment',
        ]);
        
        // Create sender IDs
        $senderId1 = SenderId::create([
            'user_id' => $user->id,
            'sender_id' => 'TESTAPP',
            'status' => 'approved',
            'external_id' => 'SND-' . uniqid(),
        ]);
        
        SenderId::create([
            'user_id' => $user->id,
            'sender_id' => 'MYCOMPANY',
            'status' => 'pending',
            'external_id' => 'SND-' . uniqid(),
        ]);
        
        // Create contact groups
        $group1 = ContactGroup::create([
            'user_id' => $user->id,
            'name' => 'Customers',
            'description' => 'All customers',
        ]);
        
        $group2 = ContactGroup::create([
            'user_id' => $user->id,
            'name' => 'Staff',
            'description' => 'Internal staff members',
        ]);
        
        // Create contacts
        for ($i = 1; $i <= 20; $i++) {
            $contact = Contact::create([
                'user_id' => $user->id,
                'first_name' => 'Contact',
                'last_name' => $i,
                'phone_number' => '+234810000' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'email' => 'contact' . $i . '@example.com',
                'status' => 'active',
            ]);
            
            // Add to groups
            if ($i <= 10) {
                $contact->groups()->attach($group1);
            } else {
                $contact->groups()->attach($group2);
            }
        }
        
        // Create message templates
        $template1 = MessageTemplate::create([
            'user_id' => $user->id,
            'name' => 'Welcome Message',
            'content' => 'Hello {{name}}, welcome to our service!',
            'message_type' => 'sms',
            'variables' => ['name'],
        ]);
        
        $template2 = MessageTemplate::create([
            'user_id' => $user->id,
            'name' => 'Promotion',
            'content' => 'Hello {{name}}, we have a special offer for you: {{offer}}',
            'message_type' => 'sms',
            'variables' => ['name', 'offer'],
        ]);
        
        // Create campaign
        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'June Promotion',
            'description' => 'Promotional campaign for June',
            'status' => 'completed',
            'started_at' => now()->subDays(10),
            'completed_at' => now()->subDays(5),
        ]);
        
        // Create messages
        $message1 = Message::create([
            'user_id' => $user->id,
            'sender_id' => $senderId1->id,
            'content' => 'Hello everyone, this is a test message.',
            'message_type' => 'sms',
            'status' => 'delivered',
            'sent_at' => now()->subDays(7),
            'campaign_id' => $campaign->id,
            'total_recipients' => 15,
            'successful_sends' => 13,
            'failed_sends' => 2,
            'cost' => 15.00,
        ]);
        
        $message2 = Message::create([
            'user_id' => $user->id,
            'sender_id' => $senderId1->id,
            'content' => 'Another test message for our customers.',
            'message_type' => 'sms',
            'status' => 'delivered',
            'sent_at' => now()->subDays(3),
            'total_recipients' => 10,
            'successful_sends' => 10,
            'failed_sends' => 0,
            'cost' => 10.00,
        ]);
        
        // Attach recipients to messages
        $message1->contacts()->attach(Contact::where('user_id', $user->id)->take(5)->get(), ['status' => 'delivered']);
        $message1->contactGroups()->attach($group1, ['status' => 'delivered']);
        
        $message2->contacts()->attach(Contact::where('user_id', $user->id)->skip(5)->take(5)->get(), ['status' => 'delivered']);
        $message2->contactGroups()->attach($group2, ['status' => 'delivered']);
    }
}