<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            // User identity (matches utilisateur.IDUtilisateur or Etablissement.IDetablissement)
            $table->unsignedBigInteger('user_id')->unique()->index()->comment('IDUtilisateur or IDetablissement');
            $table->string('user_type', 30)->default('utilisateur')->comment('utilisateur | etablissement | encadrement');
            $table->string('username', 100)->nullable()->index();

            // ─── THEME & APPEARANCE ──────────────────────────────────────────
            $table->string('theme', 20)->default('light')->comment('light | dark | transparent | image | color');
            $table->string('theme_color', 30)->nullable()->comment('Custom hex color for color theme');
            $table->string('sidebar_bg', 200)->nullable()->comment('Custom bg image URL or gradient');
            $table->string('accent_color', 20)->nullable()->comment('Accent/primary color hex');
            $table->boolean('compact_mode')->default(false);
            $table->boolean('animations_enabled')->default(true);
            $table->string('font_size', 10)->default('md')->comment('sm | md | lg');
            $table->string('language', 5)->default('ar')->comment('ar | fr | en');

            // ─── INSTITUTION IDENTITY (Branding) ─────────────────────────────
            $table->string('institution_logo_url', 500)->nullable()->comment('Custom institution logo URL');
            $table->string('institution_code', 50)->nullable()->comment('Code visible in header/sidebar');
            $table->string('institution_name_ar', 300)->nullable();
            $table->string('institution_name_fr', 300)->nullable();
            $table->string('institution_type', 50)->nullable()->comment('dfep | centre | institut | prive');

            // ─── DASHBOARD PREFERENCES ────────────────────────────────────────
            $table->json('pinned_widgets')->nullable()->comment('Array of widget IDs to pin');
            $table->json('hidden_widgets')->nullable()->comment('Array of widget IDs to hide');
            $table->string('default_tab', 50)->nullable()->comment('Default dashboard tab on login');
            $table->integer('items_per_page')->default(25)->comment('Rows per paginated table');
            $table->boolean('show_welcome_banner')->default(true);

            // ─── NOTIFICATIONS ───────────────────────────────────────────────
            $table->boolean('notif_email')->default(false);
            $table->boolean('notif_browser')->default(true);
            $table->boolean('notif_sound')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
