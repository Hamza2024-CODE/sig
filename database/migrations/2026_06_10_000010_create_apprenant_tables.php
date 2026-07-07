<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // apprenant
        if (!Schema::hasTable('apprenant')) {
            Schema::create('apprenant', function (Blueprint $table) {
                $table->integer('IDapprenant')->autoIncrement();
                $table->integer('IDCandidat')->nullable()->index();
                $table->integer('IDSection')->nullable()->index();
                $table->string('Nccp', 50)->nullable()->index();
                $table->date('DateInscr')->nullable();
                $table->integer('NbrAbsences')->default(0);
                $table->string('mode_formation', 10)->nullable();
            });
        }

        // apprenant_section_semstre (typo conservé — nom réel de la table)
        if (!Schema::hasTable('apprenant_section_semstre')) {
            Schema::create('apprenant_section_semstre', function (Blueprint $table) {
                $table->integer('IDapprenant_Section_semstre')->autoIncrement();
                $table->integer('IDapprenant')->nullable()->index();
                $table->integer('IDSection_Semestre')->nullable()->index();
                $table->decimal('NoteStage', 5, 2)->nullable();
                $table->decimal('NoteMemoire', 5, 2)->nullable();
                $table->decimal('NoteSoutenance', 5, 2)->nullable();
                $table->string('Decision', 20)->nullable();
            });
        }

        // apprenant_section_semstre_module
        if (!Schema::hasTable('apprenant_section_semstre_module')) {
            Schema::create('apprenant_section_semstre_module', function (Blueprint $table) {
                $table->integer('IDapprenant_section_semestre_module')->autoIncrement();
                $table->integer('IDapprenant_Section_semstre')->nullable();
                $table->integer('IDsection_semestre_Module')->nullable();
                $table->index('IDapprenant_Section_semstre', 'assm_ass_idx');
                $table->index('IDsection_semestre_Module', 'assm_ssm_idx');
                $table->decimal('NoteC1', 5, 2)->nullable();
                $table->decimal('NoteC2', 5, 2)->nullable();
                $table->decimal('NoteCs', 5, 2)->nullable();
                $table->decimal('NoteR', 5, 2)->nullable();
                $table->tinyInteger('absc1')->default(0);
                $table->tinyInteger('absc2')->default(0);
                $table->tinyInteger('abscs')->default(0);
            });
        }
    }

    public function down(): void {}
};
