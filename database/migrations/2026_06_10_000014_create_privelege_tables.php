<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('privelege')) {
            Schema::create('privelege', function (Blueprint $table) {
                $table->integer('IDPrivelege')->autoIncrement();
                $table->smallInteger('code')->default(0);
                $table->string('nomFr', 50)->nullable();
                $table->string('NomAr', 50)->nullable();
                $table->tinyInteger('Plan')->default(0);
                $table->tinyInteger('Phase')->default(0);
                $table->tinyInteger('Groupe')->default(0);
                $table->tinyInteger('PhaseSecond')->default(0);
                $table->string('Obs', 100)->nullable();
                $table->tinyInteger('activee')->default(0);
            });
        }

        if (!Schema::hasTable('privelege_utilisateur')) {
            Schema::create('privelege_utilisateur', function (Blueprint $table) {
                $table->integer('IDPrivelege_Utilisateur')->autoIncrement();
                $table->integer('IDUtilisateur')->default(0);
                $table->integer('IDPrivelege')->default(0);
                $table->tinyInteger('DroiAjout')->default(0);
                $table->tinyInteger('DroiModif')->default(0);
                $table->tinyInteger('DroitSuppr')->default(0);
                $table->tinyInteger('DroitTous')->default(0);
                $table->integer('IDBureau')->default(0);
                $table->integer('IDMode_formation')->default(0);
                $table->tinyInteger('activee')->default(0);
                $table->smallInteger('Code')->default(0);
                $table->integer('IDMode_gestion')->default(0);
                $table->integer('IDNature')->default(0);
                
                $table->index('IDUtilisateur');
                $table->index('IDPrivelege');
            });
        }
    }

    public function down(): void
    {
        // Do not drop legacy tables automatically
    }
};
