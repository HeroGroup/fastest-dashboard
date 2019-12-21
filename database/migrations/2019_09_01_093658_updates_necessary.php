<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatesNecessary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('logo')->nullable()->after('user_id');
            $table->string('image')->nullable()->after('logo');
            $table->string('status', 20)->default('closed')->after('max_delivery_time');
            $table->string('type', 50)->after('status');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['catable_id', 'catable_type']);
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
