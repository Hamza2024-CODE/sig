<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('offre')) {
            Schema::create('offre', function (Blueprint $table) {
                $table->integer('IDOffre')->autoIncrement();
                $table->integer('IDEts_Form')->nullable()->index();
                $table->integer('IDSpecialite')->nullable()->index();
                $table->integer('IDMode_formation')->nullable()->index();
                $table->integer('NbrInscr')->default(0);
                $table->integer('NbrInscrf')->default(0);
                $table->date('DateOuverture')->nullable();
                $table->date('DateFermeture')->nullable();
                $table->tinyInteger('Statut')->default(0);
                $table->integer('IDSession')->nullable();
                $table->integer('IDAnnee_Formation')->nullable()->index();
            });
        }
    }

    public function down(): void {}
};
