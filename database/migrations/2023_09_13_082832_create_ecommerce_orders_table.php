<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('fullname')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('payment_method')->nullable();
            $table->integer('shipping_fee')->default(0);
            $table->string('email')->nullable();
            $table->string('status')->default('Pending');
            $table->string('payment_status')->default('Pending')->nullable();
            $table->double('total_price');
            $table->string('orderID');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('phoneno')->nullable();
            $table->string('gender')->nullable();
            $table->string('age')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('payment_reference_number')->nullable();
            $table->string('orderNO');
            $table->boolean('delivered')->nullable()->default(false);
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
        Schema::dropIfExists('ecommerce_orders');
    }
}
