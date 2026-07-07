<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboard_widgets')) {
            Schema::create('dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dashboard_id');
                $table->string('type'); // matches the Blade template filename under widgets/
                $table->string('title')->nullable();
                $table->integer('grid_x')->default(0); // Col position
                $table->integer('grid_y')->default(0); // Row position
                $table->integer('grid_w')->default(4); // Column span width
                $table->integer('grid_h')->default(2); // Row span height
                $table->json('config')->nullable(); // custom widget settings (colors, limit, query, etc.)
                $table->timestamps();

                $table->foreign('dashboard_id')->references('id')->on('dashboards')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
