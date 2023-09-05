<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_cards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string("authorization_code")->nullable();
            $table->string("email")->nullable();
            $table->string("card_type")->nullable();
            $table->string("last4")->nullable();
            $table->string("exp_month")->nullable();
            $table->string("exp_year")->nullable();
            $table->string("bin")->nullable();
            $table->string("bank")->nullable();
            $table->string("channel")->nullable();
            $table->string("reusable")->nullable();
            $table->string("signature")->nullable();
            $table->string("brand")->nullable();
            $table->string("country_code")->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('ecommerce_cards');
    }
}
