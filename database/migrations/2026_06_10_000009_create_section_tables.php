<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // section
        if (!Schema::hasTable('section')) {
            Schema::create('section', function (Blueprint $table) {
                $table->integer('IDSection')->autoIncrement();
                $table->integer('IDOffre')->nullable()->index();
                $table->string('Code', 30)->nullable();
                $table->string('Intitule', 250)->nullable();
                $table->integer('IDAnnee_Formation')->nullable();
            });
        }

        // section_semestre
        if (!Schema::hasTable('section_semestre')) {
            Schema::create('section_semestre', function (Blueprint $table) {
                $table->integer('IDSection_Semestre')->autoIncrement();
                $table->integer('IDSection')->nullable()->index();
                $table->tinyInteger('NumSem')->default(1);
                $table->date('DateD')->nullable();
                $table->date('DateF')->nullable();
            });
        }

        // section_semestre_module
        if (!Schema::hasTable('section_semestre_module')) {
            Schema::create('section_semestre_module', function (Blueprint $table) {
                $table->integer('IDsection_semestre_Module')->autoIncrement();
                $table->integer('IDSection_Semestre')->nullable()->index();
                $table->integer('IDModule')->nullable();
                $table->string('NomMdl', 250)->nullable();
                $table->string('NomFrMdl', 250)->nullable();
                $table->decimal('coef', 5, 2)->default(1);
                $table->integer('IDEncadrement')->nullable()->index();
                $table->string('type_module', 30)->nullable();
            });
        }
    }

    public function down(): void {}
};
