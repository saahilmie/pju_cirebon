<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->string('update_color_marker', 20)->nullable()->after('kdam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->dropColumn('update_color_marker');
        });
    }
};
