<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('base_store')->nullable();
            $table->integer('selling_id')->nullable();
            $table->mediumText('selling_location')->nullable();
            $table->integer('shipping_id')->nullable();
            $table->mediumText('shipping_location')->nullable();
            $table->boolean('shipping_calculation')->default(true);
            $table->string('is_active')->default(true);
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
        Schema::dropIfExists('shipping_settings');
    }
}
