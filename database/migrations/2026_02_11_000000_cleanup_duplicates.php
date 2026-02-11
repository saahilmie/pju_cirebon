<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cleanup duplicate Unclear data
        // Logic: Keep the record with maximum ID for each IDPEL, delete others
        // Only target 'Unclear' data to be safe

        $sql = "
            DELETE FROM pju_data 
            WHERE id IN (
                SELECT id FROM (
                    SELECT id,
                    ROW_NUMBER() OVER (PARTITION BY idpel ORDER BY id DESC) as rn
                    FROM pju_data
                    WHERE (kdam IS NULL OR kdam = '' OR kdam NOT IN ('M', 'A'))
                ) t
                WHERE t.rn > 1
            );
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
