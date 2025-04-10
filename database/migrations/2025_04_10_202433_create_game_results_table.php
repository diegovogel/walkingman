<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'game_results', function ( Blueprint $table ) {
			$table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('resultable');
            $table->unsignedInteger('score');
			$table->timestamps();
		} );
	}

	public function down() {
		Schema::dropIfExists( 'game_results' );
	}
};
