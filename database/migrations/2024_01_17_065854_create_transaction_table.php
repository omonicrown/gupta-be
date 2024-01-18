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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('amount_paid');
            $table->string('user_email');
            $table->string('user_phone_number');
            $table->string('customer_email');
            $table->string('customer_phone_number');
            $table->string('paying_for');
            $table->string('transaction_status')->default('not_paid');
            $table->string('currency')->default('ngn');
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
        Schema::dropIfExists('transaction');
    }
};
