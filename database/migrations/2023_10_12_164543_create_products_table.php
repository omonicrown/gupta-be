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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('link_id');
            $table->string('link_name');
            $table->string('product_name');
            $table->string('user_id');
            $table->text('product_description')->nullable();
            $table->string('phone_number');
            $table->string('no_of_items')->nullable();
            $table->string('product_price');
            $table->text('product_image_1')->nullable();
            $table->text('product_image_2')->nullable();
            $table->text('product_image_3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
