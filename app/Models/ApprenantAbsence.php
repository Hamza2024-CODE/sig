<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprenantAbsence extends Model
{
    protected $table      = 'apprenant_absence';
    protected $primaryKey = 'IDapprenant_Absence';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Date',
        'Type',
        'matinsoir',
        'IDapprenant_Section_semstre',
        'Obs',
        'heure',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantSectionSemstre()
    {
        return $this->belongsTo(\ApprenantSectionSemstre::class, 'IDapprenant_Section_semstre', 'IDapprenant_Section_semstre');
    }
}