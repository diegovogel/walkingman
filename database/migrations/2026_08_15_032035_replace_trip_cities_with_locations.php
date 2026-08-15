<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_city_id');
            $table->dropConstrainedForeignId('destination_city_id');

            $table->foreignId('origin_location_id')->nullable()->after('id')->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->after('origin_location_id')->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_location_id');
            $table->dropConstrainedForeignId('destination_location_id');

            $table->foreignId('origin_city_id')->nullable()->after('id')->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->after('origin_city_id')->constrained('cities')->nullOnDelete();
        });
    }
};
