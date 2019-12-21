<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFoodAddonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('food_addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('food_id');
            $table->foreign('food_id')->references('id')->on('foods');
            $table->integer('addon_id');
            $table->foreign('addon_id')->references('id')->on('addons');
            $table->string('status', 50)->default('optional');
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('food_addons');
    }
}
