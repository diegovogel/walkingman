<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up() {
		Schema::create( 'media', function ( Blueprint $table ) {
			$table->id();
            $table->string('bucket_name', 30);
            $table->string('path', 200);
            $table->string('mime_type', 65);
			$table->timestamps();

            $table->unique(['bucket_name', 'path']);
		} );
	}

	public function down() {
		Schema::dropIfExists( 'media' );
	}
};
