<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS depenses");
        DB::statement("
            CREATE VIEW depenses AS
            SELECT 
                eg.IDetablissement as IDetablissement,
                eg.IDannee as IDannee,
                SUM(eg.Depenceannuel) as salaries_cost,
                COALESCE((SELECT SUM(COALESCE(l.surface, 80) * 1500) FROM logement l WHERE l.IDetablissement = eg.IDetablissement), 0) as logement_cost,
                (SUM(eg.Depenceannuel) + COALESCE((SELECT SUM(COALESCE(l.surface, 80) * 1500) FROM logement l WHERE l.IDetablissement = eg.IDetablissement), 0)) as total_spending
            FROM etablissement_grade eg
            GROUP BY eg.IDetablissement, eg.IDannee
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS depenses");
    }
};
