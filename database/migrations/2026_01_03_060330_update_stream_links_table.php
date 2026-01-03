<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stream_links', function (Blueprint $table) {
            // Menambahkan kolom yang kurang agar tidak error saat INSERT
            if (!Schema::hasColumn('stream_links', 'content_type')) {
                $table->string('content_type')->nullable();
            }
            if (!Schema::hasColumn('stream_links', 'is_youtube')) {
                $table->boolean('is_youtube')->default(true);
            }
            if (!Schema::hasColumn('stream_links', 'youtube_id')) {
                $table->string('youtube_id')->unique()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stream_links', function (Blueprint $table) {
            //
        });
    }
};
