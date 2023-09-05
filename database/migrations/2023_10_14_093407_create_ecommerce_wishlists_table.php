<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceWishlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_wishlists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ecommerce_product_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('quantity')->default(1);
            $table->string('color')->nullable();
            $table->double('price', 15, 2)->default(0.00);
            $table->double('total_price', 15, 2)->default(0.00);
            $table->boolean('status')->nullable()->default(true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ecommerce_product_id')->references('id')->on('ecommerce_products')->onDelete('cascade');
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
        Schema::dropIfExists('ecommerce_wishlists');
    }
}
