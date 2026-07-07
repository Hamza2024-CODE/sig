<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttestationForm extends Model
{
    protected $table      = 'attestation_form';
    protected $primaryKey = 'IDAttestation_Form';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDApprenant_Fin',
        'NserieDiplome',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantFin()
    {
        return $this->belongsTo(\ApprenantFin::class, 'IDApprenant_Fin', 'IDApprenant_Fin');
    }
}