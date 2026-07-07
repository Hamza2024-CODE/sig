<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // ─── 1. workflow_requests ──────────────────────────────────────────────
        if (!Schema::hasTable('workflow_requests')) {
            Schema::create('workflow_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('type', 30)->index();      // conge | promotion | transfert | formation
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('employee_type', 20)->default('encadrement'); // encadrement|utilisateur
                $table->unsignedBigInteger('etablissement_id')->nullable();
                $table->unsignedSmallInteger('wilaya_id')->nullable();
                $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|cancelled
                $table->json('payload')->nullable();           // type-specific data (dates, motif, grade_id…)
                $table->text('motif')->nullable();             // justification (applicant)
                $table->text('response_comment')->nullable();  // approver comment
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->index(['type','status','employee_id']);
            });
        }

        // ─── 2. workflow_steps ────────────────────────────────────────────────
        if (!Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('request_id')->index();
                $table->string('actor_role', 50);              // role required to act
                $table->unsignedInteger('actor_id')->nullable(); // actual actor
                $table->string('action', 30)->nullable();       // approved|rejected|comment
                $table->text('comment')->nullable();
                $table->tinyInteger('order')->default(1);
                $table->timestamps();
                $table->foreign('request_id')->references('id')->on('workflow_requests')->onDelete('cascade');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_requests');
    }
};