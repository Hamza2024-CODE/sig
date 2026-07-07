<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ip_bans')) {
            Schema::create('ip_bans', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->unique(); // Supports IPv4 and IPv6
                $table->integer('failed_attempts')->default(0);
                $table->timestamp('banned_until')->nullable()->index();
                $table->string('reason', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_bans');
    }
};
