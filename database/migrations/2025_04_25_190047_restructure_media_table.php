<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropUnique('media_bucket_name_path_unique');
            $table->dropColumn('bucket_name');

            $table->string('disk', 30)->after('id');
            $table->string('original_filename', 255)->after('path');
            $table->unsignedBigInteger('size')->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('disk');
            $table->dropColumn('original_filename');
            $table->dropColumn('size');

            $table->string('bucket_name', 30)->after('id');
            $table->unique(['bucket_name', 'path']);
        });
    }
};
