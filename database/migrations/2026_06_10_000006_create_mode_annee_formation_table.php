<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mode_formation')) {
            Schema::create('mode_formation', function (Blueprint $table) {
                $table->integer('IDMode_formation')->autoIncrement();
                $table->string('Code', 10)->nullable();
                $table->string('Nom', 150)->nullable();
                $table->string('NomFr', 150)->nullable();
                $table->string('Abr', 20)->nullable();
                $table->string('AbrFr', 20)->nullable();
                $table->smallInteger('NumOrd')->default(0);
                $table->smallInteger('NomOrd')->default(0);
            });
        }

        if (!Schema::hasTable('annee_formation')) {
            Schema::create('annee_formation', function (Blueprint $table) {
                $table->integer('IDAnnee_Formation')->autoIncrement();
                $table->string('CodeAnne', 10)->nullable();
                $table->string('Nom', 100)->nullable();
                $table->string('NomFr', 100)->nullable();
                $table->tinyInteger('Encour')->default(0);
                $table->smallInteger('NumOrd')->default(0);
                $table->date('DateD')->nullable();
                $table->date('DateF')->nullable();
            });
        }
    }

    public function down(): void {}
};
