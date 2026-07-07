<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttestationSucc extends Model
{
    protected $table      = 'attestation_succ';
    protected $primaryKey = 'IDAttestation_succ';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDApprenant_Fin',
        'Valide',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantFin()
    {
        return $this->belongsTo(\ApprenantFin::class, 'IDApprenant_Fin', 'IDApprenant_Fin');
    }
}