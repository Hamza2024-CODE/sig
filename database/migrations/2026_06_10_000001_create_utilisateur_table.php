<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: utilisateur
 * Existing table — migration for documentation & artisan compatibility.
 * Run with --pretend to verify without touching the live DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('utilisateur')) {
            Schema::create('utilisateur', function (Blueprint $table) {
                $table->integer('IDUtilisateur')->autoIncrement();
                $table->string('NomUser', 100)->nullable();
                $table->string('MotPass', 255)->nullable();
                $table->string('Nom', 150)->nullable();
                $table->tinyInteger('admin')->default(0);
                $table->integer('IDNature')->nullable();
                $table->integer('IDBureau')->nullable();
                $table->timestamp('last_login')->nullable();
                $table->string('password_version', 10)->default('plaintext');
                $table->integer('Code')->nullable()->default(0);
                $table->tinyInteger('activee')->default(0);
                $table->integer('IDMode_gestion')->nullable()->default(0);
                $table->integer('IDMode_formation')->nullable()->default(0);
                $table->integer('IDdirection')->nullable()->default(0);
                $table->tinyInteger('admins')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Never drop legacy tables automatically
    }
};
