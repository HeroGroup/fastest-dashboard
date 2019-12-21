<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderAddonItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_addon_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('order_item_id');
            $table->foreign('order_item_id')->references('id')->on('order_items');
            $table->integer('addon_item_id');
            $table->foreign('addon_item_id')->references('id')->on('addon_items');
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
        Schema::dropIfExists('order_addon_items');
    }
}
