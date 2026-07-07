<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('etablissement')) {
            Schema::create('etablissement', function (Blueprint $table) {
                $table->integer('IDetablissement')->autoIncrement();
                $table->string('Code', 20)->nullable();
                $table->string('Nom', 250)->nullable();
                $table->string('NomFr', 250)->nullable();
                $table->integer('IDDFEP')->nullable()->index();
                $table->integer('IDNature_etsF')->nullable();
                $table->string('Adresse', 300)->nullable();
                $table->string('Tel', 20)->nullable();
                $table->string('Email', 150)->nullable();
                $table->string('nomUser', 100)->nullable()->unique();
                $table->string('MotDePass', 255)->nullable();
                $table->date('DateDecret')->nullable();
            });
        }
    }

    public function down(): void {}
};
