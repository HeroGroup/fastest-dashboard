<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 50)->after('id');
            $table->dropColumn(['name_en', 'name_ar']);
        });
    }

    public function down()
    {
        Schema::table('address_books', function (Blueprint $table) {
            $table->string('name_en')->after('id');
            $table->string('name_ar')->after('name_en');

            $table->dropColumn('name');
        });
    }
}
