<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('card_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string("reference")->nullable();
            $table->string("channel")->nullable();
            $table->string("currency")->nullable();
            $table->string("gateway_response")->nullable();
            $table->decimal("amount", 19, 2)->nullable()->default(0.0);
            $table->decimal("fee", 19, 2)->nullable()->default(0.0);
            $table->string("status")->nullable();
            $table->string("plan")->nullable();
            $table->dateTime("paid_at")->nullable();
            $table->dateTime("initialized_at")->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('card_id')->references('id')->on('ecommerce_cards')->onDelete('set null');
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
        Schema::dropIfExists('ecommerce_transactions');
    }
}
