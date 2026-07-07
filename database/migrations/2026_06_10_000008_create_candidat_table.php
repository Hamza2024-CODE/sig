<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidat')) {
            Schema::create('candidat', function (Blueprint $table) {
                $table->integer('IDCandidat')->autoIncrement();
                $table->string('Nom', 150)->nullable();
                $table->string('Prenom', 150)->nullable();
                $table->string('NomFr', 150)->nullable();
                $table->string('PrenomFr', 150)->nullable();
                $table->string('Nin', 20)->nullable()->index();
                $table->string('NumIns', 30)->nullable()->index();
                $table->date('dateInscr')->nullable();
                $table->integer('IDOffre')->nullable()->index();
                $table->integer('IDWilayaR')->nullable();
                $table->string('sexe', 1)->nullable();
                $table->string('dateNaissance', 20)->nullable();
            });
        }
    }

    public function down(): void {}
};
