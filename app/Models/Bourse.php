<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bourse extends Model
{
    protected $table      = 'bourse';
    protected $primaryKey = 'IDbourse';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Montant',
        'dureepaye',
        'dureereste',
        'IDapprenant',
        'datedebenga',
        'datefinenga',
        'retenu',
        'neteapaye',
        'IDReleve_engagement',
        'IDBourse_type',
        'create_time',
        'update_time',
        'data_sync_time',
        'Datefinanne',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenant()
    {
        return $this->belongsTo(\Apprenant::class, 'IDapprenant', 'IDapprenant');
    }

    public function releveEngagement()
    {
        return $this->belongsTo(\ReleveEngagement::class, 'IDReleve_engagement', 'IDReleve_engagement');
    }

    public function bourseType()
    {
        return $this->belongsTo(\BourseType::class, 'IDBourse_type', 'IDBourse_type');
    }
}