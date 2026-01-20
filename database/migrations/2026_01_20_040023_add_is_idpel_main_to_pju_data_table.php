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
            $table->boolean('is_idpel_main')->default(false)->after('photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->dropColumn('is_idpel_main');
        });
    }
};
