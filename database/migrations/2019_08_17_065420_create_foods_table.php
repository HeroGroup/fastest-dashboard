<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants');
            $table->string('name_en', 50);
            $table->string('name_ar', 50);
            $table->string('description_en')->nullable();
            $table->string('description_ar')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table-> float('price', 6, 3);
            $table->integer('preparation_time_minutes')->length(2)->nullable();
            $table->float('average_rate', 2, 1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('foods');
    }
}
