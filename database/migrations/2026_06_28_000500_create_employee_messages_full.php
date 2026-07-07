<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('employee_messages')) {
            Schema::create('employee_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sender_id')->index();
                $table->string('sender_type', 20)->default('encadrement');
                $table->unsignedBigInteger('receiver_id')->nullable()->index(); // null = broadcast
                $table->string('receiver_type', 20)->nullable();
                $table->string('channel', 30)->default('direct'); // direct|broadcast|group
                $table->string('subject', 255)->nullable();
                $table->text('body');
                $table->string('priority', 10)->default('normal'); // low|normal|high|urgent
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('read_at')->nullable();
                $table->string('attachment_path', 500)->nullable();
                $table->timestamps();
                $table->index(['receiver_id','is_read']);
                $table->index(['sender_id','created_at']);
            });
        } else {
            // Add missing columns
            Schema::table('employee_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('employee_messages', 'sender_id'))     $table->unsignedBigInteger('sender_id')->nullable()->after('id');
                if (!Schema::hasColumn('employee_messages', 'receiver_id'))   $table->unsignedBigInteger('receiver_id')->nullable()->after('sender_id');
                if (!Schema::hasColumn('employee_messages', 'channel'))       $table->string('channel', 30)->default('direct')->after('receiver_id');
                if (!Schema::hasColumn('employee_messages', 'subject'))       $table->string('subject', 255)->nullable()->after('channel');
                if (!Schema::hasColumn('employee_messages', 'body'))          $table->text('body')->nullable()->after('subject');
                if (!Schema::hasColumn('employee_messages', 'priority'))      $table->string('priority', 10)->default('normal')->after('body');
                if (!Schema::hasColumn('employee_messages', 'is_read'))       $table->boolean('is_read')->default(false)->after('priority');
                if (!Schema::hasColumn('employee_messages', 'read_at'))       $table->timestamp('read_at')->nullable()->after('is_read');
                if (!Schema::hasColumn('employee_messages', 'created_at'))    $table->timestamp('created_at')->nullable()->useCurrent();
                if (!Schema::hasColumn('employee_messages', 'updated_at'))    $table->timestamp('updated_at')->nullable();
            });
        }
    }
    public function down(): void {}
};