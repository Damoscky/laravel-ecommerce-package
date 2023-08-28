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
        Schema::create('ecommerce_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('sub_category_id');
            $table->mediumText('product_name');
            $table->mediumText('long_description')->nullable();
            $table->mediumText('short_description')->nullable();
            $table->mediumText('tags')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('sku')->nullable();
            $table->integer('minimum_purchase_per_quantity');
            $table->string('manage_stock_quantity')->nullable();
            $table->bigInteger('quantity_supplied')->default(0);
            $table->bigInteger('quantity_purchased')->default(0);
            $table->bigInteger('available_quantity')->default(0);
            $table->mediumText('product_image1')->nullable();
            $table->mediumText('product_image2')->nullable();
            $table->mediumText('product_image3')->nullable();
            $table->mediumText('product_image4')->nullable();
            $table->mediumText('featured_image')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->decimal('regular_price', 15, 2)->default(0.00);
            $table->decimal('sales_price', 15, 2)->default(0.00);
            $table->decimal('shipping_fee', 15, 2)->default(0.00)->nullable();
            $table->string('weight_type')->nullable();
            $table->string('weight')->nullable();
            $table->string('length')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('discount')->nullable();
            $table->integer('views')->default(0);
            $table->string('ean')->nullable();
            $table->decimal('ratings')->default(0.00);
            $table->boolean('featured')->default(false);
            $table->boolean('promoted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_draft')->default(false);
            $table->string('status')->default("Pending");
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_products');
    }
};
