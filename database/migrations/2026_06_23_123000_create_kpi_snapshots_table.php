<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('category', 60); // pedagogical, financial, hr
            $table->string('metric_name', 120);
            $table->decimal('value', 14, 4);
            $table->json('metadata')->nullable(); 
            $table->integer('entity_id')->nullable(); 
            $table->string('entity_type', 60)->nullable(); // wilaya, etablissement, global
            $table->timestamp('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};
