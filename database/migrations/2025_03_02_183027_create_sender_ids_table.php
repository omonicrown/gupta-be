<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sender_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('sender_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('verification_document')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamps();
            
            // A user can't have duplicate sender IDs
            $table->unique(['user_id', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sender_ids');
    }
};
