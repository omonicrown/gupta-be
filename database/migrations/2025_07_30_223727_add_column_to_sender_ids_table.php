<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sender_ids', function (Blueprint $table) {
            $table->enum('message_type', ['transactional', 'promotional'])->nullable()->after('sender_id');
            $table->text('purpose')->nullable()->after('message_type'); // What they'll be sending
            $table->enum('registration_option', ['useGupta', 'customSender', 'standard'])->nullable()->after('purpose');
            $table->text('notes')->nullable()->after('rejection_reason'); // Admin or system notes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sender_ids', function (Blueprint $table) {
            $table->dropColumn(['message_type', 'purpose', 'registration_option', 'notes']);
        });
    }
};
