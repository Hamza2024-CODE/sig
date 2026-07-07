<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wilaya')) {
            Schema::create('wilaya', function (Blueprint $table) {
                $table->integer('IDWilayaa')->autoIncrement();
                $table->string('Code', 10)->nullable();
                $table->string('Nom', 100)->nullable();
                $table->string('NomFr', 100)->nullable();
            });
        }
    }

    public function down(): void {}
};
