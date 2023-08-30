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
        Schema::create('ecommerce_vendors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->mediumText('business_name');
            $table->mediumText('description')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('registration_type')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_id_number')->nullable();
            $table->mediumText('documents')->nullable();
            $table->timestamp('registration_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('status')->default("Pending");
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_vendors');
    }
};
