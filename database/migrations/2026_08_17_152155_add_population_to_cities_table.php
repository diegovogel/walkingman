<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->unsignedInteger('population')->nullable()->after('longitude');
        });

        $this->backfillPopulationsFromSeedData();
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('population');
        });
    }

    /**
     * Databases seeded before this migration have city rows the seeder will
     * never touch again, so their populations are filled in here from the
     * same JSON file the seeder reads.
     */
    private function backfillPopulationsFromSeedData(): void
    {
        $filePath = database_path('data/cities.json');

        if (! file_exists($filePath)) {
            return;
        }

        $cities = json_decode(file_get_contents($filePath), true);

        if (! is_array($cities)) {
            return;
        }

        $populations = [];

        foreach ($cities as $city) {
            $populations[$city['city'].'|'.$city['state_id']] = $city['population'];
        }

        DB::table('cities')->whereNull('population')->orderBy('id')->each(function (object $row) use ($populations) {
            $population = $populations[$row->name.'|'.$row->state_abbreviation] ?? null;

            if ($population !== null) {
                DB::table('cities')->where('id', $row->id)->update(['population' => $population]);
            }
        });
    }
};
