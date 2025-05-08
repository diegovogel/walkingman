<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scream_game_results', function (Blueprint $table) {
            $table->dropColumn('media_id');
        });
    }

    public function down(): void
    {
        Schema::table('scream_game_results', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
        });
    }
};
