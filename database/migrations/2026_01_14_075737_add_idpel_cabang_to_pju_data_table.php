<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->string('idpel_cabang', 20)->nullable()->after('idpel');
        });
    }

    public function down(): void
    {
        Schema::table('pju_data', function (Blueprint $table) {
            $table->dropColumn('idpel_cabang');
        });
    }
};
