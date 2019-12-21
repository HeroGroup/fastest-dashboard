<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddonItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addon_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('addon_id');
            $table->foreign('addon_id')->references('id')->on('addons');
            $table->string('name_en', 50);
            $table->string('name_ar', 50);
            $table->string('icon')->nullable();
            $table->float('price', 5, 3)->default(0);
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
        Schema::dropIfExists('addon_items');
    }
}
