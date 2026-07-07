<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dfep')) {
            Schema::create('dfep', function (Blueprint $table) {
                $table->integer('IDDFEP')->autoIncrement();
                $table->integer('IDWilayaa')->nullable()->index();
                $table->string('Nom', 200)->nullable();
                $table->string('NomFr', 200)->nullable();
                $table->string('Code', 10)->nullable();
                $table->string('Adresse', 300)->nullable();
                $table->string('Tel', 20)->nullable();
                $table->string('Email', 150)->nullable();
            });
        }
    }

    public function down(): void {}
};
