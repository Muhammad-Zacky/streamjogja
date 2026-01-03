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
            $table->string('content_type')->nullable()->after('title');
        });
    }

    public function down()
    {
        Schema::table('stream_links', function (Blueprint $table) {
            $table->dropColumn('content_type');
        });
    }
};
