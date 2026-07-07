<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('encadrement')) {
            Schema::create('encadrement', function (Blueprint $table) {
                $table->integer('IDEncadrement')->autoIncrement();
                $table->string('Nom', 150)->nullable();
                $table->string('Prenom', 150)->nullable();
                $table->string('nin', 20)->nullable()->index();
                $table->string('Email', 150)->nullable();
                $table->string('MotDePass', 255)->nullable();
                $table->integer('IDetablissement')->nullable()->index();
                $table->string('Grade', 100)->nullable();
                $table->string('Fonction', 100)->nullable();
                $table->timestamp('create_time')->nullable();
            });
        }
    }

    public function down(): void {}
};
