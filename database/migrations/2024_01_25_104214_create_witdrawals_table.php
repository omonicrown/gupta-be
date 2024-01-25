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
        Schema::create('witdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('account_bank');
            $table->string('account_number');
            $table->string('amount');
            $table->string('narration');
            $table->string('reference')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('beneficiary_country');
            $table->string('status')->default('pending');
            $table->string('mobile_number');
            $table->string('merchant_name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('witdrawals');
    }
};
