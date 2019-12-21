<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            // references
            $table->string('unique_number', 20);
            $table->integer('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->integer('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants');
            $table->integer('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers');

            // origin
            $table->string('pickup_name', 50)->nullable();
            $table->string('pickup_phone', 8)->nullable();
            $table->integer('pickup_address_id')->nullable();
            $table->foreign('pickup_address_id')->references('id')->on('address_books');
            $table->string('pickup_address')->nullable();
            $table->double('pickup_latitude', 16, 14)->nullable();
            $table->double('pickup_longitude', 16, 14)->nullable();

            // destination
            $table->string('destination_name', 50)->nullable();
            $table->string('destination_phone', 8)->nullable();
            $table->integer('destination_address_id')->nullable();
            $table->foreign('destination_address_id')->references('id')->on('address_books');
            $table->string('destination_address')->nullable();
            $table->double('destination_latitude', 16, 14)->nullable();
            $table->double('destination_longitude', 16, 14)->nullable();

            // delivery
            $table->string('delivery_type', 50); // [delivery , self-pickup]
            $table->timestamp('expected_deliver_datetime')->nullable();

            // payment
            $table->float('total_price', 6, 3)->nullable();
            $table->float('total_discount', 6, 3)->nullable();
            $table->string('payment_method', 50)->nullable();

            $table->string('status', 20); // [init , complete , cancel , rollback]

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
        Schema::dropIfExists('orders');
    }
}
