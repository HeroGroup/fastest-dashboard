<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('area_id');
            $table->foreign('area_id')->references('id')->on('areas');
            $table->string('block_en', 20)->nullable();
            $table->string('block_ar', 20)->nullable();
            $table->string('street_en', 20)->nullable();
            $table->string('street_ar', 20)->nullable();
            $table->string('avenue_en', 20)->nullable();
            $table->string('avenue_ar', 20)->nullable();
            $table->string('building_number', 20)->nullable();
            $table->string('place_type_en', 20)->nullable(); // [apartment , office]
            $table->string('place_type_ar', 20)->nullable(); // [apartment , office]
            $table->integer('floor')->length(2)->nullable();
            $table->string('jadda_en', 20)->nullable();
            $table->string('jadda_ar', 20)->nullable();
            $table->double('latitude', 16, 14)->nullable();
            $table->double('longitude', 16, 14)->nullable();
            $table->string('phone_number', 8)->nullable();
            $table->boolean('is_default')->default(true);
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
        Schema::dropIfExists('address_books');
    }
}
