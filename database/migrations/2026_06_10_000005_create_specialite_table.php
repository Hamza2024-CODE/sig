<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branche')) {
            Schema::create('branche', function (Blueprint $table) {
                $table->integer('IDBranche')->autoIncrement();
                $table->string('Code', 10)->nullable();
                $table->string('Nom', 150)->nullable();
                $table->string('NomFr', 150)->nullable();
            });
        }

        if (!Schema::hasTable('specialite')) {
            Schema::create('specialite', function (Blueprint $table) {
                $table->integer('IDSpecialite')->autoIncrement();
                $table->string('CodeSpec', 20)->nullable();
                $table->string('Nom', 250)->nullable();
                $table->string('NomFr', 250)->nullable();
                $table->integer('IDBranche')->nullable()->index();
                $table->smallInteger('Duree')->default(2);
                $table->string('Niveau', 10)->nullable();
            });
        }
    }

    public function down(): void {}
};
