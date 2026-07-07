<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificatScol extends Model
{
    protected $table      = 'certificat_scol';
    protected $primaryKey = 'IDCertificat_Scol';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDapprenant_Section_semstre',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantSectionSemstre()
    {
        return $this->belongsTo(\ApprenantSectionSemstre::class, 'IDapprenant_Section_semstre', 'IDapprenant_Section_semstre');
    }
}