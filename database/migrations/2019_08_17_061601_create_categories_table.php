<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('parent_category_id');
            $table->foreign('parent_category_id')->references('id')->on('categories');
            $table->string('name_en', 50);
            $table->string('name_ar', 50);
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->length(3)->nullable();
            $table->string('description_en')->nullable();
            $table->string('description_ar')->nullable();
            $table->integer('catable_id')->length(10)->nullable();
            $table->string('catable_type', 50)->nullable();
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
        Schema::dropIfExists('categories');
    }
}
