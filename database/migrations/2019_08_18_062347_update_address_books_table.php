<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAddressBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('address_books', function (Blueprint $table) {
            $table->string('block', 20)->nullable()->after('area_id');
            $table->string('street', 20)->nullable()->after('block');
            $table->string('avenue', 20)->nullable()->after('street');
            $table->string('place_type', 20)->nullable()->after('building_number'); // [apartment , office]
            $table->string('jadda', 20)->nullable()->after('floor');
            $table->dropColumn(['block_en', 'block_ar', 'street_en', 'street_ar', 'avenue_en', 'avenue_ar', 'place_type_en', 'place_type_ar', 'jadda_en', 'jadda_ar']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('address_books', function (Blueprint $table) {
            $table->string('block_en', 20)->nullable()->after('area_id');
            $table->string('block_ar', 20)->nullable()->after('block_en');
            $table->string('street_en', 20)->nullable()->after('block_ar');
            $table->string('street_ar', 20)->nullable()->after('street_en');
            $table->string('avenue_en', 20)->nullable()->after('street_ar');
            $table->string('avenue_ar', 20)->nullable()->after('avenue_ar');
            $table->string('place_type_en', 20)->nullable()->after('building_number'); // [apartment , office]
            $table->string('place_type_ar', 20)->nullable()->after('place_type_en'); // [apartment , office]
            $table->string('jadda_en', 20)->nullable()->after('floor');
            $table->string('jadda_ar', 20)->nullable()->after('jadda_en');

            $table->dropColumn(['block', 'street', 'avenue', 'place_type', 'jadda']);
        });
    }
}
