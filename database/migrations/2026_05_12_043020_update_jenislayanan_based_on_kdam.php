<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bulk update jenislayanan based on KDAM status:
     * - KDAM 'A' → jenislayanan = 'ABODEMEN'
     * - KDAM 'M' → jenislayanan = 'METER'
     * - Unclear (null/empty) → not changed
     */
    public function up(): void
    {
        DB::statement("UPDATE pju_data SET jenislayanan = 'ABODEMEN' WHERE kdam = 'A'");
        DB::statement("UPDATE pju_data SET jenislayanan = 'METER' WHERE kdam = 'M'");
    }

    /**
     * Reverse: reset jenislayanan back to null for A and M records
     */
    public function down(): void
    {
        DB::statement("UPDATE pju_data SET jenislayanan = NULL WHERE kdam = 'A'");
        DB::statement("UPDATE pju_data SET jenislayanan = NULL WHERE kdam = 'M'");
    }
};
