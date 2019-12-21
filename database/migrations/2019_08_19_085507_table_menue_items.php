<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableMenueItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name',150);
            $table->string('rtlName',150)->nullable();
            $table->string('path',150)->nullable();
            $table->string('rtlMini',150)->nullable();
            $table->string('component',150)->nullable();
            $table->string('layout',150)->nullable();
            $table->boolean('collapse')->default(false);
            $table->boolean('invisible')->default(false);
            $table->integer('parent_id')->default(0);
            $table->string('icon',150)->nullable();
            $table->integer('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('state',150)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
