<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\DigitalArchive;

class ArchiveOrphansCommand extends Command
{
    protected $signature = 'archive:orphans';
    protected $description = 'أرشفة السجلات اليتيمة والبيانات غير المترابطة تلقائياً';

    public function handle()
    {
        $this->info('بدء فحص السجلات اليتيمة لجدول apprenant...');

        $orphans = DB::table('apprenant')
            ->leftJoin('section', 'apprenant.IDSection', '=', 'section.IDSection')
            ->whereNull('section.IDSection')
            ->where('apprenant.IDSection', '>', 0)
            ->select('apprenant.*')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('لا توجد سجلات يتيمة حالياً.');
            return 0;
        }

        $this->warn("تم العثور على {$orphans->count()} سجل يتيم. جاري الترحيل للأرشيف الرقمي الموحد...");

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        try {
            foreach ($orphans as $orphan) {
                DB::transaction(function () use ($orphan) {
                    DigitalArchive::create([
                        'table_name'  => 'apprenant',
                        'original_id' => $orphan->IDapprenant,
                        'payload'     => (array)$orphan,
                        'reason'      => 'سجل يتيم - قسم غير موجود (IDSection=' . $orphan->IDSection . ')'
                    ]);
                    DB::table('apprenant')->where('IDapprenant', $orphan->IDapprenant)->delete();
                });
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('تمت عملية أرشفة السجلات وتطهير الجداول بنجاح!');
        return 0;
    }
}