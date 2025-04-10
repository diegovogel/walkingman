<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'scream_game_results', function ( Blueprint $table ) {
			$table->id();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('loudness')->default(0);
            $table->boolean('phrase_was_spoken')->default(false)->comment('True if the provided phrase was spoken by the user.');
            $table->boolean('performed_in_public')->default(false)->comment('True if the challenge was done in a public place.');
			$table->timestamps();
		} );
	}

	public function down() {
		Schema::dropIfExists( 'scream_game_results' );
	}
};
