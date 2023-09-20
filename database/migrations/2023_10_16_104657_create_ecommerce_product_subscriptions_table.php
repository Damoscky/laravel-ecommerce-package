<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceProductSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_product_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ecommerce_product_id');
            $table->unsignedBigInteger('ecommerce_order_details_id');
            $table->unsignedBigInteger('user_id');
            $table->string('interval')->nullable();
            $table->string('auth_code')->nullable();
            $table->date('start_date')->nullable(); 
            $table->date('end_date')->nullable();
            $table->date('last_sub_date')->nullable();
            $table->date('next_sub_date')->nullable();
            $table->string('quantity')->nullable();
            $table->string('status')->default("Active");
            $table->foreign('ecommerce_product_id')->references('id')->on('ecommerce_products')->onDelete('cascade');
            $table->foreign('ecommerce_order_details_id')->references('id')->on('ecommerce_order_details')->onDelete('cascade');
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
        Schema::dropIfExists('ecommerce_product_subscriptions');
    }
}
