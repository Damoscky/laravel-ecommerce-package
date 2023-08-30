<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceOrderHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_order_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ecommerce_order_detail_id');
            $table->string("status")->nullable();
            $table->foreign('ecommerce_order_detail_id')->references('id')->on('ecommerce_order_details')->onDelete('cascade');
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
        Schema::dropIfExists('ecommerce_order_histories');
    }
}
