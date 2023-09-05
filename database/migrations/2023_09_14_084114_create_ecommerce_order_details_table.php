<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_order_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ecommerce_order_id');
            $table->unsignedBigInteger('ecommerce_product_id');
            $table->unsignedBigInteger('user_id');
            $table->string('product_name')->nullable();
            $table->string('orderNO')->nullable();
            $table->string('color')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('child_category')->nullable();
            $table->integer('shipping_fee')->default(0);
            $table->string('tracking_number')->nullable();
            $table->string('sku')->nullable();
            $table->string('size')->nullable();
            $table->string('brand_id')->nullable();
            $table->string('status')->default('pending')->nullable();
            $table->mediumText('image')->nullable();
            $table->mediumText('description')->nullable();
            $table->integer('quantity_ordered')->nullable();
            $table->boolean('delivered')->default(false);
            $table->decimal('unit_price')->nullable();
            $table->string('payment_status')->default('Pending');
            $table->foreign('ecommerce_order_id')->references('id')->on('ecommerce_orders')->onDelete('cascade');
            $table->foreign('ecommerce_product_id')->references('id')->on('ecommerce_products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ecommerce_order_details');
    }
}
