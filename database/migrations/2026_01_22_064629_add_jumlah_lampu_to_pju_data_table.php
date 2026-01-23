<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->integer('jumlah_lampu')->nullable()->after('daya');
            $table->string('jumlah_lampu_source', 20)->nullable()->after('jumlah_lampu')->default('estimated'); // 'estimated' or 'manual'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->dropColumn(['jumlah_lampu', 'jumlah_lampu_source']);
        });
    }
};
