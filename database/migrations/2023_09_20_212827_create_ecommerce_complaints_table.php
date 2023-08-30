<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ecommerce_complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ecommerce_order_details_id');
            $table->mediumText('reason');
            $table->mediumText('customer_comment');
            $table->mediumText('attachment')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('status')->default("Pending");
            $table->boolean('priority')->default(false);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ecommerce_order_details_id')->references('id')->on('ecommerce_order_details')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_complaints');
    }
};
