<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suivi extends Model
{
    protected $table      = 'suivi';
    protected $primaryKey = 'IDSuivi';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Date',
        'Type',
        'IDapprenant_Section_semstre',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantSectionSemstre()
    {
        return $this->belongsTo(\ApprenantSectionSemstre::class, 'IDapprenant_Section_semstre', 'IDapprenant_Section_semstre');
    }
}