<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificatInsc extends Model
{
    protected $table      = 'certificat_insc';
    protected $primaryKey = 'IDCertificat_Insc';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDCandidat',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function candidat()
    {
        return $this->belongsTo(\Candidat::class, 'IDCandidat', 'IDCandidat');
    }
}