<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Extend IDPEL column to support generated format: "533313 - SUMBER / KAB.CIREBON"
     */
    public function up(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->string('idpel', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->string('idpel', 20)->nullable()->change();
        });
    }
};
