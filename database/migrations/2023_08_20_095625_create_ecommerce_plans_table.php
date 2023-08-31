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
        Schema::create('ecommerce_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('name');
            $table->mediumText('description')->nullable();
            $table->string('interval')->nullable();
            $table->decimal('price', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->string('status')->default("Active");
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_plans');
    }
};
