<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: توسيع حقل Motdepass في جداول apprenant و preinscrit
 *
 * المشكلة: VARCHAR(20) لا يكفي لتخزين bcrypt (60 حرف على الأقل)
 * الحل: توسيع إلى VARCHAR(255) باستخدام ALTER TABLE مباشرة
 *
 * @conformant ISO 27001 A.8.24
 */
return new class extends Migration
{
    public function up(): void
    {
        // apprenant.Motdepass: → VARCHAR(255)
        DB::statement("ALTER TABLE `apprenant` MODIFY COLUMN `Motdepass` VARCHAR(255) NULL COMMENT 'bcrypt ISO27001-A.8.24'");

        // preinscrit.Password: توسيع إذا وُجد
        try {
            DB::statement("ALTER TABLE `preinscrit` MODIFY COLUMN `Password` VARCHAR(255) NULL COMMENT 'bcrypt ISO27001-A.8.24'");
        } catch (\Exception $e) {
            // العمود غير موجود أو اسمه مختلف — تجاهل
        }
    }

    public function down(): void
    {
        // الإرجاع إلى VARCHAR(20) سيحذف البيانات المُشفَّرة
    }
};
