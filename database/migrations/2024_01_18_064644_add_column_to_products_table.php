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
        Schema::table('market_place_links', function (Blueprint $table) {
            $table->string('brand_primary_color')->default('#0071BC');
            $table->text('brand_description')->nullable();
            $table->text('facebook_url')->nullable();
            $table->text('instagram_url')->nullable();
            $table->text('tiktok_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('market_place_links', function (Blueprint $table) {
            //
        });
    }
};
